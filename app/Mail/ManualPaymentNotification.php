<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EscrowTransaction;

class ManualPaymentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public EscrowTransaction $transaction;

    /**
     * Create a new message instance.
     */
    public function __construct(EscrowTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Manual payment notification — awaiting approval')
            ->view('emails.manual_payment_notification')
            ->with([
                'transaction' => $this->transaction,
            ]);
    }
}
