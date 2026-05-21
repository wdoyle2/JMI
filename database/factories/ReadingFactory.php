<?php

namespace Database\Factories;

use App\Models\Anemometer;
use App\Models\Reading;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reading>
 */
class ReadingFactory extends Factory
{
	protected $model = Reading::class;

	/**
	 * Default state — parity with
	 * anemometers/tests/factories.py::ReadingFactory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return [
			'speed' => abs($this->faker->randomFloat(3, 0, 999)),
			'recorded_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
			'anemometer_id' => Anemometer::factory(),
		];
	}

	/**
	 * Attach tags to a created reading.
	 *
	 * - int: sample that many names from AnemometerFactory::TAG_CHOICES.
	 * - array<string>: use exactly those names.
	 *
	 * Each name is resolved via firstOrCreate on the Tag model (so
	 * repeated factory calls don't generate duplicates) and attached via
	 * the polymorphic readings.tags() relation.
	 *
	 * @param  int|array<int, string>  $tags
	 */
	public function withTags(int|array $tags): static
	{
		return $this->afterCreating(function (Reading $reading) use ($tags): void {
			if (is_int($tags)) {
				$choices = AnemometerFactory::TAG_CHOICES;
				if ($tags < 0 || $tags > count($choices)) {
					throw new \InvalidArgumentException(
						'Number of tags must be between 0 and ' . count($choices)
					);
				}
				if ($tags === 0) {
					return;
				}
				$keys = array_rand($choices, $tags);
				$names = is_array($keys)
					? array_map(fn ($k) => $choices[$k], $keys)
					: [$choices[$keys]];
			} else {
				$names = $tags;
			}

			$tagIds = [];
			foreach ($names as $name) {
				$tag = Tag::firstOrCreate(
					['name' => $name],
					['slug' => Str::slug($name)],
				);
				$tagIds[] = $tag->id;
			}

			if ($tagIds !== []) {
				$reading->tags()->syncWithoutDetaching($tagIds);
			}
		});
	}
}
