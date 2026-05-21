<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

/**
 * POST /api/auth-token — token issuance endpoint.
 *
 * The Django app uses DRF's built-in TokenAuthentication obtain view;
 * our Laravel port exposes a Sanctum-backed equivalent at
 * /api/auth-token.
 */
class AuthTokenTest extends TestCase
{
	public function test_issues_a_token_for_valid_credentials(): void
	{
		$user = User::factory()->create([
			'username' => 'testuser',
			'password' => 's3cret!!',
		]);

		$response = $this->postJson('/api/auth-token', [
			'username' => $user->username,
			'password' => 's3cret!!',
		]);

		$response->assertOk();
		$this->assertIsString($response->json('token'));
		$this->assertNotEmpty($response->json('token'));
	}

	public function test_rejects_invalid_credentials(): void
	{
		User::factory()->create([
			'username' => 'testuser',
			'password' => 's3cret!!',
		]);

		$response = $this->postJson('/api/auth-token', [
			'username' => 'testuser',
			'password' => 'wrong',
		]);

		// Accept either 401 (unauthorized) or 422 (validation) — both are
		// valid "you can't have a token" signals. Django would return 400
		// with a non-field error.
		$this->assertContains($response->status(), [401, 422]);
	}
}
