<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:owner,admin,member'],
        ]);

        $token = Str::random(40);

        Invitation::create([
            'organization_id' => $organization->id,
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'token' => $token,
        ]);

        return redirect()->back();
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->whereNull('accepted_at')->firstOrFail();
        $organization = $invitation->organization;

        $existingUser = User::where('email', $invitation->email)->first();

        if (! $existingUser) {
            $payload = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', 'min:8'],
            ]);

            $existingUser = User::create([
                'name' => $payload['name'],
                'email' => $invitation->email,
                'password' => Hash::make($payload['password']),
                'email_verified_at' => now(),
                'current_organization_id' => $organization->id,
            ]);
        }

        $organization->users()->syncWithoutDetaching([
            $existingUser->id => ['role' => $invitation->role],
        ]);

        Permissions::syncUserRole($existingUser, $organization->id, $invitation->role);
        $existingUser->forceFill(['current_organization_id' => $organization->id])->save();

        $invitation->forceFill([
            'accepted_at' => now(),
            'token' => null,
        ])->save();

        Auth::login($existingUser);

        return redirect()->route('dashboard');
    }

    public function updateMemberRole(Request $request, Organization $organization, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);

        $data = $request->validate([
            'role' => ['required', 'in:owner,admin,member'],
        ]);

        if (! $organization->users()->whereKey($user->id)->exists()) {
            abort(403);
        }

        $organization->users()->updateExistingPivot($user->id, ['role' => $data['role']]);
        Permissions::syncUserRole($user, $organization->id, $data['role']);

        return redirect()->back();
    }

}
