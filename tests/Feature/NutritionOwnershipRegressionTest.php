<?php

namespace Tests\Feature;

use App\Models\AssessmentAttempt;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\MealItemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionOwnershipRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_authenticated_nutrition_pages(): void
    {
        foreach ([route('nutrition.index'), route('nutrition.backup'), route('meal-plan')] as $path) {
            $this->get($path)->assertRedirect(route('frontend.login'));
        }
    }

    public function test_parent_cannot_select_another_users_child_on_nutrition_pages(): void
    {
        $owner = User::factory()->create();
        $otherParent = User::factory()->create();
        $child = Child::create([
            'user_id' => $otherParent->id,
            'name' => 'Private child',
            'sex' => 'girl',
        ]);

        foreach ([route('nutrition.index'), route('nutrition.backup'), route('meal-plan')] as $path) {
            $this->actingAs($owner)
                ->get($path.'?child_id='.$child->id)
                ->assertOk()
                ->assertViewMissing('child');
        }
    }

    public function test_child_path_is_clean_and_scoped_to_the_authenticated_parent(): void
    {
        $owner = User::factory()->create();
        $otherParent = User::factory()->create();
        $child = Child::create([
            'user_id' => $owner->id,
            'name' => 'Owned child',
            'sex' => 'girl',
        ]);

        $this->actingAs($owner)
            ->get(route('nutrition.child', $child))
            ->assertOk()
            ->assertViewHas('child', fn ($viewChild) => $viewChild->is($child));

        $this->actingAs($otherParent)
            ->get(route('nutrition.child', $child))
            ->assertNotFound();
    }

    public function test_parent_cannot_read_or_regenerate_another_users_diet_chart(): void
    {
        $owner = User::factory()->create();
        $otherParent = User::factory()->create();
        $child = Child::create([
            'user_id' => $owner->id,
            'name' => 'Private child',
            'sex' => 'girl',
        ]);

        $this->actingAs($otherParent)
            ->get(route('child.diet-chart.show', $child))
            ->assertNotFound();

        $this->get(route('child.diet-chart.download-pdf', $child))
            ->assertNotFound();

        $this->post(route('child.diet-chart.regenerate', $child))
            ->assertNotFound();
    }

    public function test_parent_cannot_read_another_users_assessment_attempt(): void
    {
        $owner = User::factory()->create();
        $otherParent = User::factory()->create();
        $attempt = AssessmentAttempt::create(['user_id' => $owner->id]);

        $this->actingAs($otherParent)
            ->get(route('user.assessment.result', $attempt))
            ->assertNotFound();

        $this->get(route('user.assessment.detail', $attempt))
            ->assertNotFound();
    }

    public function test_owned_child_without_an_assessment_keeps_existing_nutrition_view_data(): void
    {
        $parent = User::factory()->create();
        $child = Child::create([
            'user_id' => $parent->id,
            'name' => 'New child',
            'sex' => 'girl',
        ]);

        $this->actingAs($parent)
            ->get(route('nutrition.index'))
            ->assertOk()
            ->assertViewIs('pages.user-panel.nutrition')
            ->assertViewHas('child', fn ($viewChild) => $viewChild->is($child));

        $this->get(route('nutrition.backup'))
            ->assertOk()
            ->assertViewIs('pages.user-panel.nutrition_backup')
            ->assertViewHas('child', fn ($viewChild) => $viewChild->is($child));

        $this->get(route('meal-plan'))
            ->assertOk()
            ->assertViewIs('pages.user-panel.meal-plan')
            ->assertViewMissing('jsDays');
    }

    public function test_assessment_without_an_approved_plan_keeps_existing_targets_and_pending_state(): void
    {
        $parent = User::factory()->create();
        $child = Child::create([
            'user_id' => $parent->id,
            'name' => 'Assessed child',
            'sex' => 'girl',
        ]);
        ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'basic',
            'current_weight_kg' => 15,
            'current_height_cm' => 95,
            'diet_type' => 'vegetarian',
        ]);

        $this->actingAs($parent)
            ->get(route('nutrition.index'))
            ->assertOk()
            ->assertViewHas('latestPlan', null)
            ->assertViewHas('todayTarget', ['energy_kcal' => 2100, 'protein_g' => 152, 'fat_g' => 61]);

        $this->get(route('nutrition.backup'))
            ->assertOk()
            ->assertViewHas('latestPlan', null)
            ->assertViewMissing('dailyTargets');

        $this->get(route('meal-plan'))
            ->assertOk()
            ->assertViewHas('reviewPending', false);
    }

    public function test_child_check_in_copies_assessment_profile_and_calculates_bmi(): void
    {
        $parent = User::factory()->create();
        $child = Child::create([
            'user_id' => $parent->id,
            'name' => 'Growing child',
            'sex' => 'girl',
        ]);
        $previous = ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'basic',
            'current_weight_kg' => 15,
            'current_height_cm' => 95,
            'diet_type' => 'vegetarian',
            'main_goal' => 'General Growth',
        ]);

        $this->actingAs($parent)
            ->postJson('/nutrition/check-in', [
                'child_id' => $child->id,
                'weight_kg' => 16,
                'height_cm' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('current_weight', 16)
            ->assertJsonPath('current_height', 100);

        $checkIn = ChildAssessment::where('child_id', $child->id)->latest('id')->firstOrFail();
        $this->assertNotSame($previous->id, $checkIn->id);
        $this->assertSame('vegetarian', $checkIn->diet_type);
        $this->assertSame(15.0, $checkIn->initial_weight_kg);
        $this->assertSame(95.0, $checkIn->initial_height_cm);
        $this->assertSame(16.0, $checkIn->current_weight_kg);
        $this->assertSame(100.0, $checkIn->current_height_cm);
        $this->assertSame(16.0, $checkIn->bmi);
    }

    public function test_meal_item_toggle_preserves_json_contract_and_switches_consumed_state(): void
    {
        $parent = User::factory()->create();
        $child = Child::create([
            'user_id' => $parent->id,
            'name' => 'Meal child',
            'sex' => 'girl',
        ]);
        $payload = [
            'child_id' => $child->id,
            'food_id' => 9001,
            'meal_id' => 42,
            'log_date' => '2026-10-17',
        ];

        $this->actingAs($parent)
            ->postJson('/nutrition/toggle-item', $payload)
            ->assertOk()
            ->assertExactJson(['success' => true, 'is_consumed' => true, 'food_id' => 9001]);

        $this->postJson('/nutrition/toggle-item', $payload)
            ->assertOk()
            ->assertExactJson(['success' => true, 'is_consumed' => false, 'food_id' => 9001]);

        $log = MealItemLog::query()->firstOrFail();
        $this->assertSame($child->id, $log->child_id);
        $this->assertSame($parent->id, $log->user_id);
        $this->assertSame(42, $log->meal_id);
        $this->assertSame(9001, $log->food_id);
        $this->assertSame('2026-10-17', $log->log_date->toDateString());
        $this->assertFalse($log->is_consumed);
        $this->assertNull($log->consumed_at);
        $this->assertSame(1, MealItemLog::count());
    }
}
