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

		$anemometerFilter = $this->input('filter.anemometer');
		if (is_string($anemometerFilter)) {
			$filter = (array) $this->input('filter', []);
			$filter['anemometer'] = $this->parseAnemometerIds($anemometerFilter);

			$this->merge(['filter' => $filter]);
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
			'filter.anemometer' => ['sometimes', 'array', 'min:1'],
			'filter.anemometer.*' => [
				'required',
				'uuid',
				'distinct',
				'exists:anemometers,id',
			],
			'filter.recorded_at_after' => ['sometimes', 'date'],
			'filter.recorded_at_before' => ['sometimes', 'date'],
			'tags_any' => ['sometimes', 'string'],
			'tags_exact' => ['sometimes', 'string'],
		];
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
		return $this->input('filter.anemometer', []);
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
