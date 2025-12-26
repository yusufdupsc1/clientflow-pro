<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverdueInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice #' . ($this->invoice->invoice_number ?? $this->invoice->id) . ' is Overdue',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-overdue',
            with: [
                'invoice' => $this->invoice,
                'organization' => $this->invoice->organization,
                'client' => $this->invoice->client,
                'paymentUrl' => url('/pay/' . $this->invoice->id),
            ],
        );
    }
}
