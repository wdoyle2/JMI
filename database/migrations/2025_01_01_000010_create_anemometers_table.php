<?php

/**
 * Anemometers — parity with wind_for_life.apps.anemometers.Anemometer.
 *
 * decimal(9,6) keeps the same precision as Django's DecimalField. Range
 * validation (-90..90 / -180..180) is enforced in the FormRequest layer,
 * not the schema, matching the Django validators.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('anemometers', function (Blueprint $table): void {
			$table->uuid('id')->primary();
			$table->string('name', 100);
			$table->decimal('longitude', 9, 6);
			$table->decimal('latitude', 9, 6);
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('anemometers');
	}
};
