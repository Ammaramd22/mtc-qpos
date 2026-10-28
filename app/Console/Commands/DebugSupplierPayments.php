<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;

class DebugSupplierPayments extends Command
{
    protected $signature = 'debug:supplier-payments {contact_id=3}';
    protected $description = 'Debug and fix supplier payment distribution';

    public function handle()
    {
        $contactId = $this->argument('contact_id');
        
        $contact = Contact::find($contactId);
        if (!$contact) {
            $this->error("Contact not found");
            return 1;
        }
        
        $this->info("=== Supplier: {$contact->name} (ID: {$contact->id}) ===\n");
        
        // Check purchases
        $purchases = Transaction::where('contact_id', $contactId)
            ->where('type', 'purchase')
            ->get();
            
        $this->info("Purchases:");
        foreach ($purchases as $pur) {
            $paid = TransactionPayment::where('transaction_id', $pur->id)->sum('amount');
            $this->info("  {$pur->ref_no}: Total={$pur->final_total}, Paid={$paid}, Status={$pur->payment_status}");
        }
        
        // Check parent payments
        $parents = TransactionPayment::where('payment_for', $contactId)
            ->where('is_advance', 1)
            ->whereNull('parent_id')
            ->get();
            
        $this->info("\nParent Payments ({$parents->count()}):");
        foreach ($parents as $p) {
            $children = TransactionPayment::where('parent_id', $p->id)->count();
            $this->info("  {$p->payment_ref_no}: Rs {$p->amount}, Method={$p->method}, Children={$children}");
        }
        
        // Now distribute payments
        $transactionUtil = app(TransactionUtil::class);
        
        foreach ($parents as $parent) {
            $existingChildren = TransactionPayment::where('parent_id', $parent->id)->count();
            if ($existingChildren > 0) {
                $this->info("\n  Skipping {$parent->payment_ref_no} - already has {$existingChildren} children");
                continue;
            }
            
            $this->info("\n  Distributing {$parent->payment_ref_no}...");
            
            // Get unpaid transactions again (in case they changed)
            $dueTransactions = Transaction::where('contact_id', $contactId)
                ->whereIn('type', ['opening_balance', 'purchase'])
                ->where('payment_status', '!=', 'paid')
                ->orderBy('transaction_date', 'asc')
                ->get();
                
            $this->info("    Found {$dueTransactions->count()} unpaid transactions");
            
            if ($dueTransactions->count() == 0) {
                $this->warn("    No unpaid transactions found!");
                continue;
            }
            
            $totalAmount = $parent->amount;
            $transactionPayments = [];
            
            foreach ($dueTransactions as $transaction) {
                if ($totalAmount <= 0) break;
                
                $totalPaid = TransactionPayment::where('transaction_id', $transaction->id)->sum('amount');
                $due = $transaction->final_total - $totalPaid;
                
                $this->info("    {$transaction->ref_no}: Due={$due}, TotalAmountLeft={$totalAmount}");
                
                if ($due > 0) {
                    $payAmount = min($due, $totalAmount);
                    
                    $now = now()->toDateTimeString();
                    $prefixType = 'purchase_payment';
                    $refCount = $transactionUtil->setAndGetReferenceCount($prefixType, $parent->business_id);
                    $paymentRefNo = $transactionUtil->generateReferenceNumber($prefixType, $refCount, $parent->business_id);
                    
                    $transactionPayments[] = [
                        'transaction_id' => $transaction->id,
                        'business_id' => $parent->business_id,
                        'method' => $parent->method,
                        'transaction_no' => $parent->method,
                        'card_transaction_number' => $parent->card_transaction_number,
                        'card_number' => $parent->card_number,
                        'card_type' => $parent->card_type,
                        'card_holder_name' => $parent->card_holder_name,
                        'card_month' => $parent->card_month,
                        'card_year' => $parent->card_year,
                        'card_security' => $parent->card_security,
                        'cheque_number' => $parent->cheque_number,
                        'cheque_date' => $parent->cheque_date,
                        'cheque_status' => $parent->cheque_status,
                        'cheque_type' => $parent->cheque_type,
                        'bank_account_number' => $parent->bank_account_number,
                        'amount' => $payAmount,
                        'paid_on' => $parent->paid_on,
                        'created_by' => $parent->created_by,
                        'payment_for' => $contactId,
                        'parent_id' => $parent->id,
                        'payment_ref_no' => $paymentRefNo,
                        'is_advance' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    
                    $this->info("      -> Will pay Rs {$payAmount}");
                    
                    $totalAmount -= $payAmount;
                }
            }
            
            // Insert payments
            if (!empty($transactionPayments)) {
                TransactionPayment::insert($transactionPayments);
                $this->info("    Inserted " . count($transactionPayments) . " child payments");
                
                // Update transaction statuses
                foreach ($transactionPayments as $tp) {
                    $transaction = Transaction::find($tp['transaction_id']);
                    $totalPaid = TransactionPayment::where('transaction_id', $transaction->id)->sum('amount');
                    
                    if ($totalPaid >= $transaction->final_total) {
                        $transaction->payment_status = 'paid';
                    } else {
                        $transaction->payment_status = 'partial';
                    }
                    $transaction->save();
                    
                    $this->info("      Updated {$transaction->ref_no} to {$transaction->payment_status}");
                }
            }
            
            if ($totalAmount > 0) {
                $this->warn("    Excess Rs {$totalAmount} - updating contact balance");
                $contact->balance += $totalAmount;
                $contact->save();
            }
        }
        
        $this->info("\n=== Done! Clear cache and check ledger ===");
        return 0;
    }
}
