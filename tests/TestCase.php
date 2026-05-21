<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
	use CreatesApplication;
	use DatabaseTransactions;

	/**
	 * Authenticate the test client as a (new or provided) user via Sanctum,
	 * mirroring Django's APIClient.force_authenticate.
	 */
	protected function actingAsUser(?User $user = null): User
	{
		$user ??= User::factory()->create();
		Sanctum::actingAs($user);

		return $user;
	}

	/**
	 * Canonical "looks valid but definitely not in the DB" UUID used by the
	 * anemometer/reading 404 tests (mirrors the Django literal).
	 */
	protected function uuidInvalid(): string
	{
		return 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
	}
}
