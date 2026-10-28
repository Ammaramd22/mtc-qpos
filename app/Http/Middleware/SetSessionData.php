<?php

namespace App\Http\Middleware;

use App\Business;
use App\Utils\BusinessUtil;
use Closure;
use Illuminate\Support\Facades\Auth;

class SetSessionData
{
    /**
     * Checks if session data is set or not for a user. If data is not set then set it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->session()->has('user')) {
            $business_util = new BusinessUtil;

            $user = Auth::user();
            $session_data = ['id' => $user->id,
                'surname' => $user->surname,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'business_id' => $user->business_id,
                'language' => $user->language,
            ];
            $business = Business::findOrFail($user->business_id);

            $currency = $business->currency;
            $currency_data = ['id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ];

            $request->session()->put('user', $session_data);
            $request->session()->put('business', $business);
            $request->session()->put('currency', $currency_data);

            //set current financial year to session
            $financial_year = $business_util->getCurrentFinancialYear($business->id);
            $request->session()->put('financial_year', $financial_year);
        }

        if ($request->session()->has('business')) {
            $business = $request->session()->get('business');

            // Nested session()->put('business.xxx') can turn the Eloquent model into an array.
            // Rehydrate so the rest of the app can keep using object property access.
            if (is_array($business)) {
                $businessId = $business['id']
                    ?? $request->session()->get('user.business_id')
                    ?? (Auth::user()->business_id ?? null);
                if (! empty($businessId)) {
                    $business = Business::find($businessId);
                    if ($business) {
                        $request->session()->put('business', $business);
                    }
                }
            }

            if ($business && ! is_tax_enabled($business)) {
                if (is_array($business)) {
                    $business['enable_price_tax'] = 0;
                    $business['enable_inline_tax'] = 0;
                } elseif (is_object($business)) {
                    $business->enable_price_tax = 0;
                    $business->enable_inline_tax = 0;
                }
                $request->session()->put('business', $business);
            }
        }

        return $next($request);
    }
}
