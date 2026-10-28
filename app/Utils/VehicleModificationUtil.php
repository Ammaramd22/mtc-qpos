<?php

namespace App\Utils;

/**
 * Vehicle Modification Shop — same pattern as TyreShopUtil / MobileShopUtil.
 * Gate: business.enabled_modules contains "vehicle_modification".
 */
class VehicleModificationUtil extends Util
{
    public const STATUSES = [
        'pending',
        'in_progress',
        'waiting_for_parts',
        'completed',
        'delivered',
    ];

    public const VEHICLE_TYPES = [
        'car',
        'van',
        'suv',
        'pickup',
        'motorcycle',
        'other',
    ];

    public const SERVICES = [
        'installation' => [
            'sku' => 'QPOS-MOD-INSTALL',
            'name' => 'Installation',
        ],
        'custom_fitting' => [
            'sku' => 'QPOS-MOD-FIT',
            'name' => 'Custom Fitting',
        ],
        'body_modification' => [
            'sku' => 'QPOS-MOD-BODY',
            'name' => 'Body Modification',
        ],
        'lighting' => [
            'sku' => 'QPOS-MOD-LIGHT',
            'name' => 'Lighting Installation',
        ],
        'audio' => [
            'sku' => 'QPOS-MOD-AUDIO',
            'name' => 'Audio Installation',
        ],
        'suspension' => [
            'sku' => 'QPOS-MOD-SUSP',
            'name' => 'Suspension Work',
        ],
        'performance' => [
            'sku' => 'QPOS-MOD-PERF',
            'name' => 'Performance Upgrade',
        ],
        'custom' => [
            'sku' => 'QPOS-MOD-CUSTOM',
            'name' => 'Custom Work',
        ],
    ];

    public function moduleEnabled(?int $business_id = null): bool
    {
        return $this->isModuleEnabled('vehicle_modification', $business_id)
            && ShopModeUtil::allowsModule('vehicle_modification', $business_id);
    }

    /**
     * POS / AJAX: view vehicle job history.
     */
    public function canViewHistory($user = null): bool
    {
        if (! $this->moduleEnabled()) {
            return false;
        }
        $user = $user ?: auth()->user();
        if (! $user) {
            return false;
        }
        try {
            if (method_exists($user, 'isSoftwareOwner') && $user->isSoftwareOwner()) {
                return true;
            }
        } catch (\Throwable $e) {
            // continue
        }

        return $user->can('vehicle_modification.view_history');
    }

