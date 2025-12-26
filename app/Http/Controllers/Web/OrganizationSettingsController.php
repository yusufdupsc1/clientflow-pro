<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Models\Organization;
use App\Support\Billing\Currency;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
=======
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Models\Organization;
use App\Support\Tenancy\Tenant;
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)

class OrganizationSettingsController extends Controller
{
    use AuthorizesRequests;

<<<<<<< HEAD
    public function profile(Request $request): View
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);
=======
    /**
     * Show the organization settings page.
     */
    public function index(): View
    {
        $organization = $this->getCurrentOrganization();
        $this->authorize('update', $organization);

        return view('organizations.settings.index', [
            'organization' => $organization,
        ]);
    }

    /**
     * Show the profile settings.
     */
    public function profile(): View
    {
        $organization = $this->getCurrentOrganization();
        $this->authorize('update', $organization);
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)

        return view('organizations.settings.profile', [
            'organization' => $organization,
        ]);
    }

<<<<<<< HEAD
    public function updateProfile(Request $request): RedirectResponse
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'default_currency' => ['nullable', 'string', 'size:3', 'alpha:ascii'],
        ]);

        $organization->fill([
            'name' => $data['name'],
            'billing_email' => $data['billing_email'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'default_currency' => Currency::normalize(
                $data['default_currency'] ?? $organization->default_currency,
                strtoupper(config('stripe.default_currency', 'USD'))
            ),
        ])->save();

        return back()->with('status', __('Organization profile updated.'));
    }

    public function billing(Request $request): View
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('manageSettings', $organization);
=======
    /**
     * Update the organization profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $organization = $this->getCurrentOrganization();
        $this->authorize('update', $organization);

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

        return back()->with('success', 'Organization profile updated successfully.');
    }

    /**
     * Show the billing settings.
     */
    public function billing(): View
    {
        $organization = $this->getCurrentOrganization();
        $this->authorize('update', $organization);
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)

        return view('organizations.settings.billing', [
            'organization' => $organization,
        ]);
    }

<<<<<<< HEAD
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
=======
    /**
     * Update the billing settings.
     */
    public function updateBilling(Request $request): RedirectResponse
    {
        $organization = $this->getCurrentOrganization();
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'tax_id' => ['nullable', 'string', 'max:100'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
        ]);

        $organization->update($validated);

        return back()->with('success', 'Billing settings updated successfully.');
    }

    /**
     * Show the branding settings.
     */
    public function branding(): View
    {
        $organization = $this->getCurrentOrganization();
        $this->authorize('update', $organization);
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)

        return view('organizations.settings.branding', [
            'organization' => $organization,
        ]);
    }

<<<<<<< HEAD
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
=======
    /**
     * Update the branding settings.
     */
    public function updateBranding(Request $request): RedirectResponse
    {
        $organization = $this->getCurrentOrganization();
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif,svg', 'max:2048'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($organization->logo_path) {
                Storage::delete($organization->logo_path);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $organization->logo_path = $path;
        }

        if ($request->has('remove_logo') && $request->boolean('remove_logo')) {
            if ($organization->logo_path) {
                Storage::delete($organization->logo_path);
                $organization->logo_path = null;
            }
        }

        $organization->invoice_footer = $validated['invoice_footer'] ?? $organization->invoice_footer;
        $organization->save();

        return back()->with('success', 'Branding settings updated successfully.');
    }

    /**
     * Get the current organization.
     */
    protected function getCurrentOrganization(): Organization
    {
        $orgId = Tenant::id() ?? auth()->user()->current_organization_id;

        return Organization::findOrFail($orgId);
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
    }
}
