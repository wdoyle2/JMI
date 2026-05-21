<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreAnemometerRequest;
use App\Http\Requests\UpdateAnemometerRequest;
use App\Http\Resources\AnemometerDetailResource;
use App\Http\Resources\AnemometerResource;
use App\Http\Resources\RecentReadingsAnemometerResource;
use App\Http\Responses\DrfPagination;
use App\Models\Anemometer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

/**
 * Port of wind_for_life/apps/anemometers/api/views.py::AnemometerViewSet.
 */
class AnemometerController extends Controller
{
	/**
	 * GET /api/anemometers — paginated list.
	 *
	 * @return array<string, mixed>
	 */
	public function index(Request $request): array
	{
		$anemometers = Anemometer::query()->paginate();

		return DrfPagination::shape(
			$anemometers,
			fn (Anemometer $a) => (new AnemometerResource($a))->resolve(),
		);
	}

	/**
	 * GET /api/anemometers/{id} — detail with eager-loaded readings.
	 */
	public function show(string $id): AnemometerDetailResource
	{
		$anemometer = Anemometer::with('readings.tags')->findOrFail($id);

		return new AnemometerDetailResource($anemometer);
	}

	/**
	 * POST /api/anemometers — create.
	 */
	public function store(StoreAnemometerRequest $request): JsonResponse
	{
		$anemometer = Anemometer::create($request->validated());

		return (new AnemometerResource($anemometer))
			->response()
			->setStatusCode(Response::HTTP_CREATED);
	}

	/**
	 * PUT/PATCH /api/anemometers/{id} — update.
	 */
	public function update(UpdateAnemometerRequest $request, string $id): AnemometerResource
	{
		$anemometer = Anemometer::findOrFail($id);
		$anemometer->update($request->validated());

		return new AnemometerResource($anemometer);
	}

	/**
	 * DELETE /api/anemometers/{id} — delete.
	 */
	public function destroy(string $id): Response
	{
		$anemometer = Anemometer::findOrFail($id);
		$anemometer->delete();

		return response()->noContent();
	}

	/**
	 * GET /api/anemometers/recent-readings
	 *
	 * Port of the Django @action: annotates each anemometer with the average
	 * speed over the past 24h and 7d, and attaches its 5 most-recent readings.
	 *
	 * @return array<string, mixed>
	 */
	public function recentReadings(Request $request): array
	{
		$now = Carbon::now();
		$dayAgo = $now->copy()->subDay();
		$weekAgo = $now->copy()->subWeek();

		$anemometers = Anemometer::query()
			->selectSub(
				fn ($q) => $q->from('readings')
					->selectRaw('AVG(speed)')
					->whereColumn('readings.anemometer_id', 'anemometers.id')
					->where('readings.recorded_at', '>=', $dayAgo),
				'average_daily_speed',
			)
			->selectSub(
				fn ($q) => $q->from('readings')
					->selectRaw('AVG(speed)')
					->whereColumn('readings.anemometer_id', 'anemometers.id')
					->where('readings.recorded_at', '>=', $weekAgo),
				'average_weekly_speed',
			)
			->addSelect('anemometers.*')
			->paginate();

		// Attach the 5 most-recent readings to each model instance so the
		// resource can surface them under `recent_readings`.
		$anemometers->getCollection()->transform(function (Anemometer $a): Anemometer {
			$recent = $a->readings()
				->withoutGlobalScopes()
				->with('tags')
				->orderByDesc('recorded_at')
				->limit(5)
				->get();
			$a->setAttribute('recent_readings', $recent);

			return $a;
		});

		return DrfPagination::shape(
			$anemometers,
			fn (Anemometer $a) => (new RecentReadingsAnemometerResource($a))->resolve(),
		);
	}
}
