<?php

namespace App\Http\Requests;

use App\Support\Tenancy\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'active', 'completed'])],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
