<?php

namespace Database\Factories;

use App\Models\Anemometer;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Anemometer>
 */
class AnemometerFactory extends Factory
{
	protected $model = Anemometer::class;

	/**
	 * Verbatim port of the 25 tag names from
	 * wind_for_life/apps/anemometers/tests/factories.py::TAG_CHOICES.
	 *
	 * @var array<int, string>
	 */
	public const TAG_CHOICES = [
		'gusty',
		'breezy',
		'calm',
		'blustery',
		'strong',
		'light',
		'turbulent',
		'whistling',
		'howling',
		'steady',
		'variable',
		'chilly',
		'dry',
		'moist',
		'gentle',
		'fierce',
		'biting',
		'persistent',
		'brisk',
		'stormy',
		'drafty',
		'swirling',
		'squally',
		'whipping',
		'mild',
	];

	/**
	 * Auto-incremented sequence used to mirror factory_boy's
	 * Sequence(lambda n: f"Anemometer{n}").
	 */
	protected static int $sequence = 0;

	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		$n = self::$sequence++;

		return [
			'name' => "Anemometer{$n}",
			'latitude' => round($this->faker->randomFloat(6, -90, 90), 6),
			'longitude' => round($this->faker->randomFloat(6, -180, 180), 6),
		];
	}

	/**
	 * Attach N readings (optionally with K random tags each) after create.
	 *
	 * Mirrors the @post_generation `readings` hook in
	 * anemometers/tests/factories.py::AnemometerFactory.
	 */
	public function withReadings(int $count, int $tagsPerReading = 0): static
	{
		if ($count < 0) {
			throw new \InvalidArgumentException('Cannot have a negative number of readings');
		}

		return $this->afterCreating(function (Anemometer $anemometer) use ($count, $tagsPerReading): void {
			$factory = Reading::factory()->count($count)->for($anemometer);
			if ($tagsPerReading > 0) {
				$factory = $factory->withTags($tagsPerReading);
			}
			$factory->create();
		});
	}
}
