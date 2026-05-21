<?php

/**
 * Tags — Laravel analog of django-taggit's W4LTag table.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('tags', function (Blueprint $table): void {
			$table->uuid('id')->primary();
			$table->string('name')->unique();
			$table->string('slug')->unique();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tags');
	}
};
