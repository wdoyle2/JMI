<?php

namespace App\Http\Controllers\Api;

use App\Data\ReadingExportData;
use App\Http\Requests\ExportReadingsRequest;
use App\Models\Reading;
use App\Services\Export\ExportResponseFactory;
use App\Services\Export\ReadingExportQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GET /api/readings/export — readings export (JSON or CSV).
 *
 * @group Readings
 * @authenticated
 */
class ReadingExportController extends Controller
{
	public function __construct(
		protected ExportResponseFactory $exporter,
		protected ReadingExportQuery $exportQuery,
	) {
	}

	/**
	 * Export readings as JSON or CSV, optionally filtered.
	 *
	 * @queryParam format string required Format: `json` or `csv`. Example: json
	 * @queryParam filter[anemometer] string Comma-separated anemometer UUIDs. Example: 11111111-1111-1111-1111-111111111111
	 * @queryParam filter[recorded_at_after] string ISO-8601 lower bound (inclusive). Example: 2025-01-01T00:00:00Z
	 * @queryParam filter[recorded_at_before] string ISO-8601 upper bound (inclusive). Example: 2025-12-31T23:59:59Z
	 * @queryParam tags_any string Comma-separated tags (OR). Example: gusty,drafty
	 * @queryParam tags_exact string Comma-separated tags (exact set). Example: gusty,drafty
	 */
	public function __invoke(ExportReadingsRequest $request): JsonResponse|StreamedResponse
	{
		$readings = $this->exportQuery
			->build($request, $request->anemometerIds())
			->with(['tags', 'anemometer'])
			->get();

		$rows = $readings->map(
			fn (Reading $reading) => ReadingExportData::fromModel($reading),
		);

		return $this->exporter->make(
			format: $request->exportFormat(),
			rows: $rows,
			basename: 'readings',
			csvHeaders: ['id', 'speed', 'recorded_at', 'tags', 'anemometer_id', 'anemometer_name'],
			csvRowMapper: fn (ReadingExportData $row) => array_values($row->toCsvRow()),
		);
	}
}
