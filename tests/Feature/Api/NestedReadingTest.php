<?php

namespace Tests\Feature\Api;

use App\Models\Anemometer;
use Tests\TestCase;

/**
 * Port of the nested-reading viewset tests
 * (api:anemometers-readings-list / -detail in Django).
 */
class NestedReadingTest extends TestCase
{
	public function test_lists_nested_readings_for_an_anemometer(): void
	{
		$this->actingAsUser();
		$number = 5;
		$anemometer = Anemometer::factory()->withReadings($number)->create();

		$response = $this->getJson("/api/anemometers/{$anemometer->id}/readings");

		$response->assertOk();
		$this->assertCount($number, $response->json('results'));
	}

	public function test_returns_a_nested_reading_detail_with_id(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->withReadings(5)->create();
		$reading = $anemometer->readings()->first();

		$response = $this->getJson("/api/anemometers/{$anemometer->id}/readings/{$reading->id}");

		$response->assertOk();
		$this->assertSame($reading->id, $response->json('id'));
	}

	public function test_returns_404_for_nonexistent_nested_reading(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->withReadings(5)->create();

		$response = $this->getJson(
			"/api/anemometers/{$anemometer->id}/readings/" . $this->uuidInvalid()
		);

		$response->assertNotFound();
	}
}
