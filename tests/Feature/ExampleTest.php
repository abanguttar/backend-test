<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function response(): void
    {
        $response = $this->get('/api/index');

        $response->assertStatus(200);
    }
}
