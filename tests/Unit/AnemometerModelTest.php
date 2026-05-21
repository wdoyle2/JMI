<?php

namespace Tests\Unit;

use App\Models\Anemometer;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Mirrors the Django Anemometer.slug @property unit check.
 *
 * Django used django.utils.text.slugify; Laravel uses Str::slug which
 * produces the same lower-case hyphenated output for ASCII inputs.
 */
class AnemometerModelTest extends TestCase
{
	public function test_exposes_a_slug_accessor_that_slugifies_the_name(): void
	{
		$anemometer = Anemometer::factory()->make(['name' => 'Mistral Ridge Alpha']);

		$this->assertSame(Str::slug('Mistral Ridge Alpha'), $anemometer->slug);
		$this->assertSame('mistral-ridge-alpha', $anemometer->slug);
	}

	public function test_slug_updates_when_name_changes(): void
	{
		$anemometer = Anemometer::factory()->make(['name' => 'First']);
		$this->assertSame('first', $anemometer->slug);

		$anemometer->name = 'Second Name';
		$this->assertSame('second-name', $anemometer->slug);
	}
}
