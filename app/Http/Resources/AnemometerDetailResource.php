<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors Django AnemometerDetailSerializer:
 * fields = ["id", "name", "longitude", "latitude", "readings"]
 * where readings is the minimal form.
 */
class AnemometerDetailResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'longitude' => $this->longitude,
			'latitude' => $this->latitude,
			'readings' => ReadingResource::collection(
				$this->whenLoaded('readings', fn () => $this->readings, fn () => $this->readings),
			),
		];
	}
}
