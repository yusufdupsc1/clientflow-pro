<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\StripeService;
use App\Support\Billing\Currency;
use App\Support\Tenancy\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    /**
     * Display the unified client portal for an invoice.
     */
    public function show(Request $request, string $public_hash): View
    {
        $invoice = $this->findInvoice($public_hash);

        return $this->runInTenantContext($invoice, function () use ($request, $invoice) {
            $invoice->load(['items', 'client', 'organization', 'project']);

            $organization = $invoice->organization;
            $logoUrl = $organization?->branding_logo_path
                ? Storage::disk('public')->url($organization->branding_logo_path)
                : null;

            $invoice->currency = Currency::normalize(
                $invoice->currency,
                $organization?->default_currency ?? strtoupper(config('stripe.default_currency', 'USD'))
            );

            $remainingCents = max(0, (int) $invoice->total_cents - (int) $invoice->amount_paid_cents);

            $history = $this->fetchClientHistory($invoice);

            return view('portal.show', [
                'invoice' => $invoice,
                'paid' => $invoice->status === 'paid' || $remainingCents <= 0,
                'paymentConfirmed' => $request->boolean('success'),
                'organization' => $organization,
                'logoUrl' => $logoUrl,
                'remainingCents' => $remainingCents,
                'remainingFormatted' => number_format($remainingCents / 100, 2),
                'history' => $history,
            ]);
        });
    }

    /**
     * Redirect to Stripe Checkout from the portal.
     */
    public function checkout(Request $request, string $public_hash, StripeService $stripeService): RedirectResponse
    {
        $invoice = $this->findInvoice($public_hash);

        return $this->runInTenantContext($invoice, function () use ($invoice, $stripeService) {
            $remainingCents = max(0, (int) $invoice->total_cents - (int) $invoice->amount_paid_cents);

            if ($invoice->status === 'void') {
                return back()->with('error', __('This invoice is void.'));
            }

            if ($invoice->status === 'paid' || $remainingCents <= 0) {
                return back()->with('success', __('This invoice is already paid.'));
            }

            if ($invoice->status === 'draft') {
                $invoice->status = 'sent';
                $invoice->sent_at = now();
                $invoice->save();
            }

            $successUrl = route('portal.show', ['public_hash' => $invoice->public_hash, 'success' => 1]);
            $cancelUrl = route('portal.show', ['public_hash' => $invoice->public_hash]);

            try {
                $session = $stripeService->createCheckoutSession($invoice, $successUrl, $cancelUrl);
                return redirect()->away($session->url);
            } catch (\Throwable $e) {
                Log::error('stripe.portal_checkout.failed', [
                    'invoice_id' => $invoice->id,
                    'message' => $e->getMessage(),
                ]);

                return back()->with('error', __('Unable to start checkout: :message', ['message' => $e->getMessage()]));
            }
        });
    }

    /**
     * Download the invoice PDF from the portal.
     */
    public function download(string $public_hash): RedirectResponse|\Illuminate\Http\Response
    {
        $invoice = $this->findInvoice($public_hash);

        return $this->runInTenantContext($invoice, function () use ($invoice) {
            $invoice->load(['items', 'client', 'organization']);

            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', [
                    'invoice' => $invoice,
                    'organization' => $invoice->organization,
                    'logoPath' => $invoice->organization?->branding_logo_path ? storage_path('app/public/' . $invoice->organization->branding_logo_path) : null,
                ]);

                $filename = 'Invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';

                return $pdf->download($filename);
            } catch (\Exception $e) {
                Log::error('portal.pdf_download_failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
                return back()->with('error', 'Unable to generate PDF. Please try again later.');
            }
        });
    }

    protected function findInvoice(string $public_hash): Invoice
    {
        return Invoice::withoutGlobalScopes()
            ->where('public_hash', $public_hash)
            ->firstOrFail();
    }

    protected function runInTenantContext(Invoice $invoice, callable $callback)
    {
        Tenant::set($invoice->organization_id);

        try {
            return $callback();
        } finally {
            Tenant::clear();
        }
    }

    protected function fetchClientHistory(Invoice $invoice)
    {
        if (!$invoice->client_id) {
            return [];
        }

        return Invoice::withoutGlobalScopes()
            ->where('client_id', $invoice->client_id)
            ->where('organization_id', $invoice->organization_id)
            ->where('id', '!=', $invoice->id)
            ->where('status', '!=', 'draft')
            ->latest()
            ->take(5)
            ->get();
    }
}
