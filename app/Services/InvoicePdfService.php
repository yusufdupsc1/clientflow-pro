<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'client', 'project', 'organization']);

        $organization = $invoice->organization;
        $logoPath = null;

        if ($organization?->branding_logo_path) {
            $absoluteLogo = Storage::disk('public')->path($organization->branding_logo_path);
            if (is_file($absoluteLogo)) {
                $logoPath = $absoluteLogo;
            }
        }

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'organization' => $organization,
            'logoPath' => $logoPath,
        ])->setPaper('A4')->output();
    }
}
