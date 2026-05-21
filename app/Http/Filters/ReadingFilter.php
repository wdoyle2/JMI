<?php

namespace App\Http\Filters;

use App\Models\Reading;
use Illuminate\Database\Eloquent\Builder;

/**
 * Laravel port of wind_for_life/apps/anemometers/filters.py (ReadingFilterSet).
 *
 * Supports two query params:
 *  - tags_any   — comma-separated; reading has ANY of the listed tags (OR, distinct).
 *  - tags_exact — comma-separated; reading has EXACTLY this set of tags (AND + equality).
 *
 * The `tags_exact` implementation mirrors the two-step Django approach:
 *   1. SQL-prune to readings whose distinct tag count equals the requested count
 *      AND whose tag set is a subset of the requested names (tag-count + whereIn).
 *   2. In-PHP filter comparing `set(tag.names) == set(requested)` to catch
 *      readings that share count but carry a different tag.
 */
class ReadingFilter
{
	/**
	 * Apply tag-based filters in place on the given builder.
	 *
	 * @param  array<string, mixed>  $filters
	 */
	public static function apply(Builder $query, array $filters): Builder
	{
		if (! empty($filters['tags_any'])) {
			$query = self::filterTagsAny($query, (string) $filters['tags_any']);
		}

		if (! empty($filters['tags_exact'])) {
			$query = self::filterTagsExact($query, (string) $filters['tags_exact']);
		}

		return $query;
	}

	/**
	 * OR semantics — any of the given tag names matches.
	 */
	protected static function filterTagsAny(Builder $query, string $value): Builder
	{
		$tags = self::parseTagList($value);

		if (count($tags) === 0) {
			return $query;
		}

		return $query
			->whereHas('tags', function (Builder $q) use ($tags): void {
				$q->whereIn('name', $tags);
			})
			->distinct();
	}

	/**
	 * AND-with-set-equality semantics.
	 *
	 * Mirrors the Django two-step approach: SQL narrows the candidate set,
	 * then a pure-PHP pass enforces exact set equality. Implemented without
	 * `HAVING` so behaviour is identical across MySQL in tests and production.
	 */
	protected static function filterTagsExact(Builder $query, string $value): Builder
	{
		$tags = self::parseTagList($value);
		$tagCount = count($tags);

		if ($tagCount === 0) {
			return $query->whereRaw('1 = 0');
		}

		// Step 1: SQL-level prune — readings carrying at least one of the
		// requested tags. The count + set-equality check happens in PHP.
		$candidates = $query
			->whereHas('tags', function (Builder $q) use ($tags): void {
				$q->whereIn('name', $tags);
			})
			->distinct()
			->with('tags')
			->get();

		// Step 2: in-PHP exact set equality.
		$expected = collect($tags)->sort()->values()->all();
		$matchingIds = $candidates
			->filter(function ($reading) use ($expected): bool {
				$names = $reading->tags->pluck('name')->sort()->values()->all();

				return $names === $expected;
			})
			->pluck('id')
			->all();

		return Reading::query()->whereIn('id', $matchingIds);
	}

	/**
	 * Split a comma-separated list, trim and drop empties.
	 *
	 * @return array<int, string>
	 */
	protected static function parseTagList(string $value): array
	{
		return collect(explode(',', $value))
			->map(fn ($t) => trim((string) $t))
			->filter(fn ($t) => $t !== '')
			->values()
			->all();
	}
}
