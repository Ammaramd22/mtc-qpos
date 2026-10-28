<?php

use App\Business;
use App\Contact;
use App\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow fractional loyalty points (e.g. Rs 1350 @ 100/pt = 13.5)
     * and recalculate earned points using the current amount_for_unit_rp rate.
     * (Previously session could use a stale rate of 1 → 1:1 with invoice total.)
     */
    public function up()
    {
        if (Schema::hasTable('transactions')) {
            DB::statement('ALTER TABLE transactions MODIFY rp_earned DECIMAL(22,4) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE transactions MODIFY rp_redeemed DECIMAL(22,4) NOT NULL DEFAULT 0');
        }

        if (Schema::hasTable('contacts')) {
            DB::statement('ALTER TABLE contacts MODIFY total_rp DECIMAL(22,4) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE contacts MODIFY total_rp_used DECIMAL(22,4) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE contacts MODIFY total_rp_expired DECIMAL(22,4) NOT NULL DEFAULT 0');
        }

        $businesses = Business::where('enable_rp', 1)->get();
        foreach ($businesses as $business) {
            $rate = (float) $business->amount_for_unit_rp;
            if ($rate <= 0) {
                continue;
            }
            $min_total = (float) $business->min_order_total_for_rp;
            $max_per_order = ! empty($business->max_rp_per_order) ? (float) $business->max_rp_per_order : null;

            $sells = Transaction::where('business_id', $business->id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->get(['id', 'contact_id', 'final_total', 'rp_earned', 'rp_redeemed']);

            foreach ($sells as $sell) {
                $total = (float) $sell->final_total;
                $points = 0;
                if ($total >= $min_total) {
                    $points = round($total / $rate, 2);
                    if (! is_null($max_per_order) && $max_per_order < $points) {
                        $points = $max_per_order;
                    }
                }
                if ((float) $sell->rp_earned != $points) {
                    Transaction::where('id', $sell->id)->update(['rp_earned' => $points]);
                }
            }

            $contacts = Contact::where('business_id', $business->id)
                ->where(function ($q) {
                    $q->whereNull('is_default')->orWhere('is_default', '!=', 1);
                })
                ->get(['id', 'total_rp_expired']);

            foreach ($contacts as $contact) {
                $earned = (float) Transaction::where('contact_id', $contact->id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->sum('rp_earned');
                $redeemed = (float) Transaction::where('contact_id', $contact->id)
                    ->where('type', 'sell')
                    ->sum('rp_redeemed');
                $expired = (float) ($contact->total_rp_expired ?? 0);
                $balance = max(0, round($earned - $redeemed - $expired, 2));

                Contact::where('id', $contact->id)->update([
                    'total_rp' => $balance,
                    'total_rp_used' => round($redeemed, 2),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('transactions')) {
            DB::statement('ALTER TABLE transactions MODIFY rp_earned INT NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE transactions MODIFY rp_redeemed INT NOT NULL DEFAULT 0');
        }

        if (Schema::hasTable('contacts')) {
            DB::statement('ALTER TABLE contacts MODIFY total_rp INT NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE contacts MODIFY total_rp_used INT NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE contacts MODIFY total_rp_expired INT NOT NULL DEFAULT 0');
        }
    }
};
