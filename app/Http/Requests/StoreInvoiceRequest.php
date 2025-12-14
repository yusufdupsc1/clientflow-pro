<?php

namespace App\Http\Requests;

use App\Support\Tenancy\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = Tenant::id();

        return [
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) use ($tenantId) {
                    if ($tenantId) {
                        $query->where('organization_id', $tenantId);
                    }
                }),
            ],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(function ($query) use ($tenantId) {
                    if ($tenantId) {
                        $query->where('organization_id', $tenantId);
                    }
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
