<?php

namespace Tests\Feature;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C5: the child profile page shows the child's own nutrient gaps from the
 * approved plan (no fixed fallback list), an honest "Nutrients Met" tile instead
 * of a fake health score, an "Appetite" label, and its script has no duplicate
 * declarations (the duplicate `const pDesc` stopped the whole script).
 */
class ChildProfilePageTest extends TestCase
{
    use RefreshDatabase;

    private function makeChildFor(User $parent, array $assessment = []): ChildAssessment
    {
        $child = Child::create([
            'user_id' => $parent->id,
            'name'    => 'Profile Kid',
            'sex'     => 'girl',
            'dob'     => now()->subYears(8)->toDateString(),
        ]);

        return ChildAssessment::create(array_merge([
            'child_id'          => $child->id,
            'model_level'       => 'plus',
            'current_weight_kg' => 24,
            'current_height_cm' => 125,
            'diet_type'         => 'vegetarian',
            'picky_level'       => 'Normal appetite',
            'activity_level'    => 'Moderately active',
        ], $assessment));
    }

    /**
     * Only the visible profile section. The page also holds a hidden edit/onboarding
     * block that legitimately contains words such as "Iron" or "Moderately active".
     */
    private function profileView(string $html): string
    {
        $start = strpos($html, 'id="vProfile"');
        $end   = strpos($html, 'VIEW: EDIT/ONBOARDING');
        $this->assertNotFalse($start, 'profile section not found');
        $this->assertNotFalse($end, 'edit section marker not found');

        return substr($html, $start, $end - $start);
    }

    private function gap(float $percent, string $category): array
    {
        return [
            'target'           => 10,
            'absorbed_amount'  => $percent / 10,
            'adequacy_percent' => $percent,
            'gap_category'     => $category,
        ];
    }

    private function makePlan(ChildAssessment $assessment, array $gaps, string $status = 'active', string $review = 'approved'): DietPlan
    {
        return DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => $status,
            'review_status'       => $review,
            'plan_data'           => ['nutrient_gaps' => $gaps],
        ]);
    }

    public function test_gaps_come_from_the_approved_plan_most_serious_first_and_no_fixed_list(): void
    {
        $parent = User::factory()->create();
        $assessment = $this->makeChildFor($parent);
        $this->makePlan($assessment, [
            'calcium_mg'   => $this->gap(95, 'Met'),
            'vitamin_c_mg' => $this->gap(60, 'Significant gap'),
            'iron_mg'      => $this->gap(20, 'Critical gap'),
        ]);

        $response = $this->actingAs($parent)->get(route('child-profile'))->assertOk();
        $html = $this->profileView($response->getContent());

        $this->assertSame(['Iron', 'Vitamin C', 'Calcium'], array_column($response->viewData('nutrientGaps'), 'label'));
        $this->assertTrue(strpos($html, 'Iron') < strpos($html, 'Vitamin C'));
        $this->assertTrue(strpos($html, 'Vitamin C') < strpos($html, 'Calcium'));

        // The old hard-coded fallback rows must be gone.
        $this->assertStringNotContainsString('Omega-3', $html);
        $this->assertStringNotContainsString('Vitamin D', $html);

        // 1 of 3 nutrients is met.
        $this->assertStringContainsString('1/3', $html);
        $this->assertStringContainsString('Nutrients Met', $html);
    }

    public function test_each_child_sees_their_own_gaps(): void
    {
        $parentA = User::factory()->create();
        $this->makePlan($this->makeChildFor($parentA), ['zinc_mg' => $this->gap(30, 'Critical gap')]);

        $parentB = User::factory()->create();
        $this->makePlan($this->makeChildFor($parentB), ['iron_mg' => $this->gap(30, 'Critical gap')]);

        $htmlA = $this->profileView($this->actingAs($parentA)->get(route('child-profile'))->assertOk()->getContent());
        $this->assertStringContainsString('Zinc', $htmlA);
        $this->assertStringNotContainsString('Iron', $htmlA);

        $htmlB = $this->profileView($this->actingAs($parentB)->get(route('child-profile'))->assertOk()->getContent());
        $this->assertStringContainsString('Iron', $htmlB);
        $this->assertStringNotContainsString('Zinc', $htmlB);
    }

    public function test_plan_still_under_review_shows_no_gaps_and_a_waiting_message(): void
    {
        $parent = User::factory()->create();
        $assessment = $this->makeChildFor($parent);
        $this->makePlan($assessment, ['magnesium_mg' => $this->gap(10, 'Critical gap')], 'pending_review', 'pending_review');

        $response = $this->actingAs($parent)->get(route('child-profile'))->assertOk();
        $html = $this->profileView($response->getContent());

        $this->assertSame([], $response->viewData('nutrientGaps'));
        $this->assertStringNotContainsString('Magnesium', $html);
        $this->assertStringContainsString('will appear here once your diet chart has been reviewed', $html);
        $this->assertStringNotContainsString('0/0', $html);
    }

    public function test_label_is_appetite_not_picky_level_and_missing_values_are_not_invented(): void
    {
        $parent = User::factory()->create();
        $this->makeChildFor($parent, ['picky_level' => null, 'activity_level' => null]);

        $html = $this->profileView($this->actingAs($parent)->get(route('child-profile'))->assertOk()->getContent());

        $this->assertStringContainsString('Appetite', $html);
        $this->assertStringNotContainsString('Picky level', $html);
        $this->assertStringNotContainsString('Slightly picky', $html);
        $this->assertStringNotContainsString('Moderately active', $html);
        $this->assertStringContainsString('Not provided', $html);
    }

    public function test_script_has_no_duplicate_declarations_and_no_fake_score_count_up(): void
    {
        $js = file_get_contents(public_path('js/child-profile.js'));

        preg_match_all('/^(?:const|let|function)\s+([A-Za-z_$][\w$]*)/m', $js, $matches);
        $duplicates = array_keys(array_filter(array_count_values($matches[1]), fn ($count) => $count > 1));

        $this->assertSame([], $duplicates, 'child-profile.js declares these names more than once.');
        $this->assertStringNotContainsString("cu('scoreVal'", $js);
    }
}