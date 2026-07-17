<?php

namespace App\Http\Controllers\Api;

use App\Http\Filters\ReadingFilter;
use App\Http\Requests\StoreReadingRequest;
use App\Http\Requests\UpdateReadingRequest;
use App\Http\Resources\ReadingDetailResource;
use App\Http\Resources\ReadingResource;
use App\Http\Responses\DrfPagination;
use App\Models\Reading;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Port of wind_for_life/apps/anemometers/api/views.py::ReadingViewSet.
 *
 * Read/write serializer parity is achieved by using ReadingResource for
 * list+retrieve (read shape) while StoreReadingRequest / UpdateReadingRequest
 * accept the `anemometer` FK input (write shape: WriteReadingMinimalSerializer).
 *
 * Note: export lives on GET /api/readings/export (ReadingExportController).
 * The list endpoint still has no `anemometer=` filter (separate gap).
 */
class ReadingController extends Controller
{
	/**
	 * GET /api/readings — paginated, filterable by tags_any / tags_exact.
	 *
	 * @return array<string, mixed>
	 */
	public function index(Request $request): array
	{
		$query = Reading::query();

		$query = ReadingFilter::apply($query, $request->only(['tags_any', 'tags_exact']));

		$readings = $query->paginate();

		return DrfPagination::shape(
			$readings,
			fn (Reading $r) => (new ReadingResource($r))->resolve(),
		);
	}

	/**
	 * GET /api/readings/{id} — detail with embedded anemometer.
	 */
	public function show(string $id): ReadingDetailResource
	{
		$reading = Reading::with(['anemometer', 'tags'])->findOrFail($id);

		return new ReadingDetailResource($reading);
	}

	/**
	 * POST /api/readings — create (syncs tags by name).
	 */
	public function store(StoreReadingRequest $request): JsonResponse
	{
		$data = $request->validated();
		$tagNames = $data['tags'] ?? [];
		unset($data['tags']);

		$reading = DB::transaction(function () use ($data, $tagNames): Reading {
			/** @var Reading $reading */
			$reading = Reading::create($data);
			$this->syncTags($reading, $tagNames);

			return $reading;
		});

		$reading->load('tags');

		return (new ReadingResource($reading))
			->response()
			->setStatusCode(Response::HTTP_CREATED);
	}

	/**
	 * PUT/PATCH /api/readings/{id} — update (re-syncs tags when provided).
	 */
	public function update(UpdateReadingRequest $request, string $id): ReadingResource
	{
		$reading = Reading::findOrFail($id);
		$data = $request->validated();

		$hasTags = array_key_exists('tags', $data);
		$tagNames = $data['tags'] ?? [];
		unset($data['tags']);

		DB::transaction(function () use ($reading, $data, $hasTags, $tagNames): void {
			if (! empty($data)) {
				$reading->update($data);
			}
			if ($hasTags) {
				$this->syncTags($reading, $tagNames);
			}
		});

		$reading->load('tags');

		return new ReadingResource($reading);
	}

	/**
	 * DELETE /api/readings/{id}.
	 */
	public function destroy(string $id): Response
	{
		$reading = Reading::findOrFail($id);
		$reading->delete();

		return response()->noContent();
	}

	/**
	 * Resolve tag names → Tag rows (creating missing ones) and sync the
	 * polymorphic taggables pivot — mimics django-taggit's add-by-name.
	 *
	 * @param  array<int, string>  $names
	 */
	protected function syncTags(Reading $reading, array $names): void
	{
		$clean = collect($names)
			->map(fn ($n) => trim((string) $n))
			->filter(fn ($n) => $n !== '')
			->unique()
			->values();

		$ids = $clean->map(function (string $name): string {
			/** @var Tag $tag */
			$tag = Tag::firstOrCreate(['name' => $name]);

			return $tag->id;
		})->all();

		$reading->tags()->sync($ids);
	}
}
