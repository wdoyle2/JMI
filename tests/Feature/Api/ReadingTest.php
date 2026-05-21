<?php

namespace Tests\Feature\Api;

use App\Models\Anemometer;
use App\Models\Reading;
use Tests\TestCase;

/**
 * Port of the reading viewset tests from
 * wind_for_life/apps/anemometers/tests/test_anemometers.py.
 *
 * Divergences:
 *   - DRF validation errors are 400; Laravel FormRequest validation
 *     returns 422. Assertions updated accordingly.
 *   - Unauthenticated requests return 401 (Sanctum) rather than 403.
 */
class ReadingTest extends TestCase
{
	public function test_returns_paginated_reading_list(): void
	{
		$this->actingAsUser();
		$number = 5;
		Reading::factory()->count($number)->create();

		$response = $this->getJson('/api/readings');

		$response->assertOk();
		$this->assertGreaterThanOrEqual($number, count($response->json('results')));
	}

	public function test_creates_a_reading_with_tags_and_persists_it(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create();

		$payload = [
			'speed' => 9.8,
			'recorded_at' => '2025-06-21T08:00:00Z',
			'anemometer' => $anemometer->id,
			'tags' => ['gusty', 'steady'],
		];

		$response = $this->postJson('/api/readings', $payload);

		$response->assertCreated();
		$this->assertEquals(
			['gusty', 'steady'],
			collect($response->json('tags'))->sort()->values()->all(),
		);
		$this->assertTrue(Reading::where('anemometer_id', $anemometer->id)->exists());
	}

	public function test_patches_reading_speed(): void
	{
		$this->actingAsUser();
		$reading = Reading::factory()->create(['speed' => 5.0]);
		$newSpeed = 8.9;

		$response = $this->patchJson("/api/readings/{$reading->id}", ['speed' => $newSpeed]);

		$response->assertOk();
		$this->assertEqualsWithDelta($newSpeed, (float) $response->json('speed'), 0.0001);
	}

	public function test_rejects_reading_creation_with_missing_fields(): void
	{
		$this->actingAsUser();

		$response = $this->postJson('/api/readings', ['speed' => 10.5]);

		// Django/DRF: 400. Laravel FormRequest: 422.
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['anemometer']);
	}

	public function test_rejects_reading_creation_with_invalid_speed(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create();

		$response = $this->postJson('/api/readings', [
			'speed' => 'invalid',
			'recorded_at' => '2025-06-21T10:00:00Z',
			'anemometer' => $anemometer->id,
		]);

		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['speed']);
	}

	public function test_rejects_unauthenticated_reading_creation(): void
	{
		// Django returns 403; Sanctum returns 401.
		$anemometer = Anemometer::factory()->create();

		$response = $this->postJson('/api/readings', [
			'speed' => 12.0,
			'recorded_at' => '2025-06-21T10:00:00Z',
			'anemometer' => $anemometer->id,
			'tags' => ['gusty'],
		]);

		$response->assertStatus(401);
	}

	public function test_rejects_reading_creation_for_invalid_anemometer_uuid(): void
	{
		$this->actingAsUser();

		$response = $this->postJson('/api/readings', [
			'speed' => 15.0,
			'recorded_at' => '2025-06-21T12:00:00Z',
			'anemometer' => 'invalid-uuid',
		]);

		// Django/DRF: 400. Laravel FormRequest: 422.
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['anemometer']);
	}
}
