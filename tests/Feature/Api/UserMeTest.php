<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

/**
 * Port of wind_for_life/apps/users/tests/api/test_views.py
 * (the "user-me" test) and test_urls.py (the URL resolution assertions
 * — we verify route registration by hitting the URL directly).
 *
 * The original Django test inspects AbstractUser-specific fields
 * (username + URL hyperlink). Our Laravel user keeps `username` but
 * does not expose a hyperlinked URL — we just assert the current
 * user's identifying fields are returned.
 */
class UserMeTest extends TestCase
{
	public function test_returns_the_authenticated_user_for_users_me(): void
	{
		$user = User::factory()->create([
			'username' => 'testuser',
			'name' => 'Test User',
		]);
		$this->actingAsUser($user);

		$response = $this->getJson('/api/users/me');

		$response->assertOk();
		$this->assertSame('testuser', $response->json('username'));
		$this->assertSame('Test User', $response->json('name'));
	}

	public function test_rejects_unauthenticated_access_to_users_me(): void
	{
		$response = $this->getJson('/api/users/me');

		$response->assertStatus(401);
	}

	public function test_registers_the_user_list_route(): void
	{
		$this->actingAsUser();

		$response = $this->getJson('/api/users');

		$response->assertOk();
	}
}
