<?php

namespace Tests\Feature\Api;

use App\Models\Anemometer;
use Tests\TestCase;

/**
 * Port of wind_for_life/apps/anemometers/tests/test_anemometers.py
 * (anemometer viewset section).
 *
 * Divergences from the Django original:
 *   - DRF returns 403 for unauthenticated requests. Laravel Sanctum
 *     returns 401 by default via AuthenticationException — we assert
 *     that instead. This is an intentional framework-level difference.
 */
class AnemometerTest extends TestCase
{
	public function test_returns_anemometer_detail_with_id(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->withReadings(5)->create();

		$response = $this->getJson("/api/anemometers/{$anemometer->id}");

		$response->assertOk();
		$this->assertSame($anemometer->id, $response->json('id'));
	}

	public function test_returns_paginated_recent_readings_with_aggregate_fields(): void
	{
		$this->actingAsUser();
		$numAnemometers = 5;
		Anemometer::factory()->count($numAnemometers)->create();

		$response = $this->getJson('/api/anemometers/recent-readings');

		$response->assertOk();
		$this->assertCount($numAnemometers, $response->json('results'));
		$first = $response->json('results.0');
		$this->assertArrayHasKey('recent_readings', $first);
		$this->assertArrayHasKey('average_daily_speed', $first);
		$this->assertArrayHasKey('average_weekly_speed', $first);
	}

	public function test_returns_404_when_anemometer_does_not_exist(): void
	{
		$this->actingAsUser();

		$response = $this->getJson('/api/anemometers/' . $this->uuidInvalid());

		$response->assertNotFound();
	}

	public function test_rejects_unauthenticated_anemometer_list_access(): void
	{
		// NOTE: Django/DRF returns 403 here. Laravel's Sanctum "auth:sanctum"
		// middleware throws AuthenticationException, which the exception
		// handler renders as 401 for JSON requests. This is the documented
		// framework-level difference.
		$response = $this->getJson('/api/anemometers');

		$response->assertStatus(401);
	}

	public function test_patches_anemometer_name(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create(['name' => 'Old Name']);

		$response = $this->patchJson(
			"/api/anemometers/{$anemometer->id}",
			['name' => 'Updated Name'],
		);

		$response->assertOk();
		$this->assertSame('Updated Name', $response->json('name'));
	}
}
