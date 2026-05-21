<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Default seeder invoked by `php artisan db:seed` —
 * runs the initial admin user seed. DemoSeeder is optional and must be
 * called explicitly (candidates populate demo data via artisan command).
 */
class DatabaseSeeder extends Seeder
{
	public function run(): void
	{
		$this->call([
			InitialUserSeeder::class,
		]);
	}
}
