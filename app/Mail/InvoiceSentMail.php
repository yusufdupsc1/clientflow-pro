<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public string $pdfContent;

    public function __construct(Invoice $invoice, string $pdfContent)
    {
        $this->invoice = $invoice;
        $this->pdfContent = $pdfContent;
    }

    public function build(): self
    {
        return $this->subject('Invoice '.$this->invoice->invoice_number)
            ->view('emails.invoice-sent', [
                'invoice' => $this->invoice,
            ])
            ->attachData(
                $this->pdfContent,
                'invoice-'.$this->invoice->invoice_number.'.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
