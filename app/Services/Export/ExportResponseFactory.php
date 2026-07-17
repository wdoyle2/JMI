<?php

namespace App\Services\Export;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use League\Csv\Writer;
use Spatie\LaravelData\Data;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds downloadable JSON / CSV responses from Spatie Data export rows.
 */
class ExportResponseFactory
{
	/**
	 * @param  Collection<int, Data>  $rows
	 * @param  string  $basename  Filename without extension (e.g. `anemometers`)
	 * @param  array<int, string>  $csvHeaders
	 * @param  callable(Data): array<int, scalar|null>  $csvRowMapper  values in header order
	 */
	public function make(
		string $format,
		Collection $rows,
		string $basename,
		array $csvHeaders,
		callable $csvRowMapper,
	): JsonResponse|StreamedResponse {
		$filename = $basename . '.' . $format;

		return $format === 'csv'
			? $this->csv($rows, $filename, $csvHeaders, $csvRowMapper)
			: $this->json($rows, $filename);
	}

	/**
	 * @param  Collection<int, Data>  $rows
	 */
	protected function json(Collection $rows, string $filename): JsonResponse
	{
		return response()
			->json($rows->map(fn (Data $row) => $row->toArray())->values()->all())
			->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
	}

	/**
	 * @param  Collection<int, Data>  $rows
	 * @param  array<int, string>  $csvHeaders
	 * @param  callable(Data): array<int, scalar|null>  $csvRowMapper
	 */
	protected function csv(
		Collection $rows,
		string $filename,
		array $csvHeaders,
		callable $csvRowMapper,
	): StreamedResponse {
		return response()->streamDownload(function () use ($rows, $csvHeaders, $csvRowMapper): void {
			// league/csv requires a seekable stream; php://output is not.
			$csv = Writer::createFromFileObject(new SplTempFileObject());
			$csv->insertOne($csvHeaders);

			foreach ($rows as $row) {
				$csv->insertOne($csvRowMapper($row));
			}

			$csv->output();
		}, $filename, [
			'Content-Type' => 'text/csv; charset=UTF-8',
		]);
	}
}
