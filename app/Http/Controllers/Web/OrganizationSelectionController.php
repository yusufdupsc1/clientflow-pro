<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSelectionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'organization_id' => ['required', 'integer'],
        ]);

        $organizationId = (int) $data['organization_id'];

        $belongs = $user->organizations()->whereKey($organizationId)->exists();

        if (! $belongs) {
            abort(403);
        }

        $user->forceFill(['current_organization_id' => $organizationId])->save();

        return redirect()->route('dashboard');
    }
}
