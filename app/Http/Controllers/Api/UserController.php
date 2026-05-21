<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\DrfPagination;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Port of wind_for_life/apps/users/api/views.py::UserViewSet.
 *
 * Django filters the queryset to the authenticated user only — we honour
 * that here so /api/users returns at most one row (the requester).
 * Lookup field is `username`.
 */
class UserController extends Controller
{
	/**
	 * GET /api/users — the auth'd user only, still paginated for parity.
	 *
	 * @return array<string, mixed>
	 */
	public function index(Request $request): array
	{
		$authUser = $request->user();
		$users = User::query()
			->when($authUser, fn ($q) => $q->whereKey($authUser->getKey()))
			->unless($authUser, fn ($q) => $q->whereRaw('1 = 0'))
			->paginate();

		return DrfPagination::shape(
			$users,
			fn (User $u) => (new UserResource($u))->resolve(),
		);
	}

	/**
	 * GET /api/users/{username}.
	 */
	public function show(string $username): UserResource
	{
		$user = User::query()->where('username', $username)->firstOrFail();

		return new UserResource($user);
	}

	/**
	 * PATCH /api/users/{username}.
	 */
	public function update(UpdateUserRequest $request, string $username): UserResource
	{
		$user = User::query()->where('username', $username)->firstOrFail();
		$user->update($request->validated());

		return new UserResource($user);
	}

	/**
	 * GET /api/users/me — the authenticated user.
	 */
	public function me(Request $request): UserResource
	{
		return new UserResource($request->user());
	}
}
