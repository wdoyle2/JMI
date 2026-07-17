<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportAnemometersRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	protected function prepareForValidation(): void
	{
		if (! $this->filled('format')) {
			$this->merge(['format' => 'json']);
		}
	}

	/**
	 * @return array<string, array<int, mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'format' => ['required', Rule::in(['json', 'csv'])],
			'filter' => ['sometimes', 'array'],
			'filter.name' => ['sometimes', 'string', 'max:100'],
		];
	}

	public function exportFormat(): string
	{
		return (string) $this->validated('format');
	}
}
