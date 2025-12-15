<?php

namespace Tests;

use Illuminate\Foundation\Vite;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestingVite;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Vite::class, new TestingVite());
    }
}
