<?php

namespace Tests\Feature\Api;

use App\Models\Anemometer;
use App\Models\Reading;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnemometerExportTest extends TestCase
{
	public function test_rejects_unauthenticated_export(): void
	{
		$response = $this->getJson('/api/anemometers/export');

		$response->assertUnauthorized();
	}

	public function test_exports_anemometers_as_json_with_average_speeds(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create([
			'name' => 'North Tower',
			'longitude' => '1.234567',
			'latitude' => '52.123456',
		]);

		$recent = Reading::factory()->for($anemometer)->create(['speed' => 10.0]);
		$recent->forceFill(['recorded_at' => Carbon::now()->subHours(2)])->saveQuietly();

		$older = Reading::factory()->for($anemometer)->create(['speed' => 20.0]);
		$older->forceFill(['recorded_at' => Carbon::now()->subDays(3)])->saveQuietly();

		$response = $this->getJson('/api/anemometers/export?format=json');

		$response->assertOk();
		$response->assertHeader('content-disposition', 'attachment; filename="anemometers.json"');

		$row = collect($response->json())->firstWhere('id', $anemometer->id);
		$this->assertNotNull($row);
		$this->assertSame('North Tower', $row['name']);
		$this->assertSame('1.234567', $row['longitude']);
		$this->assertSame('52.123456', $row['latitude']);
		$this->assertEqualsWithDelta(10.0, $row['average_daily_speed'], 0.0001);
		$this->assertEqualsWithDelta(15.0, $row['average_weekly_speed'], 0.0001);
	}

	public function test_exports_anemometers_as_csv(): void
	{
		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create(['name' => 'CSV Station']);

		$response = $this->get('/api/anemometers/export?format=csv');

		$response->assertOk();
		$response->assertHeader('content-type', 'text/csv; charset=UTF-8');
		$response->assertHeader('content-disposition', 'attachment; filename=anemometers.csv');
		$content = $response->streamedContent();
		$this->assertStringContainsString(
			'id,name,longitude,latitude,average_daily_speed,average_weekly_speed',
			$content,
		);
		$this->assertStringContainsString('CSV Station', $content);
		$this->assertStringContainsString($anemometer->id, $content);
	}

	public function test_filters_anemometers_by_partial_name(): void
	{
		$this->actingAsUser();
		$match = Anemometer::factory()->create(['name' => 'Alpha Wind']);
		Anemometer::factory()->create(['name' => 'Beta Wind']);

		$response = $this->getJson('/api/anemometers/export?format=json&filter[name]=Alpha');

		$response->assertOk();
		$ids = collect($response->json())->pluck('id')->all();
		$this->assertContains($match->id, $ids);
		$this->assertCount(1, $ids);
	}

	public function test_rejects_invalid_format(): void
	{
		$this->actingAsUser();

		$response = $this->getJson('/api/anemometers/export?format=xml');

		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['format']);
	}

	public function test_defaults_format_to_json(): void
	{
		$this->actingAsUser();
		Anemometer::factory()->create();

		$response = $this->getJson('/api/anemometers/export');

		$response->assertOk();
		$this->assertIsArray($response->json());
	}
}
