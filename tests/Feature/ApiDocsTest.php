<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_docs_route_serves_markdown(): void
    {
        $response = $this->get('/api/docs');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $response->assertSee('Authorization: Bearer', false);
        $response->assertSee('/api/clients', false);
    }
}
