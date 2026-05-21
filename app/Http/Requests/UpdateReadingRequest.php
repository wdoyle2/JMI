<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	protected function prepareForValidation(): void
	{
		if ($this->has('anemometer') && ! $this->has('anemometer_id')) {
			$this->merge([
				'anemometer_id' => $this->input('anemometer'),
			]);
		}
	}

	/**
	 * @return array<string, array<int, string>|string>
	 */
	public function rules(): array
	{
		return [
			'speed' => ['sometimes', 'required', 'numeric'],
			'recorded_at' => ['sometimes', 'required', 'date'],
			'anemometer_id' => ['sometimes', 'required', 'uuid', 'exists:anemometers,id'],
			'tags' => ['sometimes', 'array'],
			'tags.*' => ['string'],
		];
	}
}
