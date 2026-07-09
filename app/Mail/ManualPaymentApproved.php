<?php

namespace App\Mail;

use App\Models\EscrowTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ManualPaymentApproved extends Mailable
{
    use Queueable, SerializesModels;

    public EscrowTransaction $transaction;

    public function __construct(EscrowTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function build()
    {
        return $this->subject('Your manual payment has been approved')
            ->view('emails.manual_payment_approved')
            ->with([
                'transaction' => $this->transaction,
            ]);
    }
}
