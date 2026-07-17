<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportReadingsRequest extends FormRequest
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
			'filter.anemometer' => ['sometimes', 'string'],
			'filter.recorded_at_after' => ['sometimes', 'date'],
			'filter.recorded_at_before' => ['sometimes', 'date'],
			'tags_any' => ['sometimes', 'string'],
			'tags_exact' => ['sometimes', 'string'],
		];
	}

	public function withValidator($validator): void
	{
		$validator->after(function ($validator): void {
			$raw = $this->input('filter.anemometer');
			if ($raw === null || $raw === '') {
				return;
			}

			foreach ($this->parseAnemometerIds((string) $raw) as $id) {
				if (! preg_match('/^[0-9a-fA-F-]{36}$/', $id)) {
					$validator->errors()->add(
						'filter.anemometer',
						'Each anemometer id must be a valid UUID.',
					);

					return;
				}
			}
		});
	}

	public function exportFormat(): string
	{
		return (string) $this->validated('format');
	}

	/**
	 * @return array<int, string>
	 */
	public function anemometerIds(): array
	{
		$raw = $this->input('filter.anemometer');

		if ($raw === null || $raw === '') {
			return [];
		}

		return $this->parseAnemometerIds((string) $raw);
	}

	/**
	 * @return array<int, string>
	 */
	protected function parseAnemometerIds(string $value): array
	{
		return collect(explode(',', $value))
			->map(fn ($id) => trim((string) $id))
			->filter(fn ($id) => $id !== '')
			->values()
			->all();
	}
}
