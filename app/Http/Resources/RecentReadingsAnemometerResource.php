<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors Django RecentReadingsAnemometerSerializer:
 * id, name, longitude, latitude, average_daily_speed (float),
 * average_weekly_speed (float), recent_readings (5 newest, minimal).
 *
 * The controller attaches `average_daily_speed`, `average_weekly_speed`
 * and `recent_readings` (a Collection) onto the model instance.
 */
class RecentReadingsAnemometerResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		$daily = $this->resource->average_daily_speed ?? null;
		$weekly = $this->resource->average_weekly_speed ?? null;

		return [
			'id' => $this->id,
			'name' => $this->name,
			'longitude' => $this->longitude,
			'latitude' => $this->latitude,
			'average_daily_speed' => $daily !== null ? (float) $daily : null,
			'average_weekly_speed' => $weekly !== null ? (float) $weekly : null,
			'recent_readings' => ReadingResource::collection(
				$this->resource->recent_readings ?? collect(),
			),
		];
	}
}
