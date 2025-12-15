<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditExportTest extends TestCase
{
    use RefreshDatabase;

    private function orgWithOwner(string $label = 'org'): array
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'email' => strtolower($label).'.owner+'.Str::random(5).'@example.com',
        ]);

        $org = Organization::create([
            'name' => ucfirst($label).' Org',
            'slug' => Str::slug($label.'-'.Str::random(5)),
            'owner_user_id' => $owner->id,
        ]);

        $org->users()->attach($owner->id, ['role' => 'owner']);
        $owner->forceFill(['current_organization_id' => $org->id])->save();

        return [$org, $owner];
    }

    public function test_owner_can_view_audit_logs_member_cannot(): void
    {
        [$org, $owner] = $this->orgWithOwner('audit');

        ActivityLog::create([
            'organization_id' => $org->id,
            'actor_user_id' => $owner->id,
            'action' => 'test.action',
            'subject_type' => 'Test',
            'subject_id' => 1,
        ]);

        $this->actingAs($owner)->get('/audit')->assertOk()->assertSee('test.action');

        $member = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($member->id, ['role' => 'member']);
        $member->forceFill(['current_organization_id' => $org->id])->save();

        $this->actingAs($member)->get('/audit')->assertForbidden();
    }

    public function test_exports_are_scoped_to_org(): void
    {
        [$orgA, $ownerA] = $this->orgWithOwner('expA');
        [$orgB, $ownerB] = $this->orgWithOwner('expB');

        $this->actingAs($ownerA);
        ActivityLog::create([
            'organization_id' => $orgA->id,
            'actor_user_id' => $ownerA->id,
            'action' => 'invoices.sent',
            'subject_type' => 'Invoice',
            'subject_id' => 10,
        ]);

        $this->actingAs($ownerA)
            ->get('/invoices/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $this->actingAs($ownerB)
            ->get('/invoices/export')
            ->assertOk();
    }
}
