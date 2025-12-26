<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\Billing\Currency;
use App\Support\Tenancy\Tenant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);

        return view('organizations.settings.index', [
            'organization' => $organization,
        ]);
    }

    public function profile(Request $request): View
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);

        return view('organizations.settings.profile', [
            'organization' => $organization,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:2'],
        ]);

        $organization->update($validated);

        return back()->with('status', 'Organization profile updated.');
    }

    public function billing(Request $request): View
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);

        return view('organizations.settings.billing', [
            'organization' => $organization,
        ]);
    }

    public function updateBilling(Request $request): RedirectResponse
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);

        $data = $request->validate([
            'tax_id' => ['nullable', 'string', 'max:100'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'stripe_mode' => ['required', Rule::in(['test', 'live'])],
            'stripe_live_secret' => ['nullable', 'string', 'max:255'],
            'stripe_live_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_live_webhook_secret' => ['nullable', 'string', 'max:255'],
            'stripe_test_secret' => ['nullable', 'string', 'max:255'],
            'stripe_test_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_test_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['stripe_mode'] === 'live' && (!($data['stripe_live_secret'] ?? null) || !($data['stripe_live_publishable_key'] ?? null))) {
            return back()->withErrors([
                'stripe_live_secret' => __('Live keys are required when live mode is enabled.'),
            ]);
        }

        $organization->fill($data);

        $organization->default_currency = Currency::normalize(
            $data['default_currency'] ?? $organization->default_currency,
            strtoupper(config('stripe.default_currency', 'USD'))
        );

        $organization->save();

        return back()->with('status', 'Billing settings updated.');
    }

    public function branding(Request $request): View
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);

        return view('organizations.settings.branding', [
            'organization' => $organization,
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);

        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif,svg', 'max:2048'],
            'branding_logo' => ['nullable', 'image', 'max:2048'],
            'branding_color' => ['nullable', 'string', 'max:20'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
        ]);

        $logoFile = $request->file('branding_logo') ?? $request->file('logo');

        if ($logoFile) {
            // Delete old logo if exists
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            if ($organization->branding_logo_path) {
                Storage::disk('public')->delete($organization->branding_logo_path);
            }

            $path = $logoFile->store("branding/{$organization->id}", 'public');
            $organization->logo_path = $path;
            $organization->branding_logo_path = $path;
        }

        if ($request->has('remove_logo') && $request->boolean('remove_logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
                $organization->logo_path = null;
                $organization->branding_logo_path = null;
            }
        }

        if (array_key_exists('branding_color', $validated)) {
            $organization->branding_color = $validated['branding_color'];
        }

        $organization->invoice_footer = $validated['invoice_footer'] ?? $organization->invoice_footer;
        $organization->save();

        return back()->with('status', 'Branding settings updated.');
    }

    protected function resolveOrganization(Request $request): Organization
    {
        $org = $request->attributes->get('organization')
            ?? auth()->user()?->currentOrganization;

        if (!$org) {
            $orgId = Tenant::id() ?? auth()->user()?->current_organization_id;
            if ($orgId) {
                $org = Organization::find($orgId);
            }
        }

        return $org ?? abort(403, 'Organization not selected.');
    }
}
