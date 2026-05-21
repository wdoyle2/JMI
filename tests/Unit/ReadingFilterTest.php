<?php

namespace Tests\Unit;

use App\Http\Filters\ReadingFilter;
use App\Models\Anemometer;
use App\Models\Reading;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Direct unit tests for the ReadingFilter class — mirrors the
 * filterset-level tests in test_anemometers.py::test_filter_tags_any,
 * test_filter_tags_exact_single_tag, and test_filter_tags_exact_multiple_tags.
 *
 * The filter is a static helper at App\Http\Filters\ReadingFilter that
 * applies tag-based constraints to a Reading query builder.
 */
class ReadingFilterTest extends TestCase
{
	/** @var array<string, Reading> */
	protected array $readings = [];

	protected function setUp(): void
	{
		parent::setUp();

		$anemometer = Anemometer::factory()->create();

		$this->readings = [
			'gusty' => Reading::factory()->for($anemometer)->hasAttached($this->resolveTags(['gusty']))->create(),
			'gusty_drafty' => Reading::factory()->for($anemometer)->hasAttached($this->resolveTags(['gusty', 'drafty']))->create(),
			'drafty_stormy' => Reading::factory()->for($anemometer)->hasAttached($this->resolveTags(['drafty', 'stormy']))->create(),
			'calm' => Reading::factory()->for($anemometer)->hasAttached($this->resolveTags(['calm']))->create(),
		];
	}

	public function test_filter_tags_any_returns_readings_with_any_of_the_requested_tags(): void
	{
		$ids = $this->applyReadingFilter(['tags_any' => 'gusty,drafty']);

		$this->assertContains($this->readings['gusty']->id, $ids);
		$this->assertContains($this->readings['gusty_drafty']->id, $ids);
		$this->assertContains($this->readings['drafty_stormy']->id, $ids);
		$this->assertNotContains($this->readings['calm']->id, $ids);
	}

	public function test_filter_tags_exact_single_tag_returns_only_exactly_that_tag_readings(): void
	{
		$ids = $this->applyReadingFilter(['tags_exact' => 'gusty']);

		$this->assertContains($this->readings['gusty']->id, $ids);
		$this->assertNotContains($this->readings['gusty_drafty']->id, $ids);
		$this->assertNotContains($this->readings['drafty_stormy']->id, $ids);
		$this->assertNotContains($this->readings['calm']->id, $ids);
	}

	public function test_filter_tags_exact_multiple_tags_returns_only_the_exact_set_match(): void
	{
		$ids = $this->applyReadingFilter(['tags_exact' => 'gusty,drafty']);

		$this->assertContains($this->readings['gusty_drafty']->id, $ids);
		$this->assertNotContains($this->readings['gusty']->id, $ids);
		$this->assertNotContains($this->readings['drafty_stormy']->id, $ids);
		$this->assertNotContains($this->readings['calm']->id, $ids);
	}

	/**
	 * @param  array<string, mixed>  $filters
	 * @return array<int, string>
	 */
	private function applyReadingFilter(array $filters): array
	{
		// Materialise rows then pluck in PHP. Plucking `id` at the SQL level
		// would emit `SELECT DISTINCT id ... ORDER BY recorded_at` (the filter's
		// distinct() + the Reading model's global ordering scope), which MySQL
		// rejects under ONLY_FULL_GROUP_BY. Real callers paginate (SELECT *), so
		// they never hit this — it is a unit-test artifact, not app behaviour.
		return ReadingFilter::apply(Reading::query(), $filters)->get()->pluck('id')->all();
	}

	/**
	 * @param  array<int, string>  $names
	 */
	private function resolveTags(array $names): Collection
	{
		return collect($names)->map(fn (string $name) => Tag::firstOrCreate(['name' => $name]));
	}
}
