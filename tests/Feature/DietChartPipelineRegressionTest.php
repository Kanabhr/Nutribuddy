<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DeficiencyRule;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\QuestionnaireAnswer;
use App\Models\NutritionLead;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\ChildProfileService;
use App\Services\Nutrition\DietChartGeneratorService;
use App\Services\Nutrition\RiskFlaggingEngine;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DietChartPipelineRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed questionnaire_questions for question IDs
        for ($i = 1; $i <= 35; $i++) {
            DB::table('questionnaire_questions')->insertOrIgnore([
                'id' => $i,
                'question' => 'Question '.$i,
                'model' => 'basic',
                'section' => 'basic',
                'answer_type' => 'text',
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_parent_can_submit_nutrition_questionnaire_via_api(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $parent = User::factory()->create();

        $payload = [
            'q_1' => 'Aarav Sharma',
            'q_2' => '2019-05-15',
            'q_3' => 'boy',
            'q_4' => 18.5,
            'q_5' => '105 cm',
            'q_6' => 'vegetarian',
            'q_7' => 'None',
            'q_8' => 'North India',
            'plan_slug' => 'premium',
        ];

        $response = $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure(['success', 'child_id', 'assessment_id']);

        $this->assertDatabaseHas('children', [
            'name' => 'Aarav Sharma',
            'sex' => 'boy',
            'user_id' => $parent->id,
        ]);
    }

    public function test_questionnaire_profile_fields_follow_question_order_not_database_ids(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $parent = User::factory()->create();
        $questions = [
            101 => [1, 'Child name'],
            102 => [2, 'Date of birth'],
            103 => [3, 'Sex'],
            104 => [4, 'Weight'],
            105 => [5, 'Height'],
            106 => [6, 'Diet type'],
            107 => [7, 'Allergies'],
            108 => [8, 'Region'],
        ];
        foreach ($questions as $id => [$sortOrder, $label]) {
            DB::table('questionnaire_questions')->insert([
                'id' => $id,
                'question' => $label,
                'model' => '3 Pro',
                'section' => 'profile',
                'answer_type' => 'text',
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'q_101' => 'Mira Sharma',
            'q_102' => '2018-04-12',
            'q_103' => 'girl',
            'q_104' => '21.5',
            'q_105' => '118 cm',
            'q_106' => 'vegan',
            'q_107' => 'nuts',
            'q_108' => 'South India',
            'plan_slug' => 'pro',
        ])->assertOk();

        $child = Child::where('user_id', $parent->id)->firstOrFail();
        $assessment = ChildAssessment::where('child_id', $child->id)->firstOrFail();

        $this->assertSame('Mira Sharma', $child->name);
        $this->assertSame('girl', $child->sex);
        $this->assertSame('2018-04-12', $child->dob->toDateString());
        $this->assertSame(21.5, (float) $assessment->current_weight_kg);
        $this->assertSame(118.0, (float) $assessment->current_height_cm);
        $this->assertSame('vegan', $assessment->diet_type);
        $this->assertSame('nuts', $assessment->allergies);
        $this->assertSame('South India', $assessment->region);
    }

    public function test_region_cuisine_question_accepts_and_normalizes_multiple_regions(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $questions = [
            1 => ['Child name', 'Text', null, false],
            2 => ['Date of birth', 'Date', null, false],
            3 => ['Sex', 'Choice', 'Boy / Girl', false],
            4 => ['Current weight', 'Number (kg)', null, false],
            5 => ['Current height', 'Number (cm)', null, true],
            6 => ['Diet type', 'Choice', 'Veg / Vegan / Non-Veg / Eggetarian / Jain', false],
            7 => ['Allergies & intolerances', 'Multi', 'Dairy / Nuts / Gluten / Egg / None', false],
            8 => ['Region / cuisine liked', 'Choice', 'North / West / East / South / No preference', false],
        ];
        foreach ($questions as $order => [$label, $type, $options, $optional]) {
            DB::table('questionnaire_questions')->where('id', $order)->update([
                'question' => $label,
                'model' => '1 Basic',
                'section' => 'Food Frame',
                'answer_type' => $type,
                'options_scale' => $options,
                'only_ask_if' => 'always',
                'is_optional' => $optional,
            ]);
        }

        $parent = User::factory()->create();
        $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug' => 'basic',
            'q_1' => 'Mira',
            'q_2' => '2018-04-12',
            'q_3' => 'Girl',
            'q_4' => 21,
            'q_6' => 'Veg',
            'q_7' => ['None'],
            'q_8' => ['North', 'South'],
        ])->assertOk();

        $assessment = ChildAssessment::query()->firstOrFail();
        $this->assertSame('North, South', $assessment->region);
        $this->assertSame(['North', 'South'], QuestionnaireAnswer::query()
            ->where('child_assessment_id', $assessment->id)
            ->where('question_id', 8)
            ->firstOrFail()
            ->answer_value);

        $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug' => 'basic',
            'q_8' => ['No preference', 'North'],
        ])->assertUnprocessable()->assertJsonValidationErrors('q_8');

        $profile = app(ChildProfileService::class)->normalize($assessment, [8 => ['West', 'South']]);
        $this->assertSame('West, South', $profile['region']);
        $this->assertSame('West, South', $assessment->fresh()->region);
    }

    public function test_visible_signs_are_normalized_and_create_repeat_safe_clinical_flags(): void
    {
        $parent = User::factory()->create();
        $child = Child::create(['user_id' => $parent->id, 'name' => 'Mira', 'sex' => 'girl']);
        $assessment = ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'basic',
            'current_weight_kg' => 20,
            'current_height_cm' => 110,
            'diet_type' => 'vegetarian',
        ]);
        DeficiencyRule::create(['sign' => 'Pale eyes/skin', 'likely_gap' => 'Iron risk']);

        $profile = app(ChildProfileService::class)->normalize($assessment, [
            26 => ['Pale eyes/skin', 'None', 'Pale eyes/skin'],
        ]);

        $this->assertSame(['Pale eyes/skin'], $profile['visible_signs']);
        $engine = app(RiskFlaggingEngine::class);
        $first = $engine->generateFlags($assessment, $profile);
        $second = $engine->generateFlags($assessment, $profile);

        $this->assertCount(1, $first);
        $this->assertCount(1, $second);
        $this->assertDatabaseCount('clinical_flags', 1);
    }

    public function test_guest_cannot_submit_a_questionnaire(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->postJson(route('api.nutrition.questionnaire.submit'), ['q_1' => 'Guest Child'])
            ->assertForbidden();

        $this->assertDatabaseCount('children', 0);
    }

    public function test_invalid_question_id_is_rejected_before_writes(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'q_1' => 'Aarav',
            'q_9999' => 'unknown question',
        ])->assertUnprocessable()->assertJsonValidationErrors('q_9999');

        $this->assertDatabaseCount('children', 0);
        $this->assertDatabaseCount('child_assessments', 0);
    }

    public function test_failed_questionnaire_save_rolls_back_and_hides_exception_details(): void
    {
        $parent = User::factory()->create();
        $profiles = \Mockery::mock(ChildProfileService::class);
        $profiles->shouldReceive('heightInCentimeters')->andThrow(new \RuntimeException('internal database detail'));
        $this->app->instance(ChildProfileService::class, $profiles);

        $response = $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'q_1' => 'Aarav',
            'q_2' => '2019-05-15',
            'q_3' => 'boy',
            'q_5' => '105 cm',
        ]);

        $response->assertInternalServerError()
            ->assertJsonMissing(['message' => 'internal database detail'])
            ->assertJsonPath('message', 'Unable to save the assessment. Please try again.');
        $this->assertDatabaseCount('children', 0);
        $this->assertDatabaseCount('child_assessments', 0);
    }

    public function test_admin_with_permission_can_view_nutrition_leads_index(): void
    {
        Permission::create(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Diet Supervisor', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $customer = User::factory()->create();
        $child = Child::create(['user_id' => $customer->id, 'name' => 'Rohan', 'sex' => 'boy']);

        $plan = SubscriptionPlan::create([
            'name' => 'Monthly Care',
            'slug' => 'monthly-care',
            'price' => 999,
            'monthly_price' => 999,
            'is_active' => true,
        ]);

        $sub = DietSubscription::create([
            'user_id' => $customer->id,
            'subscription_plan_id' => $plan->id,
            'model' => 'Basic',
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 999,
        ]);

        NutritionLead::create([
            'user_id' => $customer->id,
            'child_id' => $child->id,
            'diet_subscription_id' => $sub->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.nutrition.leads.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.nutrition.leads.index');
        $response->assertViewHas('leads');
    }

    public function test_admin_can_assign_lead_to_nutritionist(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        Permission::create(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Admin Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $nutritionist = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $customer = User::factory()->create();
        $child = Child::create(['user_id' => $customer->id, 'name' => 'Ananya', 'sex' => 'girl']);

        $plan = SubscriptionPlan::create([
            'name' => 'Annual Care',
            'slug' => 'annual-care',
            'price' => 2999,
            'monthly_price' => 2999,
            'is_active' => true,
        ]);

        $sub = DietSubscription::create([
            'user_id' => $customer->id,
            'subscription_plan_id' => $plan->id,
            'model' => 'Basic',
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 2999,
        ]);

        $lead = NutritionLead::create([
            'user_id' => $customer->id,
            'child_id' => $child->id,
            'diet_subscription_id' => $sub->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')->patch(route('admin.nutrition.leads.assign', $lead), [
            'assigned_to' => $nutritionist->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nutrition_leads', [
            'id' => $lead->id,
            'assigned_to' => $nutritionist->id,
            'status' => 'assigned',
        ]);
    }

    public function test_nutritionist_can_view_assigned_lead_queue(): void
    {
        Permission::create(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');

        $nutritionist = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $nutritionist->assignRole($role);

        $customer = User::factory()->create();
        $child = Child::create(['user_id' => $customer->id, 'name' => 'Kabir', 'sex' => 'boy']);

        $plan = SubscriptionPlan::create([
            'name' => 'Quarterly Care',
            'slug' => 'quarterly-care',
            'price' => 1999,
            'monthly_price' => 1999,
            'is_active' => true,
        ]);

        $sub = DietSubscription::create([
            'user_id' => $customer->id,
            'subscription_plan_id' => $plan->id,
            'model' => 'Basic',
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 1999,
        ]);

        $lead = NutritionLead::create([
            'user_id' => $customer->id,
            'child_id' => $child->id,
            'diet_subscription_id' => $sub->id,
            'assigned_to' => $nutritionist->id,
            'status' => 'assigned',
        ]);

        $assessment = ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'basic',
            'current_weight_kg' => 15,
            'current_height_cm' => 95,
            'diet_type' => 'vegetarian',
        ]);
        DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status' => 'pending',
            'review_status' => 'pending_review',
        ]);

        $response = $this->actingAs($nutritionist, 'admin')->get(route('admin.nutrition.lead-queue.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.nutrition.leads.queue');
        $response->assertViewHas('leads');

        $this->actingAs($nutritionist, 'admin')
            ->get(route('admin.nutrition.lead-queue.show', $lead))
            ->assertOk()
            ->assertViewIs('admin.nutrition.leads.show')
            ->assertViewHas(['lead', 'dietPlan', 'foodsByMeal', 'mealsByDay', 'products']);
    }

    public function test_nutritionist_approval_updates_chart_and_lead_together(): void
    {
        Permission::create(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Chart Reviewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');
        $nutritionist = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $nutritionist->assignRole($role);

        $customer = User::factory()->create();
        $child = Child::create(['user_id' => $customer->id, 'name' => 'Mira', 'sex' => 'girl']);
        $subscriptionPlan = SubscriptionPlan::create([
            'name' => 'Review Plan',
            'slug' => 'review-plan',
            'price' => 100,
            'monthly_price' => 100,
            'is_active' => true,
        ]);
        $subscription = DietSubscription::create([
            'user_id' => $customer->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'model' => 'Basic',
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 100,
        ]);
        $lead = NutritionLead::create([
            'user_id' => $customer->id,
            'child_id' => $child->id,
            'diet_subscription_id' => $subscription->id,
            'assigned_to' => $nutritionist->id,
            'status' => 'awaiting_review',
        ]);
        $assessment = ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'basic',
            'current_weight_kg' => 15,
            'current_height_cm' => 95,
            'diet_type' => 'vegetarian',
        ]);
        $dietPlan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status' => 'pending',
            'review_status' => 'pending_review',
        ]);

        $this->actingAs($nutritionist, 'admin')
            ->patch(route('admin.nutrition.lead-queue.approve', [$lead, $dietPlan]), [
                'review_notes' => 'Reviewed for release.',
            ])
            ->assertRedirect(route('admin.nutrition.lead-queue.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('diet_plans', [
            'id' => $dietPlan->id,
            'status' => 'active',
            'review_status' => 'approved',
            'reviewed_by' => $nutritionist->id,
            'review_notes' => 'Reviewed for release.',
        ]);
        $this->assertDatabaseHas('nutrition_leads', [
            'id' => $lead->id,
            'status' => 'completed',
        ]);
    }

    public function test_parent_can_view_child_diet_chart_when_assessment_exists(): void
    {
        $parent = User::factory()->create();
        $plan = SubscriptionPlan::create([
            'name' => 'Regeneration Plan',
            'slug' => 'regeneration-plan',
            'price' => 100,
            'monthly_price' => 100,
            'is_active' => true,
        ]);
        $child = Child::create([
            'user_id' => $parent->id,
            'name' => 'Pooja',
            'sex' => 'girl',
        ]);

        ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'basic',
            'current_weight_kg' => 15.0,
            'current_height_cm' => 95.0,
            'diet_type' => 'vegetarian',
        ]);
        DietSubscription::create([
            'user_id' => $parent->id,
            'child_id' => $child->id,
            'subscription_plan_id' => $plan->id,
            'model' => 'Basic',
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 100,
            'plans_remaining' => 4,
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($parent)->get(route('child.diet-chart.show', $child));

        $response->assertStatus(200);
        $response->assertViewIs('child-profile.diet-chart-display');
        $response->assertViewHas(['child', 'assessment', 'hasPlan']);
        $response->assertViewHas('purchasedPlans', 4);
    }

    public function test_regenerating_a_diet_chart_consumes_one_paid_child_quota(): void
    {
        [$parent, $child, $subscription] = $this->createChildWithRegenerationQuota(1);
        $generator = \Mockery::mock(DietChartGeneratorService::class);
        $generator->shouldReceive('generateDietChart')->once()->andReturn(['success' => true]);
        $this->app->instance(DietChartGeneratorService::class, $generator);

        $this->actingAs($parent)
            ->post(route('child.diet-chart.regenerate', $child))
            ->assertRedirect(route('child-profile'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('diet_subscriptions', [
            'id' => $subscription->id,
            'plans_remaining' => 0,
        ]);
    }

    public function test_failed_diet_chart_regeneration_restores_the_quota(): void
    {
        [$parent, $child, $subscription] = $this->createChildWithRegenerationQuota(1);
        $generator = \Mockery::mock(DietChartGeneratorService::class);
        $generator->shouldReceive('generateDietChart')->once()->andReturn(['success' => false]);
        $this->app->instance(DietChartGeneratorService::class, $generator);

        $this->actingAs($parent)
            ->post(route('child.diet-chart.regenerate', $child))
            ->assertRedirect(route('child-profile'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('diet_subscriptions', [
            'id' => $subscription->id,
            'plans_remaining' => 1,
        ]);
    }

    /** @return array{User, Child, DietSubscription} */
    private function createChildWithRegenerationQuota(int $quota): array
    {
        $parent = User::factory()->create();
        $child = Child::create([
            'user_id' => $parent->id,
            'name' => 'Test Child',
            'sex' => 'boy',
        ]);
        ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'basic',
            'current_weight_kg' => 15,
            'current_height_cm' => 95,
            'diet_type' => 'vegetarian',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Regeneration Plan',
            'slug' => 'regeneration-plan-'.$quota,
            'price' => 100,
            'monthly_price' => 100,
            'is_active' => true,
        ]);
        $subscription = DietSubscription::create([
            'user_id' => $parent->id,
            'child_id' => $child->id,
            'subscription_plan_id' => $plan->id,
            'model' => 'Basic',
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 100,
            'plans_remaining' => $quota,
            'expires_at' => now()->addMonth(),
        ]);

        return [$parent, $child, $subscription];
    }
}
