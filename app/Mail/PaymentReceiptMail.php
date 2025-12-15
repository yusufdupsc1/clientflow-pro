<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public Payment $payment;

    public function __construct(Invoice $invoice, Payment $payment)
    {
        $this->invoice = $invoice;
        $this->payment = $payment;
    }

    public function build(): self
    {
        return $this->subject('Payment received for Invoice '.$this->invoice->invoice_number)
            ->view('emails.payment-receipt', [
                'invoice' => $this->invoice,
                'payment' => $this->payment,
            ]);
    }
}
