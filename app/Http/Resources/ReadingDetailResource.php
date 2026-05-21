<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors Django ReadingDetailSerializer:
 * fields = ["id", "speed", "recorded_at", "tags", "anemometer"]
 * anemometer is the minimal embedded object.
 */
class ReadingDetailResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'speed' => $this->speed,
			'recorded_at' => optional($this->recorded_at)->toJSON(),
			'tags' => $this->whenLoaded(
				'tags',
				fn () => $this->tags->pluck('name')->values()->all(),
				fn () => $this->tags()->pluck('name')->values()->all(),
			),
			'anemometer' => new AnemometerResource(
				$this->whenLoaded('anemometer', fn () => $this->anemometer, fn () => $this->anemometer),
			),
		];
	}
}
