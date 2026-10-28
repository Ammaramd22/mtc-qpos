<?php

namespace App\Utils;

use App\CustomerVehicle;
use App\WorkshopJob;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Shared customer → vehicle helpers for Vehicle Modification and Tyre Shop modes.
 * Uses the existing customer_vehicles table (no duplicate vehicle systems).
 */
class CustomerVehicleUtil extends Util
{
    /**
     * Shop modes that may attach vehicles to customers.
     */
    public static function supportedModes(): array
    {
        return ['vehicle_modification', 'tyre_shop'];
    }

    public function enabled(?int $business_id = null): bool
    {
        $mode = ShopModeUtil::current($business_id);

        return in_array($mode, self::supportedModes(), true);
    }

    public static function normalizeRegistration(?string $value): string
    {
        return VehicleModificationUtil::normalizeRegistration($value);
    }

    /**
     * Trim ends / collapse inner whitespace for display storage (preserve casing/hyphens).
     */
    public static function formatDisplayRegistration(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $value;
    }

    /**
     * Soft uniqueness: one normalized registration per business (active rows).
     */
    public function findDuplicate(
        int $business_id,
        string $normalized,
        ?int $exclude_vehicle_id = null
    ): ?CustomerVehicle {
        if ($normalized === '') {
            return null;
        }

        $query = CustomerVehicle::where('business_id', $business_id);

        if (Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
            $query->where('registration_normalized', $normalized);
        } else {
            $query->whereNotNull('registration_number')
                ->where('registration_number', '!=', '');
        }

        if ($exclude_vehicle_id) {
            $query->where('id', '!=', $exclude_vehicle_id);
        }

        if (Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
            return $query->with('contact')->first();
        }

        foreach ($query->with('contact')->limit(200)->get() as $row) {
            if (self::normalizeRegistration($row->registration_number) === $normalized) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Create or update vehicles from contact form payload.
     *
     * Expected shape:
     *   vehicles[n][id] (optional)
     *   vehicles[n][registration_number]
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $remove_ids
     * @return array{vehicles: \Illuminate\Support\Collection, created: array<int, CustomerVehicle>}
     *
     * @throws ValidationException
     */
    public function syncForContact(
        int $business_id,
        int $contact_id,
        array $rows,
        array $remove_ids = []
    ): array {
        if (! $this->enabled($business_id)) {
            return ['vehicles' => collect(), 'created' => []];
        }

        foreach ($remove_ids as $remove_id) {
            $remove_id = (int) $remove_id;
            if ($remove_id <= 0) {
                continue;
            }
            $vehicle = CustomerVehicle::where('business_id', $business_id)
                ->where('contact_id', $contact_id)
                ->find($remove_id);
            if ($vehicle) {
                $this->safeDelete($vehicle);
            }
        }

        $created = [];
        $seen_normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $display = self::formatDisplayRegistration($row['registration_number'] ?? null);
            if ($display === null) {
                // Skip empty optional rows; ignore blank submissions
                continue;
            }

            $normalized = self::normalizeRegistration($display);
            if ($normalized === '') {
                continue;
            }

            if (isset($seen_normalized[$normalized])) {
                throw ValidationException::withMessages([
                    'vehicles' => [__('lang_v1.vehicle_number_duplicate_in_form')],
                ]);
            }
            $seen_normalized[$normalized] = true;

            $vehicle_id = ! empty($row['id']) ? (int) $row['id'] : null;
            $duplicate = $this->findDuplicate($business_id, $normalized, $vehicle_id);

            if ($duplicate && (int) $duplicate->contact_id !== $contact_id) {
                throw ValidationException::withMessages([
                    'vehicles' => [__('lang_v1.vehicle_number_already_exists', [
                        'number' => $display,
                        'customer' => $duplicate->contact->name ?? '#'.$duplicate->contact_id,
                    ])],
                ]);
            }

            if ($duplicate && ! $vehicle_id) {
                // Same customer + same plate already exists — reuse, do not duplicate
                $vehicle = $duplicate;
                $vehicle->registration_number = $display;
                $vehicle->save();
                continue;
            }

            if ($vehicle_id) {
                $vehicle = CustomerVehicle::where('business_id', $business_id)
                    ->where('contact_id', $contact_id)
                    ->find($vehicle_id);
                if (! $vehicle) {
                    continue;
                }
                $vehicle->registration_number = $display;
                $vehicle->save();
            } else {
                $create = [
                    'business_id' => $business_id,
                    'contact_id' => $contact_id,
                    'registration_number' => $display,
                ];
                if (Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
                    $create['registration_normalized'] = $normalized;
                }
                $vehicle = CustomerVehicle::create($create);
                $created[] = $vehicle;
            }
        }

        $vehicles = CustomerVehicle::where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->orderByDesc('id')
            ->get();

        return [
            'vehicles' => $vehicles,
            'created' => $created,
        ];
    }

    /**
     * Persist a single vehicle (workshop / manage-vehicles flows).
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function saveVehicle(int $business_id, array $input, ?CustomerVehicle $vehicle = null): CustomerVehicle
    {
        $display = self::formatDisplayRegistration($input['registration_number'] ?? null);
        $normalized = $display ? self::normalizeRegistration($display) : '';

        if ($display !== null && $normalized !== '') {
            $exclude = $vehicle ? (int) $vehicle->id : null;
            $duplicate = $this->findDuplicate($business_id, $normalized, $exclude);
            if ($duplicate) {
                $same_row = $vehicle && (int) $duplicate->id === (int) $vehicle->id;
                if (! $same_row) {
                    throw ValidationException::withMessages([
                        'registration_number' => [__('lang_v1.vehicle_number_already_exists', [
                            'number' => $display,
                            'customer' => $duplicate->contact->name ?? '#'.$duplicate->contact_id,
                        ])],
                    ]);
                }
            }
        }

        $payload = [
            'business_id' => $business_id,
            'contact_id' => (int) $input['contact_id'],
            'registration_number' => $display,
            'make' => $input['make'] ?? null,
            'model' => $input['model'] ?? null,
            'year' => ! empty($input['year']) ? (int) $input['year'] : null,
            'vehicle_type' => $input['vehicle_type'] ?? null,
            'notes' => $input['notes'] ?? null,
        ];

        if (Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
            $payload['registration_normalized'] = $normalized !== '' ? $normalized : null;
        }

        if ($vehicle) {
            $vehicle->update($payload);

            return $vehicle->fresh();
        }

        return CustomerVehicle::create($payload);
    }

    /**
     * Soft-delete when possible; hard-block if job history exists.
     *
     * @throws ValidationException
     */
    public function safeDelete(CustomerVehicle $vehicle): void
    {
        $job_count = WorkshopJob::where('business_id', $vehicle->business_id)
            ->where('vehicle_id', $vehicle->id)
            ->count();

        if ($job_count > 0) {
            throw ValidationException::withMessages([
                'vehicle' => [__('lang_v1.vehicle_cannot_delete_has_history')],
            ]);
        }

        try {
            if (Schema::hasColumn('customer_vehicles', 'deleted_at')) {
                $vehicle->delete();

                return;
            }
        } catch (\Throwable $e) {
            // fall through to hard delete
        }

        $vehicle->forceDelete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function formatForResponse($vehicles): array
    {
        $out = [];
        foreach ($vehicles as $v) {
            $out[] = [
                'id' => (int) $v->id,
                'registration_number' => $v->registration_number,
                'display' => $v->displayName(),
                'contact_id' => (int) $v->contact_id,
            ];
        }

        return $out;
    }
}
