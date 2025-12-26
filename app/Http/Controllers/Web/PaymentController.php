<?php

namespace App\Http\Controllers\Web;

use App\Actions\Billing\RefundPayment;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected RefundPayment $refundPayment
    ) {
    }

    /**
     * Process a refund for a payment.
     */
    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment->invoice);

        $validated = $request->validate([
            'amount_cents' => ['nullable', 'integer', 'min:1', 'max:' . $payment->amount_cents],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->refundPayment->execute(
            $payment,
            $validated['amount_cents'] ?? null,
            $validated['reason'] ?? null
        );

        if ($result['success']) {
            return redirect()->back()->with('success', 'Payment refunded successfully.');
        }

        return redirect()->back()->with('error', $result['error'] ?? 'Refund failed.');
    }
}
