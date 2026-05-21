<?php

namespace App\Console\Commands;

use App\Models\Anemometer;
use Illuminate\Console\Command;

/**
 * Port of wind_for_life.apps.anemometers.management.commands.create_test_anemometers.
 *
 * Django signature:
 *   manage.py create_test_anemometers <num_anemometers> [num_readings=0] [num_tags=0]
 *
 * Artisan equivalent:
 *   artisan w4l:create-anemometers <num_anemometers> [num_readings=0] [num_tags=0]
 *
 * Delegates to AnemometerFactory::withReadings() which mirrors the
 * factory_boy `readings` / `readings__num_tags` post-generation hook.
 */
class CreateAnemometersCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'w4l:create-anemometers {num_anemometers : Number of anemometers} {num_readings=0 : Readings per anemometer} {num_tags=0 : Tags per reading}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create N anemometers (optionally with readings and tags) for local/dev fixtures.';

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		$numAnemometers = (int) $this->argument('num_anemometers');
		$numReadings = (int) $this->argument('num_readings');
		$numTags = (int) $this->argument('num_tags');

		$this->info(sprintf(
			'Creating %d anemometer(s) with %d reading(s) and %d tag(s) each…',
			$numAnemometers,
			$numReadings,
			$numTags,
		));

		$bar = $this->output->createProgressBar($numAnemometers);
		$bar->start();

		Anemometer::factory()
			->withReadings($numReadings, $numTags)
			->count($numAnemometers)
			->create()
			->each(function () use ($bar): void {
				$bar->advance();
			});

		$bar->finish();
		$this->newLine();

		$this->info('Done.');

		return self::SUCCESS;
	}
}
