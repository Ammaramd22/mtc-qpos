<?php

namespace App\Http\Controllers;

use App\MobileImei;
use App\MobileReloadTransaction;
use App\MobileRepair;
use App\Product;
use App\Services\MyReloadService;
use App\Utils\MobileShopUtil;
use App\Utils\ProductUtil;
use App\Warranty;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MobileShopController extends Controller
{
    protected MobileShopUtil $mobileShopUtil;

    public function __construct(MobileShopUtil $mobileShopUtil)
    {
        $this->mobileShopUtil = $mobileShopUtil;
    }

    protected function guardModule(): void
    {
        if (! $this->mobileShopUtil->moduleEnabled()) {
            abort(403, __('lang_v1.mobile_shop_module_disabled'));
        }
    }

    protected function guardReloadModule(): void
    {
        if (! $this->mobileShopUtil->canUseReload()) {
            abort(403, __('lang_v1.reload_module_disabled'));
        }
    }

    public function index()
    {
        $this->guardModule();

        return view('mobile_shop.index');
    }

    public function imeiIndex(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = MobileImei::with(['product', 'location'])
                ->where('business_id', $business_id);

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->input('product_id'));
            }

            return Datatables::of($query)
                ->addColumn('product_name', fn ($row) => $row->product->name ?? '—')
                ->addColumn('location_name', fn ($row) => $row->location->name ?? '—')
                ->editColumn('status', function ($row) {
                    return __('lang_v1.mobile_imei_status_'.$row->status);
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([self::class, 'imeiEdit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i></button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $products = Product::where('business_id', $business_id)
            ->where('enable_sr_no', 1)
            ->pluck('name', 'id');

        return view('mobile_shop.imei_index', compact('products'));
    }

    public function imeiCreate()
    {
        $this->guardModule();
        $business_id = request()->session()->get('user.business_id');

        $products = Product::where('business_id', $business_id)
            ->where('enable_sr_no', 1)
            ->with('variations')
            ->get();

        $locations = \App\BusinessLocation::forDropdown($business_id);

        return view('mobile_shop.imei_create', compact('products', 'locations'));
    }

    public function imeiStore(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        try {
            $request->validate([
                'product_id' => 'required|integer',
                'variation_id' => 'required|integer',
                'location_id' => 'required|integer',
                'imeis' => 'required|array|min:1',
                'imeis.*' => 'nullable|string|max:20',
            ]);

            $raw = $request->input('imeis', []);
            $imeis = [];
            foreach ($raw as $imei) {
                $imei = trim((string) $imei);
                if ($imei === '') {
                    continue;
                }
                $imeis[] = $imei;
            }

            if (empty($imeis)) {
                return ['success' => false, 'msg' => __('lang_v1.imei_required')];
            }

            // Reject duplicates within the same submit
            $unique = array_values(array_unique($imeis));
            if (count($unique) !== count($imeis)) {
                return ['success' => false, 'msg' => __('lang_v1.mobile_imei_duplicate_in_list')];
            }

            $existing = MobileImei::where('business_id', $business_id)
                ->whereIn('imei', $unique)
                ->pluck('imei')
                ->all();

            if (! empty($existing)) {
                return [
                    'success' => false,
                    'msg' => __('lang_v1.mobile_imei_already_exists', ['imeis' => implode(', ', $existing)]),
                ];
            }

            $created = 0;
            foreach ($unique as $imei) {
                MobileImei::create([
                    'business_id' => $business_id,
                    'product_id' => $request->input('product_id'),
                    'variation_id' => $request->input('variation_id'),
                    'location_id' => $request->input('location_id'),
                    'imei' => $imei,
                    'status' => 'available',
                    'notes' => $request->input('notes'),
                    'created_by' => auth()->id(),
                ]);
                $created++;
            }

            $output = [
                'success' => true,
                'msg' => __('lang_v1.mobile_imei_bulk_added', ['count' => $created]),
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            $output = ['success' => false, 'msg' => $e->validator->errors()->first()];
        } catch (\Illuminate\Database\QueryException $e) {
            $output = ['success' => false, 'msg' => __('lang_v1.mobile_imei_duplicate')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function imeiEdit($id)
    {
        $this->guardModule();
        $business_id = request()->session()->get('user.business_id');

        $imei = MobileImei::where('business_id', $business_id)->findOrFail($id);
        $warranties = Warranty::forDropdown($business_id);

        return view('mobile_shop.imei_edit', compact('imei', 'warranties'));
    }

    public function imeiUpdate(Request $request, $id)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        try {
            $imei = MobileImei::where('business_id', $business_id)->findOrFail($id);

            if ($request->filled('warranty_id')) {
                $this->mobileShopUtil->assignWarranty(
                    $business_id,
                    $imei->id,
                    (int) $request->input('warranty_id'),
                    $request->input('warranty_start')
                );
            }

            $imei->update([
                'notes' => $request->input('notes'),
                'status' => $request->input('status', $imei->status),
            ]);

            $output = ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * JSON: available IMEIs for POS picker.
     */
    public function availableImeis(Request $request)
    {
        if (! $this->mobileShopUtil->moduleEnabled()) {
            return response()->json(['imeis' => []]);
        }

        $business_id = $request->session()->get('user.business_id');
        $product_id = (int) $request->input('product_id');
        $variation_id = (int) $request->input('variation_id');
        $location_id = (int) $request->input('location_id');

        $imeis = $this->mobileShopUtil->getAvailableImeis(
            $business_id,
            $product_id,
            $variation_id,
            $location_id
        );

        return response()->json(['imeis' => $imeis]);
    }

    public function warrantyLookup(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');
        $result = null;

        if ($request->filled('imei')) {
            $result = $this->mobileShopUtil->lookupByImei($business_id, $request->input('imei'));
        }

        $warranties = Warranty::forDropdown($business_id);

        return view('mobile_shop.warranty_lookup', compact('result', 'warranties'));
    }

    public function warrantyAssign(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        try {
            $request->validate([
                'imei_id' => 'required|integer',
                'warranty_id' => 'required|integer',
            ]);

            $ok = $this->mobileShopUtil->assignWarranty(
                $business_id,
                (int) $request->input('imei_id'),
                (int) $request->input('warranty_id'),
                $request->input('warranty_start')
            );

            $output = $ok
                ? ['success' => true, 'msg' => __('lang_v1.updated_success')]
                : ['success' => false, 'msg' => __('messages.something_went_wrong')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function repairIndex(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = MobileRepair::with(['imei.product', 'contact'])
                ->where('business_id', $business_id);

            return Datatables::of($query)
                ->addColumn('imei_no', fn ($row) => $row->imei->imei ?? '—')
                ->addColumn('product_name', fn ($row) => $row->imei->product->name ?? '—')
                ->addColumn('customer', fn ($row) => $row->contact->name ?? '—')
                ->editColumn('status', fn ($row) => __('lang_v1.mobile_repair_status_'.$row->status))
                ->addColumn('payment_label', function ($row) {
                    if ($row->payment_status === 'paid') {
                        return '<span class="label label-success">'.__('lang_v1.mobile_repair_payment_paid').'</span>';
                    }
                    if ($row->payment_status === 'unpaid' && (float) $row->charge > 0) {
                        return '<span class="label label-warning">'.__('lang_v1.mobile_repair_payment_pending').'</span>'
                            .' <small class="text-muted">Rs '.number_format((float) $row->charge, 2).'</small>';
                    }

                    return '<span class="label label-default">'.__('lang_v1.mobile_repair_payment_waived').'</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<button data-href="'.action([self::class, 'repairEdit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal" title="'.__('messages.edit').'"><i class="glyphicon glyphicon-edit"></i></button> ';
                    if ($row->status === 'completed' && $row->payment_status === 'unpaid' && (float) $row->charge > 0) {
                        $html .= '<a href="'.action([self::class, 'repairCollectPayment'], [$row->id]).'" class="btn btn-xs btn-success" title="'.__('lang_v1.mobile_repair_collect_payment').'"><i class="fa fa-cash-register"></i></a>';
                    } elseif ($row->status !== 'completed') {
                        $html .= '<span class="text-muted" style="font-size:11px;" title="'.__('lang_v1.mobile_repair_complete_first').'"><i class="fa fa-info-circle"></i></span>';
                    }

                    return $html;
                })
                ->rawColumns(['action', 'payment_label'])
                ->make(true);
        }

        return view('mobile_shop.repair_index');
    }

    public function repairCreate(Request $request)
    {
        $this->guardModule();

        if ($request->ajax()) {
            return view('mobile_shop.repair_create');
        }

        return view('mobile_shop.repair_create_page', [
            'imei' => $request->input('imei'),
        ]);
    }

    public function repairStore(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        try {
            $request->validate([
                'imei' => 'required|string',
                'issue_description' => 'required|string',
            ]);

            $imei_record = MobileImei::where('business_id', $business_id)
                ->where('imei', trim($request->input('imei')))
                ->first();

            if (! $imei_record) {
                return ['success' => false, 'msg' => __('lang_v1.mobile_imei_not_found')];
            }

            $under_warranty = $imei_record->isUnderWarranty();
            $charge = $under_warranty ? 0 : (float) $request->input('charge', 0);

            MobileRepair::create([
                'business_id' => $business_id,
                'mobile_imei_id' => $imei_record->id,
                'reference_no' => $this->mobileShopUtil->generateRepairReference($business_id),
                'status' => 'received',
                'issue_description' => $request->input('issue_description'),
                'phone_check' => $this->sanitizePhoneCheck($request->input('phone_check')),
                'repair_notes' => $request->input('repair_notes'),
                'cost' => $request->input('cost', 0),
                'charge' => $charge,
                'warranty_covered' => $under_warranty,
                'payment_status' => ($under_warranty || $charge <= 0) ? 'waived' : 'unpaid',
                'contact_id' => $imei_record->contact_id,
                'created_by' => auth()->id(),
                'received_at' => now(),
            ]);

            if ($imei_record->status === 'sold') {
                $imei_record->update(['status' => 'in_repair']);
            }

            $output = ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function repairEdit($id)
    {
        $this->guardModule();
        $business_id = request()->session()->get('user.business_id');

        $repair = MobileRepair::with('imei.product')
            ->where('business_id', $business_id)
            ->findOrFail($id);

        return view('mobile_shop.repair_edit', compact('repair'));
    }

    public function repairUpdate(Request $request, $id)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        try {
            $repair = MobileRepair::where('business_id', $business_id)->findOrFail($id);

            $status = $request->input('status', $repair->status);
            $completed_at = $repair->completed_at;

            if ($status === 'completed' && empty($completed_at)) {
                $completed_at = now();
                if ($repair->imei && $repair->imei->status === 'in_repair') {
                    $repair->imei->update(['status' => 'sold']);
                }
            }

            $charge = (float) $request->input('charge', $repair->charge);
            $payment_status = $repair->payment_status;

            if ($status === 'completed') {
                if ($repair->warranty_covered || $charge <= 0) {
                    $payment_status = 'waived';
                } elseif ($repair->payment_status !== 'paid') {
                    $payment_status = 'unpaid';
                }
            }

            $repair->update([
                'status' => $status,
                'repair_notes' => $request->input('repair_notes'),
                'phone_check' => $this->sanitizePhoneCheck($request->input('phone_check')),
                'cost' => $request->input('cost', $repair->cost),
                'charge' => $charge,
                'completed_at' => $completed_at,
                'payment_status' => $payment_status,
            ]);

            $output = ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Send a completed repair charge to POS for payment collection.
     */
    public function repairCollectPayment(Request $request, $id)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');
        $user_id = auth()->id();

        $repair = MobileRepair::with(['imei.product', 'contact'])
            ->where('business_id', $business_id)
            ->findOrFail($id);

        $error = $this->mobileShopUtil->validateRepairForPayment($repair);
        if ($error) {
            return redirect()
                ->action([self::class, 'repairIndex'])
                ->with('status', ['success' => 0, 'msg' => $error]);
        }

        $location_id = (int) $request->session()->get('user.business_location_id');
        if ($location_id <= 0) {
            $location_id = (int) \App\BusinessLocation::where('business_id', $business_id)->value('id');
        }

        $request->session()->put(
            'mobile_repair_checkout',
            $this->mobileShopUtil->buildRepairCheckoutPayload($repair, $business_id, $user_id, $location_id)
        );

        return redirect()->action([\App\Http\Controllers\SellPosController::class, 'create']);
    }

    /**
     * List repairs awaiting POS payment (for in-POS picker).
     */
    public function repairPendingPayments(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        $repairs = MobileRepair::with(['imei.product', 'contact'])
            ->where('business_id', $business_id)
            ->where('status', 'completed')
            ->where('payment_status', 'unpaid')
            ->where('charge', '>', 0)
            ->where('warranty_covered', 0)
            ->orderByDesc('completed_at')
            ->limit(50)
            ->get()
            ->map(function ($repair) {
                return [
                    'id' => $repair->id,
                    'reference_no' => $repair->reference_no,
                    'imei' => $repair->imei->imei ?? '',
                    'device' => $repair->imei->product->name ?? '—',
                    'customer' => $repair->contact->name ?? '—',
                    'charge' => (float) $repair->charge,
                    'charge_formatted' => number_format((float) $repair->charge, 2),
                    'issue' => \Illuminate\Support\Str::limit($repair->issue_description, 80),
                ];
            });

        return response()->json(['success' => true, 'repairs' => $repairs]);
    }

    /**
     * Return POS checkout payload for a repair (AJAX from POS picker).
     */
    public function repairPosCheckout(Request $request, $id)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');
        $user_id = auth()->id();

        $repair = MobileRepair::with(['imei.product', 'contact'])
            ->where('business_id', $business_id)
            ->findOrFail($id);

        $error = $this->mobileShopUtil->validateRepairForPayment($repair);
        if ($error) {
            return response()->json(['success' => false, 'msg' => $error]);
        }

        $location_id = (int) $request->session()->get('user.business_location_id');
        if ($location_id <= 0) {
            $location_id = (int) \App\BusinessLocation::where('business_id', $business_id)->value('id');
        }

        return response()->json([
            'success' => true,
            'checkout' => $this->mobileShopUtil->buildRepairCheckoutPayload($repair, $business_id, $user_id, $location_id),
        ]);
    }

    public function reloadIndex(Request $request)
    {
        $this->guardReloadModule();
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = MobileReloadTransaction::where('business_id', $business_id)
                ->orderByDesc('created_at');

            return Datatables::of($query)
                ->addColumn('payment_type_label', fn ($row) => $row->type_label)
                ->addColumn('provider_label', fn ($row) => $row->provider_label)
                ->addColumn('account_display', fn ($row) => $row->account_display)
                ->editColumn('amount', fn ($row) => number_format($row->amount, 2))
                ->editColumn('status', function ($row) {
                    $class = match ($row->status) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    };

                    return '<span class="label label-'.$class.'">'.ucfirst($row->status).'</span>';
                })
                ->editColumn('created_at', fn ($row) => $row->created_at ? $row->created_at->format('d-m-Y H:i') : '—')
                ->rawColumns(['status'])
                ->make(true);
        }

        $operators = MobileReloadTransaction::operators();
        $service = $this->getMyReloadService($business_id);
        $myreload_configured = $service->isConfigured();
        $myreload_demo = $service->isDemoCredentials();

        return view('mobile_shop.reload_index', compact('operators', 'myreload_configured', 'myreload_demo'));
    }

    /**
     * Recent bill payments for POS history tab.
     */
    public function billHistory(Request $request)
    {
        $this->guardReloadModule();
        $business_id = $request->session()->get('user.business_id');

        $rows = MobileReloadTransaction::where('business_id', $business_id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(function ($row) {
                return [
                    'reference_no' => $row->reference_no,
                    'payment_type' => $row->type_label,
                    'provider' => $row->provider_label,
                    'account' => $row->account_display,
                    'amount' => number_format($row->amount, 2),
                    'status' => ucfirst($row->status),
                    'status_class' => $row->status === 'success' ? 'success' : ($row->status === 'failed' ? 'danger' : 'warning'),
                    'created_at' => $row->created_at ? $row->created_at->format('d-m-Y H:i') : '',
                ];
            });

        return response()->json(['success' => true, 'history' => $rows]);
    }

    public function reloadStore(Request $request)
    {
        $this->guardReloadModule();
        $business_id = $request->session()->get('user.business_id');

        try {
            $payment_type = $request->input('payment_type', 'reload');

            $rules = [
                'payment_type' => 'required|in:reload,utility,other',
                'amount' => 'required|numeric|min:50',
            ];

            if ($payment_type === 'reload') {
                $rules['mobile_number'] = 'required|string|min:9|max:20';
                $rules['operator'] = 'required|string';
            } elseif ($payment_type === 'utility') {
                $rules['bill_provider'] = 'required|string';
                $rules['account_number'] = 'required|string|min:3|max:60';
            } else {
                $rules['notes'] = 'required|string|min:2|max:255';
            }

            $request->validate($rules);

            $reference = $this->mobileShopUtil->generateReloadReference($business_id);
            $service = $this->getMyReloadService($business_id);
            $amount = (float) $request->input('amount');

            if ($payment_type === 'reload') {
                $mobile = MyReloadService::normalizeMobile((string) $request->input('mobile_number'));
                if (strlen($mobile) < 9) {
                    return [
                        'success' => false,
                        'msg' => __('lang_v1.mobile_number_required'),
                    ];
                }

                // Demo credentials (or API not configured): simulate locally.
                // Live customer keys call MyReload; Demo portal key is not a working public endpoint.
                if ($service->isConfigured() && ! $service->isDemoCredentials()) {
                    $result = $service->reload(
                        $mobile,
                        $request->input('operator'),
                        $amount,
                        $reference
                    );
                } else {
                    $result = $service->demoReload(
                        $mobile,
                        $request->input('operator'),
                        $amount
                    );
                }

                $record = [
                    'payment_type' => 'reload',
                    'mobile_number' => $mobile,
                    'operator' => $request->input('operator'),
                    'bill_provider' => null,
                    'account_number' => null,
                    'notes' => null,
                ];
            } else {
                $provider = $payment_type === 'utility'
                    ? $request->input('bill_provider')
                    : 'other';

                $accountOrDesc = $request->input('account_number') ?: $request->input('notes');

                $result = $service->demoBillPayment(
                    $payment_type,
                    $provider,
                    $accountOrDesc,
                    $amount
                );

                $record = [
                    'payment_type' => $payment_type,
                    'mobile_number' => '',
                    'operator' => '',
                    'bill_provider' => $payment_type === 'utility' ? $request->input('bill_provider') : null,
                    'account_number' => $request->input('account_number'),
                    'notes' => $request->input('notes'),
                ];
            }

            MobileReloadTransaction::create(array_merge($record, [
                'business_id' => $business_id,
                'reference_no' => $reference,
                'amount' => $amount,
                'status' => $result['success'] ? 'success' : 'failed',
                'provider_ref' => $result['provider_ref'] ?? null,
                'provider_response' => json_encode($result['response'] ?? []),
                'created_by' => auth()->id(),
            ]));

            $output = [
                'success' => $result['success'],
                'msg' => $result['message'],
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            $output = ['success' => false, 'msg' => $e->validator->errors()->first()];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => false,
                'msg' => config('app.debug')
                    ? (__('messages.something_went_wrong').' — '.$e->getMessage())
                    : __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    protected function getMyReloadService(int $business_id): MyReloadService
    {
        $business = \App\Business::find($business_id);
        $settings = is_array($business->common_settings ?? null)
            ? $business->common_settings
            : (json_decode($business->common_settings ?? '{}', true) ?: []);

        $reload = $settings['reload'] ?? [];
        $legacy = $settings['mobile_shop'] ?? [];

        // Each business/customer must save its own API User Name + API Key.
        // Do not fall back to a shared global/demo key from .env for live calls.
        $username = trim((string) (
            $reload['myreload_api_username']
            ?? $reload['myreload_merchant_id']
            ?? $legacy['myreload_merchant_id']
            ?? ''
        ));
        $apiKey = trim((string) (
            $reload['myreload_api_key']
            ?? $legacy['myreload_api_key']
            ?? ''
        ));

        $enabled = array_key_exists('myreload_enabled', $reload)
            ? ! empty($reload['myreload_enabled'])
            : ! empty($legacy['myreload_enabled']);

        return new MyReloadService([
            'enabled' => ($enabled && $username !== '' && $apiKey !== '') ? 1 : 0,
            'api_url' => $reload['myreload_api_url']
                ?? $legacy['myreload_api_url']
                ?? config('myreload.api_url'),
            'api_username' => $username,
            'merchant_id' => $username,
            'api_key' => $apiKey,
            'callback_url' => $reload['myreload_callback_url'] ?? '',
        ]);
    }

    /**
     * Keep only valid phone-check keys/values from the intake form.
     */
    protected function sanitizePhoneCheck($input): ?array
    {
        if (! is_array($input) || empty($input)) {
            return null;
        }

        $allowed_keys = array_keys(MobileRepair::phoneCheckItems());
        $allowed_values = ['ok', 'faulty', 'na'];
        $clean = [];

        foreach ($input as $key => $value) {
            if (! in_array($key, $allowed_keys, true)) {
                continue;
            }
            if (! in_array($value, $allowed_values, true)) {
                continue;
            }
            $clean[$key] = $value;
        }

        return empty($clean) ? null : $clean;
    }
}
