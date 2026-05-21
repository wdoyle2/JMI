<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * Bootstraps the Laravel application for tests.
 *
 * Mirrors Django's conftest.py role — one place where the test
 * harness builds the app/container before any test runs.
 */
trait CreatesApplication
{
	/**
	 * Creates the application.
	 */
	public function createApplication(): Application
	{
		$app = require __DIR__ . '/../bootstrap/app.php';

		$app->make(Kernel::class)->bootstrap();

		return $app;
	}
}
