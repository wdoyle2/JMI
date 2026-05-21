<?php

/**
 * Users table — Laravel analog of Django's AbstractUser.
 *
 * The PK stays a big-int auto-increment to match Django's BigAutoField
 * (see users/migrations/0001_initial.py). Every other domain table in
 * this port uses UUID primary keys instead.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('users', function (Blueprint $table): void {
			$table->id();
			$table->string('username', 150)->unique();
			$table->string('email')->unique();
			$table->string('name', 255)->nullable();
			$table->timestamp('email_verified_at')->nullable();
			$table->string('password');
			$table->boolean('is_staff')->default(false);
			$table->boolean('is_superuser')->default(false);
			$table->rememberToken();
			$table->timestamps();
		});

		Schema::create('password_reset_tokens', function (Blueprint $table): void {
			$table->string('email')->primary();
			$table->string('token');
			$table->timestamp('created_at')->nullable();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('password_reset_tokens');
		Schema::dropIfExists('users');
	}
};
