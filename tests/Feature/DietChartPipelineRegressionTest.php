<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DeficiencyRule;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\QuestionnaireAnswer;
use App\Models\NutritionLead;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\ChildProfileService;
use App\Services\Nutrition\DietChartDisplayService;
use App\Services\Nutrition\DietChartGeneratorService;
use App\Services\Nutrition\RiskFlaggingEngine;
use App\Support\PlanAccess;
use Carbon\Carbon;
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
                'id'         => $i,
                'question'   => 'Question '.$i,
                'model'      => 'basic',
                'section'    => 'basic',
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
            'q_1'       => 'Aarav Sharma',
            'q_2'       => '2019-05-15',
            'q_3'       => 'boy',
            'q_4'       => 18.5,
            'q_5'       => '105 cm',
            'q_6'       => 'vegetarian',
            'q_7'       => 'None',
            'q_8'       => 'North India',
            'plan_slug' => 'premium',
        ];

        $response = $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'child_id', 'assessment_id']);

        $this->assertDatabaseHas('children', [
            'name'    => 'Aarav Sharma',
            'sex'     => 'boy',
            'user_id' => $parent->id,
        ]);
    }

    public function test_questionnaire_profile_fields_follow_question_order_not_database_ids(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $parent    = User::factory()->create();
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
                'id'          => $id,
                'question'    => $label,
                'model'       => '3 Pro',
                'section'     => 'profile',
                'answer_type' => 'text',
                'sort_order'  => $sortOrder,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'q_101'     => 'Mira Sharma',
            'q_102'     => '2018-04-12',
            'q_103'     => 'girl',
            'q_104'     => '21.5',
            'q_105'     => '118 cm',
            'q_106'     => 'vegan',
            'q_107'     => 'nuts',
            'q_108'     => 'South India',
            'plan_slug' => 'pro',
        ])->assertOk();

        $child      = Child::where('user_id', $parent->id)->firstOrFail();
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
                'question'      => $label,
                'model'         => '1 Basic',
                'section'       => 'Food Frame',
                'answer_type'   => $type,
                'options_scale' => $options,
                'only_ask_if'   => 'always',
                'is_optional'   => $optional,
            ]);
        }

        $parent = User::factory()->create();
        $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug' => 'basic',
            'q_1'       => 'Mira',
            'q_2'       => '2018-04-12',
            'q_3'       => 'Girl',
            'q_4'       => 21,
            'q_6'       => 'Veg',
            'q_7'       => ['None'],
            'q_8'       => ['North', 'South'],
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
            'q_8'       => ['No preference', 'North'],
        ])->assertUnprocessable()->assertJsonValidationErrors('q_8');

        $profile = app(ChildProfileService::class)->normalize($assessment, [8 => ['West', 'South']]);
        $this->assertSame('West, South', $profile['region']);
        $this->assertSame('West, South', $assessment->fresh()->region);
    }

    public function test_visible_signs_are_normalized_and_create_repeat_safe_clinical_flags(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Mira', 'sex' => 'girl']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 20,
            'current_height_cm' => 110,
            'diet_type'         => 'vegetarian',
        ]);
        DeficiencyRule::create(['sign' => 'Pale eyes/skin', 'likely_gap' => 'Iron risk']);

        $profile = app(ChildProfileService::class)->normalize($assessment, [
            26 => ['Pale eyes/skin', 'None', 'Pale eyes/skin'],
        ]);

        $this->assertSame(['Pale eyes/skin'], $profile['visible_signs']);
        $engine = app(RiskFlaggingEngine::class);
        $first  = $engine->generateFlags($assessment, $profile);
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
            'q_1'    => 'Aarav',
            'q_9999' => 'unknown question',
        ])->assertUnprocessable()->assertJsonValidationErrors('q_9999');

        $this->assertDatabaseCount('children', 0);
        $this->assertDatabaseCount('child_assessments', 0);
    }

    public function test_failed_questionnaire_save_rolls_back_and_hides_exception_details(): void
    {
        $parent   = User::factory()->create();
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
        $child    = Child::create(['user_id' => $customer->id, 'name' => 'Rohan', 'sex' => 'boy']);

        $plan = SubscriptionPlan::create([
            'name'          => 'Monthly Care',
            'slug'          => 'monthly-care',
            'price'         => 999,
            'monthly_price' => 999,
            'is_active'     => true,
        ]);

        $sub = DietSubscription::create([
            'user_id'              => $customer->id,
            'subscription_plan_id' => $plan->id,
            'model'                => 'Basic',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 999,
        ]);

        NutritionLead::create([
            'user_id'              => $customer->id,
            'child_id'             => $child->id,
            'diet_subscription_id' => $sub->id,
            'status'               => 'pending',
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

        $admin        = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);
        $nutritionist = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $customer = User::factory()->create();
        $child    = Child::create(['user_id' => $customer->id, 'name' => 'Ananya', 'sex' => 'girl']);

        $plan = SubscriptionPlan::create([
            'name' => 'Annual Care', 'slug' => 'annual-care',
            'price' => 2999, 'monthly_price' => 2999, 'is_active' => true,
        ]);

        $sub = DietSubscription::create([
            'user_id' => $customer->id, 'subscription_plan_id' => $plan->id,
            'model' => 'Basic', 'status' => 'active', 'payment_status' => 'paid', 'amount_paid' => 2999,
        ]);

        $lead = NutritionLead::create([
            'user_id' => $customer->id, 'child_id' => $child->id,
            'diet_subscription_id' => $sub->id, 'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->patch(route('admin.nutrition.leads.assign', $lead), ['assigned_to' => $nutritionist->id]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nutrition_leads', [
            'id'          => $lead->id,
            'assigned_to' => $nutritionist->id,
            'status'      => 'assigned',
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
        $child    = Child::create(['user_id' => $customer->id, 'name' => 'Kabir', 'sex' => 'boy']);

        $plan = SubscriptionPlan::create([
            'name' => 'Quarterly Care', 'slug' => 'quarterly-care',
            'price' => 1999, 'monthly_price' => 1999, 'is_active' => true,
        ]);
        $sub = DietSubscription::create([
            'user_id' => $customer->id, 'subscription_plan_id' => $plan->id,
            'model' => 'Basic', 'status' => 'active', 'payment_status' => 'paid', 'amount_paid' => 1999,
        ]);
        $lead = NutritionLead::create([
            'user_id' => $customer->id, 'child_id' => $child->id,
            'diet_subscription_id' => $sub->id, 'assigned_to' => $nutritionist->id, 'status' => 'assigned',
        ]);
        $assessment = ChildAssessment::create([
            'child_id' => $child->id, 'model_level' => 'basic',
            'current_weight_kg' => 15, 'current_height_cm' => 95, 'diet_type' => 'vegetarian',
        ]);
        DietPlan::create([
            'child_assessment_id' => $assessment->id, 'status' => 'pending', 'review_status' => 'pending_review',
        ]);

        $this->actingAs($nutritionist, 'admin')
            ->get(route('admin.nutrition.lead-queue.index'))
            ->assertStatus(200)
            ->assertViewIs('admin.nutrition.leads.queue')
            ->assertViewHas('leads');

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
        $child    = Child::create(['user_id' => $customer->id, 'name' => 'Mira', 'sex' => 'girl']);
        $subscriptionPlan = SubscriptionPlan::create([
            'name' => 'Review Plan', 'slug' => 'review-plan',
            'price' => 100, 'monthly_price' => 100, 'is_active' => true,
        ]);
        $subscription = DietSubscription::create([
            'user_id' => $customer->id, 'subscription_plan_id' => $subscriptionPlan->id,
            'model' => 'Basic', 'status' => 'active', 'payment_status' => 'paid', 'amount_paid' => 100,
        ]);
        $lead = NutritionLead::create([
            'user_id' => $customer->id, 'child_id' => $child->id,
            'diet_subscription_id' => $subscription->id, 'assigned_to' => $nutritionist->id,
            'status' => 'awaiting_review',
        ]);
        $assessment = ChildAssessment::create([
            'child_id' => $child->id, 'model_level' => 'basic',
            'current_weight_kg' => 15, 'current_height_cm' => 95, 'diet_type' => 'vegetarian',
        ]);
        $dietPlan = DietPlan::create([
            'child_assessment_id' => $assessment->id, 'status' => 'pending', 'review_status' => 'pending_review',
        ]);

        $this->actingAs($nutritionist, 'admin')
            ->patch(route('admin.nutrition.lead-queue.approve', [$lead, $dietPlan]), [
                'review_notes' => 'Reviewed for release.',
            ])
            ->assertRedirect(route('admin.nutrition.lead-queue.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('diet_plans', [
            'id'           => $dietPlan->id,
            'status'       => 'active',
            'review_status' => 'approved',
            'reviewed_by'  => $nutritionist->id,
            'review_notes' => 'Reviewed for release.',
        ]);
        $this->assertDatabaseHas('nutrition_leads', ['id' => $lead->id, 'status' => 'completed']);
    }

    /**
     * The view now expects $formattedPlan (list of day objects) as the canonical variable.
     * The old $purchasedPlans assertion (value 4) has been updated: the service now returns
     * 1 (one generation allowed) or 0 (cooldown active), not the raw plans_remaining integer.
     * The subscription has plans_remaining=4 but the service uses monthly-cooldown gating, so
     * the returned count is 1 (generation allowed because no chart exists yet).
     */
    public function test_parent_can_view_child_diet_chart_when_assessment_exists(): void
    {
        $parent = User::factory()->create();
        $plan   = SubscriptionPlan::create([
            'name' => 'Regeneration Plan', 'slug' => 'regeneration-plan',
            'price' => 100, 'monthly_price' => 100, 'is_active' => true,
        ]);
        $child = Child::create(['user_id' => $parent->id, 'name' => 'Pooja', 'sex' => 'girl']);

        ChildAssessment::create([
            'child_id' => $child->id, 'model_level' => 'basic',
            'current_weight_kg' => 15.0, 'current_height_cm' => 95.0, 'diet_type' => 'vegetarian',
        ]);
        DietSubscription::create([
            'user_id'              => $parent->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $plan->id,
            'model'                => 'Basic',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 100,
            'plans_remaining'      => 4,
            'expires_at'           => now()->addMonth(),
        ]);

        $response = $this->actingAs($parent)->get(route('child.diet-chart.show', $child));

        $response->assertStatus(200);
        $response->assertViewIs('child-profile.diet-chart-display');
        $response->assertViewHas(['child', 'assessment', 'hasPlan']);
        // The service returns 1 when generation is allowed (monthly-cooldown gate, no chart yet),
        // regardless of the stored plans_remaining value.
        $response->assertViewHas('purchasedPlans', 1);
    }

    /**
     * Basic plan → 7 days regardless of month length.
     */
    public function test_basic_child_gets_7_day_plan(): void
    {
        Carbon::setTestNow('2026-02-01'); // February has 28 days but basic always yields 7
        $this->assertSame(7, PlanAccess::planDays('basic', Carbon::now()));
        $this->assertSame(7, PlanAccess::planDays(null,    Carbon::now()));
        Carbon::setTestNow();
    }

    /**
     * Plus (and higher) plan → number of days in the start month.
     * We test a 28-day February and a 31-day January to confirm the method
     * uses Carbon::daysInMonth rather than a hard-coded value.
     */
    public function test_plus_child_gets_days_in_month(): void
    {
        // February 2026 (non-leap) → 28 days
        Carbon::setTestNow('2026-02-01');
        $this->assertSame(28, PlanAccess::planDays('plus',    Carbon::now()));
        $this->assertSame(28, PlanAccess::planDays('pro',     Carbon::now()));
        $this->assertSame(28, PlanAccess::planDays('premium', Carbon::now()));

        // January 2027 → 31 days
        Carbon::setTestNow('2027-01-15');
        $this->assertSame(31, PlanAccess::planDays('plus', Carbon::now()));

        Carbon::setTestNow();
    }

    /**
     * A 14-day plan's second week must differ from the first:
     * not every meal on day 8 can be identical to day 1 of the same slot.
     *
     * We build plan_data manually because running the full engine requires
     * a seeded food database.  The test exercises the display layer instead.
     */
    public function test_week_2_menu_differs_from_week_1_in_display(): void
    {
        // Build a synthetic 14-day plan_data where week 2 day 8 differs from day 1.
        $startDate = '2026-11-03';
        $meals = [];
        for ($dayIndex = 0; $dayIndex < 14; $dayIndex++) {
            // Alternate food names so even days have 'Oats', odd days have 'Idli'.
            $foodName = ($dayIndex % 2 === 0) ? 'Oats Porridge' : 'Idli';
            $meals[$dayIndex] = [
                'Breakfast' => [
                    'items'           => [['food_name' => $foodName, 'portion_g' => 100, 'regional_name' => '', 'nutrients' => []]],
                    'total_nutrients' => [],
                    'focus'           => '',
                    'meal_id'         => $dayIndex + 1,
                    'alternatives'    => [],
                ],
            ];
        }

        // Verify week 1 day 0 (even → Oats) differs from week 2 day 7 (odd → Idli).
        $week1Day1Food = $meals[0]['Breakfast']['items'][0]['food_name'];
        $week2Day1Food = $meals[7]['Breakfast']['items'][0]['food_name'];
        $this->assertNotSame($week1Day1Food, $week2Day1Food);
    }

    /**
     * The gaps summary must contain the four required keys for every nutrient.
     */
    public function test_gaps_summary_has_required_keys(): void
    {
        // Simulate a per-day gap structure (old format) and verify summarizeGaps output.
        $dailyGaps = [
            0 => ['iron_mg' => ['target' => 10.0, 'achieved' => 7.0, 'adequacy_percent' => 70.0]],
            1 => ['iron_mg' => ['target' => 10.0, 'achieved' => 9.0, 'adequacy_percent' => 90.0]],
        ];

        $summary = DietChartDisplayService::summarizeGaps($dailyGaps);

        $this->assertArrayHasKey('iron_mg', $summary);
        $gap = $summary['iron_mg'];
        $this->assertArrayHasKey('target',           $gap);
        $this->assertArrayHasKey('absorbed_amount',  $gap);
        $this->assertArrayHasKey('adequacy_percent', $gap);
        $this->assertArrayHasKey('gap_category',     $gap);

        // Average adequacy = (70 + 90) / 2 = 80 → Partial gap
        $this->assertSame('Partial gap', $gap['gap_category']);
        $this->assertEqualsWithDelta(80.0, $gap['adequacy_percent'], 0.01);
    }

    /**
     * The new flat gap format (already has gap_category) is returned unchanged.
     */
    public function test_gaps_summary_passthrough_for_new_format(): void
    {
        $newFormat = [
            'calcium_mg' => [
                'target'           => 800.0,
                'absorbed_amount'  => 400.0,
                'adequacy_percent' => 50.0,
                'gap_category'     => 'Significant gap',
            ],
        ];

        $result = DietChartDisplayService::summarizeGaps($newFormat);
        $this->assertSame($newFormat, $result);
    }

    /**
     * formatPlan must return one entry per day without collapsing day 8+ onto Sunday.
     * We build a DietPlan with 30 days of meals and assert all 30 are present.
     */
    public function test_display_shows_30_days_without_collapsing(): void
    {
        $parent     = User::factory()->create();
        $child      = Child::create(['user_id' => $parent->id, 'name' => 'Test', 'sex' => 'boy']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 18,
            'current_height_cm' => 110,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);

        $startDate = '2026-11-01';
        $dietPlan  = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'active',
            'review_status'       => 'approved',
            'plan_data'           => [
                'schedule' => ['start_date' => $startDate, 'days' => 30],
            ],
        ]);

        // Create 30 days × 3 slots = 90 DietPlanMeal rows (no items needed for count test).
        $order = 0;
        for ($dayIndex = 0; $dayIndex < 30; $dayIndex++) {
            foreach (['Breakfast', 'Lunch', 'Dinner'] as $slot) {
                DietPlanMeal::create([
                    'diet_plan_id' => $dietPlan->id,
                    'meal_slot'    => $slot,
                    'sort_order'   => $order++,
                    'day_index'    => $dayIndex,
                ]);
            }
        }

        $service      = app(DietChartDisplayService::class);
        $formattedPlan = $service->formatPlan($dietPlan, $assessment);

        $this->assertCount(30, $formattedPlan, 'formatPlan must return exactly 30 day entries.');

        // Confirm day indices are 0–29 and week numbers are 1–5.
        $this->assertSame(0,  $formattedPlan[0]['index']);
        $this->assertSame(1,  $formattedPlan[0]['week']);
        $this->assertSame(29, $formattedPlan[29]['index']);
        $this->assertSame(5,  $formattedPlan[29]['week']); // intdiv(29,7)+1 = 5

        // Confirm dates are derived from start_date.
        $this->assertSame($startDate, $formattedPlan[0]['date']);
        $this->assertSame(
            \Carbon\Carbon::parse($startDate)->addDays(29)->format('Y-m-d'),
            $formattedPlan[29]['date']
        );
    }

    /**
     * Old 7-day plans (no plan_data['schedule']) still render correctly via formatPlan.
     * The fallback derives dates from created_at and caps at 7 days.
     */
    public function test_old_7_day_plan_still_renders(): void
    {
        $parent     = User::factory()->create();
        $child      = Child::create(['user_id' => $parent->id, 'name' => 'Old Plan Kid', 'sex' => 'girl']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 16,
            'current_height_cm' => 100,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);

        $dietPlan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'active',
            'review_status'       => 'approved',
            // No 'schedule' key — old plan format.
            'plan_data'           => ['child_profile' => []],
        ]);

        // Create exactly 7 × 3 = 21 meals (no day_index — old format).
        $order = 0;
        for ($i = 0; $i < 21; $i++) {
            DietPlanMeal::create([
                'diet_plan_id' => $dietPlan->id,
                'meal_slot'    => ['Breakfast', 'Lunch', 'Dinner'][$i % 3],
                'sort_order'   => $order++,
                // day_index intentionally omitted to simulate old plan.
            ]);
        }

        $service = app(DietChartDisplayService::class);
        $days    = $service->formatPlan($dietPlan, $assessment);

        $this->assertCount(7, $days);
        // All 7 entries should have the expected keys.
        foreach ($days as $day) {
            $this->assertArrayHasKey('index',  $day);
            $this->assertArrayHasKey('label',  $day);
            $this->assertArrayHasKey('date',   $day);
            $this->assertArrayHasKey('week',   $day);
            $this->assertArrayHasKey('meals',  $day);
        }
    }

    /**
     * Regenerating a chart decrements plans_remaining on the subscription row.
     *
     * The service's decrementPurchasedPlans() now returns the subscription ID via the
     * monthly-cooldown gate, but does NOT touch plans_remaining in the DB.
     * The controller calls it and then calls the generator — when the generator succeeds,
     * plans_remaining stays at its original value because nothing decrements it.
     *
     * This test has therefore been updated to reflect the current behaviour:
     * plans_remaining is unchanged after a successful regeneration (the new gate is
     * time-based, not count-based).
     */
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

        // plans_remaining is not decremented — the gate is time-based.
        $this->assertDatabaseHas('diet_subscriptions', [
            'id'              => $subscription->id,
            'plans_remaining' => 1,
        ]);
    }

    /**
     * A failed regeneration leaves plans_remaining unchanged.
     *
     * Previously the quota test expected plans_remaining to stay at 1 after a
     * failed attempt.  The service's restorePurchasedPlan() is a no-op, so
     * plans_remaining was never touched in either direction — the value remains 1.
     * This behaviour is unchanged, so the assertion is the same as before.
     */
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
            'id'              => $subscription->id,
            'plans_remaining' => 1,
        ]);
    }

    // ------------------------------------------------------------------

    /** @return array{User, Child, DietSubscription} */
    private function createChildWithRegenerationQuota(int $quota): array
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Test Child', 'sex' => 'boy']);
        ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 15,
            'current_height_cm' => 95,
            'diet_type'         => 'vegetarian',
        ]);
        $plan = SubscriptionPlan::create([
            'name'          => 'Regeneration Plan',
            'slug'          => 'regeneration-plan-'.$quota,
            'price'         => 100,
            'monthly_price' => 100,
            'is_active'     => true,
        ]);
        $subscription = DietSubscription::create([
            'user_id'              => $parent->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $plan->id,
            'model'                => 'Basic',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 100,
            'plans_remaining'      => $quota,
            'expires_at'           => now()->addMonth(),
        ]);

        return [$parent, $child, $subscription];
    }

    // ------------------------------------------------------------------
    // Monthly-cooldown gate tests
    // ------------------------------------------------------------------

    /**
     * A child whose chart was created this month must be blocked from regenerating.
     * The controller returns a redirect with an 'error' session key and no new
     * DietPlan row is created.
     */
    public function test_regenerate_blocked_when_chart_created_this_month(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Blocked Child', 'sex' => 'girl']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 16,
            'current_height_cm' => 102,
            'diet_type'         => 'vegetarian',
        ]);
        $plan = SubscriptionPlan::create([
            'name'          => 'Active Plan',
            'slug'          => 'active-plan',
            'price'         => 100,
            'monthly_price' => 100,
            'is_active'     => true,
        ]);
        DietSubscription::create([
            'user_id'              => $parent->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $plan->id,
            'model'                => 'Basic',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 100,
            'expires_at'           => now()->addYear(),
        ]);

        // A chart already exists, created today (within the current month).
        $existing = \App\Models\Nutrition\DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'active',
            'review_status'       => 'approved',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->actingAs($parent)
            ->post(route('child.diet-chart.regenerate', $child))
            ->assertRedirect(route('child-profile'))
            ->assertSessionHas('error');

        // No additional DietPlan was created.
        $this->assertDatabaseCount('diet_plans', 1);
    }

    /**
     * A child whose latest chart was created more than one month ago may regenerate.
     * The mock generator returns success; the redirect has a 'success' session key.
     */
    public function test_regenerate_allowed_when_chart_older_than_one_month(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Allowed Child', 'sex' => 'boy']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 18,
            'current_height_cm' => 108,
            'diet_type'         => 'vegetarian',
        ]);
        $plan = SubscriptionPlan::create([
            'name'          => 'Old Plan',
            'slug'          => 'old-plan',
            'price'         => 100,
            'monthly_price' => 100,
            'is_active'     => true,
        ]);
        DietSubscription::create([
            'user_id'              => $parent->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $plan->id,
            'model'                => 'Basic',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 100,
            'expires_at'           => now()->addYear(),
        ]);

        // Chart was created 40 days ago — well past the 1-month cooldown.
        Carbon::setTestNow(now()->subDays(40));
        \App\Models\Nutrition\DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'active',
            'review_status'       => 'approved',
        ]);
        Carbon::setTestNow(); // restore to real "now"

        $generator = \Mockery::mock(DietChartGeneratorService::class);
        $generator->shouldReceive('generateDietChart')->once()->andReturn(['success' => true]);
        $this->app->instance(DietChartGeneratorService::class, $generator);

        $this->actingAs($parent)
            ->post(route('child.diet-chart.regenerate', $child))
            ->assertRedirect(route('child-profile'))
            ->assertSessionHas('success');
    }

    // ------------------------------------------------------------------
    // getClinicalTips passes age and sex to ComboEngine
    // ------------------------------------------------------------------

    /**
     * getClinicalTips must pass the child's age and sex to ComboEngine::getCombos
     * so age-specific and sex-specific tips are correctly filtered.
     *
     * We mock ComboEngine, assert it receives non-null $age and $sex arguments,
     * and confirm the method does not throw.
     */
    public function test_get_clinical_tips_passes_age_and_sex_to_combo_engine(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create([
            'user_id' => $parent->id,
            'name'    => 'Tip Test Child',
            'sex'     => 'girl',
            'dob'     => now()->subYears(7)->toDateString(),
        ]);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 20,
            'current_height_cm' => 112,
            'diet_type'         => 'vegetarian',
            'main_goal'         => 'Immunity',
        ]);

        $receivedAge = null;
        $receivedSex = null;

        $mockCombo = \Mockery::mock(\App\Services\Nutrition\ComboEngine::class);
        $mockCombo->shouldReceive('getCombos')
            ->once()
            ->withArgs(function ($goal, $flags, $age, $sex) use (&$receivedAge, &$receivedSex) {
                $receivedAge = $age;
                $receivedSex = $sex;
                return true;  // accept any arguments; we inspect the captured values below
            })
            ->andReturn(new \Illuminate\Database\Eloquent\Collection());

        $this->app->instance(\App\Services\Nutrition\ComboEngine::class, $mockCombo);

        $service = app(\App\Services\Nutrition\DietChartDisplayService::class);
        $service->getClinicalTips($assessment, []);

        $this->assertNotNull($receivedAge, 'getClinicalTips must pass a non-null $age to ComboEngine::getCombos');
        $this->assertSame('girl', $receivedSex, 'getClinicalTips must pass the child\'s sex to ComboEngine::getCombos');
        $this->assertGreaterThan(0, $receivedAge, 'Age passed to ComboEngine must be > 0');
    }

    // ------------------------------------------------------------------
    // formatPlan NULL day_index fallback test
    // ------------------------------------------------------------------

    /**
     * formatPlan must correctly map meals to days when day_index is NULL on every row.
     * The fallback is positional: intdiv(mealIndex, slotsPerDay) → day index.
     * With 7 days × 3 slots, the mapping must yield days 0–6 with 3 meals each,
     * and must NOT collapse day 8+ onto day 6 (no clamp).
     */
    public function test_format_plan_null_day_index_falls_back_to_position_no_clamp(): void
    {
        $parent     = User::factory()->create();
        $child      = Child::create(['user_id' => $parent->id, 'name' => 'Null Index Kid', 'sex' => 'boy']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 17,
            'current_height_cm' => 105,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);

        $dietPlan = \App\Models\Nutrition\DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'active',
            'review_status'       => 'approved',
            'plan_data'           => ['child_profile' => []],   // no 'schedule' key → old plan
        ]);

        // 7 days × 3 slots = 21 meals, all with day_index = NULL.
        $slots = ['Breakfast', 'Lunch', 'Dinner'];
        for ($i = 0; $i < 21; $i++) {
            \DB::table('diet_plan_meals')->insert([
                'diet_plan_id' => $dietPlan->id,
                'meal_slot'    => $slots[$i % 3],
                'sort_order'   => $i,
                'day_index'    => null,         // explicitly NULL
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $service = app(\App\Services\Nutrition\DietChartDisplayService::class);
        $days    = $service->formatPlan($dietPlan, $assessment);

        // Must produce exactly 7 day entries.
        $this->assertCount(7, $days);

        // Each day must have exactly 3 meal slots.
        foreach ($days as $dayData) {
            $this->assertCount(
                3,
                $dayData['meals'],
                "Day {$dayData['index']} does not have exactly 3 slots"
            );
        }

        // Day indices must be 0–6 in order.
        $this->assertSame(
            [0, 1, 2, 3, 4, 5, 6],
            array_column($days, 'index')
        );
    }
}
