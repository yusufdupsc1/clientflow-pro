<?php

namespace Tests\Feature;

use App\Console\Commands\DemoSeedCommand;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_creates_sample_data(): void
    {
        $this->artisan(DemoSeedCommand::class)->assertExitCode(0);

        $this->assertGreaterThanOrEqual(2, Organization::count());
        $this->assertGreaterThanOrEqual(3, User::count());
        $this->assertGreaterThanOrEqual(1, Invoice::count());
    }
}
