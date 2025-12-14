<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Tenancy\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if ($user->current_organization_id === null) {
            abort(403, 'No organization selected.');
        }

        $organization = $user->currentOrganization;

        if (! $organization instanceof Organization || $organization->getKey() !== $user->current_organization_id) {
            abort(403, 'No organization selected.');
        }

        $belongsToOrganization = $user->organizations()->whereKey($organization->getKey())->exists();

        if (! $belongsToOrganization) {
            abort(403, 'Invalid organization selection.');
        }

        App::instance('currentOrganization', $organization);
        App::instance('currentOrganizationId', $organization->getKey());
        $request->attributes->set('organization', $organization);
        Tenant::set($organization->getKey());

        return $next($request);
    }
}
