<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Models\Withdrawal;
use App\Models\SquadcoWebhookEvent;
use App\Services\SquadcoService;

Route::post('/webhooks/squadco', function (Request $request) {
    $payloadRaw = $request->getContent();
    $payload = $request->all();
    $signature = $request->header('X-Squadco-Signature') ?? $request->header('X-Signature');

    Log::info('Received Squadco webhook (raw)', ['payload_raw' => $payloadRaw, 'headers' => $request->headers->all()]);

    $squadco = app(SquadcoService::class);
    $verified = $squadco->verifyWebhook($payloadRaw, $signature);
    if (! $verified) {
        Log::warning('Squadco webhook signature verification failed');
        return response()->json(['received' => false, 'reason' => 'invalid_signature'], 400);
    }

    // Persist the webhook event for auditing
    $event = SquadcoWebhookEvent::create([
        'event_type' => $payload['event'] ?? ($payload['type'] ?? null),
        'reference' => $payload['reference'] ?? ($payload['data']['reference'] ?? null),
        'payload' => $payload,
    ]);

    $reference = $payload['reference'] ?? ($payload['data']['reference'] ?? null);
    $status = $payload['status'] ?? ($payload['data']['status'] ?? null);

    if ($reference) {
        $withdrawal = Withdrawal::where('reference', $reference)->first();
        if ($withdrawal) {
            if (in_array($status, ['processed', 'completed', 'success'])) {
                $withdrawal->status = 'approved';
            } elseif (in_array($status, ['failed', 'declined', 'error'])) {
                $withdrawal->status = 'failed';
            }
            $withdrawal->provider_response = array_merge($withdrawal->provider_response ?? [], ['webhook_event_id' => $event->id, 'webhook_payload' => $payload]);
            $withdrawal->save();
        }
    }

    return response()->json(['received' => true]);
});
