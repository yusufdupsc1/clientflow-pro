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
            'total_cents' => $this->total_cents,
            'client' => new ClientResource($this->whenLoaded('client')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
