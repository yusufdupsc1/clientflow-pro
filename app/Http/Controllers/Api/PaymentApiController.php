<?php

namespace App\Http\Controllers\Api;

use App\Actions\Billing\PostPayment;
use App\Actions\Billing\RefundPayment;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Invoice $invoice, PostPayment $postPayment)
    {
        $this->authorize('update', $invoice);

        $data = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = $postPayment->handle($invoice, $data);

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }

    public function refund(Request $request, Invoice $invoice, Payment $payment, RefundPayment $refundPayment)
    {
        $this->authorize('update', $invoice);

        if ($payment->invoice_id !== $invoice->id) {
            abort(404);
        }

        $data = $request->validate([
            'amount_cents' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $refundPayment->handle($payment, $data);

        return (new PaymentResource($payment->refresh()))->response()->setStatusCode(200);
    }
}
