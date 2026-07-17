<?php

namespace Tests\Unit;

use App\Data\AnemometerExportData;
use App\Services\Export\ExportResponseFactory;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExportResponseFactoryTest extends TestCase
{
	public function test_json_rows_are_consumed_only_when_the_response_is_streamed(): void
	{
		$consumed = false;
		$rows = (function () use (&$consumed): \Generator {
			$consumed = true;

			yield new AnemometerExportData(
				id: '11111111-1111-1111-1111-111111111111',
				name: 'North Tower',
				longitude: '1.000000',
				latitude: '52.000000',
				average_daily_speed: 10.0,
				average_weekly_speed: 12.0,
			);
		})();

		$response = app(ExportResponseFactory::class)->make(
			format: 'json',
			rows: $rows,
			basename: 'anemometers',
			csvHeaders: [],
			csvRowMapper: fn (AnemometerExportData $row) => array_values($row->toCsvRow()),
		);

		$this->assertFalse($consumed);

		$content = TestResponse::fromBaseResponse($response)->streamedContent();

		$this->assertTrue($consumed);
		$this->assertSame(
			[['id' => '11111111-1111-1111-1111-111111111111', 'name' => 'North Tower']],
			collect(json_decode($content, true, flags: JSON_THROW_ON_ERROR))
				->map(fn (array $row) => [
					'id' => $row['id'],
					'name' => $row['name'],
				])
				->all(),
		);
	}

	public function test_csv_stream_escapes_spreadsheet_formulas(): void
	{
		$rows = (function (): \Generator {
			yield new AnemometerExportData(
				id: '11111111-1111-1111-1111-111111111111',
				name: '=2+2',
				longitude: '1.000000',
				latitude: '52.000000',
				average_daily_speed: null,
				average_weekly_speed: null,
			);
		})();

		$response = app(ExportResponseFactory::class)->make(
			format: 'csv',
			rows: $rows,
			basename: 'anemometers',
			csvHeaders: ['id', 'name', 'longitude', 'latitude'],
			csvRowMapper: fn (AnemometerExportData $row) => array_values($row->toCsvRow()),
		);

		$content = TestResponse::fromBaseResponse($response)->streamedContent();

		$this->assertStringContainsString("'=2+2", $content);
	}
}
