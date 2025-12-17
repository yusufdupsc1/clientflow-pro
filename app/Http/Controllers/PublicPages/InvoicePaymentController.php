<?php

namespace App\Http\Controllers\PublicPages;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\StripeService;
use App\Support\Tenancy\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InvoicePaymentController extends Controller
{
    public function show(Request $request, string $public_hash): View
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->where('public_hash', $public_hash)
            ->firstOrFail();

        Tenant::set($invoice->organization_id);
        $invoice->loadMissing(['items', 'client']);

        $organization = $invoice->organization()->first();
        $logoUrl = $organization?->branding_logo_path
            ? Storage::disk('public')->url($organization->branding_logo_path)
            : null;

        $remainingCents = max(0, (int) $invoice->total_cents - (int) $invoice->amount_paid_cents);

        $view = view('payments.checkout', [
            'invoice' => $invoice,
            'paid' => $invoice->status === 'paid' || $remainingCents <= 0,
            'paymentConfirmed' => $request->boolean('success'),
            'organization' => $organization,
            'logoUrl' => $logoUrl,
            'remainingCents' => $remainingCents,
        ]);

        Tenant::clear();

        return $view;
    }

    public function checkout(Request $request, string $public_hash, StripeService $stripeService): RedirectResponse
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->where('public_hash', $public_hash)
            ->firstOrFail();

        Tenant::set($invoice->organization_id);

        $remainingCents = max(0, (int) $invoice->total_cents - (int) $invoice->amount_paid_cents);

        if ($invoice->status === 'void') {
            return back()->withErrors(['invoice' => __('This invoice is void.')]);
        }

        if ($invoice->status === 'paid' || $remainingCents <= 0) {
            return back()->withErrors(['invoice' => __('This invoice is already paid.')]);
        }

        if ($invoice->status === 'draft') {
            $invoice->status = 'sent';
            $invoice->sent_at = now();
            $invoice->save();
        }

        $successUrl = route('pay.invoices.show', $invoice->public_hash).'?success=1';
        $cancelUrl = route('pay.invoices.show', $invoice->public_hash);

        try {
            $url = $stripeService->createCheckoutSession($invoice, $successUrl, $cancelUrl);
        } catch (\Throwable $e) {
            Log::error('stripe.checkout.failed', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'stripe' => __('Unable to start checkout: :message', ['message' => $e->getMessage()]),
            ]);
        } finally {
            Tenant::clear();
        }

        return redirect()->away($url);
    }
}
