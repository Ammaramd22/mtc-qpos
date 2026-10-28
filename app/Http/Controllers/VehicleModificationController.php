<?php

namespace App\Http\Controllers;

use App\Contact;
use App\CustomerVehicle;
use App\Product;
use App\Utils\ProductUtil;
use App\Utils\VehicleModificationUtil;
use App\Variation;
use App\WorkshopJob;
use App\WorkshopJobItem;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VehicleModificationController extends Controller
{
    protected VehicleModificationUtil $modUtil;

    public function __construct(VehicleModificationUtil $modUtil)
    {
        $this->modUtil = $modUtil;
    }

    protected function guardModule(): void
    {
        if (! $this->modUtil->moduleEnabled()) {
            abort(403, __('lang_v1.vehicle_modification_module_disabled'));
        }
    }

    public function index()
    {
        $this->guardModule();

        $business_id = session('user.business_id');
        $open_jobs = WorkshopJob::where('business_id', $business_id)
            ->whereNotIn('status', ['delivered'])
            ->count();
        $vehicles = CustomerVehicle::where('business_id', $business_id)->count();

        return view('vehicle_modification.index', compact('open_jobs', 'vehicles'));
    }

    public function vehicleIndex(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = CustomerVehicle::with('contact')
                ->where('business_id', $business_id);

            return Datatables::of($query)
                ->addColumn('customer', fn ($row) => e($row->contact->name ?? '—'))
                ->addColumn('vehicle', fn ($row) => e($row->displayName()))
                ->editColumn('vehicle_type', function ($row) {
                    return $row->vehicle_type
                        ? __('lang_v1.vehicle_type_'.$row->vehicle_type)
                        : '—';
                })
                ->addColumn('action', function ($row) {
                    $edit = '<button data-href="'.action([self::class, 'vehicleEdit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i></button>';
                    $job = '<a href="'.action([self::class, 'jobCreate']).'?vehicle_id='.$row->id.'" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> '.__('lang_v1.new_workshop_job').'</a>';

                    return $edit.' '.$job;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('vehicle_modification.vehicle_index');
    }

    public function vehicleCreate()
    {
        $this->guardModule();
        $business_id = session('user.business_id');
        $customers = Contact::customersDropdown($business_id, false);
        $types = VehicleModificationUtil::VEHICLE_TYPES;

        return view('vehicle_modification.partials.vehicle_form', compact('customers', 'types'));
    }

    public function vehicleStore(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        $request->validate([
            'contact_id' => 'required|integer',
            'registration_number' => 'nullable|string|max:40',
            'make' => 'nullable|string|max:80',
            'model' => 'nullable|string|max:80',
            'year' => 'nullable|integer|min:1900|max:2100',
            'vehicle_type' => 'nullable|string|max:40',
        ]);

        try {
            $vehicleUtil = app(\App\Utils\CustomerVehicleUtil::class);
            $vehicle = $vehicleUtil->saveVehicle($business_id, [
                'contact_id' => (int) $request->input('contact_id'),
                'registration_number' => $request->input('registration_number'),
                'make' => $request->input('make'),
                'model' => $request->input('model'),
                'year' => $request->input('year'),
                'vehicle_type' => $request->input('vehicle_type'),
                'notes' => $request->input('notes'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => 0,
                    'msg' => collect($e->errors())->flatten()->implode(' '),
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => 1,
                'msg' => __('lang_v1.added_success'),
                'vehicle' => [
                    'id' => $vehicle->id,
                    'text' => $vehicle->displayName(),
                    'contact_id' => $vehicle->contact_id,
                ],
            ]);
        }

        return redirect()
            ->action([self::class, 'vehicleIndex'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.added_success')]);
    }

    public function vehicleEdit($id)
    {
        $this->guardModule();
        $business_id = session('user.business_id');
        $vehicle = CustomerVehicle::where('business_id', $business_id)->findOrFail($id);
        $customers = Contact::customersDropdown($business_id, false);
        $types = VehicleModificationUtil::VEHICLE_TYPES;

        return view('vehicle_modification.partials.vehicle_form', compact('vehicle', 'customers', 'types'));
    }

    public function vehicleUpdate(Request $request, $id)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');
        $vehicle = CustomerVehicle::where('business_id', $business_id)->findOrFail($id);

        $request->validate([
            'contact_id' => 'required|integer',
            'registration_number' => 'nullable|string|max:40',
            'make' => 'nullable|string|max:80',
            'model' => 'nullable|string|max:80',
            'year' => 'nullable|integer|min:1900|max:2100',
            'vehicle_type' => 'nullable|string|max:40',
        ]);

        try {
            $vehicleUtil = app(\App\Utils\CustomerVehicleUtil::class);
            $vehicleUtil->saveVehicle($business_id, [
                'contact_id' => (int) $request->input('contact_id'),
                'registration_number' => $request->input('registration_number'),
                'make' => $request->input('make'),
                'model' => $request->input('model'),
                'year' => $request->input('year'),
                'vehicle_type' => $request->input('vehicle_type'),
                'notes' => $request->input('notes'),
            ], $vehicle);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->action([self::class, 'vehicleIndex'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.updated_success')]);
    }

    public function vehiclesForCustomer(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');
        $contact_id = (int) $request->input('contact_id');

        $vehicles = CustomerVehicle::where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($v) => ['id' => $v->id, 'text' => $v->displayName()]);

        return response()->json($vehicles);
    }

    public function jobIndex(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = WorkshopJob::with(['contact', 'vehicle'])
                ->where('business_id', $business_id);

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            return Datatables::of($query)
                ->addColumn('customer', fn ($row) => e($row->contact->name ?? '—'))
                ->addColumn('vehicle', fn ($row) => e($row->vehicle ? $row->vehicle->displayName() : '—'))
                ->editColumn('status', fn ($row) => __('lang_v1.workshop_status_'.$row->status))
                ->editColumn('payment_status', fn ($row) => __('lang_v1.workshop_payment_'.$row->payment_status))
                ->editColumn('estimated_total', fn ($row) => number_format((float) $row->estimated_total, 2))
                ->addColumn('action', function ($row) {
                    $html = '<a href="'.action([self::class, 'jobEdit'], [$row->id]).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>';
                    if ($row->payment_status === 'unpaid' && (float) $row->estimated_total > 0 && in_array($row->status, ['completed', 'in_progress', 'waiting_for_parts'], true)) {
                        $html .= ' <a href="'.action([self::class, 'jobCollectPayment'], [$row->id]).'" class="btn btn-xs btn-success"><i class="fa fa-cash-register"></i> '.__('lang_v1.collect_payment').'</a>';
                    }

                    return $html;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $statuses = VehicleModificationUtil::STATUSES;

        return view('vehicle_modification.job_index', compact('statuses'));
    }

    public function jobCreate(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');
        $customers = Contact::customersDropdown($business_id, false);
        $statuses = VehicleModificationUtil::STATUSES;
        $vehicle_id = $request->input('vehicle_id');
        $vehicle = null;
        if ($vehicle_id) {
            $vehicle = CustomerVehicle::where('business_id', $business_id)->find($vehicle_id);
        }

        return view('vehicle_modification.job_form', compact('customers', 'statuses', 'vehicle'));
    }

    public function jobStore(Request $request)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');

        $request->validate([
            'contact_id' => 'required|integer',
            'status' => 'required|in:'.implode(',', VehicleModificationUtil::STATUSES),
        ]);

        $job = WorkshopJob::create([
            'business_id' => $business_id,
            'contact_id' => (int) $request->input('contact_id'),
            'vehicle_id' => $request->input('vehicle_id') ?: null,
            'location_id' => $request->session()->get('user.business_location_id') ?: null,
            'reference_no' => $this->modUtil->generateJobReference($business_id),
            'status' => $request->input('status', 'pending'),
            'description' => $request->input('description'),
            'notes' => $request->input('notes'),
            'payment_status' => 'unpaid',
            'created_by' => auth()->id(),
            'started_at' => $request->input('status') === 'in_progress' ? now() : null,
        ]);

        $this->syncJobItems($job, $request);
        $job->recalcTotal();

        return redirect()
            ->action([self::class, 'jobIndex'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.added_success')]);
    }

    public function jobEdit($id)
    {
        $this->guardModule();
        $business_id = session('user.business_id');
        $job = WorkshopJob::with(['items.product', 'vehicle', 'contact'])
            ->where('business_id', $business_id)
            ->findOrFail($id);
        $customers = Contact::customersDropdown($business_id, false);
        $statuses = VehicleModificationUtil::STATUSES;
        $vehicles = CustomerVehicle::where('business_id', $business_id)
            ->where('contact_id', $job->contact_id)
            ->get();

        return view('vehicle_modification.job_form', compact('job', 'customers', 'statuses', 'vehicles'));
    }

    public function jobUpdate(Request $request, $id)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');
        $job = WorkshopJob::where('business_id', $business_id)->findOrFail($id);

        $request->validate([
            'contact_id' => 'required|integer',
            'status' => 'required|in:'.implode(',', VehicleModificationUtil::STATUSES),
        ]);

        $status = $request->input('status');
        $updates = [
            'contact_id' => (int) $request->input('contact_id'),
            'vehicle_id' => $request->input('vehicle_id') ?: null,
            'status' => $status,
            'description' => $request->input('description'),
            'notes' => $request->input('notes'),
        ];
        if ($status === 'in_progress' && empty($job->started_at)) {
            $updates['started_at'] = now();
        }
        if ($status === 'completed' && empty($job->completed_at)) {
            $updates['completed_at'] = now();
        }
        if ($status === 'delivered' && empty($job->delivered_at)) {
            $updates['delivered_at'] = now();
        }

        $job->update($updates);
        $this->syncJobItems($job, $request);
        $job->recalcTotal();

        return redirect()
            ->action([self::class, 'jobIndex'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.updated_success')]);
    }

    public function jobCollectPayment(Request $request, $id)
    {
        $this->guardModule();
        $business_id = $request->session()->get('user.business_id');
        $job = WorkshopJob::with(['items', 'contact', 'vehicle'])
            ->where('business_id', $business_id)
            ->findOrFail($id);

        if ($job->payment_status === 'paid') {
            return redirect()
                ->action([self::class, 'jobIndex'])
                ->with('status', ['success' => 0, 'msg' => __('lang_v1.workshop_already_paid')]);
        }

        $lines = [];
        foreach ($job->items as $item) {
            if (empty($item->variation_id)) {
                continue;
            }
            $lines[] = [
                'variation_id' => (int) $item->variation_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'name' => $item->name,
            ];
        }

        $request->session()->put('workshop_job_checkout', [
            'job_id' => $job->id,
            'contact_id' => $job->contact_id,
            'reference_no' => $job->reference_no,
            'description' => $job->description,
            'vehicle' => $job->vehicle ? $job->vehicle->displayName() : '',
            'lines' => $lines,
        ]);

        return redirect()->action([SellPosController::class, 'create']);
    }

    public function posServices(Request $request)
    {
        $this->guardModule();
        if (! $request->ajax()) {
            abort(404);
        }

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $location_id = (int) $request->get('location_id');
        if (empty($location_id)) {
            return response()->json(['success' => 0, 'msg' => 'Select a business location']);
        }

        $services = $this->modUtil->getServicesForPos($business_id, $user_id, $location_id);

        return response()->json(['success' => 1, 'services' => $services]);
    }

    protected function syncJobItems(WorkshopJob $job, Request $request): void
    {
        $job->items()->delete();
        $product_ids = $request->input('item_product_id', []);
        $types = $request->input('item_type', []);
        $qtys = $request->input('item_qty', []);
        $prices = $request->input('item_price', []);
        $names = $request->input('item_name', []);

        if (! is_array($product_ids)) {
            return;
        }

        $productUtil = new ProductUtil();

        foreach ($product_ids as $i => $product_id) {
            if (empty($product_id) && empty($names[$i])) {
                continue;
            }
            $qty = $productUtil->num_uf($qtys[$i] ?? 1) ?: 1;
            $price = $productUtil->num_uf($prices[$i] ?? 0);
            $variation_id = null;
            $name = $names[$i] ?? null;
            if (! empty($product_id)) {
                $product = Product::find($product_id);
                if ($product) {
                    $name = $name ?: $product->name;
                    $variation = Variation::where('product_id', $product->id)->first();
                    $variation_id = $variation->id ?? null;
                    if ($price == 0 && $variation) {
                        $price = (float) $variation->sell_price_inc_tax;
                    }
                }
            }

            WorkshopJobItem::create([
                'workshop_job_id' => $job->id,
                'product_id' => $product_id ?: null,
                'variation_id' => $variation_id,
                'item_type' => in_array(($types[$i] ?? 'part'), ['part', 'service'], true) ? $types[$i] : 'part',
                'name' => $name,
                'quantity' => $qty,
                'unit_price' => $price,
            ]);
        }
    }

    protected function guardHistory(): void
    {
        $this->guardModule();
        if (! $this->modUtil->canViewHistory()) {
            abort(403, __('lang_v1.vehicle_history_permission_denied'));
        }
    }

    /**
     * POS AJAX: search by vehicle registration or customer phone.
     */
    public function historySearch(Request $request)
    {
        $this->guardHistory();
        if (! $request->ajax() && ! $request->wantsJson()) {
            abort(404);
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $type = (string) $request->input('type', 'registration');
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json([
                'success' => 1,
                'vehicles' => [],
                'customers' => [],
                'msg' => __('lang_v1.vehicle_history_enter_search'),
            ]);
        }

        if ($type === 'phone') {
            $result = $this->modUtil->searchByPhone($business_id, $q);

            return response()->json([
                'success' => 1,
                'type' => 'phone',
                'customers' => $result['customers'],
                'vehicles' => $result['vehicles'],
            ]);
        }

        $vehicles = $this->modUtil->searchVehiclesByRegistration($business_id, $q);

        return response()->json([
            'success' => 1,
            'type' => 'registration',
            'customers' => [],
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * POS AJAX: job list for one vehicle.
     */
    public function historyVehicle(Request $request, $id)
    {
        $this->guardHistory();
        if (! $request->ajax() && ! $request->wantsJson()) {
            abort(404);
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $data = $this->modUtil->vehicleJobHistory($business_id, (int) $id);

        return response()->json([
            'success' => 1,
            'vehicle' => $data['vehicle'],
            'jobs' => $data['jobs'],
        ]);
    }

    /**
     * POS AJAX: one job detail.
     */
    public function historyJob(Request $request, $id)
    {
        $this->guardHistory();
        if (! $request->ajax() && ! $request->wantsJson()) {
            abort(404);
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $job = $this->modUtil->jobHistoryDetail($business_id, (int) $id);

        return response()->json([
            'success' => 1,
            'job' => $job,
        ]);
    }
}
