<?php

namespace App\Data;

use App\Models\Anemometer;
use Spatie\LaravelData\Data;

class AnemometerExportData extends Data
{
	public function __construct(
		public string $id,
		public string $name,
		public string $longitude,
		public string $latitude,
		public ?float $average_daily_speed,
		public ?float $average_weekly_speed,
	) {
	}

	public static function fromModel(Anemometer $anemometer): self
	{
		$daily = $anemometer->average_daily_speed ?? null;
		$weekly = $anemometer->average_weekly_speed ?? null;

		return new self(
			id: (string) $anemometer->id,
			name: (string) $anemometer->name,
			longitude: (string) $anemometer->longitude,
			latitude: (string) $anemometer->latitude,
			average_daily_speed: $daily !== null ? (float) $daily : null,
			average_weekly_speed: $weekly !== null ? (float) $weekly : null,
		);
	}

	/**
	 * Flat associative row for CSV writers.
	 *
	 * @return array<string, string>
	 */
	public function toCsvRow(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'longitude' => $this->longitude,
			'latitude' => $this->latitude,
			'average_daily_speed' => $this->average_daily_speed === null
				? ''
				: (string) $this->average_daily_speed,
			'average_weekly_speed' => $this->average_weekly_speed === null
				? ''
				: (string) $this->average_weekly_speed,
		];
	}
}
