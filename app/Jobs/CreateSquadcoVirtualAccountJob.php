<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\SquadcoService;

class CreateSquadcoVirtualAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $firstName;
    protected $lastName;
    protected $mobileNumber;

    /**
     * Create a new job instance.
     */
    public function __construct($email, $firstName, $lastName, $mobileNumber)
    {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->mobileNumber = $mobileNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('CreateSquadcoVirtualAccountJob started', ['email' => $this->email]);

            $user = User::where('email', $this->email)->first();
            if (!$user) {
                Log::warning('CreateSquadcoVirtualAccountJob: User not found', ['email' => $this->email]);
                return;
            }

            $squadco = app(SquadcoService::class);

            $full_name = trim($this->firstName . ' ' . $this->lastName) ?: $this->email;

            $ref = 'SQVA' . time() . mt_rand(10000000, 9999999999);
            $payload = [
                'account_name' => $full_name,
                'email' => $this->email,
                'currency' => env('DEFAULT_CURRENCY', 'NGN'),
                'reference' => $ref,
                'phone' => $this->mobileNumber,
            ];

            $resp = $squadco->createVirtualAccount($payload);

            // Map common response shapes to our StaticVirtualAccount format
            $account_number = null;
            $bank_name = null;
            $order_ref = $ref;

            if (is_array($resp) || is_object($resp)) {
                $r = is_array($resp) ? $resp : (array) $resp;
                if (!empty($r['data'])) {
                    $d = (array) $r['data'];
                    // common shapes
                    if (!empty($d['account_number'])) {
                        $account_number = $d['account_number'];
                    } elseif (!empty($d['account'][0]['account_number'])) {
                        $account_number = $d['account'][0]['account_number'];
                    } elseif (!empty($d['virtual_account']['account_number'])) {
                        $account_number = $d['virtual_account']['account_number'];
                    }

                    if (!empty($d['bank_name'])) {
                        $bank_name = $d['bank_name'];
                    } elseif (!empty($d['account'][0]['bank_name'])) {
                        $bank_name = $d['account'][0]['bank_name'];
                    } elseif (!empty($d['virtual_account']['bank'])) {
                        $bank_name = $d['virtual_account']['bank'];
                    }

                    if (!empty($d['reference'])) {
                        $order_ref = $d['reference'];
                    }
                }

                // fallback top-level
                if (!$account_number) {
                    if (!empty($r['account_number'])) $account_number = $r['account_number'];
                    if (!empty($r['virtual_account']['account_number'])) $account_number = $r['virtual_account']['account_number'];
                    if (!empty($r['data']['account_number'])) $account_number = $r['data']['account_number'];
                }
            }

            if (!empty($account_number)) {
                $svadata = [
                    'account_number' => $account_number,
                    'bank_name' => $bank_name ?? 'Unknown',
                    'txt_ref' => $ref,
                    'order_ref' => $order_ref ?? $ref,
                    'email' => $this->email,
                    'user_id' => $user->id,
                ];

                \App\Http\Controllers\Funding\StaticVirtualAccountController::save($svadata);
                Log::info('CreateSquadcoVirtualAccountJob: Virtual account created', [
                    'email' => $this->email,
                    'account_number' => $account_number,
                ]);
            } else {
                Log::warning('CreateSquadcoVirtualAccountJob: No account number in response', [
                    'email' => $this->email,
                    'response' => $resp,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('CreateSquadcoVirtualAccountJob failed', [
                'email' => $this->email,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Re-throw so the queue can retry (default: 3 attempts)
            throw $exception;
        }
    }
}
