<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'organization_id' => $this->organization_id,
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'paid_at' => $this->paid_at,
            'refunded_cents' => $this->refunded_cents,
            'refunded_at' => $this->refunded_at,
            'method' => $this->method,
            'reference' => $this->reference,
            'provider' => $this->provider,
            'external_id' => $this->external_id,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'stripe_charge_id' => $this->stripe_charge_id,
            'stripe_refund_id' => $this->stripe_refund_id,
            'stripe_receipt_url' => $this->stripe_receipt_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
