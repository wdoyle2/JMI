<?php

namespace Tests\Feature\Api;

use App\Models\Anemometer;
use App\Models\Reading;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingExportTest extends TestCase
{
	public function test_rejects_unauthenticated_export(): void
	{
		$response = $this->getJson('/api/readings/export');

		$response->assertUnauthorized();
	}

	public function test_exports_readings_as_json_with_anemometer_fields(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create(['name' => 'Harbor']);
		$reading = Reading::factory()->for($anemometer)->withTags(['gusty'])->create(['speed' => 12.5]);

		$response = $this->getJson('/api/readings/export?format=json');

		$response->assertOk();
		$response->assertHeader('content-disposition', 'attachment; filename="readings.json"');
		$response->assertJsonFragment([
			'id' => $reading->id,
			'speed' => 12.5,
			'anemometer_id' => $anemometer->id,
			'anemometer_name' => 'Harbor',
			'tags' => ['gusty'],
		]);
	}

	public function test_exports_readings_as_csv(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create(['name' => 'CSV Anemometer']);
		$reading = Reading::factory()->for($anemometer)->withTags(['calm'])->create(['speed' => 3.1]);

		$response = $this->get('/api/readings/export?format=csv');

		$response->assertOk();
		$response->assertHeader('content-type', 'text/csv; charset=UTF-8');
		$response->assertHeader('content-disposition', 'attachment; filename=readings.csv');
		$content = $response->streamedContent();
		$this->assertStringContainsString(
			'id,speed,recorded_at,tags,anemometer_id,anemometer_name',
			$content,
		);
		$this->assertStringContainsString($reading->id, $content);
		$this->assertStringContainsString('calm', $content);
		$this->assertStringContainsString('CSV Anemometer', $content);
	}

	public function test_filters_readings_by_anemometer(): void
	{
		$this->actingAsUser();
		$a = Anemometer::factory()->create();
		$b = Anemometer::factory()->create();
		$keep = Reading::factory()->for($a)->create();
		$drop = Reading::factory()->for($b)->create();

		$response = $this->getJson('/api/readings/export?format=json&filter[anemometer]=' . $a->id);

		$response->assertOk();
		$ids = collect($response->json())->pluck('id')->all();
		$this->assertContains($keep->id, $ids);
		$this->assertNotContains($drop->id, $ids);
	}

	public function test_filters_readings_by_multiple_anemometers(): void
	{
		$this->actingAsUser();
		$a = Anemometer::factory()->create();
		$b = Anemometer::factory()->create();
		$c = Anemometer::factory()->create();
		$keepA = Reading::factory()->for($a)->create();
		$keepB = Reading::factory()->for($b)->create();
		$drop = Reading::factory()->for($c)->create();

		$response = $this->getJson(
			'/api/readings/export?format=json&filter[anemometer]=' . $a->id . ',' . $b->id,
		);

		$response->assertOk();
		$ids = collect($response->json())->pluck('id')->all();
		$this->assertContains($keepA->id, $ids);
		$this->assertContains($keepB->id, $ids);
		$this->assertNotContains($drop->id, $ids);
	}

	public function test_filters_readings_by_date_range(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create();

		$inside = Reading::factory()->for($anemometer)->create();
		$inside->forceFill(['recorded_at' => Carbon::parse('2025-06-15T12:00:00Z')])->saveQuietly();

		$before = Reading::factory()->for($anemometer)->create();
		$before->forceFill(['recorded_at' => Carbon::parse('2025-05-01T12:00:00Z')])->saveQuietly();

		$after = Reading::factory()->for($anemometer)->create();
		$after->forceFill(['recorded_at' => Carbon::parse('2025-07-01T12:00:00Z')])->saveQuietly();

		$response = $this->getJson(
			'/api/readings/export?format=json'
			. '&filter[recorded_at_after]=2025-06-01T00:00:00Z'
			. '&filter[recorded_at_before]=2025-06-30T23:59:59Z',
		);

		$response->assertOk();
		$ids = collect($response->json())->pluck('id')->all();
		$this->assertContains($inside->id, $ids);
		$this->assertNotContains($before->id, $ids);
		$this->assertNotContains($after->id, $ids);
	}

	public function test_combines_anemometer_and_tag_filters(): void
	{
		$this->actingAsUser();
		$a = Anemometer::factory()->create();
		$b = Anemometer::factory()->create();

		$match = Reading::factory()->for($a)->withTags(['gusty'])->create();
		Reading::factory()->for($a)->withTags(['calm'])->create();
		Reading::factory()->for($b)->withTags(['gusty'])->create();

		$response = $this->getJson(
			'/api/readings/export?format=json&filter[anemometer]=' . $a->id . '&tags_any=gusty',
		);

		$response->assertOk();
		$ids = collect($response->json())->pluck('id')->all();
		$this->assertSame([$match->id], $ids);
	}

	public function test_rejects_invalid_anemometer_uuid(): void
	{
		$this->actingAsUser();

		$response = $this->getJson('/api/readings/export?format=json&filter[anemometer]=not-a-uuid');

		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['filter.anemometer']);
	}

	public function test_rejects_invalid_format(): void
	{
		$this->actingAsUser();

		$response = $this->getJson('/api/readings/export?format=xml');

		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['format']);
	}
}
