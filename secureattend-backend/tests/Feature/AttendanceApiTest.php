<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');
    }

    public function test_employee_can_check_in_inside_the_work_geofence(): void
    {
        $token = $this->demoToken();
        $response = $this->withToken($token)->postJson('/api/attendance/check-in', $this->validPayload());

        $response->assertOk()
            ->assertJsonPath('attendance.status', 'present')
            ->assertJsonPath('attendance.distance_meters', 0);

        $this->assertDatabaseCount('attendances', 1);
        Storage::disk('public')->assertExists($response->json('attendance.check_in_photo_path'));

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('my_attendance.status', 'present')
            ->assertJsonPath('monthly.values.5', 1);
    }

    public function test_check_in_is_rejected_outside_the_work_geofence(): void
    {
        $payload = $this->validPayload();
        $payload['latitude'] = -6.21;

        $this->withToken($this->demoToken())
            ->postJson('/api/attendance/check-in', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('inside_radius', false);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_check_in_is_rejected_for_mock_or_stale_location(): void
    {
        $token = $this->demoToken();

        $this->withToken($token)
            ->postJson('/api/attendance/check-in', $this->validPayload([
                'mock_location_detected' => true,
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('mock_location_detected', true);

        $this->withToken($token)
            ->postJson('/api/attendance/check-in', $this->validPayload([
                'location_age_ms' => 60001,
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('stale_location', true);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_employee_can_check_out_after_checking_in(): void
    {
        $token = $this->demoToken();

        $this->withToken($token)
            ->postJson('/api/attendance/check-in', $this->validPayload())
            ->assertOk();

        $response = $this->withToken($token)
            ->postJson('/api/attendance/check-out', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('attendance.status', 'present')
            ->assertJsonStructure(['attendance' => ['check_out_at']]);

        $this->assertNotNull($response->json('attendance.check_out_at'));
        $this->assertDatabaseCount('attendances', 1);
    }

    private function demoToken(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'rina@secureattend.test',
            'password' => 'password123',
        ]);

        $response->assertOk();

        return $response->json('token');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 10,
            'photo' => base64_encode('secureattend-test-photo'),
        ], $overrides);
    }
}
