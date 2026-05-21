<?php

/**
 * Readings — parity with wind_for_life.apps.anemometers.Reading.
 *
 * Django Meta: ordering = ["-recorded_at"], indexes on
 * (anemometer, recorded_at), on_delete=CASCADE.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('readings', function (Blueprint $table): void {
			$table->uuid('id')->primary();
			$table->float('speed');
			$table->timestamp('recorded_at')->useCurrent();
			$table->uuid('anemometer_id');
			$table->timestamps();

			$table->foreign('anemometer_id')
				->references('id')
				->on('anemometers')
				->cascadeOnDelete();

			$table->index(['anemometer_id', 'recorded_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('readings');
	}
};
