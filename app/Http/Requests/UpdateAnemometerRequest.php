<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnemometerRequest extends FormRequest
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
			'name' => ['sometimes', 'required', 'string', 'max:100'],
			'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
			'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
		];
	}
}
