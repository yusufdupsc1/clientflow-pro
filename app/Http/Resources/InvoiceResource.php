<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'client_id' => $this->client_id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'notes' => $this->notes,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'sent_at' => $this->sent_at,
            'paid_at' => $this->paid_at,
            'amount_paid_cents' => $this->amount_paid_cents,
            'subtotal_cents' => $this->subtotal_cents,
            'discount_cents' => $this->discount_cents,
            'tax_cents' => $this->tax_cents,
            'tax_rate_percent' => $this->tax_rate_percent,
            'total_cents' => $this->total_cents,
            'currency' => $this->currency,
            'public_hash' => $this->public_hash,
            'stripe_price_currency' => $this->stripe_price_currency,
            'stripe_payment_link_url' => $this->stripe_payment_link_url,
            'stripe_mode' => $this->stripe_mode,
            'client' => new ClientResource($this->whenLoaded('client')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
