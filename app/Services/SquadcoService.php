<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Exception;

/**
 * Minimal Squadco API client skeleton.
 * Replace placeholder endpoints and keys with environment variables.
 */
class SquadcoService
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.squadco.base_url', env('SQUADCO_BASE_URL', '')), '/');
        $this->apiKey = config('services.squadco.api_key', env('SQUADCO_API_KEY'));
        $this->retries = (int) config('services.squadco.request_retries', 3);
        $this->retryDelay = (int) config('services.squadco.request_retry_delay_ms', 200);
    }

    /** Create a virtual account for a user. Returns array response or throws */
    public function createVirtualAccount(array $payload): array
    {
        // Example payload: ['account_name'=>..., 'email'=>..., 'currency'=>'NGN']
        try {
            $headers = ['Accept' => 'application/json'];
            if (!empty($payload['reference'])) {
                $headers['Idempotency-Key'] = $payload['reference'];
            }
            $response = Http::withHeaders($headers)
                ->withToken($this->apiKey)
                ->timeout(10)
                ->retry($this->retries, $this->retryDelay)
                ->post($this->baseUrl . '/virtual-accounts', $payload);

            if ($response->failed()) {
                throw new Exception('Squadco createVirtualAccount error: ' . $response->body());
            }

            $json = $response->json() ?? [];
            return $json;
        } catch (ConnectionException $e) {
            throw new Exception('Squadco createVirtualAccount connection error: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Squadco createVirtualAccount failed: ' . $e->getMessage());
        }
    }

    /** Transfer funds from central account to a bank account (payout). */
    public function payoutToBank(array $payload): array
    {
        // payload: ['account_number','bank_code','amount','narration','reference']
        try {
            $headers = ['Accept' => 'application/json'];
            if (!empty($payload['reference'])) {
                $headers['Idempotency-Key'] = $payload['reference'];
            }
            $response = Http::withHeaders($headers)
                ->withToken($this->apiKey)
                ->timeout(15)
                ->retry($this->retries, $this->retryDelay)
                ->post($this->baseUrl . '/payouts/bank', $payload);

            if ($response->failed()) {
                throw new Exception('Squadco payoutToBank error: ' . $response->body());
            }

            $json = $response->json() ?? [];
            return $json;
        } catch (ConnectionException $e) {
            throw new Exception('Squadco payoutToBank connection error: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Squadco payoutToBank failed: ' . $e->getMessage());
        }
    }

    // Add other helper methods (verify webhook signature, get account balance, etc.) as needed.

    /**
     * Verify webhook signature using configured webhook secret (HMAC-SHA256).
     * Expects hex-encoded signature in header.
     */
    public function verifyWebhook(string $payloadRaw, ?string $signatureHeader): bool
    {
        $secret = config('services.squadco.webhook_secret', env('SQUADCO_WEBHOOK_SECRET'));
        if (empty($secret) || empty($signatureHeader)) {
            return false;
        }
        // compute HMAC-SHA256
        $computed = hash_hmac('sha256', $payloadRaw, $secret);
        // Signature header may include scheme or prefix like 'sha256='
        if (strpos($signatureHeader, '=') !== false) {
            [$scheme, $sig] = explode('=', $signatureHeader, 2) + [null, null];
            if ($sig && hash_equals($computed, $sig)) {
                return true;
            }
        }

        return hash_equals($computed, $signatureHeader) || str_contains($signatureHeader, $computed);
    }
}
