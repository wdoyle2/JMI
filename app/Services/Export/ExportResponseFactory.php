<?php

namespace App\Services\Export;

use League\Csv\EscapeFormula;
use League\Csv\Writer;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds downloadable JSON / CSV responses from Spatie Data export rows.
 */
class ExportResponseFactory
{
	/**
	 * @param  iterable<int, Data>  $rows
	 * @param  string  $basename  Filename without extension (e.g. `anemometers`)
	 * @param  array<int, string>  $csvHeaders
	 * @param  callable(Data): array<int, scalar|null>  $csvRowMapper  values in header order
	 */
	public function make(
		string $format,
		iterable $rows,
		string $basename,
		array $csvHeaders,
		callable $csvRowMapper,
	): StreamedResponse {
		$filename = $basename . '.' . $format;

		return $format === 'csv'
			? $this->csv($rows, $filename, $csvHeaders, $csvRowMapper)
			: $this->json($rows, $filename);
	}

	/**
	 * Stream a valid JSON array without materialising the full export.
	 *
	 * @param  iterable<int, Data>  $rows
	 */
	protected function json(iterable $rows, string $filename): StreamedResponse
	{
		return response()->streamDownload(function () use ($rows): void {
			echo '[';
			$first = true;

			foreach ($rows as $row) {
				if (! $first) {
					echo ',';
				}

				echo json_encode(
					$row->toArray(),
					JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
				);
				$first = false;
			}

			echo ']';
		}, $filename, [
			'Content-Type' => 'application/json; charset=UTF-8',
		]);
	}

	/**
	 * @param  iterable<int, Data>  $rows
	 * @param  array<int, string>  $csvHeaders
	 * @param  callable(Data): array<int, scalar|null>  $csvRowMapper
	 */
	protected function csv(
		iterable $rows,
		string $filename,
		array $csvHeaders,
		callable $csvRowMapper,
	): StreamedResponse {
		return response()->streamDownload(function () use ($rows, $csvHeaders, $csvRowMapper): void {
			$stream = fopen('php://output', 'w');
			if ($stream === false) {
				throw new \RuntimeException('Unable to open the response output stream.');
			}

			$csv = Writer::from($stream);
			$csv->addFormatter((new EscapeFormula())->escapeRecord(...));
			$csv->insertOne($csvHeaders);

			try {
				foreach ($rows as $row) {
					$csv->insertOne($csvRowMapper($row));
				}
			} finally {
				fclose($stream);
			}
		}, $filename, [
			'Content-Type' => 'text/csv; charset=UTF-8',
		]);
	}
}
