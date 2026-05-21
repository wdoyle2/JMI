<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		//
	}

	public function boot(): void
	{
		// Match the Django/DRF response shape the candidate tests assert
		// against: no `data` wrapper on single resources. The DRF-style
		// paginator envelope ({count, next, previous, results}) is built
		// in controllers via App\Http\Responses\DrfPagination::shape().
		JsonResource::withoutWrapping();
	}
}
