<?php

namespace App\Services;

use App\MobileReloadTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MyReload multi-recharge gateway integration.
 *
 * @see https://myreload.lk/
 */
class MyReloadService
{
    protected array $config;

    public function __construct(?array $business_settings = null)
    {
        $defaults = config('myreload', []);
        $this->config = array_merge($defaults, array_filter($business_settings ?? [], function ($value) {
            return $value !== null && $value !== '';
        }));

        // Prefer api_username; fall back to legacy merchant_id.
        if (empty($this->config['api_username']) && ! empty($this->config['merchant_id'])) {
            $this->config['api_username'] = $this->config['merchant_id'];
        }
        if (empty($this->config['merchant_id']) && ! empty($this->config['api_username'])) {
            $this->config['merchant_id'] = $this->config['api_username'];
        }
    }

    public function getApiUsername(): string
    {
        return (string) ($this->config['api_username'] ?? $this->config['merchant_id'] ?? '');
    }

    public function getApiKey(): string
    {
        return (string) ($this->config['api_key'] ?? '');
    }

    public function getCallbackUrl(): string
    {
        return (string) ($this->config['callback_url'] ?? '');
    }

    public function isConfigured(): bool
    {
        if (empty($this->config['enabled'])) {
            return false;
        }

        return $this->getApiUsername() !== ''
            && $this->getApiKey() !== ''
            && ! empty($this->config['api_url']);
    }

    public function isDemoCredentials(): bool
    {
        $username = strtolower($this->getApiUsername());
        $demoUser = strtolower((string) config('myreload.demo_api_username', 'Demo'));
        $demoKey = (string) config('myreload.demo_api_key', '');

        return ($demoUser !== '' && $username === $demoUser)
            || ($demoKey !== '' && hash_equals($demoKey, $this->getApiKey()));
    }

    /**
     * Normalize Sri Lankan mobiles to 07xxxxxxxx (digits only).
     */
    public static function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?: '';

        if (str_starts_with($digits, '94') && strlen($digits) >= 11) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Submit a mobile reload request to MyReload (live credentials only).
     */
    public function reload(string $mobile_number, string $operator, float $amount, string $reference): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => __('lang_v1.myreload_not_configured'),
                'demo' => true,
            ];
        }

        if ($this->isDemoCredentials()) {
            return $this->demoReload($mobile_number, $operator, $amount);
        }

        $mobile = self::normalizeMobile($mobile_number);

        try {
            $payload = [
                'username' => $this->getApiUsername(),
                'api_user' => $this->getApiUsername(),
                'api_username' => $this->getApiUsername(),
                'merchant_id' => $this->getApiUsername(),
                'api_key' => $this->getApiKey(),
                'mobile_number' => $mobile,
                'mobile' => $mobile,
                'operator' => strtolower($operator),
                'amount' => round($amount, 2),
                'reference' => $reference,
                'order_id' => $reference,
            ];

            $callback = $this->getCallbackUrl();
            if ($callback !== '') {
                $payload['callback_url'] = $callback;
                $payload['callback'] = $callback;
            }

            $response = Http::timeout($this->config['timeout'] ?? 30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->getApiKey(),
                    'X-API-KEY' => $this->getApiKey(),
                    'X-API-USER' => $this->getApiUsername(),
                    'Accept' => 'application/json',
                ])
                ->asForm()
                ->post($this->config['api_url'], $payload);

            // Some gateways expect JSON — retry once if form post is rejected as unsupported.
            if ($response->status() === 415 || $response->status() === 406) {
                $response = Http::timeout($this->config['timeout'] ?? 30)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$this->getApiKey(),
                        'X-API-KEY' => $this->getApiKey(),
                        'X-API-USER' => $this->getApiUsername(),
                        'Accept' => 'application/json',
                    ])
                    ->post($this->config['api_url'], $payload);
            }

            $body = $response->json();
            if (! is_array($body)) {
                $body = [];
            }
            $providerSuccess = $response->successful()
                && (
                    ! empty($body['success'])
                    || strtolower((string) ($body['status'] ?? '')) === 'success'
                    || strtolower((string) ($body['Status'] ?? '')) === 'success'
                    || (string) ($body['code'] ?? '') === '00'
                );

            if ($providerSuccess) {
                return [
                    'success' => true,
                    'message' => $body['message'] ?? $body['Message'] ?? __('lang_v1.myreload_success'),
                    'provider_ref' => $body['transaction_id']
                        ?? $body['TransactionId']
                        ?? $body['ref']
                        ?? $body['reference']
                        ?? null,
                    'response' => $body,
                    'demo' => false,
                ];
            }

            $apiMessage = $body['message']
                ?? $body['Message']
                ?? $body['error']
                ?? $body['description']
                ?? null;

            if (empty($apiMessage)) {
                $apiMessage = __('lang_v1.myreload_failed').' (HTTP '.$response->status().')';
            }

            Log::warning('MyReload API rejected reload', [
                'http_status' => $response->status(),
                'body' => $body,
                'reference' => $reference,
            ]);

            return [
                'success' => false,
                'message' => $apiMessage,
                'response' => $body,
                'http_status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('MyReload API error: '.$e->getMessage(), [
                'reference' => $reference,
            ]);

            return [
                'success' => false,
                'message' => __('lang_v1.myreload_failed').': '.$e->getMessage(),
            ];
        }
    }

    /**
     * Local simulated reload when live API is disabled / not configured.
     */
    public function demoReload(string $mobile_number, string $operator, float $amount): array
    {
        $mobile = self::normalizeMobile($mobile_number) ?: $mobile_number;

        return [
            'success' => true,
            'message' => __('lang_v1.myreload_demo_success', [
                'operator' => MobileReloadTransaction::operators()[$operator] ?? $operator,
                'amount' => number_format($amount, 2),
                'mobile' => $mobile,
            ]),
            'provider_ref' => 'DEMO-'.strtoupper(substr(md5($mobile.$amount.time()), 0, 10)),
            'demo' => true,
        ];
    }

    /**
     * Local simulated bill payment when API is not set.
     */
    public function demoBillPayment(string $payment_type, string $provider, string $account_or_desc, float $amount): array
    {
        $type_label = MobileReloadTransaction::paymentTypes()[$payment_type] ?? $payment_type;
        $provider_label = $payment_type === 'utility'
            ? (MobileReloadTransaction::utilityProviders()[$provider] ?? $provider)
            : $account_or_desc;

        return [
            'success' => true,
            'message' => __('lang_v1.bill_payment_demo_success', [
                'type' => $type_label,
                'provider' => $provider_label,
                'amount' => number_format($amount, 2),
                'account' => $account_or_desc,
            ]),
            'provider_ref' => 'DEMO-BILL-'.strtoupper(substr(md5($provider.$account_or_desc.$amount.time()), 0, 10)),
            'demo' => true,
        ];
    }
}
