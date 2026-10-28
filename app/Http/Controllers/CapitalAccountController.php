<?php

namespace App\Http\Controllers;

use App\CapitalEntry;
use App\CapitalPartner;
use App\Utils\CapitalAccountUtil;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CapitalAccountController extends Controller
{
    protected $commonUtil;

    protected $moduleUtil;

    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
    }

    protected function guardAccess(string $permission = 'capital_account.access')
    {
        if (! CapitalAccountUtil::isEnabled()) {
            abort(403, __('lang_v1.capital_account_module_disabled'));
        }
        if (! auth()->user()->can('capital_account.access')) {
            abort(403, 'Unauthorized action.');
        }
        if ($permission !== 'capital_account.access' && ! auth()->user()->can($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Partner Balance Sheet dashboard.
     */
    public function index(Request $request)
    {
        $this->guardAccess();

        $business_id = $request->session()->get('user.business_id');
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        $sheet = CapitalAccountUtil::getBalanceSheet($business_id, $start, $end);

        return view('capital_account.index', compact('sheet', 'start', 'end'));
    }

    /**
     * Partners list (DataTables).
     */
    public function partners(Request $request)
    {
        $this->guardAccess();
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $rows = CapitalPartner::where('business_id', $business_id)
                ->orderBy('name');

            return DataTables::of($rows)
                ->editColumn('investment_amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.$row->investment_amount.'</span>';
                })
                ->editColumn('profit_percent', function ($row) {
                    return number_format((float) $row->profit_percent, 2).'%';
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="label bg-green">'.__('lang_v1.active').'</span>'
                        : '<span class="label bg-red">'.__('lang_v1.inactive').'</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (auth()->user()->can('capital_account.manage_partners')) {
                        $html .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-href="'.action([\App\Http\Controllers\CapitalAccountController::class, 'editPartner'], [$row->id]).'" data-container=".capital_partner_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button> ';
                        $html .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete-capital-partner" data-href="'.action([\App\Http\Controllers\CapitalAccountController::class, 'destroyPartner'], [$row->id]).'"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                    }

                    return $html;
                })
                ->rawColumns(['investment_amount', 'is_active', 'action'])
                ->make(true);
        }

        return view('capital_account.partners');
    }

    public function createPartner()
    {
        $this->guardAccess('capital_account.manage_partners');

        return view('capital_account.partials.partner_form');
    }

    public function storePartner(Request $request)
    {
        $this->guardAccess('capital_account.manage_partners');

        try {
            $business_id = $request->session()->get('user.business_id');
            $input = $request->only(['name', 'short_name', 'notes']);
            $input['business_id'] = $business_id;
            $input['investment_amount'] = $this->commonUtil->num_uf($request->input('investment_amount', 0));
            $input['profit_percent'] = $this->commonUtil->num_uf($request->input('profit_percent', 0));
            $input['is_active'] = $request->has('is_active') ? 1 : 0;
            $input['created_by'] = $request->session()->get('user.id');

            CapitalPartner::create($input);

            return ['success' => true, 'msg' => __('lang_v1.capital_partner_added')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function editPartner($id)
    {
        $this->guardAccess('capital_account.manage_partners');
        $business_id = request()->session()->get('user.business_id');
        $partner = CapitalPartner::where('business_id', $business_id)->findOrFail($id);

        return view('capital_account.partials.partner_form', compact('partner'));
    }

    public function updatePartner(Request $request, $id)
    {
        $this->guardAccess('capital_account.manage_partners');

        try {
            $business_id = $request->session()->get('user.business_id');
            $partner = CapitalPartner::where('business_id', $business_id)->findOrFail($id);
            $partner->name = $request->input('name');
            $partner->short_name = $request->input('short_name');
            $partner->notes = $request->input('notes');
            $partner->investment_amount = $this->commonUtil->num_uf($request->input('investment_amount', 0));
            $partner->profit_percent = $this->commonUtil->num_uf($request->input('profit_percent', 0));
            $partner->is_active = $request->has('is_active') ? 1 : 0;
            $partner->save();

            return ['success' => true, 'msg' => __('lang_v1.capital_partner_updated')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function destroyPartner($id)
    {
        $this->guardAccess('capital_account.manage_partners');

        try {
            $business_id = request()->session()->get('user.business_id');
            $partner = CapitalPartner::where('business_id', $business_id)->findOrFail($id);
            $partner->delete();

            return ['success' => true, 'msg' => __('lang_v1.capital_partner_deleted')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Capital ledger entries (expenses, investments, etc.).
     */
    public function entries(Request $request)
    {
        $this->guardAccess();
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $rows = CapitalEntry::where('capital_entries.business_id', $business_id)
                ->leftJoin('capital_partners as cp', 'capital_entries.capital_partner_id', '=', 'cp.id')
                ->leftJoin('expense_categories as ec', 'capital_entries.expense_category_id', '=', 'ec.id')
                ->select(
                    'capital_entries.*',
                    'cp.name as partner_name',
                    'cp.short_name as partner_short',
                    'ec.name as expense_category_name'
                )
                ->orderBy('capital_entries.entry_date', 'desc')
                ->orderBy('capital_entries.id', 'desc');

            if ($request->filled('type')) {
                $rows->where('capital_entries.type', $request->get('type'));
            }
            if ($request->filled('source')) {
                $rows->where('capital_entries.source', $request->get('source'));
            }

            $typeLabels = CapitalEntry::typeLabels();
            $sourceLabels = CapitalEntry::sourceLabels();

            return DataTables::of($rows)
                ->editColumn('entry_date', function ($row) {
                    return $this->commonUtil->format_date($row->entry_date);
                })
                ->editColumn('type', function ($row) use ($typeLabels) {
                    $label = $typeLabels[$row->type] ?? $row->type;
                    if ($row->type === 'asset' && ! empty($row->asset_name)) {
                        $label .= ' — '.e($row->asset_name);
                    }

                    return $label;
                })
                ->editColumn('source', function ($row) use ($sourceLabels) {
                    return $sourceLabels[$row->source] ?? $row->source;
                })
                ->editColumn('amount', function ($row) {
                    $isOut = ! $row->isCredit();
                    $cls = $isOut ? 'text-danger' : 'text-success';
                    $sign = $isOut ? '-' : '+';

                    return '<span class="'.$cls.'">'.$sign.' <span class="display_currency" data-currency_symbol="true">'.$row->amount.'</span></span>';
                })
                ->addColumn('category', function ($row) {
                    if (! empty($row->expense_category_name)) {
                        return e($row->expense_category_name);
                    }
                    if ($row->type === 'asset' && ! empty($row->asset_name)) {
                        return e($row->asset_name);
                    }

                    return '<span class="text-muted">—</span>';
                })
                ->addColumn('partner', function ($row) {
                    if (empty($row->partner_name)) {
                        return '<span class="text-muted">—</span>';
                    }
                    $n = $row->partner_name;
                    if (! empty($row->partner_short)) {
                        $n .= ' ('.$row->partner_short.')';
                    }

                    return e($n);
                })
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (auth()->user()->can('capital_account.manage_entries')) {
                        $html .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-href="'.action([\App\Http\Controllers\CapitalAccountController::class, 'editEntry'], [$row->id]).'" data-container=".capital_entry_modal"><i class="glyphicon glyphicon-edit"></i></button> ';
                        $html .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete-capital-entry" data-href="'.action([\App\Http\Controllers\CapitalAccountController::class, 'destroyEntry'], [$row->id]).'"><i class="glyphicon glyphicon-trash"></i></button>';
                    }

                    return $html;
                })
                ->rawColumns(['amount', 'partner', 'category', 'action', 'type'])
                ->make(true);
        }

        $partners = CapitalPartner::where('business_id', $business_id)->where('is_active', 1)->pluck('name', 'id');
        $typeLabels = CapitalEntry::typeLabels();
        $sourceLabels = $this->sourceLabelsWithAccounts($business_id);
        $expenseCategories = CapitalAccountUtil::expenseCategoriesDropdown($business_id);
        $settings = CapitalAccountUtil::getSettings($business_id);

        return view('capital_account.entries', compact('partners', 'typeLabels', 'sourceLabels', 'expenseCategories', 'settings'));
    }

    public function createEntry()
    {
        $this->guardAccess('capital_account.manage_entries');
        $business_id = request()->session()->get('user.business_id');
        $partners = CapitalPartner::where('business_id', $business_id)->where('is_active', 1)->pluck('name', 'id');
        $typeLabels = CapitalEntry::typeLabels();
        $sourceLabels = $this->sourceLabelsWithAccounts($business_id);
        $expenseCategories = CapitalAccountUtil::expenseCategoriesDropdown($business_id);
        $settings = CapitalAccountUtil::getSettings($business_id);

        return view('capital_account.partials.entry_form', compact('partners', 'typeLabels', 'sourceLabels', 'expenseCategories', 'settings'));
    }

    public function storeEntry(Request $request)
    {
        $this->guardAccess('capital_account.manage_entries');

        try {
            $business_id = $request->session()->get('user.business_id');
            $type = $request->input('type');
            if (! array_key_exists($type, CapitalEntry::typeLabels())) {
                return ['success' => false, 'msg' => __('messages.something_went_wrong')];
            }

            $settings = CapitalAccountUtil::getSettings((int) $business_id);
            $categoryId = $request->input('expense_category_id') ?: null;
            if (in_array($type, CapitalEntry::EXPENSE_CATEGORY_TYPES, true) && empty($categoryId)) {
                $categoryId = $settings['expense_category_id'] ?? null;
            }
            if (! in_array($type, CapitalEntry::EXPENSE_CATEGORY_TYPES, true)) {
                $categoryId = null;
            }

            $entry = CapitalEntry::create([
                'business_id' => $business_id,
                'capital_partner_id' => $request->input('capital_partner_id') ?: null,
                'type' => $type,
                'source' => $request->input('source', 'cash_drawer'),
                'expense_category_id' => $categoryId,
                'amount' => $this->commonUtil->num_uf($request->input('amount', 0)),
                'entry_date' => $this->commonUtil->uf_date($request->input('entry_date')),
                'reference_no' => $request->input('reference_no'),
                'asset_name' => $type === 'asset' ? ($request->input('asset_name') ?: $request->input('reference_no')) : null,
                'note' => $request->input('note'),
                'created_by' => $request->session()->get('user.id'),
            ]);

            try {
                CapitalAccountUtil::syncLinkedRecords($entry, (int) $request->session()->get('user.id'));
            } catch (\Throwable $syncEx) {
                \Log::warning('Capital entry sync failed after save: '.$syncEx->getMessage());
            }

            return response()->json(['success' => true, 'msg' => __('lang_v1.capital_entry_added')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function editEntry($id)
    {
        $this->guardAccess('capital_account.manage_entries');
        $business_id = request()->session()->get('user.business_id');
        $entry = CapitalEntry::where('business_id', $business_id)->findOrFail($id);
        $partners = CapitalPartner::where('business_id', $business_id)->where('is_active', 1)->pluck('name', 'id');
        $typeLabels = CapitalEntry::typeLabels();
        $sourceLabels = $this->sourceLabelsWithAccounts($business_id);
        $expenseCategories = CapitalAccountUtil::expenseCategoriesDropdown($business_id);
        $settings = CapitalAccountUtil::getSettings((int) $business_id);

        return view('capital_account.partials.entry_form', compact('entry', 'partners', 'typeLabels', 'sourceLabels', 'expenseCategories', 'settings'));
    }

    public function updateEntry(Request $request, $id)
    {
        $this->guardAccess('capital_account.manage_entries');

        try {
            $business_id = $request->session()->get('user.business_id');
            $entry = CapitalEntry::where('business_id', $business_id)->findOrFail($id);
            $type = $request->input('type');
            if (! array_key_exists($type, CapitalEntry::typeLabels())) {
                return ['success' => false, 'msg' => __('messages.something_went_wrong')];
            }

            $settings = CapitalAccountUtil::getSettings((int) $business_id);
            $categoryId = $request->input('expense_category_id') ?: null;
            if (in_array($type, CapitalEntry::EXPENSE_CATEGORY_TYPES, true) && empty($categoryId)) {
                $categoryId = $settings['expense_category_id'] ?? null;
            }
            if (! in_array($type, CapitalEntry::EXPENSE_CATEGORY_TYPES, true)) {
                $categoryId = null;
            }

            $entry->capital_partner_id = $request->input('capital_partner_id') ?: null;
            $entry->type = $type;
            $entry->source = $request->input('source', 'cash_drawer');
            $entry->expense_category_id = $categoryId;
            $entry->amount = $this->commonUtil->num_uf($request->input('amount', 0));
            $entry->entry_date = $this->commonUtil->uf_date($request->input('entry_date'));
            $entry->reference_no = $request->input('reference_no');
            $entry->asset_name = $type === 'asset' ? ($request->input('asset_name') ?: $request->input('reference_no')) : null;
            $entry->note = $request->input('note');
            $entry->save();

            try {
                CapitalAccountUtil::syncLinkedRecords($entry, (int) $request->session()->get('user.id'));
            } catch (\Throwable $syncEx) {
                \Log::warning('Capital entry sync failed after update: '.$syncEx->getMessage());
            }

            return response()->json(['success' => true, 'msg' => __('lang_v1.capital_entry_updated')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroyEntry($id)
    {
        $this->guardAccess('capital_account.manage_entries');

        try {
            $business_id = request()->session()->get('user.business_id');
            $entry = CapitalEntry::where('business_id', $business_id)->findOrFail($id);

            try {
                CapitalAccountUtil::unlinkLinkedRecords($entry);
            } catch (\Throwable $unlinkEx) {
                // Still allow capital entry delete if linked expense/account cleanup fails
                \Log::warning('Capital entry unlink failed: '.$unlinkEx->getMessage());
            }

            $entry->delete();

            return response()->json(['success' => true, 'msg' => __('lang_v1.capital_entry_deleted')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * Link Bank / Cash to Payment Accounts + default expense category.
     */
    public function settings()
    {
        $this->guardAccess('capital_account.manage_entries');
        $business_id = request()->session()->get('user.business_id');
        $settings = CapitalAccountUtil::getSettings($business_id);
        $accounts = CapitalAccountUtil::accountsDropdown($business_id);
        $expense_categories = CapitalAccountUtil::expenseCategoriesDropdown($business_id);
        $locations = \App\BusinessLocation::forDropdown($business_id);

        return view('capital_account.settings', compact('settings', 'accounts', 'expense_categories', 'locations'));
    }

    public function updateSettings(Request $request)
    {
        $this->guardAccess('capital_account.manage_entries');

        try {
            $business_id = $request->session()->get('user.business_id');
            CapitalAccountUtil::saveSettings($business_id, $request->only([
                'bank_account_id', 'cash_account_id', 'expense_category_id', 'location_id',
            ]));

            $output = ['success' => 1, 'msg' => __('lang_v1.capital_settings_saved')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()
            ->action([\App\Http\Controllers\CapitalAccountController::class, 'settings'])
            ->with('status', $output);
    }

    protected function sourceLabelsWithAccounts(int $business_id): array
    {
        $labels = CapitalEntry::sourceLabels();
        $settings = CapitalAccountUtil::getSettings($business_id);
        if (! empty($settings['bank_account_id'])) {
            $acc = \App\Account::find($settings['bank_account_id']);
            if ($acc) {
                $labels['bank'] = __('lang_v1.capital_source_bank').' → '.$acc->name;
            }
        }
        if (! empty($settings['cash_account_id'])) {
            $acc = \App\Account::find($settings['cash_account_id']);
            if ($acc) {
                $labels['cash_drawer'] = __('lang_v1.capital_source_cash_drawer').' → '.$acc->name;
            }
        }

        return $labels;
    }
}
