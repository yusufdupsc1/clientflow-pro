<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build(): self
    {
        $invoice = $this->invoice->fresh(['items', 'client', 'project', 'organization']);
        $pdfContent = app(InvoicePdfService::class)->render($invoice);

        $fileName = 'invoice-'.($invoice->invoice_number ?? $invoice->id).'.pdf';

        return $this->subject('Invoice '.($invoice->invoice_number ?? $invoice->id))
            ->view('emails.invoice-sent', [
                'invoice' => $invoice,
            ])
            ->attachData(
                $pdfContent,
                $fileName,
                ['mime' => 'application/pdf']
            );
    }
}
