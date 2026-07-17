<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Unauthenticated;

/**
 * Laravel analog of DRF's obtain_auth_token endpoint.
 *
 * POST /api/auth-token  { "username": "...", "password": "..." }
 *   → 200 { "token": "<plain-text-sanctum-token>" }
 *
 * Returns the Sanctum personal access token plaintext exactly once, the
 * same ergonomic contract DRF's obtain_auth_token provides.
 */
#[Group('Authentication')]
#[Unauthenticated]
class AuthTokenController extends Controller
{
	public function __invoke(Request $request): JsonResponse
	{
		$credentials = $request->validate([
			'username' => ['required', 'string'],
			'password' => ['required', 'string'],
		]);

		/** @var User|null $user */
		$user = User::query()->where('username', $credentials['username'])->first();

		if (! $user || ! Hash::check($credentials['password'], $user->password)) {
			throw ValidationException::withMessages([
				'username' => ['Invalid credentials.'],
			]);
		}

		$token = $user->createToken('api-token')->plainTextToken;

		return response()->json(['token' => $token]);
	}
}
