<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\TransactionUtil;

class FixSupplierChequePayments extends Command
{
    protected $signature = 'fix:supplier-cheque-payments {contact_name?}';
    protected $description = 'Fix missing child payments for supplier cheque payments';

    public function handle()
    {
        $contactName = $this->argument('contact_name') ?: 'Aadhil';
        
        $contact = Contact::where('name', 'like', "%$contactName%")
            ->orWhere('contact_id', 'like', "%$contactName%")
            ->first();
        
        if (!$contact) {
            $this->error("Contact not found with name: $contactName");
            return 1;
        }
        
        $this->info("Found contact: {$contact->name} (ID: {$contact->id})");
        
        // Find parent cheque payment
        $parentPayment = TransactionPayment::where('payment_for', $contact->id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->whereNull('transaction_id')
            ->first();
        
        if (!$parentPayment) {
            $this->error("No parent cheque payment found for this contact");
            return 1;
        }
        
        $this->info("Found parent payment: {$parentPayment->payment_ref_no} for Rs {$parentPayment->amount}");
        
        // Check if child payments already exist
        $existingChildren = TransactionPayment::where('parent_id', $parentPayment->id)->count();
        if ($existingChildren > 0) {
            $this->warn("Child payments already exist: $existingChildren");
            return 0;
        }
        
        // Get all purchases for this contact
        $purchases = Transaction::where('contact_id', $contact->id)
            ->where('type', 'purchase')
            ->whereIn('payment_status', ['due', 'partial', 'paid']) // Include paid because they might be wrongly marked
            ->orderBy('transaction_date', 'asc')
            ->get();
        
        $this->info("Found {$purchases->count()} purchases");
        
        $totalAmount = $parentPayment->amount;
        $transactionUtil = app(TransactionUtil::class);
        $childPaymentsCreated = 0;
        
        foreach ($purchases as $transaction) {
            if ($totalAmount <= 0) break;
            
            $totalPaid = TransactionPayment::where('transaction_id', $transaction->id)->sum('amount');
            $due = $transaction->final_total - $totalPaid;
            
            $this->info("Purchase {$transaction->ref_no}: Total={$transaction->final_total}, Paid={$totalPaid}, Due={$due}, Status={$transaction->payment_status}");
            
            if ($due > 0) {
                $payAmount = min($due, $totalAmount);
                
                $childPayment = new TransactionPayment();
                $childPayment->business_id = $parentPayment->business_id;
                $childPayment->transaction_id = $transaction->id;
                $childPayment->method = $parentPayment->method;
                $childPayment->amount = $payAmount;
                $childPayment->paid_on = $parentPayment->paid_on;
                $childPayment->created_by = $parentPayment->created_by;
                $childPayment->payment_for = $contact->id;
                $childPayment->parent_id = $parentPayment->id;
                $childPayment->cheque_number = $parentPayment->cheque_number;
                $childPayment->cheque_date = $parentPayment->cheque_date;
                $childPayment->cheque_status = $parentPayment->cheque_status;
                $childPayment->cheque_type = $parentPayment->cheque_type;
                $childPayment->is_advance = 0;
                
                // Generate ref number
                $refCount = $transactionUtil->setAndGetReferenceCount('purchase_payment', $parentPayment->business_id);
                $childPayment->payment_ref_no = $transactionUtil->generateReferenceNumber('purchase_payment', $refCount, $parentPayment->business_id);
                
                $childPayment->save();
                
                $this->info("  -> Created child payment: Rs {$payAmount}");
                
                $totalAmount -= $payAmount;
                $childPaymentsCreated++;
                
                // Update transaction status
                $newPaid = $totalPaid + $payAmount;
                if ($newPaid >= $transaction->final_total) {
                    $transaction->payment_status = 'paid';
                } else {
                    $transaction->payment_status = 'partial';
                }
                $transaction->save();
            }
        }
        
        if ($totalAmount > 0) {
            $this->warn("Excess amount: Rs {$totalAmount} - updating contact balance");
            $contact->balance += $totalAmount;
            $contact->save();
        }
        
        $this->info("Created {$childPaymentsCreated} child payments");
        $this->info("Done! Run 'php artisan cache:clear' to refresh the ledger.");
        
        return 0;
    }
}
