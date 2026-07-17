<?php

namespace App\Data;

use App\Models\Reading;
use Spatie\LaravelData\Data;

class ReadingExportData extends Data
{
	/**
	 * @param  array<int, string>  $tags
	 */
	public function __construct(
		public string $id,
		public float $speed,
		public ?string $recorded_at,
		public array $tags,
		public string $anemometer_id,
		public ?string $anemometer_name,
	) {
	}

	public static function fromModel(Reading $reading): self
	{
		$tags = $reading->relationLoaded('tags')
			? $reading->tags->pluck('name')->values()->all()
			: $reading->tags()->pluck('name')->values()->all();

		return new self(
			id: (string) $reading->id,
			speed: (float) $reading->speed,
			recorded_at: optional($reading->recorded_at)?->toJSON(),
			tags: $tags,
			anemometer_id: (string) $reading->anemometer_id,
			anemometer_name: $reading->relationLoaded('anemometer')
				? optional($reading->anemometer)->name
				: null,
		);
	}

	/**
	 * Flat associative row for CSV writers (tags joined for a single cell).
	 *
	 * @return array<string, string>
	 */
	public function toCsvRow(): array
	{
		return [
			'id' => $this->id,
			'speed' => (string) $this->speed,
			'recorded_at' => (string) ($this->recorded_at ?? ''),
			'tags' => implode(',', $this->tags),
			'anemometer_id' => $this->anemometer_id,
			'anemometer_name' => (string) ($this->anemometer_name ?? ''),
		];
	}
}
