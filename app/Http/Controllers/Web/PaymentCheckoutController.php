<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PaymentCheckoutController extends Controller
{
    /**
     * Display the public payment checkout page for an invoice.
     */
    public function show(Invoice $invoice): View|RedirectResponse
    {
        // Only allow payment for sent invoices
        if ($invoice->status !== 'sent') {
            return redirect()->route('pay.status', $invoice)->with('error', 'This invoice cannot be paid.');
        }

        // Check if already fully paid
        if ($invoice->amount_paid_cents >= $invoice->total_cents) {
            return redirect()->route('pay.status', $invoice)->with('success', 'This invoice has already been paid.');
        }

        $invoice->loadMissing(['items', 'client', 'organization']);

        $remainingCents = $invoice->total_cents - $invoice->amount_paid_cents;

        return view('pay.checkout', [
            'invoice' => $invoice,
            'remainingCents' => $remainingCents,
            'remainingFormatted' => number_format($remainingCents / 100, 2),
            'currency' => strtoupper($invoice->currency ?? 'USD'),
        ]);
    }

    /**
     * Redirect to Stripe Checkout.
     */
    public function redirect(Invoice $invoice, StripeService $stripe): RedirectResponse
    {
        // Validate invoice can be paid
        if ($invoice->status !== 'sent') {
            return back()->with('error', 'This invoice cannot be paid.');
        }

        if ($invoice->amount_paid_cents >= $invoice->total_cents) {
            return redirect()->route('pay.status', $invoice)->with('success', 'This invoice has already been paid.');
        }

        // Validate environment
        if (!$stripe->validateEnvironment()) {
            return back()->with('error', 'Payment system configuration error. Please contact support.');
        }

        $successUrl = url("/pay/{$invoice->id}/success?session_id={CHECKOUT_SESSION_ID}");
        $cancelUrl = url("/pay/{$invoice->id}");

        try {
            $session = $stripe->createCheckoutSession($invoice, $successUrl, $cancelUrl);
            return redirect($session->url);
        } catch (\Exception $e) {
            \Log::error('stripe.checkout_redirect_failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Unable to create payment session. Please try again.');
        }
    }

    /**
     * Handle successful payment return from Stripe.
     */
    public function success(Invoice $invoice, Request $request): View
    {
        $sessionId = $request->query('session_id');

        // The webhook will handle the actual payment recording
        // This is just a thank-you page

        return view('pay.success', [
            'invoice' => $invoice,
            'sessionId' => $sessionId,
        ]);
    }

    /**
     * Display invoice payment status.
     */
    public function status(Invoice $invoice): View
    {
        $invoice->loadMissing(['items', 'client', 'organization', 'payments']);

        return view('pay.status', [
            'invoice' => $invoice,
            'isPaid' => $invoice->status === 'paid',
            'isVoid' => $invoice->status === 'void',
        ]);
    }
}
