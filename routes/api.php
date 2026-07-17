<?php

use App\Http\Controllers\Api\AnemometerController;
use App\Http\Controllers\Api\AnemometerExportController;
use App\Http\Controllers\Api\AnemometerReadingController;
use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\ReadingController;
use App\Http\Controllers\Api\ReadingExportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — parity with Django config/api_router.py
|--------------------------------------------------------------------------
|
| Intentionally omitted (parity with the `main` branch of the Django repo):
|   - ?anemometer= filter on /api/readings
|
*/

Route::post('auth-token', AuthTokenController::class);

Route::middleware('auth:sanctum')->group(function () {
	// users
	Route::get('users/me', [UserController::class, 'me']);
	Route::get('users', [UserController::class, 'index']);
	Route::get('users/{username}', [UserController::class, 'show']);
	Route::patch('users/{username}', [UserController::class, 'update']);

	// anemometers (custom actions registered BEFORE the resource so
	// `/anemometers/export` and `/anemometers/recent-readings` aren't
	// caught by the {id} wildcard).
	Route::get('anemometers/export', AnemometerExportController::class);
	Route::get('anemometers/recent-readings', [AnemometerController::class, 'recentReadings']);
	Route::apiResource('anemometers', AnemometerController::class);

	// nested readings under anemometer
	Route::get('anemometers/{anemometer}/readings', [AnemometerReadingController::class, 'index']);
	Route::get('anemometers/{anemometer}/readings/{reading}', [AnemometerReadingController::class, 'show']);

	// readings
	Route::get('readings/export', ReadingExportController::class);
	Route::apiResource('readings', ReadingController::class);
});
