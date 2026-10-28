<?php

namespace App\Utils;

use App\MobileImei;
use App\MobileRepair;
use App\Product;
use App\Transaction;
use App\TransactionSellLine;
use App\Unit;
use App\Warranty;
use Carbon\Carbon;

class MobileShopUtil extends Util
{
    public function moduleEnabled(?int $business_id = null): bool
    {
        return $this->isModuleEnabled('mobile_shop', $business_id)
            && ShopModeUtil::allowsModule('mobile_shop', $business_id);
    }

    /**
     * Separate Reload / MyReload module (can run without Mobile Shop).
     */
    public function reloadModuleEnabled(?int $business_id = null): bool
    {
        return $this->isModuleEnabled('reload', $business_id);
    }

    /**
     * Reload / bill-payment screens require the Reload module
     * or MyReload API enabled for this business.
     */
    public function canUseReload(?int $business_id = null): bool
    {
        if ($this->reloadModuleEnabled($business_id)) {
            return true;
        }

        $business_id = $business_id ?: (session('user.business_id') ?? null);
        if (empty($business_id)) {
            $common = session('business.common_settings', []);
        } else {
            $business = \App\Business::find($business_id);
            $common = is_array($business->common_settings ?? null)
                ? $business->common_settings
                : (json_decode($business->common_settings ?? '{}', true) ?: []);
        }
        if (is_string($common)) {
            $common = json_decode($common, true) ?: [];
        }

        return ! empty($common['reload']['myreload_enabled']);
    }

    /**
     * Available IMEIs for a product at a location.
     */
    public function getAvailableImeis(int $business_id, int $product_id, int $variation_id, int $location_id): array
    {
        return MobileImei::where('business_id', $business_id)
            ->where('product_id', $product_id)
            ->where('variation_id', $variation_id)
            ->where('location_id', $location_id)
            ->where('status', 'available')
            ->orderBy('imei')
            ->pluck('imei', 'id')
            ->toArray();
    }

    /**
     * After a sale, mark selected IMEIs as sold and link warranty.
     */
    public function syncImeisFromSale(Transaction $transaction, array $products, int $location_id): void
    {
        if (! $this->moduleEnabled($transaction->business_id)) {
            return;
        }

        if ($transaction->status !== 'final') {
            return;
        }

        $sell_lines = TransactionSellLine::where('transaction_id', $transaction->id)
            ->orderBy('id')
            ->get();

        foreach ($products as $idx => $product_line) {
            if (empty($product_line['sell_line_note']) || empty($product_line['product_id'])) {
                continue;
            }

            $product = Product::find($product_line['product_id']);
            if (empty($product) || empty($product->enable_sr_no)) {
                continue;
            }

            $imeis = $this->parseImeisFromNote($product_line['sell_line_note']);
            if (empty($imeis)) {
                continue;
            }

            $sell_line = $sell_lines[$idx] ?? null;
            $warranty_id = ! empty($product_line['warranty_id']) ? (int) $product_line['warranty_id'] : null;
            $warranty_starts = Carbon::parse($transaction->transaction_date);
            $warranty_ends = null;

            if ($warranty_id) {
                $warranty = Warranty::find($warranty_id);
                if ($warranty) {
                    $warranty_ends = Carbon::parse($warranty->getEndDate($transaction->transaction_date));
                }
            }

            foreach ($imeis as $imei) {
                $record = MobileImei::where('business_id', $transaction->business_id)
                    ->where('imei', $imei)
                    ->first();

                if ($record) {
                    $record->update([
                        'status' => 'sold',
                        'sell_transaction_id' => $transaction->id,
                        'sell_line_id' => $sell_line->id ?? null,
                        'contact_id' => $transaction->contact_id,
                        'sold_at' => $transaction->transaction_date,
                        'warranty_id' => $warranty_id,
                        'warranty_starts_at' => $warranty_id ? $warranty_starts : null,
                        'warranty_ends_at' => $warranty_ends,
                    ]);
                } else {
                    MobileImei::create([
                        'business_id' => $transaction->business_id,
                        'product_id' => $product_line['product_id'],
                        'variation_id' => $product_line['variation_id'] ?? $product->variations()->value('id'),
                        'location_id' => $location_id,
                        'imei' => $imei,
                        'status' => 'sold',
                        'sell_transaction_id' => $transaction->id,
                        'sell_line_id' => $sell_line->id ?? null,
                        'contact_id' => $transaction->contact_id,
                        'sold_at' => $transaction->transaction_date,
                        'warranty_id' => $warranty_id,
                        'warranty_starts_at' => $warranty_id ? $warranty_starts : null,
                        'warranty_ends_at' => $warranty_ends,
                        'created_by' => $transaction->created_by,
                    ]);
                }
            }
        }
    }

    /**
     * Lookup IMEI and return full details for warranty / repair screens.
     */
    public function lookupByImei(int $business_id, string $imei): ?array
    {
        $record = MobileImei::with(['product', 'variation', 'warranty', 'contact', 'sellTransaction', 'repairs'])
            ->where('business_id', $business_id)
            ->where('imei', trim($imei))
            ->first();

        if (! $record) {
            return null;
        }

        $warranty_name = $record->warranty ? $record->warranty->display_name : null;
        $under_warranty = $record->isUnderWarranty();

        return [
            'id' => $record->id,
            'imei' => $record->imei,
            'status' => $record->status,
            'product_name' => $record->product->name ?? '—',
            'sku' => $record->variation->sub_sku ?? $record->product->sku ?? '',
            'sold_at' => $record->sold_at ? $this->format_date($record->sold_at, true) : null,
            'invoice_no' => $record->sellTransaction->invoice_no ?? null,
            'customer' => $record->contact->name ?? null,
            'warranty_id' => $record->warranty_id,
            'warranty_name' => $warranty_name,
            'warranty_starts_at' => $record->warranty_starts_at ? $this->format_date($record->warranty_starts_at, true) : null,
            'warranty_ends_at' => $record->warranty_ends_at ? $this->format_date($record->warranty_ends_at, true) : null,
            'under_warranty' => $under_warranty,
            'notes' => $record->notes,
            'repairs_count' => $record->repairs->count(),
            'repairs' => $record->repairs->map(function (MobileRepair $r) {
                return [
                    'reference_no' => $r->reference_no,
                    'status' => $r->status,
                    'issue' => $r->issue_description,
                    'received_at' => $r->received_at ? $this->format_date($r->received_at, true) : null,
                ];
            })->toArray(),
        ];
    }

