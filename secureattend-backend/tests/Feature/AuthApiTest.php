<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_user_can_login_with_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'rina@secureattend.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'rina@secureattend.test')
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'rina@secureattend.test',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/dashboard')
            ->assertStatus(200)
            ->assertJsonStructure(['stats', 'recent', 'locations', 'monthly', 'my_attendance']);
    }
}
