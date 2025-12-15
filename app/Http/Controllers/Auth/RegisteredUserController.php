<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Support\Permissions;



class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $organizationName = $data['organization_name'] ?? "{$data['name']}'s Organization";
            $organization = $this->createOrganizationWithUniqueSlug(
                $organizationName,
                $data['organization_slug'] ?? null,
                $user->id
            );

            $user->organizations()->attach($organization->id, ['role' => 'owner']);
            Permissions::ensureDefaultRolesForOrganization($organization->id);
            Permissions::syncUserRole($user, $organization->id, 'owner');

            $user->forceFill([
                'current_organization_id' => $organization->id,
            ])->save();

            return $user->fresh('currentOrganization');
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function createOrganizationWithUniqueSlug(string $name, ?string $providedSlug, int $ownerUserId): Organization
    {
        $base = Str::slug($providedSlug ?: $name) ?: Str::random(8);
        $suffix = 0;

        while (true) {
            $slug = $suffix === 0 ? $base : "{$base}-{$suffix}";

            try {
                return Organization::create([
                    'name' => $name,
                    'slug' => $slug,
                    'owner_user_id' => $ownerUserId,
                ]);
            } catch (\Illuminate\Database\QueryException $exception) {
                if (str_contains($exception->getMessage(), 'organizations_slug_unique')) {
                    $suffix++;
                    continue;
                }

                throw $exception;
            }
        }
    }
}