    /**
     * Normalize registration for flexible search (ignore spaces / hyphens / case).
     */
    public static function normalizeRegistration(?string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value) ?? '');
    }

    /**
     * Digits-only phone for matching.
     */
    public static function normalizePhone(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public function generateJobReference(int $business_id): string
    {
        $count = \App\WorkshopJob::where('business_id', $business_id)->count() + 1;

        return 'JOB-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Search vehicles by registration (normalized) — limited for POS.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchVehiclesByRegistration(int $business_id, string $query, int $limit = 25): array
    {
        $needle = self::normalizeRegistration($query);
        if ($needle === '' || strlen($needle) < 3) {
            return [];
        }

        $query = \App\CustomerVehicle::with('contact')
            ->withCount('jobs')
            ->where('business_id', $business_id)
            ->orderByDesc('id');

        if (\Illuminate\Support\Facades\Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
            $query->whereNotNull('registration_normalized')
                ->where('registration_normalized', '!=', '')
                ->where('registration_normalized', 'like', '%'.$needle.'%')
                ->limit($limit);

            return $query->get()->map(fn ($v) => $this->formatVehicleResult($v))->all();
        }

        $vehicles = $query
            ->whereNotNull('registration_number')
            ->where('registration_number', '!=', '')
            ->limit(200)
            ->get();

        $out = [];
        foreach ($vehicles as $v) {
            $norm = self::normalizeRegistration($v->registration_number);
            if ($norm === '' || strpos($norm, $needle) === false) {
                continue;
            }
            $out[] = $this->formatVehicleResult($v);
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * Search customers by phone, then list their vehicles.
     *
     * @return array{customers: array<int, array<string, mixed>>, vehicles: array<int, array<string, mixed>>}
     */
    public function searchByPhone(int $business_id, string $query, int $limit = 25): array
    {
        $digits = self::normalizePhone($query);
        if ($digits === '' || strlen($digits) < 7) {
            return ['customers' => [], 'vehicles' => []];
        }

        $contacts = \App\Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->where(function ($q) use ($digits) {
                $tail = strlen($digits) > 9 ? substr($digits, -9) : $digits;
                $q->where('mobile', 'like', '%'.$tail.'%')
                    ->orWhere('landline', 'like', '%'.$tail.'%');
            })
            ->orderBy('name')
            ->limit(80)
            ->get(['id', 'name', 'mobile', 'landline']);

        // Prefer digit-normalized matches when raw LIKE is too broad
        $customers = [];
        foreach ($contacts as $c) {
            $m = self::normalizePhone($c->mobile);
            $l = self::normalizePhone($c->landline);
            if (strpos($m, $digits) === false && strpos($l, $digits) === false
                && (strlen($digits) < 9 || (strpos($m, substr($digits, -9)) === false && strpos($l, substr($digits, -9)) === false))) {
                continue;
            }
            $customers[] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'mobile' => $c->mobile,
                'landline' => $c->landline,
            ];
            if (count($customers) >= $limit) {
                break;
            }
        }

        $contactIds = array_column($customers, 'id');
        $vehicles = [];
        if (! empty($contactIds)) {
            $rows = \App\CustomerVehicle::with('contact')
                ->withCount('jobs')
                ->where('business_id', $business_id)
                ->whereIn('contact_id', $contactIds)
                ->orderByDesc('id')
                ->limit(50)
                ->get();
            foreach ($rows as $v) {
                $vehicles[] = $this->formatVehicleResult($v);
            }
        }

        return [
            'customers' => $customers,
            'vehicles' => $vehicles,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatVehicleResult(\App\CustomerVehicle $v): array
    {
        $jobCount = isset($v->jobs_count)
            ? (int) $v->jobs_count
            : (int) \App\WorkshopJob::where('business_id', $v->business_id)
                ->where('vehicle_id', $v->id)
                ->count();

        return [
            'id' => (int) $v->id,
            'contact_id' => (int) $v->contact_id,
            'registration_number' => $v->registration_number,
            'make' => $v->make,
            'model' => $v->model,
            'year' => $v->year,
            'vehicle_type' => $v->vehicle_type,
            'display' => $v->displayName(),
            'customer_name' => $v->contact->name ?? '',
            'customer_mobile' => $v->contact->mobile ?? '',
            'job_count' => $jobCount,
        ];
    }

    /**
     * Job history for one vehicle (newest first).
     *
     * @return array{vehicle: array<string, mixed>, jobs: array<int, array<string, mixed>>}
     */
    public function vehicleJobHistory(int $business_id, int $vehicle_id, int $limit = 50): array
    {
        $vehicle = \App\CustomerVehicle::with('contact')
            ->withCount('jobs')
            ->where('business_id', $business_id)
            ->findOrFail($vehicle_id);

        $jobs = \App\WorkshopJob::with(['items', 'sellTransaction'])
            ->where('business_id', $business_id)
            ->where('vehicle_id', $vehicle_id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $jobRows = [];
        foreach ($jobs as $job) {
            $jobRows[] = $this->formatJobSummary($job);
        }

        return [
            'vehicle' => $this->formatVehicleResult($vehicle),
            'jobs' => $jobRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatJobSummary(\App\WorkshopJob $job): array
    {
        $parts = [];
        $services = [];
        foreach ($job->items as $item) {
            $line = trim(($item->name ?: 'Item').' × '.(float) $item->quantity);
            if (($item->item_type ?? 'part') === 'service') {
                $services[] = $line;
            } else {
                $parts[] = $line;
            }
        }

        $txn = $job->sellTransaction;

        return [
            'id' => (int) $job->id,
            'reference_no' => $job->reference_no,
            'invoice_no' => $txn->invoice_no ?? null,
            'transaction_id' => $job->sell_transaction_id,
            'date' => optional($job->created_at)->format('Y-m-d H:i'),
            'status' => $job->status,
            'status_label' => __('lang_v1.workshop_status_'.$job->status),
            'payment_status' => $job->payment_status,
            'payment_label' => __('lang_v1.workshop_payment_'.$job->payment_status),
            'estimated_total' => (float) $job->estimated_total,
            'description' => $job->description,
            'notes' => $job->notes,
            'parts_summary' => implode(', ', $parts),
            'services_summary' => implode(', ', $services),
            'item_count' => $job->items->count(),
        ];
    }

    /**
     * Full job detail for POS modal.
     *
     * @return array<string, mixed>
     */
    public function jobHistoryDetail(int $business_id, int $job_id): array
    {
        $job = \App\WorkshopJob::with(['items.product', 'contact', 'vehicle', 'sellTransaction', 'location'])
            ->where('business_id', $business_id)
            ->findOrFail($job_id);

        $items = [];
        foreach ($job->items as $item) {
            $items[] = [
                'item_type' => $item->item_type,
                'name' => $item->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => $item->lineTotal(),
                'notes' => $item->notes,
                'product_sku' => $item->product->sku ?? null,
            ];
        }

        $summary = $this->formatJobSummary($job);
        $summary['customer_name'] = $job->contact->name ?? '';
        $summary['customer_mobile'] = $job->contact->mobile ?? '';
        $summary['vehicle'] = $job->vehicle ? $this->formatVehicleResult($job->vehicle) : null;
        $summary['location'] = $job->location->name ?? null;
        $summary['items'] = $items;
        $summary['started_at'] = optional($job->started_at)->format('Y-m-d H:i');
        $summary['completed_at'] = optional($job->completed_at)->format('Y-m-d H:i');
        $summary['delivered_at'] = optional($job->delivered_at)->format('Y-m-d H:i');

        return $summary;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getServicesForPos(int $business_id, int $user_id, int $location_id): array
    {
        $tyreSvc = new TyreShopServiceUtil();
        $out = [];
        foreach (self::SERVICES as $key => $meta) {
            $product = $tyreSvc->getOrCreateServiceProduct(
                $business_id,
                $user_id,
                $location_id,
                $meta['sku'],
                $meta['name']
            );
            $variation = $product->variations()->first();
            $out[$key] = [
                'key' => $key,
                'name' => $meta['name'],
                'sku' => $meta['sku'],
                'product_id' => $product->id,
                'variation_id' => $variation ? (int) $variation->id : null,
                'needs_custom_name' => $key === 'custom',
            ];
        }

        return $out;
    }

    public function markJobPaid(int $business_id, int $job_id, int $transaction_id): void
    {
        $job = \App\WorkshopJob::where('business_id', $business_id)->find($job_id);
        if (! $job || $job->payment_status === 'paid') {
            return;
        }
        $job->payment_status = 'paid';
        $job->sell_transaction_id = $transaction_id;
        if ($job->status === 'pending') {
            $job->status = 'in_progress';
            $job->started_at = $job->started_at ?: now();
        }
        $job->save();
    }
}
