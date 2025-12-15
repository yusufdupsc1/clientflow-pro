<?php

namespace App\Console\Commands;

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\PostPayment;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Tenancy\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DemoSeedCommand extends Command
{
    protected $signature = 'app:demo-seed';

    protected $description = 'Seed demo data for local exploration';

    public function handle(): int
    {
        $this->comment('Seeding demo data...');

        $organizations = collect([
            ['name' => 'Acme Studio', 'slug' => 'acme-studio'],
            ['name' => 'Beta Labs', 'slug' => 'beta-labs'],
        ])->map(function ($data) {
            $owner = User::factory()->create([
                'name' => $data['name'].' Owner',
                'email' => Str::slug($data['slug']).'.owner@example.com',
                'email_verified_at' => now(),
                'current_organization_id' => null,
            ]);

            $org = Organization::create([
                'name' => $data['name'],
                'slug' => $data['slug'].'-'.Str::random(5),
                'owner_user_id' => $owner->id,
            ]);

            Permissions::ensureDefaultRolesForOrganization($org->id);
            Permissions::syncUserRole($owner, $org->id, 'owner');
            $owner->organizations()->attach($org->id, ['role' => 'owner']);
            $owner->forceFill(['current_organization_id' => $org->id])->save();

            // Add an admin and member
            $admin = User::factory()->create([
                'name' => $data['name'].' Admin',
                'email' => Str::slug($data['slug']).'.admin@example.com',
                'email_verified_at' => now(),
                'current_organization_id' => $org->id,
            ]);
            $admin->organizations()->attach($org->id, ['role' => 'admin']);
            Permissions::syncUserRole($admin, $org->id, 'admin');

            $member = User::factory()->create([
                'name' => $data['name'].' Member',
                'email' => Str::slug($data['slug']).'.member@example.com',
                'email_verified_at' => now(),
                'current_organization_id' => $org->id,
            ]);
            $member->organizations()->attach($org->id, ['role' => 'member']);
            Permissions::syncUserRole($member, $org->id, 'member');

            $this->seedDataForOrg($org);

            return $org;
        });

        $this->info("Seeded {$organizations->count()} demo organizations.");

        return self::SUCCESS;
    }

    protected function seedDataForOrg(Organization $org): void
    {
        Tenant::set($org->id);

        $client = Client::create([
            'organization_id' => $org->id,
            'name' => 'Client '.$org->name,
            'email' => 'client+'.Str::random(5).'@example.com',
        ]);

        $project = Project::create([
            'organization_id' => $org->id,
            'client_id' => $client->id,
            'name' => 'Project '.$org->name,
            'status' => 'active',
        ]);

        $invoice = app(CreateInvoice::class)->handle([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'title' => 'Demo Invoice for '.$org->name,
            'items' => [
                ['description' => 'Setup', 'quantity' => 1, 'unit_price_cents' => 15000],
                ['description' => 'Monthly service', 'quantity' => 1, 'unit_price_cents' => 5000],
            ],
        ]);

        $invoice = app(\App\Actions\Billing\UpdateInvoice::class)->handle($invoice, [
            'status' => 'sent',
            'title' => $invoice->title,
            'items' => [
                ['description' => 'Setup', 'quantity' => 1, 'unit_price_cents' => 15000],
                ['description' => 'Monthly service', 'quantity' => 1, 'unit_price_cents' => 5000],
            ],
        ]);

        app(PostPayment::class)->handle($invoice, [
            'amount_cents' => 5000,
            'method' => 'card',
            'reference' => 'demo-'.Str::random(6),
        ]);
    }
}
