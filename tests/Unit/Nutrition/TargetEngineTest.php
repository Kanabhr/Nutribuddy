<?php

namespace Tests\Unit\Nutrition;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\IcmrNutritionTarget;
use App\Services\Nutrition\TargetEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TargetEngineTest
 *
 * Uses an in-memory SQLite DB (configured in phpunit.xml) so no real
 * database connection is required.  Each test gets a fresh schema via
 * RefreshDatabase.
 *
 * Rows are inserted directly — no seeders run — so the test is fast
 * and independent of seeder state.
 */
class TargetEngineTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Insert a minimal IcmrNutritionTarget row and return it.
     */
    private function makeTarget(int $ageMin, int $ageMax, string $sex, float $energyKcal): IcmrNutritionTarget
    {
        return IcmrNutritionTarget::create([
            'age_min'     => $ageMin,
            'age_max'     => $ageMax,
            'sex'         => $sex,
            'energy_kcal' => $energyKcal,
            // All other nutrient columns default to 0 — fine for these tests.
        ]);
    }

    /**
     * Build a Child stub whose Carbon::parse($child->dob)->age resolves
     * to exactly $age years old as of today.
     */
    private function childOfAge(int $age, string $sex): Child
    {
        $child = new Child();
        $child->dob = Carbon::now()->subYears($age)->format('Y-m-d');
        $child->sex = $sex;
        return $child;
    }

    // ──────────────────────────────────────────────────────────────
    // Tests
    // ──────────────────────────────────────────────────────────────

    /**
     * A child aged 8 should get the 7-9 row (1700 kcal).
     * The 7-9 band is sex='all', so any sex resolves to it.
     */
    public function test_child_aged_8_gets_7_to_9_band(): void
    {
        $this->makeTarget(1,  3,  'all', 1110);
        $this->makeTarget(4,  6,  'all', 1360);
        $this->makeTarget(7,  9,  'all', 1700);   // ← expected
        $this->makeTarget(10, 12, 'boy', 2220);
        $this->makeTarget(10, 12, 'girl', 2060);

        $engine = new TargetEngine();
        $result = $engine->getTargets($this->childOfAge(8, 'boy'));

        $this->assertNotNull($result, 'TargetEngine returned null for age 8');
        $this->assertEquals(1700, $result->energy_kcal,
            "Expected 1700 kcal (7-9 band) for age 8, got {$result->energy_kcal}");
        $this->assertEquals(7,  $result->age_min);
        $this->assertEquals(9,  $result->age_max);
    }

    /**
     * A 12-year-old boy should get the 10-12 boys row (2220 kcal),
     * not the girls row (2060) or any other band.
     */
    public function test_boy_aged_12_gets_10_to_12_boy_band(): void
    {
        $this->makeTarget(7,  9,  'all',  1700);
        $this->makeTarget(10, 12, 'boy',  2220);   // ← expected
        $this->makeTarget(10, 12, 'girl', 2060);
        $this->makeTarget(13, 15, 'boy',  2860);
        $this->makeTarget(13, 15, 'girl', 2400);

        $engine = new TargetEngine();
        $result = $engine->getTargets($this->childOfAge(12, 'boy'));

        $this->assertNotNull($result, 'TargetEngine returned null for age 12 boy');
        $this->assertEquals(2220, $result->energy_kcal,
            "Expected 2220 kcal (10-12 boy) for age 12 boy, got {$result->energy_kcal}");
        $this->assertEquals('boy', $result->sex);
        $this->assertEquals(10, $result->age_min);
        $this->assertEquals(12, $result->age_max);
    }

    /**
     * A 12-year-old girl should get the 10-12 girls row (2060 kcal).
     */
    public function test_girl_aged_12_gets_10_to_12_girl_band(): void
    {
        $this->makeTarget(10, 12, 'boy',  2220);
        $this->makeTarget(10, 12, 'girl', 2060);   // ← expected

        $engine = new TargetEngine();
        $result = $engine->getTargets($this->childOfAge(12, 'girl'));

        $this->assertNotNull($result);
        $this->assertEquals(2060, $result->energy_kcal);
        $this->assertEquals('girl', $result->sex);
    }

    /**
     * When there is no sex-specific row, the 'all' row for the same band
     * should be returned (fallback path).
     */
    public function test_falls_back_to_all_when_no_sex_specific_row_exists(): void
    {
        $this->makeTarget(4, 6, 'all', 1360);   // ← expected (only row for age 5)

        $engine = new TargetEngine();
        $result = $engine->getTargets($this->childOfAge(5, 'girl'));

        $this->assertNotNull($result);
        $this->assertEquals(1360, $result->energy_kcal);
        $this->assertEquals('all', $result->sex);
    }

    /**
     * An age outside every seeded band triggers the catch-all (row 0).
     * The engine must not return null.
     */
    public function test_catch_all_returns_a_row_for_age_outside_all_bands(): void
    {
        $this->makeTarget(1, 3, 'all', 1110);   // only band; age 20 is outside it

        $engine = new TargetEngine();
        $result = $engine->getTargets($this->childOfAge(20, 'boy'));

        $this->assertNotNull($result, 'TargetEngine must not return null for out-of-band age');
    }
}
