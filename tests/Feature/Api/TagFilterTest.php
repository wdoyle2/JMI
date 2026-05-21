<?php

namespace Tests\Feature\Api;

use App\Models\Anemometer;
use App\Models\Reading;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Port of the tags_any / tags_exact filter tests.
 *
 * The Django originals exercise the FilterSet class directly. Here we
 * drive the actual API endpoint — same semantics, but through the HTTP
 * boundary so the query-string parsing in the Laravel filter layer is
 * exercised too. A pure-unit counterpart lives at
 * tests/Unit/ReadingFilterTest.php.
 */
class TagFilterTest extends TestCase
{
	/** @var array<string, Reading> */
	protected array $readings = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->actingAsUser();
		$anemometer = Anemometer::factory()->create();

		$this->readings = [
			'gusty' => Reading::factory()->for($anemometer)->hasAttached(
				$this->tagsForNames(['gusty'])
			)->create(),
			'gusty_drafty' => Reading::factory()->for($anemometer)->hasAttached(
				$this->tagsForNames(['gusty', 'drafty'])
			)->create(),
			'drafty_stormy' => Reading::factory()->for($anemometer)->hasAttached(
				$this->tagsForNames(['drafty', 'stormy'])
			)->create(),
			'calm' => Reading::factory()->for($anemometer)->hasAttached(
				$this->tagsForNames(['calm'])
			)->create(),
		];
	}

	public function test_tags_any_includes_readings_sharing_any_requested_tag(): void
	{
		$response = $this->getJson('/api/readings?tags_any=gusty,drafty');
		$response->assertOk();

		$ids = collect($response->json('results'))->pluck('id')->all();

		$this->assertContains($this->readings['gusty']->id, $ids);
		$this->assertContains($this->readings['gusty_drafty']->id, $ids);
		$this->assertContains($this->readings['drafty_stormy']->id, $ids);
		$this->assertNotContains($this->readings['calm']->id, $ids);
	}

	public function test_tags_exact_with_a_single_tag_returns_only_exact_matches(): void
	{
		$response = $this->getJson('/api/readings?tags_exact=gusty');
		$response->assertOk();

		$ids = collect($response->json('results'))->pluck('id')->all();

		$this->assertContains($this->readings['gusty']->id, $ids);
		$this->assertNotContains($this->readings['gusty_drafty']->id, $ids);
		$this->assertNotContains($this->readings['drafty_stormy']->id, $ids);
		$this->assertNotContains($this->readings['calm']->id, $ids);
	}

	public function test_tags_exact_with_multiple_tags_returns_only_the_exact_set_match(): void
	{
		$response = $this->getJson('/api/readings?tags_exact=gusty,drafty');
		$response->assertOk();

		$ids = collect($response->json('results'))->pluck('id')->all();

		$this->assertContains($this->readings['gusty_drafty']->id, $ids);
		$this->assertNotContains($this->readings['gusty']->id, $ids);
		$this->assertNotContains($this->readings['drafty_stormy']->id, $ids);
		$this->assertNotContains($this->readings['calm']->id, $ids);
	}

	/**
	 * Resolve (or create) Tag rows for the given names and return them as a
	 * collection ready to be passed to hasAttached().
	 *
	 * @param  array<int, string>  $names
	 */
	private function tagsForNames(array $names): Collection
	{
		return collect($names)->map(fn (string $name) => Tag::firstOrCreate(['name' => $name]));
	}
}
