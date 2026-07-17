<?php

namespace App\Services\Export;

use App\Http\Filters\ReadingFilter;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Builds the filtered Eloquent query for readings export.
 * Kept separate from HTTP concerns so filter behaviour is unit-testable.
 */
class ReadingExportQuery
{
	/**
	 * @param  array<int, string>  $anemometerIds
	 */
	public function build(Request $request, array $anemometerIds = []): Builder
	{
		$query = QueryBuilder::for(Reading::class, $request)
			->allowedFilters([
				AllowedFilter::callback('anemometer', function (Builder $query) use ($anemometerIds): void {
					if ($anemometerIds === []) {
						return;
					}
					$query->whereIn('anemometer_id', $anemometerIds);
				}),
				AllowedFilter::callback('recorded_at_after', function (Builder $query, $value): void {
					$query->where('recorded_at', '>=', Carbon::parse($value));
				}),
				AllowedFilter::callback('recorded_at_before', function (Builder $query, $value): void {
					$query->where('recorded_at', '<=', Carbon::parse($value));
				}),
			])
			->getEloquentBuilder();

		return ReadingFilter::apply(
			$query,
			$request->only(['tags_any', 'tags_exact']),
		);
	}
}
