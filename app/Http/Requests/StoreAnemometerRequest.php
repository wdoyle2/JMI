<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnemometerRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	/**
	 * @return array<string, array<int, string>|string>
	 */
	public function rules(): array
	{
		return [
			'name' => ['required', 'string', 'max:100'],
			'longitude' => ['required', 'numeric', 'between:-180,180'],
			'latitude' => ['required', 'numeric', 'between:-90,90'],
		];
	}
}
