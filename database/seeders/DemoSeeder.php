<?php

namespace Database\Seeders;

use App\Models\Anemometer;
use Database\Factories\AnemometerFactory;
use Illuminate\Database\Seeder;

/**
 * Optional demo dataset — 50 anemometers, each with 100 readings,
 * each reading with 2 random tags drawn from TAG_CHOICES.
 *
 * Idempotent-safe: skips population when any anemometers already exist,
 * so running `db:seed --class=DemoSeeder` twice does not duplicate data.
 */
class DemoSeeder extends Seeder
{
	public function run(): void
	{
		if (Anemometer::query()->exists()) {
			$this->command?->info('DemoSeeder: anemometers already present, skipping.');

			return;
		}

		AnemometerFactory::new()
			->withReadings(100, 2)
			->count(50)
			->create();
	}
}
