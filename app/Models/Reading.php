<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Reading — mirrors wind_for_life.apps.anemometers.models.Reading.
 *
 * Django Meta: ordering = ["-recorded_at"] and an index on
 * (anemometer, recorded_at). The default order is applied via a global
 * scope so any Reading::query()->get() returns newest-first like Django.
 */
class Reading extends BaseUuidModel
{
	use HasFactory;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'speed',
		'recorded_at',
		'anemometer_id',
	];

	/**
	 * Attribute casts.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'speed' => 'float',
			'recorded_at' => 'datetime',
		];
	}

	/**
	 * Apply the default Django ordering (-recorded_at) via a global scope,
	 * and stamp `recorded_at` on create to mirror the Django model's
	 * `editable=False` / `auto_now_add=True` behaviour.
	 */
	protected static function booted(): void
	{
		static::addGlobalScope('orderByRecordedAtDesc', function (Builder $query): void {
			$query->orderBy('recorded_at', 'desc');
		});

		static::creating(function (Reading $reading): void {
			$reading->recorded_at = now();
		});
	}

	/**
	 * Local scope equivalent for explicit call sites.
	 */
	public function scopeOrderByRecorded(Builder $query, string $direction = 'desc'): Builder
	{
		return $query->orderBy('recorded_at', $direction);
	}

	/**
	 * Parent anemometer (CASCADE on delete at the DB level).
	 */
	public function anemometer(): BelongsTo
	{
		return $this->belongsTo(Anemometer::class);
	}

	/**
	 * Polymorphic tag relation through the `taggables` pivot table —
	 * Laravel equivalent of django-taggit's UUIDTaggedItem.
	 */
	public function tags(): MorphToMany
	{
		return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
	}
}
