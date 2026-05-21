<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Anemometer — mirrors wind_for_life.apps.anemometers.models.Anemometer.
 *
 * Latitude / longitude range validation belongs to the FormRequest
 * layer (Team C). The schema only enforces decimal(9,6) precision.
 */
class Anemometer extends BaseUuidModel
{
	use HasFactory;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'name',
		'longitude',
		'latitude',
	];

	/**
	 * Attribute casts.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'longitude' => 'decimal:6',
			'latitude' => 'decimal:6',
		];
	}

	/**
	 * An anemometer has many readings (cascade on delete at the DB level).
	 */
	public function readings(): HasMany
	{
		return $this->hasMany(Reading::class);
	}

	/**
	 * Computed slug — not persisted, matching Django's @property slug.
	 */
	protected function slug(): Attribute
	{
		return Attribute::make(
			get: fn () => Str::slug((string) $this->name),
		);
	}
}
