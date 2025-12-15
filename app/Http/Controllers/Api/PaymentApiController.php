<?php

namespace App\Http\Controllers\Api;

use App\Actions\Billing\PostPayment;
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
}
