<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors Django AnemometerMinimalSerializer:
 * fields = ["id", "name", "longitude", "latitude"]
 */
class AnemometerResource extends JsonResource
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
		];
	}
}