    /**
     * Assign or update warranty on a sold IMEI.
     */
    public function assignWarranty(int $business_id, int $imei_id, int $warranty_id, ?string $start_date = null): bool
    {
        $record = MobileImei::where('business_id', $business_id)->find($imei_id);
        if (! $record) {
            return false;
        }

        $warranty = Warranty::where('business_id', $business_id)->find($warranty_id);
        if (! $warranty) {
            return false;
        }

        $starts = $start_date ? Carbon::parse($start_date) : ($record->sold_at ? Carbon::parse($record->sold_at) : now());
        $ends = Carbon::parse($warranty->getEndDate($starts->toDateTimeString()));

        $record->update([
            'warranty_id' => $warranty_id,
            'warranty_starts_at' => $starts,
            'warranty_ends_at' => $ends,
        ]);

        return true;
    }

    public function generateRepairReference(int $business_id): string
    {
        $count = MobileRepair::where('business_id', $business_id)->count() + 1;

        return 'REP-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    public function generateReloadReference(int $business_id): string
    {
        $count = \App\MobileReloadTransaction::where('business_id', $business_id)->count() + 1;

        return 'RLD-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    protected function parseImeisFromNote(string $note): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($note));

        return array_values(array_filter(array_map('trim', $lines)));
    }

    /**
     * Generic repair line item for POS checkout.
     */
    public function getOrCreateRepairServiceProduct(int $business_id, int $user_id, int $location_id): Product
    {
        $product = Product::where('business_id', $business_id)->where('sku', 'MB-REPAIR-SVC')->first();
        if ($product) {
            $product->product_locations()->syncWithoutDetaching([$location_id]);
            if (empty($product->image) && is_file(public_path('uploads/img/mobile_repair_svc.jpg'))) {
                $product->image = 'mobile_repair_svc.jpg';
                $product->save();
            }

            return $product;
        }

        $unit = Unit::where('business_id', $business_id)->first();
        $productUtil = new ProductUtil();

        $product = Product::create([
            'name' => __('lang_v1.mobile_repair_service'),
            'business_id' => $business_id,
            'type' => 'single',
            'unit_id' => $unit->id,
            'tax_type' => 'inclusive',
            'enable_stock' => 0,
            'sku' => 'MB-REPAIR-SVC',
            'barcode_type' => 'C128',
            'image' => is_file(public_path('uploads/img/mobile_repair_svc.jpg')) ? 'mobile_repair_svc.jpg' : null,
            'product_description' => 'Labour / parts charge for mobile repair jobs',
            'enable_sr_no' => 0,
            'not_for_selling' => 0,
            'is_inactive' => 0,
            'created_by' => $user_id,
        ]);

        $productUtil->createSingleProductVariation($product->id, $product->sku, 0, 0, 0, 0, 0);
        $product->product_locations()->sync([$location_id]);

        return $product->fresh(['variations']);
    }

    public function markRepairPaid(int $business_id, int $repair_id, int $transaction_id): void
    {
        $repair = MobileRepair::where('business_id', $business_id)->find($repair_id);
        if (! $repair) {
            return;
        }

        $repair->update([
            'payment_status' => 'paid',
            'sell_transaction_id' => $transaction_id,
        ]);
    }

    /**
     * Validate whether a repair can be sent to POS for payment.
     */
    public function validateRepairForPayment(MobileRepair $repair): ?string
    {
        if ($repair->status !== 'completed') {
            return __('lang_v1.mobile_repair_complete_first');
        }

        if ($repair->payment_status === 'paid') {
            return __('lang_v1.mobile_repair_already_paid');
        }

        if ($repair->warranty_covered || (float) $repair->charge <= 0) {
            return __('lang_v1.mobile_repair_no_charge');
        }

        return null;
    }

    /**
     * Build POS cart payload for a repair payment.
     */
    public function buildRepairCheckoutPayload(MobileRepair $repair, int $business_id, int $user_id, int $location_id): array
    {
        $repair->loadMissing(['imei.product', 'contact']);

        $product = $this->getOrCreateRepairServiceProduct($business_id, $user_id, $location_id);
        $variation = $product->variations()->first();

        $imei_label = $repair->imei->imei ?? '';
        $device = $repair->imei->product->name ?? 'Device';
        $issue = trim($repair->issue_description ?? '');
        $meta = $repair->reference_no.' · '.$device.($imei_label ? ' · IMEI '.$imei_label : '');

        return [
            'repair_id' => $repair->id,
            'variation_id' => $variation->id,
            'unit_price' => (float) $repair->charge,
            'contact_id' => $repair->contact_id,
            'reference_no' => $repair->reference_no,
            'issue' => $issue,
            'description' => $issue !== '' ? ($issue."\n".$meta) : $meta,
            'imei' => $imei_label,
            'device' => $device,
            'charge_formatted' => number_format((float) $repair->charge, 2),
        ];
    }
}
