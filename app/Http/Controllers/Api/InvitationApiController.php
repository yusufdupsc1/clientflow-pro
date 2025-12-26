<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\RoleChangedMail;

class InvitationApiController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageMembers', $organization);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:owner,admin,member'],
        ]);

        $token = Str::random(40);

        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'token' => $token,
        ]);

        return response()->json($invitation, 201);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        $organization = $invitation->organization;

        $user = User::where('email', $invitation->email)->first();

        if (!$user) {
            $payload = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', 'min:8'],
            ]);

            $user = User::create([
                'name' => $payload['name'],
                'email' => $invitation->email,
                'password' => Hash::make($payload['password']),
                'email_verified_at' => now(),
                'current_organization_id' => $organization->id,
            ]);
        }

        $organization->users()->syncWithoutDetaching([
            $user->id => ['role' => $invitation->role],
        ]);

        Permissions::syncUserRole($user, $organization->id, $invitation->role);

        $user->forceFill(['current_organization_id' => $organization->id])->save();

        $invitation->forceFill([
            'accepted_at' => now(),
            'token' => null,
        ])->save();

        return response()->json([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => $invitation->role,
        ]);
    }

    public function updateMemberRole(Request $request, Organization $organization, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $organization);

        $data = $request->validate([
            'role' => ['required', 'in:owner,admin,member'],
        ]);

        if (!$organization->users()->whereKey($user->id)->exists()) {
            abort(403);
        }

        // Get old role before updating
        $oldRole = $organization->users()->whereKey($user->id)->first()->pivot->role;
        $newRole = $data['role'];

        // Only update and notify if role actually changed
        if ($oldRole !== $newRole) {
            $organization->users()->updateExistingPivot($user->id, ['role' => $newRole]);
            Permissions::syncUserRole($user, $organization->id, $newRole);

            if ($user->email) {
                Mail::to($user->email)->queue(new RoleChangedMail($user, $organization, $oldRole, $newRole));
            }
        }

        return response()->json([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $data['role'],
        ]);
    }
}
