<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Laravel equivalent of wind_for_life/fixtures/initial_user.json.
 *
 * Django's fixture stored an argon2-hashed password; Hash::make('admin')
 * is sufficient here (bcrypt/argon per hashing config). Idempotent via
 * updateOrCreate keyed on username.
 */
class InitialUserSeeder extends Seeder
{
	public function run(): void
	{
		User::updateOrCreate(
			['username' => 'admin'],
			[
				'email' => 'admin@example.com',
				'name' => '',
				'password' => Hash::make('admin'),
				'is_staff' => true,
				'is_superuser' => true,
				'email_verified_at' => now(),
			],
		);
	}
}
