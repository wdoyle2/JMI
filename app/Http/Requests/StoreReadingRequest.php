<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create-reading payload — Django WriteReadingMinimalSerializer accepts
 * the relation under the key `anemometer` and tags as a list of names.
 *
 * We keep the public input key `anemometer` (for DRF parity) and map it
 * to `anemometer_id` in `prepareForValidation` so the controller can
 * mass-assign from `validated()`.
 */
class StoreReadingRequest extends FormRequest
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
			'speed' => ['required', 'numeric'],
			'recorded_at' => ['required', 'date'],
			'anemometer' => ['required', 'uuid', 'exists:anemometers,id'],
			'tags' => ['sometimes', 'array'],
			'tags.*' => ['string'],
		];
	}

	/**
	 * Map the public DRF-style input key `anemometer` onto the
	 * `anemometer_id` FK that Eloquent mass-assigns from `validated()`.
	 *
	 * @return array<string, mixed>
	 */
	public function validated($key = null, $default = null)
	{
		$data = parent::validated($key, $default);

		if (is_array($data) && array_key_exists('anemometer', $data)) {
			$data['anemometer_id'] = $data['anemometer'];
			unset($data['anemometer']);
		}

		return $data;
	}
}
