<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OverdueInvoiceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build(): self
    {
        return $this->subject('Overdue Invoice '.$this->invoice->invoice_number)
            ->view('emails.overdue-invoice', [
                'invoice' => $this->invoice,
            ]);
    }
}
