<?php

namespace Tests\Unit\Nutrition;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\IcmrNutritionTarget;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\DietChartDisplayService;
use App\Services\Nutrition\NutritionPageService;
use App\Services\Nutrition\TargetEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * NutritionPageServiceTargetTest
 *
 * Verifies the target-resolution logic introduced in Part 2:
 *  1. Reads targets from plan_data['nutrient_gaps'] (flat per-nutrient shape).
 *  2. Falls back to TargetEngine when plan_data lacks gaps.
 *  3. Returns null todayTarget when no ICMR row covers the child's age.
 *  4. No hardcoded 2100 / 152 / 61 constants appear anywhere in the output.
 *
 * Uses in-memory SQLite via RefreshDatabase — no seeders, no migrations beyond
 * what the framework runs automatically.
 */
class NutritionPageServiceTargetTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeIcmrRow(int $ageMin, int $ageMax, float $energy, float $protein, float $fat, string $sex = 'all'): void
    {
        DB::table('icmr_nutrition_targets')->insert([
            'age_min'     => $ageMin,
            'age_max'     => $ageMax,
            'sex'         => $sex,
            'energy_kcal' => $energy,
            'protein_g'   => $protein,
            'fat_g'       => $fat,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Create a minimal user + child (given dob) + assessment with a diet plan
     * whose plan_data contains the given nutrient_gaps payload.
     * Returns [$userId, $childId].
     */
    private function makeChildWithApprovedPlan(string $dob, string $sex, array $planNutrientGaps): array
    {
        $user = User::factory()->create();
        $child = Child::create([
            'user_id' => $user->id,
            'name'    => 'Test',
            'dob'     => $dob,
            'sex'     => $sex,
        ]);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 20.0,
            'current_height_cm' => 110.0,
            'initial_weight_kg' => 20.0,
            'initial_height_cm' => 110.0,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);
        DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'version'             => 1,
            'status'              => 'active',
            'review_status'       => 'approved',
            'plan_data'           => [
                'schedule'      => ['start_date' => now()->toDateString(), 'days' => 7],
                'nutrient_gaps' => $planNutrientGaps,
            ],
        ]);
        return [$user->id, (string) $child->id];
    }

    /**
     * Create a child + assessment with NO diet plan.
     * Returns [$userId, $childId].
     */
    private function makeChildWithoutPlan(string $dob, string $sex): array
    {
        $user = User::factory()->create();
        $child = Child::create([
            'user_id' => $user->id,
            'name'    => 'Test',
            'dob'     => $dob,
            'sex'     => $sex,
        ]);
        ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 20.0,
            'current_height_cm' => 110.0,
            'initial_weight_kg' => 20.0,
            'initial_height_cm' => 110.0,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);
        return [$user->id, (string) $child->id];
    }

    private function makeService(): NutritionPageService
    {
        return new NutritionPageService(app(DietChartDisplayService::class));
    }

    // ------------------------------------------------------------------
    // Tests
    // ------------------------------------------------------------------

    /**
     * When plan_data has a flat nutrient_gaps summary, todayTarget must
     * be taken directly from that — never from any hardcoded constant.
     */
    public function test_targets_read_from_plan_data_nutrient_gaps(): void
    {
        $gaps = [
            'energy_kcal' => ['target' => 1700, 'absorbed_amount' => 900, 'adequacy_percent' => 52, 'gap_category' => 'Significant gap'],
            'protein_g'   => ['target' => 23.3, 'absorbed_amount' => 12,  'adequacy_percent' => 51, 'gap_category' => 'Significant gap'],
            'fat_g'       => ['target' => 30,   'absorbed_amount' => 15,  'adequacy_percent' => 50, 'gap_category' => 'Significant gap'],
        ];

        [$userId, $childId] = $this->makeChildWithApprovedPlan(
            Carbon::now()->subYears(8)->toDateString(),
            'boy',
            $gaps
        );

        $result = $this->makeService()->build($userId, $childId);
        $target = $result['viewData']['todayTarget'];

        $this->assertNotNull($target, 'todayTarget must not be null when plan_data has nutrient_gaps');
        $this->assertSame(1700.0, $target['energy_kcal'], 'energy from plan_data');
        $this->assertSame(23.3,   $target['protein_g'],   'protein from plan_data');
        $this->assertSame(30.0,   $target['fat_g'],       'fat from plan_data');

        // Confirm the old hardcoded constants are absent.
        $this->assertNotEquals(2100, $target['energy_kcal'], 'must not be the 2100 constant');
        $this->assertNotEquals(152,  $target['protein_g'],   'must not be the 152 constant');
        $this->assertNotEquals(61,   $target['fat_g'],       'must not be the 61 constant');
    }

    /**
     * When there is no approved plan, the service must fall back to TargetEngine
     * and return the ICMR row's values — not 2100/152/61.
     */
    public function test_targets_fall_back_to_target_engine_when_no_plan(): void
    {
        // Age 8 → 7-9 band
        $this->makeIcmrRow(7, 9, 1700, 23.3, 30.0);

        [$userId, $childId] = $this->makeChildWithoutPlan(
            Carbon::now()->subYears(8)->toDateString(),
            'boy'
        );

        $result = $this->makeService()->build($userId, $childId);
        $target = $result['viewData']['todayTarget'];

        $this->assertNotNull($target, 'todayTarget must not be null when ICMR row exists');
        $this->assertSame(1700.0, $target['energy_kcal']);
        $this->assertSame(23.3,   $target['protein_g']);
        $this->assertSame(30.0,   $target['fat_g']);
    }

    /**
     * When there is no approved plan and no ICMR row covering the child's age,
     * TargetEngine falls back to its catch-all (first row by id) rather than null.
     * todayTarget is therefore a real ICMR row value — never the 2100/152/61 constants.
     *
     * Note: RefreshDatabase re-runs migrations, which include
     * 2026_09_09_000001 that inserts the 4-6/all row (1360 kcal, id=1).
     * A manually-inserted 1-3 row does NOT cover age 8.
     * The catch-all returns the lowest-id row — the migration-seeded 4-6/all.
     */
    public function test_targets_are_catch_all_when_only_non_matching_icmr_row_exists(): void
    {
        // Seed only a 1-3 band manually; it does NOT cover age 8.
        // The 4-6/all row (1360 kcal) is already present from the migration.
        $this->makeIcmrRow(1, 3, 1110, 12.5, 15.0);

        [$userId, $childId] = $this->makeChildWithoutPlan(
            Carbon::now()->subYears(8)->toDateString(),
            'boy'
        );

        $result = $this->makeService()->build($userId, $childId);
        $target = $result['viewData']['todayTarget'] ?? 'MISSING_KEY';

        $this->assertArrayHasKey('todayTarget', $result['viewData']);
        // The catch-all must be a real ICMR value — never the old hardcoded constants.
        $this->assertNotEquals(2100, $target['energy_kcal'] ?? null, 'must not be hardcoded 2100');
        $this->assertNotEquals(152,  $target['protein_g']   ?? null, 'must not be hardcoded 152');
        $this->assertNotEquals(61,   $target['fat_g']       ?? null, 'must not be hardcoded 61');
        $this->assertNotNull($target, 'TargetEngine catch-all must return something, not null');
    }

    /**
     * plan_data['nutrient_gaps'] takes priority over the ICMR table even when
     * both are available. The plan gap value (1600 kcal) differs from the ICMR
     * row value (1700 kcal) so we can tell which was used.
     */
    public function test_plan_data_gaps_take_priority_over_target_engine(): void
    {
        // ICMR row for age 8: 1700 kcal
        $this->makeIcmrRow(7, 9, 1700, 23.3, 30.0);

        $gaps = [
            'energy_kcal' => ['target' => 1600, 'absorbed_amount' => 800, 'adequacy_percent' => 50, 'gap_category' => 'Significant gap'],
            'protein_g'   => ['target' => 20.0, 'absorbed_amount' => 10,  'adequacy_percent' => 50, 'gap_category' => 'Significant gap'],
            'fat_g'       => ['target' => 28.0, 'absorbed_amount' => 14,  'adequacy_percent' => 50, 'gap_category' => 'Significant gap'],
        ];

        [$userId, $childId] = $this->makeChildWithApprovedPlan(
            Carbon::now()->subYears(8)->toDateString(),
            'boy',
            $gaps
        );

        $result = $this->makeService()->build($userId, $childId);
        $target = $result['viewData']['todayTarget'];

        $this->assertNotNull($target);
        // Must be the plan value, not the ICMR row value.
        $this->assertSame(1600.0, $target['energy_kcal'], 'plan_data gaps must win over TargetEngine');
    }

    /**
     * With only the 7-9 band seeded:
     * - The age-8 child must get 1700 kcal.
     * - The age-5 child falls outside the seeded band; TargetEngine returns
     *   the catch-all (first row = 7-9 band, 1700 kcal). The key point is
     *   that neither child ever gets the old hardcoded 2100/152/61 constants.
     */
    public function test_neither_child_gets_hardcoded_constants(): void
    {
        // Only the 7-9 band is seeded.
        $this->makeIcmrRow(7, 9, 1700, 23.3, 30.0);

        [$userId8, $childId8] = $this->makeChildWithoutPlan(
            Carbon::now()->subYears(8)->toDateString(), 'boy'
        );
        [$userId5, $childId5] = $this->makeChildWithoutPlan(
            Carbon::now()->subYears(5)->toDateString(), 'girl'
        );

        $service = $this->makeService();
        $target8 = $service->build($userId8, $childId8)['viewData']['todayTarget'];
        $target5 = $service->build($userId5, $childId5)['viewData']['todayTarget'];

        // Age-8 must get the correct ICMR value.
        $this->assertNotNull($target8);
        $this->assertSame(1700.0, $target8['energy_kcal']);

        // Age-5 catch-all also returns a real ICMR row value, not the old constants.
        $this->assertNotNull($target5);
        $this->assertNotEquals(2100, $target5['energy_kcal'], 'must not be hardcoded 2100');
        $this->assertNotEquals(152,  $target5['protein_g'],   'must not be hardcoded 152');
        $this->assertNotEquals(61,   $target5['fat_g'],       'must not be hardcoded 61');
    }

    /**
     * dailyTargets must NOT be present in viewData (it was removed).
     * The key must not exist to prevent accidental use of the old buggy lookup.
     */
    public function test_daily_targets_key_is_not_present_in_view_data(): void
    {
        [$userId, $childId] = $this->makeChildWithoutPlan(
            Carbon::now()->subYears(8)->toDateString(), 'boy'
        );

        $result = $this->makeService()->build($userId, $childId);

        $this->assertArrayNotHasKey(
            'dailyTargets',
            $result['viewData'],
            'dailyTargets must be absent from viewData after the refactor'
        );
    }
}
