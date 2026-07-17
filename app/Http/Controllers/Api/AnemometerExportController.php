<?php

namespace App\Http\Controllers\Api;

use App\Data\AnemometerExportData;
use App\Http\Requests\ExportAnemometersRequest;
use App\Models\Anemometer;
use App\Services\Export\ExportResponseFactory;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GET /api/anemometers/export — catalog export (JSON or CSV).
 *
 * @group Anemometers
 * @authenticated
 */
class AnemometerExportController extends Controller
{
	public function __construct(
		protected ExportResponseFactory $exporter,
	) {
	}

	/**
	 * Export anemometers as JSON or CSV, including daily / weekly average speeds.
	 *
	 * @queryParam format string required Format: `json` or `csv`. Example: json
	 * @queryParam filter[name] string Partial name match. Example: North
	 */
	public function __invoke(ExportAnemometersRequest $request): StreamedResponse
	{
		$now = Carbon::now();
		$dayAgo = $now->copy()->subDay();
		$weekAgo = $now->copy()->subWeek();

		$base = Anemometer::query()
			->selectSub(
				fn ($q) => $q->from('readings')
					->selectRaw('AVG(speed)')
					->whereColumn('readings.anemometer_id', 'anemometers.id')
					->where('readings.recorded_at', '>=', $dayAgo),
				'average_daily_speed',
			)
			->selectSub(
				fn ($q) => $q->from('readings')
					->selectRaw('AVG(speed)')
					->whereColumn('readings.anemometer_id', 'anemometers.id')
					->where('readings.recorded_at', '>=', $weekAgo),
				'average_weekly_speed',
			)
			->addSelect('anemometers.*');

		$rows = QueryBuilder::for($base)
			->allowedFilters([
				AllowedFilter::partial('name'),
			])
			->orderBy('name')
			->lazy(500)
			->map(
				fn (Anemometer $anemometer) => AnemometerExportData::fromModel($anemometer),
			);

		return $this->exporter->make(
			format: $request->exportFormat(),
			rows: $rows,
			basename: 'anemometers',
			csvHeaders: [
				'id',
				'name',
				'longitude',
				'latitude',
				'average_daily_speed',
				'average_weekly_speed',
			],
			csvRowMapper: fn (AnemometerExportData $row) => array_values($row->toCsvRow()),
		);
	}
}
