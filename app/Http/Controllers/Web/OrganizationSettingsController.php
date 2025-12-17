<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    use AuthorizesRequests;

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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'default_currency' => ['nullable', 'string', 'size:3'],
        ]);

        $organization->fill([
            'name' => $data['name'],
            'billing_email' => $data['billing_email'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'default_currency' => strtoupper($data['default_currency'] ?? $organization->default_currency ?? config('stripe.default_currency', 'USD')),
        ])->save();

        return back()->with('status', __('Organization profile updated.'));
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
            'stripe_mode' => ['required', Rule::in(['test', 'live'])],
            'stripe_live_secret' => ['nullable', 'string', 'max:255'],
            'stripe_live_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_live_webhook_secret' => ['nullable', 'string', 'max:255'],
            'stripe_test_secret' => ['nullable', 'string', 'max:255'],
            'stripe_test_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_test_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['stripe_mode'] === 'live' && (! $data['stripe_live_secret'] || ! $data['stripe_live_publishable_key'])) {
            return back()->withErrors([
                'stripe_live_secret' => __('Live keys are required when live mode is enabled.'),
            ]);
        }

        $organization->fill($data)->save();

        return back()->with('status', __('Billing settings saved.'));
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

        $data = $request->validate([
            'branding_logo' => ['nullable', 'image', 'max:2048'],
            'branding_color' => ['nullable', 'string', 'max:20'],
        ]);

        if ($request->hasFile('branding_logo')) {
            $path = $request->file('branding_logo')->store("branding/{$organization->id}", ['disk' => 'public']);
            $organization->branding_logo_path = $path;
        }

        if (isset($data['branding_color'])) {
            $organization->branding_color = $data['branding_color'];
        }

        $organization->save();

        return back()->with('status', __('Branding updated.'));
    }

    protected function resolveOrganization(Request $request): Organization
    {
        return $request->attributes->get('organization')
            ?? auth()->user()?->currentOrganization
            ?? abort(403, 'Organization not selected.');
    }
}
