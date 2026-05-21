<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Tiny helper that reshapes a Laravel paginator into the DRF response
 * envelope the candidate tests assert against:
 *
 *   { "count": ..., "next": ..., "previous": ..., "results": [...] }
 */
final class DrfPagination
{
	/**
	 * @template T
	 *
	 * @param  LengthAwarePaginator<T>  $paginator
	 * @param  callable(T): array<string, mixed>|null  $transform
	 * @return array<string, mixed>
	 */
	public static function shape(LengthAwarePaginator $paginator, ?callable $transform = null): array
	{
		$items = $transform
			? $paginator->getCollection()->map($transform)->values()->all()
			: $paginator->getCollection()->values()->all();

		return [
			'count' => $paginator->total(),
			'next' => $paginator->nextPageUrl(),
			'previous' => $paginator->previousPageUrl(),
			'results' => $items,
		];
	}
}
