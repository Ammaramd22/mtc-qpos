<?php

namespace App\Http\Controllers;

use App\TransactionPayment;
use App\Transaction;
use App\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ChequeController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = auth()->user()->business_id;

            $cheques = TransactionPayment::where('transaction_payments.business_id', $business_id)
                ->where('transaction_payments.method', 'cheque')
                ->where(function ($q) {
                    $q->where('transaction_payments.is_return', 0)
                        ->orWhereNull('transaction_payments.is_return');
                })
                ->whereNull('transaction_payments.parent_id') // Show only parent payments to avoid duplicates
                ->when(!empty(request()->get('cheque_type')), function($query) {
                    $query->where('transaction_payments.cheque_type', request()->get('cheque_type'));
                })
                ->when(!empty(request()->get('status')), function($query) {
                    $query->where('transaction_payments.cheque_status', request()->get('status'));
                })
                ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->leftJoin('contacts as c1', 't.contact_id', '=', 'c1.id') // Contact from transaction
                ->leftJoin('contacts as c2', 'transaction_payments.payment_for', '=', 'c2.id') // Contact from payment_for (for advance payments)
                ->select([
                    'transaction_payments.id',
                    'transaction_payments.cheque_number',
                    'transaction_payments.cheque_name',
                    'transaction_payments.cheque_bank_name',
                    'transaction_payments.cheque_date',
                    'transaction_payments.cheque_status',
                    'transaction_payments.cheque_type',
                    'transaction_payments.amount',
                    'transaction_payments.paid_on',
                    'transaction_payments.payment_ref_no',
                    't.ref_no as transaction_ref',
                    't.type as transaction_type',
                    't.contact_id',
                    DB::raw('COALESCE(c1.name, c2.name) as contact_name'),
                    DB::raw('COALESCE(c1.contact_id, c2.contact_id) as contact_code'),
                    DB::raw('COALESCE(c1.mobile, c2.mobile) as contact_mobile'),
                ])
                ->orderBy('transaction_payments.cheque_date', 'asc');

            return DataTables::of($cheques)
                ->editColumn('cheque_name', function ($row) {
                    return !empty($row->cheque_name) ? e($row->cheque_name) : '-';
                })
                ->addColumn('cheque_type', function ($row) {
                    if ($row->cheque_type === 'outgoing') {
                        return '<span class="label label-info"><i class="fas fa-arrow-up"></i> Pay Supplier</span>';
                    }
                    return '<span class="label label-default"><i class="fas fa-arrow-down"></i> Receive</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    $badges = [
                        'pending'  => '<span class="label label-warning">Pending</span>',
                        'cleared'  => '<span class="label label-success">Cleared</span>',
                        'returned' => '<span class="label label-danger">Returned</span>',
                    ];
                    return $badges[$row->cheque_status] ?? '<span class="label label-default">'.ucfirst($row->cheque_status).'</span>';
                })
                ->addColumn('due_alert', function ($row) {
                    if ($row->cheque_status !== 'pending' || empty($row->cheque_date)) return '';
                    $days = now()->diffInDays(\Carbon\Carbon::parse($row->cheque_date), false);
                    if ($days < 0) return '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Overdue</span>';
                    if ($days == 0) return '<span class="text-warning"><i class="fas fa-bell"></i> Due today — remind customer</span>';
                    if ($days <= 3) return '<span class="text-warning"><i class="fas fa-clock"></i> Due in '.$days.' day(s) — remind</span>';
                    if ($days <= 7) return '<span class="text-info"><i class="fas fa-bell"></i> Coming in '.$days.' days</span>';
                    return '<span class="text-success"><i class="fas fa-check"></i> '.$days.' days left</span>';
                })
                ->addColumn('action', function ($row) {
                    $cheque_date_fmt = !empty($row->cheque_date) ? \Carbon\Carbon::parse($row->cheque_date)->format(session('business.date_format', 'm/d/Y')) : '';
                    $html  = '<div class="btn-group">';
                    $html .= '<button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-radius:8px;font-weight:600;padding:5px 12px;">';
                    $html .= '<i class="fas fa-ellipsis-h"></i>';
                    $html .= '</button>';
                    $html .= '<ul class="dropdown-menu dropdown-menu-right" style="min-width:180px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);border:1px solid #e2e8f0;padding:4px;">';
                    $html .= '<li><a href="#" class="btn-cheque-view dropdown-item" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:7px;font-size:0.82rem;font-weight:600;color:#1e293b;" data-id="'.$row->id.'"><i class="fas fa-eye" style="color:#2563eb;width:14px;"></i> View Cheque</a></li>';
                    $html .= '<li><a href="#" class="btn-cheque-edit dropdown-item" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:7px;font-size:0.82rem;font-weight:600;color:#1e293b;" '
                           . 'data-id="'.$row->id.'" '
                           . 'data-cheque_number="'.e($row->cheque_number).'" '
                           . 'data-cheque_name="'.e($row->cheque_name ?? '').'" '
                           . 'data-cheque_bank_name="'.e($row->cheque_bank_name ?? '').'" '
                           . 'data-cheque_date="'.e($cheque_date_fmt).'" '
                           . 'data-amount="'.number_format($row->amount, 2, '.', '').'" '
                           . 'data-status="'.$row->cheque_status.'">'
                           . '<i class="fas fa-edit" style="color:#3b82f6;width:14px;"></i> Edit</a></li>';
                    if ($row->cheque_status === 'pending') {
                        $html .= '<li><a href="#" class="btn-cheque-remind dropdown-item" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:7px;font-size:0.82rem;font-weight:600;color:#1e293b;" data-id="'.$row->id.'">'
                               . '<i class="fas fa-bell" style="color:#f59e0b;width:14px;"></i> Remind Customer</a></li>';
                        $html .= '<li role="separator"><hr style="margin:4px 10px;border-color:#f1f5f9;"></li>';
                        $html .= '<li><a href="#" class="btn-cheque-action dropdown-item" data-id="'.$row->id.'" data-status="cleared" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:7px;font-size:0.82rem;font-weight:600;color:#15803d;"><i class="fas fa-check" style="width:14px;"></i> Mark Cleared</a></li>';
                        $html .= '<li><a href="#" class="btn-cheque-action dropdown-item" data-id="'.$row->id.'" data-status="returned" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:7px;font-size:0.82rem;font-weight:600;color:#b91c1c;"><i class="fas fa-times" style="width:14px;"></i> Mark Returned</a></li>';
                        $html .= '<li role="separator"><hr style="margin:4px 10px;border-color:#f1f5f9;"></li>';
                        $html .= '<li><a href="#" class="btn-cheque-delete dropdown-item" data-id="'.$row->id.'" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:7px;font-size:0.82rem;font-weight:600;color:#dc2626;"><i class="fas fa-trash" style="width:14px;"></i> Delete</a></li>';
                    } elseif ($row->cheque_status === 'returned') {
                        $html .= '<li><span class="dropdown-item" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:7px;font-size:0.82rem;font-weight:600;color:#b91c1c;pointer-events:none;"><i class="fas fa-undo" style="width:14px;"></i> Returned</span></li>';
                    } else {
                        $html .= '<li><span class="dropdown-item" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:7px;font-size:0.82rem;font-weight:600;color:#15803d;pointer-events:none;"><i class="fas fa-check-circle" style="width:14px;"></i> Cleared</span></li>';
                    }
                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.number_format($row->amount, 2).'</span>';
                })
                ->editColumn('cheque_date', function ($row) {
                    return !empty($row->cheque_date) ? \Carbon\Carbon::parse($row->cheque_date)->format(session('business.date_format', 'd/m/Y')) : '-';
                })
                ->editColumn('paid_on', function ($row) {
                    return !empty($row->paid_on) ? \Carbon\Carbon::parse($row->paid_on)->format(session('business.date_format', 'd/m/Y')) : '-';
                })
                ->rawColumns(['cheque_type', 'status_badge', 'due_alert', 'action', 'amount'])
                ->make(true);
        }

        $status_filter = [
            ''         => 'All Cheques',
            'pending'  => 'Pending',
            'cleared'  => 'Cleared',
            'returned' => 'Returned',
        ];

        return view('cheque.index', compact('status_filter'));
    }

    public function updateStatus(Request $request, $id)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create')) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized'], 403);
        }

        $request->validate(['status' => 'required|in:cleared,returned']);

        $business_id = auth()->user()->business_id;

        try {
            DB::beginTransaction();

            $payment = TransactionPayment::where('business_id', $business_id)
                ->where('id', $id)
                ->where('method', 'cheque')
                ->firstOrFail();

            $old_status = $payment->cheque_status;
            if ($old_status === $request->status) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'msg' => 'Cheque already marked as '.ucfirst($request->status),
                ]);
            }

            $payment->cheque_status = $request->status;
            $payment->save();

            if ($request->status === 'returned' && $old_status !== 'returned') {
                $this->applyChequeReturned($payment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => $request->status === 'returned'
                    ? __('lang_v1.cheque_returned_success')
                    : 'Cheque marked as '.ucfirst($request->status),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cheque status update failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => 'Failed to update cheque status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reverse a returned cheque against the customer/supplier account.
     * Creates an is_return payment so ledger shows "Cheque Returned" as debit
     * and the contact due / invoice payment status is restored.
     */
    protected function applyChequeReturned(TransactionPayment $payment)
    {
        $transactionUtil = app(\App\Utils\TransactionUtil::class);

        // Reverse bank / account posting for the original cheque
        AccountTransaction::where('transaction_payment_id', $payment->id)->delete();

        $cheque_label = $payment->cheque_number ?: $payment->id;
        $note_line = 'Cheque Returned #'.$cheque_label.' on '.now()->format('d/m/Y');

        // Avoid duplicate reverse rows if already returned once
        $already_reversed = TransactionPayment::where('business_id', $payment->business_id)
            ->where('is_return', 1)
            ->where('method', 'cheque')
            ->where('amount', $payment->amount)
            ->where(function ($q) use ($payment, $cheque_label) {
                $q->where('note', 'like', '%Cheque Returned #'.$cheque_label.'%');
                if (! empty($payment->transaction_id)) {
                    $q->orWhere(function ($q2) use ($payment) {
                        $q2->where('transaction_id', $payment->transaction_id)
                            ->where('cheque_number', $payment->cheque_number)
                            ->where('is_return', 1);
                    });
                }
            })
            ->exists();

        if (! $already_reversed) {
            $ref_count = $transactionUtil->setAndGetReferenceCount('payment');
            $payment_ref_no = $transactionUtil->generateReferenceNumber('payment', $ref_count);

            // is_return=1 subtracts from paid totals (sell & purchase via getTotalPaid)
            // and appears as debit on customer ledger
            TransactionPayment::create([
                'transaction_id'   => $payment->transaction_id,
                'business_id'      => $payment->business_id,
                'amount'           => $payment->amount,
                'method'           => 'cheque',
                'is_return'        => 1,
                'payment_for'      => $payment->payment_for,
                'paid_on'          => now()->toDateTimeString(),
                'created_by'       => auth()->id(),
                'payment_ref_no'   => $payment_ref_no,
                'cheque_number'    => $payment->cheque_number,
                'cheque_name'      => $payment->cheque_name,
                'cheque_bank_name' => $payment->cheque_bank_name,
                'cheque_date'      => $payment->cheque_date,
                'cheque_status'    => 'returned',
                'cheque_type'      => $payment->cheque_type,
                'note'             => $note_line,
                'account_id'       => null,
            ]);
        }

        $payment->note = trim(($payment->note ?? '')."\n[".$note_line.' — amount added back to contact due]');
        $payment->save();

        if (! empty($payment->transaction_id)) {
            $transaction = Transaction::find($payment->transaction_id);
            if ($transaction) {
                $transactionUtil->updatePaymentStatus($transaction->id);
                $transaction->additional_notes = trim(($transaction->additional_notes ?? '')."\n[".$note_line.']');
                $transaction->save();
            }
        }
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create')) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized'], 403);
        }

        $request->validate([
            'cheque_number'     => 'required|string|max:100',
            'cheque_name'       => 'nullable|string|max:191',
            'cheque_bank_name'  => 'nullable|string|max:100',
            'cheque_date'       => 'nullable|string|max:20',
            'cheque_status'     => 'required|in:pending,cleared,returned',
        ]);

        $business_id = auth()->user()->business_id;

        $payment = TransactionPayment::where('business_id', $business_id)
            ->where('id', $id)
            ->where('method', 'cheque')
            ->firstOrFail();

        $old_status = $payment->cheque_status;

        $payment->cheque_number    = $request->cheque_number;
        $payment->cheque_name      = $request->cheque_name;
        $payment->cheque_bank_name = $request->cheque_bank_name;
        $payment->cheque_status    = $request->cheque_status;

        if (!empty($request->cheque_date)) {
            try {
                $date_format = session('business.date_format', 'm/d/Y');
                $payment->cheque_date = \Carbon\Carbon::createFromFormat($date_format, $request->cheque_date)->format('Y-m-d');
            } catch (\Exception $e) {
                $payment->cheque_date = null;
            }
        } else {
            $payment->cheque_date = null;
        }

        $payment->save();

        if ($request->cheque_status === 'returned' && $old_status !== 'returned') {
            $this->applyChequeReturned($payment);
        } elseif ($request->cheque_status !== 'returned' && ! empty($payment->transaction_id)) {
            app(\App\Utils\TransactionUtil::class)->updatePaymentStatus($payment->transaction_id);
        }

        return response()->json(['success' => true, 'msg' => 'Cheque updated successfully']);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('purchase.create')) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized'], 403);
        }

        $request->validate([
            'supplier_id'       => 'required|exists:contacts,id',
            'amount'            => 'required|numeric|min:0.01',
            'cheque_number'     => 'required|string|max:100',
            'cheque_name'       => 'nullable|string|max:191',
            'cheque_bank_name'  => 'nullable|string|max:100',
            'cheque_date'       => 'required|date',
            'paid_on'           => 'required|date',
        ]);

        $business_id = auth()->user()->business_id;
        $user_id = auth()->user()->id;

        try {
            DB::beginTransaction();

            // Get supplier contact
            $supplier = \App\Contact::findOrFail($request->supplier_id);

            // Generate reference number for payment
            $transactionUtil = app(\App\Utils\TransactionUtil::class);
            $ref_count = $transactionUtil->setAndGetReferenceCount('payment');
            $prefix_type = 'payment';
            $payment_ref_no = $transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            // Create transaction payment record for supplier cheque payment
            $payment = TransactionPayment::create([
                'business_id'       => $business_id,
                'contact_id'        => $request->supplier_id,
                'amount'            => $request->amount,
                'method'            => 'cheque',
                'cheque_type'       => 'outgoing', // This is an outgoing cheque (paying supplier)
                'cheque_number'     => $request->cheque_number,
                'cheque_name'       => $request->cheque_name,
                'cheque_bank_name'  => $request->cheque_bank_name,
                'cheque_date'       => $request->cheque_date,
                'cheque_status'     => 'pending',
                'paid_on'           => $request->paid_on,
                'payment_ref_no'    => $payment_ref_no,
                'created_by'        => $user_id,
                'note'              => $request->note ?? 'Cheque payment to supplier: ' . $supplier->name,
            ]);

            // Update supplier balance (reduce balance since we're paying them)
            $supplier->balance += $request->amount; // Add to balance (supplier balance is reversed)
            $supplier->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg'     => 'Supplier cheque payment recorded successfully',
                'data'    => $payment,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Supplier cheque payment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg'     => 'Failed to record supplier cheque payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function dashboardWidget()
    {
        $business_id = auth()->user()->business_id;
        $today = now()->toDateString();
        $in3days = now()->addDays(3)->toDateString();

        // Basic counts
        $due_soon = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_status', 'pending')
            ->whereBetween('cheque_date', [$today, $in3days])
            ->count();

        $overdue = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_status', 'pending')
            ->where('cheque_date', '<', $today)
            ->count();

        $overdue_amount = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_status', 'pending')
            ->where('cheque_date', '<', $today)
            ->sum('amount');

        $pending_total = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_status', 'pending')
            ->sum('amount');

        // Overall stats by status
        $pending_count = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_status', 'pending')
            ->count();

        $cleared_count = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_status', 'cleared')
            ->count();

        $returned_count = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_status', 'returned')
            ->count();

        $total_amount = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->sum('amount');

        // Supplier (outgoing) stats
        $supplier_pending = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'outgoing')
            ->where('cheque_status', 'pending')
            ->count();

        $supplier_pending_amount = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'outgoing')
            ->where('cheque_status', 'pending')
            ->sum('amount');

        $supplier_cleared = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'outgoing')
            ->where('cheque_status', 'cleared')
            ->count();

        $supplier_cleared_amount = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'outgoing')
            ->where('cheque_status', 'cleared')
            ->sum('amount');

        $supplier_returned = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'outgoing')
            ->where('cheque_status', 'returned')
            ->count();

        $supplier_returned_amount = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'outgoing')
            ->where('cheque_status', 'returned')
            ->sum('amount');

        $supplier_total = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'outgoing')
            ->sum('amount');

        // Customer (incoming) stats
        $customer_pending = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'incoming')
            ->where('cheque_status', 'pending')
            ->count();

        $customer_pending_amount = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'incoming')
            ->where('cheque_status', 'pending')
            ->sum('amount');

        $customer_cleared = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'incoming')
            ->where('cheque_status', 'cleared')
            ->count();

        $customer_cleared_amount = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'incoming')
            ->where('cheque_status', 'cleared')
            ->sum('amount');

        $customer_returned = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'incoming')
            ->where('cheque_status', 'returned')
            ->count();

        $customer_returned_amount = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'incoming')
            ->where('cheque_status', 'returned')
            ->sum('amount');

        $customer_total = TransactionPayment::where('business_id', $business_id)
            ->where('method', 'cheque')
            ->whereNull('parent_id')
            ->where('cheque_type', 'incoming')
            ->sum('amount');

        return compact(
            'due_soon', 'overdue', 'overdue_amount', 'pending_total',
            'pending_count', 'cleared_count', 'returned_count', 'total_amount',
            'supplier_pending', 'supplier_pending_amount', 'supplier_cleared', 'supplier_cleared_amount',
            'supplier_returned', 'supplier_returned_amount', 'supplier_total',
            'customer_pending', 'customer_pending_amount', 'customer_cleared', 'customer_cleared_amount',
            'customer_returned', 'customer_returned_amount', 'customer_total'
        );
    }

    public function destroy($id)
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $payment = TransactionPayment::where('business_id', $business_id)
                ->where('id', $id)
                ->where('method', 'cheque')
                ->firstOrFail();
            
            // Only allow deleting pending cheques
            if ($payment->cheque_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'msg' => 'Only pending cheques can be deleted. Cleared or returned cheques cannot be deleted.'
                ], 403);
            }
            
            DB::beginTransaction();
            
            // Update contact balance if this is a supplier payment (outgoing)
            if ($payment->cheque_type === 'outgoing' && !empty($payment->payment_for)) {
                $contact = \App\Contact::find($payment->payment_for);
                if ($contact) {
                    // Reverse the payment effect on supplier balance
                    $contact->balance -= $payment->amount;
                    $contact->save();
                }
            }
            
            // Delete the payment
            $payment->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'msg' => 'Cheque payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cheque deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'Failed to delete cheque payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display cheque report page
     */
    public function report()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = auth()->user()->business_id;
        
        // Get filter parameters
        $cheque_type = request()->get('cheque_type', '');
        $status = request()->get('status', '');
        $date_from = request()->get('date_from', '');
        $date_to = request()->get('date_to', '');
        
        // Build query
        $query = TransactionPayment::where('transaction_payments.business_id', $business_id)
            ->where('transaction_payments.method', 'cheque')
            ->whereNull('transaction_payments.parent_id')
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c1', 't.contact_id', '=', 'c1.id')
            ->leftJoin('contacts as c2', 'transaction_payments.payment_for', '=', 'c2.id')
            ->select([
                'transaction_payments.*',
                't.ref_no as transaction_ref',
                't.type as transaction_type',
                DB::raw('COALESCE(c1.name, c2.name) as contact_name'),
                DB::raw('COALESCE(c1.contact_id, c2.contact_id) as contact_code'),
            ]);
        
        // Apply filters
        if ($cheque_type) {
            $query->where('transaction_payments.cheque_type', $cheque_type);
        }
        if ($status) {
            $query->where('transaction_payments.cheque_status', $status);
        }
        if ($date_from) {
            $query->whereDate('transaction_payments.cheque_date', '>=', $date_from);
        }
        if ($date_to) {
            $query->whereDate('transaction_payments.cheque_date', '<=', $date_to);
        }
        
        $cheques = $query->orderBy('transaction_payments.cheque_date', 'desc')->get();
        
        // Calculate summary statistics
        $stats = [
            'total_count' => $cheques->count(),
            'total_amount' => $cheques->sum('amount'),
            'pending_count' => $cheques->where('cheque_status', 'pending')->count(),
            'pending_amount' => $cheques->where('cheque_status', 'pending')->sum('amount'),
            'cleared_count' => $cheques->where('cheque_status', 'cleared')->count(),
            'cleared_amount' => $cheques->where('cheque_status', 'cleared')->sum('amount'),
            'returned_count' => $cheques->where('cheque_status', 'returned')->count(),
            'returned_amount' => $cheques->where('cheque_status', 'returned')->sum('amount'),
            'supplier_count' => $cheques->where('cheque_type', 'outgoing')->count(),
            'supplier_amount' => $cheques->where('cheque_type', 'outgoing')->sum('amount'),
            'customer_count' => $cheques->where('cheque_type', 'incoming')->count(),
            'customer_amount' => $cheques->where('cheque_type', 'incoming')->sum('amount'),
        ];
        
        return view('cheque.report', compact('cheques', 'stats', 'cheque_type', 'status', 'date_from', 'date_to'));
    }

    /**
     * Show a single cheque for view/print modal.
     */
    public function show($id)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = auth()->user()->business_id;

        $cheque = TransactionPayment::where('transaction_payments.business_id', $business_id)
            ->where('transaction_payments.id', $id)
            ->where('transaction_payments.method', 'cheque')
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c1', 't.contact_id', '=', 'c1.id')
            ->leftJoin('contacts as c2', 'transaction_payments.payment_for', '=', 'c2.id')
            ->select([
                'transaction_payments.id',
                'transaction_payments.cheque_number',
                'transaction_payments.cheque_name',
                'transaction_payments.cheque_bank_name',
                'transaction_payments.cheque_date',
                'transaction_payments.cheque_status',
                'transaction_payments.cheque_type',
                'transaction_payments.amount',
                'transaction_payments.paid_on',
                'transaction_payments.payment_ref_no',
                'transaction_payments.note',
                't.ref_no as transaction_ref',
                't.type as transaction_type',
                DB::raw('COALESCE(c1.name, c2.name) as contact_name'),
                DB::raw('COALESCE(c1.contact_id, c2.contact_id) as contact_code'),
            ])
            ->first();

        if (!$cheque) {
            return response()->json(['success' => false, 'msg' => 'Cheque not found.'], 404);
        }

        $amount_words = \App\Utils\SriLankanBankUtil::numberToWords((float) $cheque->amount);
        $date_format  = session('business.date_format', 'd/m/Y');
        $cheque_date  = !empty($cheque->cheque_date) ? \Carbon\Carbon::parse($cheque->cheque_date)->format($date_format) : '';
        $paid_on      = !empty($cheque->paid_on) ? \Carbon\Carbon::parse($cheque->paid_on)->format($date_format) : '';

        return response()->json([
            'success' => true,
            'cheque' => [
                'id'                => $cheque->id,
                'cheque_number'     => $cheque->cheque_number,
                'cheque_name'       => $cheque->cheque_name,
                'cheque_bank_name'  => $cheque->cheque_bank_name,
                'cheque_date'       => $cheque_date,
                'cheque_status'     => $cheque->cheque_status,
                'cheque_type'       => $cheque->cheque_type,
                'amount'            => number_format($cheque->amount, 2),
                'amount_words'      => $amount_words,
                'paid_on'           => $paid_on,
                'contact_name'      => $cheque->contact_name,
                'transaction_ref'   => $cheque->transaction_ref,
                'note'              => $cheque->note,
            ],
        ]);
    }

    /**
     * Build / send a reminder that a cheque is coming due (WhatsApp + optional SMS).
     */
    public function remind(Request $request, $id)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('purchase.view')) {
            return response()->json(['success' => false, 'msg' => 'Unauthorized'], 403);
        }

        $business_id = auth()->user()->business_id;

        $payment = TransactionPayment::where('transaction_payments.business_id', $business_id)
            ->where('transaction_payments.id', $id)
            ->where('transaction_payments.method', 'cheque')
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c1', 't.contact_id', '=', 'c1.id')
            ->leftJoin('contacts as c2', 'transaction_payments.payment_for', '=', 'c2.id')
            ->select([
                'transaction_payments.*',
                DB::raw('COALESCE(c1.name, c2.name) as contact_name'),
                DB::raw('COALESCE(c1.mobile, c2.mobile) as contact_mobile'),
                DB::raw('COALESCE(c1.id, c2.id) as contact_pk'),
            ])
            ->first();

        if (! $payment) {
            return response()->json(['success' => false, 'msg' => 'Cheque not found.'], 404);
        }

        if ($payment->cheque_status !== 'pending') {
            return response()->json(['success' => false, 'msg' => 'Reminders are only for pending cheques.'], 422);
        }

        $business = \App\Business::find($business_id);
        $date_format = session('business.date_format', 'd/m/Y');
        $due_date = ! empty($payment->cheque_date)
            ? \Carbon\Carbon::parse($payment->cheque_date)->format($date_format)
            : '-';
        $days = ! empty($payment->cheque_date)
            ? now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($payment->cheque_date)->startOfDay(), false)
            : null;

        $when = 'soon';
        if ($days !== null) {
            if ($days < 0) {
                $when = 'overdue (was due on '.$due_date.')';
            } elseif ($days === 0) {
                $when = 'due today ('.$due_date.')';
            } elseif ($days === 1) {
                $when = 'due tomorrow ('.$due_date.')';
            } else {
                $when = 'coming due in '.$days.' days ('.$due_date.')';
            }
        }

        $contact_name = $payment->contact_name ?: 'Customer';
        $amount = number_format((float) $payment->amount, 2);
        $cheque_no = $payment->cheque_number ?: '-';
        $bank = $payment->cheque_bank_name ?: '';
        $business_name = $business->name ?? config('app.name');

        $message = "Dear {$contact_name},\n\n"
            ."This is a friendly reminder that you have a cheque {$when}.\n\n"
            ."Cheque No: {$cheque_no}\n"
            .($bank ? "Bank: {$bank}\n" : '')
            ."Amount: Rs {$amount}\n"
            ."Due Date: {$due_date}\n\n"
            ."Please ensure sufficient funds in your account. Thank you.\n"
            ."- {$business_name}";

        $mobile = preg_replace('/\s+/', '', (string) ($payment->contact_mobile ?? ''));
        $util = app(\App\Utils\Util::class);
        $whatsapp_link = null;
        $sms_sent = false;

        if (! empty($mobile)) {
            $whatsapp_link = $util->getWhatsappNotificationLink([
                'mobile_number' => $mobile,
                'whatsapp_text' => $message,
            ]);

            // Optional SMS if business SMS is configured and requested
            if ($request->boolean('send_sms') && ! empty($business->sms_settings)) {
                try {
                    $util->sendSms([
                        'mobile_number' => $mobile,
                        'sms_body' => $message,
                        'sms_settings' => $business->sms_settings,
                    ]);
                    $sms_sent = true;
                } catch (\Throwable $e) {
                    \Log::warning('Cheque remind SMS failed: '.$e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'msg' => empty($mobile)
                ? 'Contact has no mobile number. You can copy the message below.'
                : 'Reminder ready.',
            'message' => $message,
            'mobile' => $mobile,
            'contact_name' => $contact_name,
            'whatsapp_link' => $whatsapp_link,
            'sms_sent' => $sms_sent,
            'days_until_due' => $days,
            'due_date' => $due_date,
        ]);
    }
}
