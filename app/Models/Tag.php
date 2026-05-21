<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * Tag — Laravel equivalent of django-taggit's W4LTag.
 *
 * The slug is auto-populated from name on create (Django taggit behaviour).
 */
class Tag extends BaseUuidModel
{
	use HasFactory;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'name',
		'slug',
	];

	/**
	 * Hook model lifecycle events.
	 */
	protected static function booted(): void
	{
		static::creating(function (Tag $tag): void {
			if (empty($tag->slug) && ! empty($tag->name)) {
				$tag->slug = Str::slug((string) $tag->name);
			}
		});
	}

	/**
	 * Inverse polymorphic relation — readings tagged with this tag.
	 * Mirrors Django's related_name="tagged_readings".
	 */
	public function readings(): MorphToMany
	{
		return $this->morphedByMany(Reading::class, 'taggable')->withTimestamps();
	}
}
