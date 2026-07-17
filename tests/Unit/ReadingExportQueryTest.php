<?php

namespace Tests\Unit;

use App\Models\Anemometer;
use App\Models\Reading;
use App\Services\Export\ReadingExportQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Direct unit tests for readings-export query assembly
 * (anemometer + date-range filters).
 */
class ReadingExportQueryTest extends TestCase
{
	public function test_filters_by_anemometer_ids(): void
	{
		$a = Anemometer::factory()->create();
		$b = Anemometer::factory()->create();
		$keep = Reading::factory()->for($a)->create();
		Reading::factory()->for($b)->create();

		$request = Request::create('/api/readings/export', 'GET', [
			'filter' => ['anemometer' => $a->id],
		]);

		$ids = app(ReadingExportQuery::class)
			->build($request, [$a->id])
			->get()
			->pluck('id')
			->all();

		$this->assertSame([$keep->id], $ids);
	}

	public function test_filters_by_recorded_at_bounds(): void
	{
		$anemometer = Anemometer::factory()->create();

		$inside = Reading::factory()->for($anemometer)->create();
		$inside->forceFill(['recorded_at' => Carbon::parse('2025-03-15T10:00:00Z')])->saveQuietly();

		$outside = Reading::factory()->for($anemometer)->create();
		$outside->forceFill(['recorded_at' => Carbon::parse('2025-01-01T10:00:00Z')])->saveQuietly();

		$request = Request::create('/api/readings/export', 'GET', [
			'filter' => [
				'recorded_at_after' => '2025-03-01T00:00:00Z',
				'recorded_at_before' => '2025-03-31T23:59:59Z',
			],
		]);

		$ids = app(ReadingExportQuery::class)
			->build($request)
			->get()
			->pluck('id')
			->all();

		$this->assertContains($inside->id, $ids);
		$this->assertNotContains($outside->id, $ids);
	}
}
