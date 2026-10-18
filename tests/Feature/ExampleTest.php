<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * F-01: Rute dashboard wajib login, akses tanpa kredensial ditolak.
     */
    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/');

        $response->assertStatus(401);
    }
}
