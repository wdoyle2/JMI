<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors Django UserSerializer: fields = ["username", "name", "url"].
 * The integer primary key is deliberately NOT exposed (parity with DRF).
 * `url` is the hyperlink to the user-detail route (by username).
 */
class UserResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'username' => $this->username,
			'name' => $this->name,
			'url' => url('/api/users/' . $this->username),
		];
	}
}
