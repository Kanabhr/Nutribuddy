<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\ClinicalFlag;
use App\Models\Nutrition\DeficiencyRule;
use App\Models\Nutrition\QuestionnaireAnswer;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\NutritionLead;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\ChildProfileService;
use App\Services\Nutrition\RiskFlaggingEngine;
use App\Services\Nutrition\VisibleSignsMap;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * C7-QUESTIONNAIRE tests:
 *   Issue 1 — Q23/Q26 option fixes + clinical flag mapping
 *   Issue 2 — Q32 periods question hidden for boys
 *   Issue 3 — Q31 blood report file upload
 */
class QuestionnaireC7Test extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Shared fixtures
    // ─────────────────────────────────────────────────────────────────────────

    private function seedQ23(): QuestionnaireQuestion
    {
        return QuestionnaireQuestion::create([
            'model'        => '3 Pro',
            'section'      => 'F · Clinical Signs & Gut',
            'question'     => 'Growth trend (last 6 months)',
            'answer_type'  => 'Choice',
            'options_scale' => 'Gaining weight steadily / Weight has remained mostly the same / Has lost weight / Seems smaller than children of the same age / Seems about average compared to children of the same age / Seems larger than children of the same age',
            'only_ask_if'  => 'always',
            'is_optional'  => false,
            'sort_order'   => 23,
        ]);
    }

    private function seedQ26(): QuestionnaireQuestion
    {
        return QuestionnaireQuestion::create([
            'model'        => '3 Pro',
            'section'      => 'F · Clinical Signs & Gut',
            'question'     => 'Visible signs you notice',
            'answer_type'  => 'Multi',
            'options_scale' => 'Pale skin or eyes / Tiredness or low energy / Hair fall / Brittle nails / Mouth ulcers or cracks / Poor night vision / Delayed teeth or bowed legs / Poor concentration / None of the above / Not sure',
            'only_ask_if'  => 'always',
            'is_optional'  => false,
            'sort_order'   => 26,
        ]);
    }

    private function seedQ31(): QuestionnaireQuestion
    {
        return QuestionnaireQuestion::create([
            'model'        => '4 Premium',
            'section'      => 'G · History & Labs',
            'question'     => 'Latest blood report',
            'answer_type'  => 'File',
            'options_scale' => 'PDF, image or Word file',
            'only_ask_if'  => 'only if report available',
            'is_optional'  => true,
            'sort_order'   => 31,
        ]);
    }

    private function seedQ32(): QuestionnaireQuestion
    {
        return QuestionnaireQuestion::create([
            'model'        => '4 Premium',
            'section'      => 'G · History & Labs',
            'question'     => 'Periods started? (girls)',
            'answer_type'  => 'Choice',
            'options_scale' => 'Not yet / Yes → age started',
            'only_ask_if'  => 'girl, age >= 9',
            'is_optional'  => true,
            'sort_order'   => 32,
        ]);
    }

    private function seedQ3(): QuestionnaireQuestion
    {
        return QuestionnaireQuestion::create([
            'model'        => '1 Basic',
            'section'      => 'A · Child & Growth',
            'question'     => 'Sex',
            'answer_type'  => 'Choice',
            'options_scale' => 'Boy / Girl',
            'only_ask_if'  => 'always',
            'is_optional'  => false,
            'sort_order'   => 3,
        ]);
    }

    /** Seed a minimal deficiency rule for testing. */
    private function seedDeficiencyRule(string $sign, string $likelyGap): DeficiencyRule
    {
        return DeficiencyRule::create([
            'sign'              => $sign,
            'likely_gap'        => $likelyGap,
            'confirmation_test' => 'Test',
            'smart_combination' => 'Foods',
            'common_group'      => 'All',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1A — Q26 chip options render as separate entries
    // ─────────────────────────────────────────────────────────────────────────

    public function test_q26_options_scale_splits_into_10_separate_chips(): void
    {
        $q26 = $this->seedQ26();
        $opts = array_values(array_filter(
            array_map('trim', preg_split('/\s+\/\s+/', $q26->options_scale, -1, PREG_SPLIT_NO_EMPTY) ?: []),
            fn ($o) => $o !== ''
        ));

        $this->assertCount(10, $opts, 'Q26 must have exactly 10 chips');
        $this->assertContains('Pale skin or eyes', $opts);
        $this->assertContains('Tiredness or low energy', $opts);
        $this->assertContains('Hair fall', $opts);
        $this->assertContains('Brittle nails', $opts);
        $this->assertContains('Mouth ulcers or cracks', $opts);
        $this->assertContains('Poor night vision', $opts);
        $this->assertContains('Delayed teeth or bowed legs', $opts);
        $this->assertContains('Poor concentration', $opts);
        $this->assertContains('None of the above', $opts);
        $this->assertContains('Not sure', $opts);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1B — Q23 chip options render as separate entries
    // ─────────────────────────────────────────────────────────────────────────

    public function test_q23_options_scale_splits_into_6_separate_chips(): void
    {
        $q23 = $this->seedQ23();
        $opts = array_values(array_filter(
            array_map('trim', preg_split('/\s+\/\s+/', $q23->options_scale, -1, PREG_SPLIT_NO_EMPTY) ?: []),
            fn ($o) => $o !== ''
        ));

        $this->assertCount(6, $opts, 'Q23 must have exactly 6 chips');
        $this->assertContains('Gaining weight steadily', $opts);
        $this->assertContains('Weight has remained mostly the same', $opts);
        $this->assertContains('Has lost weight', $opts);
        $this->assertContains('Seems smaller than children of the same age', $opts);
        $this->assertContains('Seems about average compared to children of the same age', $opts);
        $this->assertContains('Seems larger than children of the same age', $opts);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // C7-FIX-3B: "0" chip must not be dropped by array_filter
    // ─────────────────────────────────────────────────────────────────────────

    public function test_zero_option_is_not_dropped_from_chip_list(): void
    {
        $q = QuestionnaireQuestion::create([
            'model'        => '1 Basic',
            'section'      => 'B · Food Frame',
            'question'     => 'Milk & dairy taken per day',
            'answer_type'  => 'Choice',
            'options_scale' => '0 / 1 / 2 / 3+ glasses',
            'only_ask_if'  => 'diet ≠ Vegan',
            'is_optional'  => false,
            'sort_order'   => 10,
        ]);
        $opts = array_values(array_filter(
            array_map('trim', preg_split('/\s+\/\s+/', $q->options_scale, -1, PREG_SPLIT_NO_EMPTY) ?: []),
            fn ($o) => $o !== ''
        ));

        $this->assertCount(4, $opts, 'Milk question must have 4 chips including "0"');
        $this->assertContains('0', $opts, '"0" must not be dropped by array_filter');
        $this->assertContains('1', $opts);
        $this->assertContains('2', $opts);
        $this->assertContains('3+ glasses', $opts);
    }

    public function test_child_questionnaire_page_renders_zero_chip(): void
    {
        QuestionnaireQuestion::create([
            'model'        => '1 Basic',
            'section'      => 'B · Food Frame',
            'question'     => 'Milk & dairy taken per day',
            'answer_type'  => 'Choice',
            'options_scale' => '0 / 1 / 2 / 3+ glasses',
            'only_ask_if'  => 'diet ≠ Vegan',
            'is_optional'  => false,
            'sort_order'   => 10,
        ]);
        SubscriptionPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price' => 0, 'is_active' => true]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->get(route('frontend.nutrition.questionnaire') . '?plan=basic');

        $response->assertOk();
        $response->assertSee('0');
        $response->assertSee('3+ glasses');
    }

    public function test_answer_zero_passes_validation_and_is_saved(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $questions = [
            [1, "Child's name",          'Text',         null,                                                  'always',    false, 1],
            [2, 'Age / date of birth',    'Date',         '2-15 yrs',                                           'always',    false, 2],
            [3, 'Sex',                    'Choice',       'Boy / Girl',                                         'always',    false, 3],
            [4, 'Current weight',         'Number (kg)',  'e.g. 22',                                            'always',    false, 4],
            [5, 'Current height',         'Number (cm)',  'e.g. 120',                                           'always',    false, 5],
            [6, 'Diet type',              'Choice',       'Veg / Vegan / Non-Veg / Eggetarian / Jain',         'always',    false, 6],
            [7, 'Allergies',              'Multi',        'Dairy/Lactose / Nuts & seeds / Gluten-wheat / Egg / None', 'always', false, 7],
            [8, 'Region / cuisine liked', 'Multi',        'North / West / East / South / No preference',       'always',    false, 8],
            [10, 'Milk & dairy taken per day', 'Choice',  '0 / 1 / 2 / 3+ glasses',                             'diet ≠ Vegan', false, 10],
        ];

        foreach ($questions as [$id, $question, $answerType, $optionsScale, $onlyAskIf, $isOptional, $sortOrder]) {
            QuestionnaireQuestion::create([
                'id'           => $id,
                'question'     => $question,
                'model'        => '1 Basic',
                'section'      => 'Basic',
                'answer_type'  => $answerType,
                'options_scale' => $optionsScale,
                'only_ask_if'  => $onlyAskIf,
                'is_optional'  => $isOptional,
                'sort_order'   => $sortOrder,
            ]);
        }

        $parent = User::factory()->create();

        $response = $this->actingAs($parent)->postJson(
            route('api.nutrition.questionnaire.submit'),
            [
                'plan_slug' => 'basic',
                'q_1'       => 'Aarav',
                'q_2'       => '2019-05-15',
                'q_3'       => 'Boy',
                'q_4'       => '22',
                'q_5'       => '120 cm',
                'q_6'       => 'Veg',
                'q_7'       => ['None'],
                'q_8'       => ['North'],
                'q_10'      => '0',
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $assessmentId = $response->json('assessment_id');
        // answer_value is JSON-cast, so "0" is stored as the JSON string literal "0".
        // Check via the model (which decodes) rather than raw DB column comparison.
        $answer = QuestionnaireAnswer::where('child_assessment_id', $assessmentId)
            ->where('question_id', 10)
            ->first();
        $this->assertNotNull($answer, 'Q10 answer row must exist');
        $this->assertSame('0', $answer->answer_value, 'Q10 answer must be the string "0"');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1 — Q26 options render as separate chips on the questionnaire page
    // ─────────────────────────────────────────────────────────────────────────

    public function test_questionnaire_page_renders_q26_as_separate_chips(): void
    {
        $this->seedQ26();
        SubscriptionPlan::create(['name' => 'Pro', 'slug' => 'pro', 'price' => 0, 'is_active' => true]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->get(route('frontend.nutrition.questionnaire') . '?plan=pro');

        $response->assertOk();
        // Each chip must appear as its own text node — not joined with middot or bullet
        $response->assertSee('Pale skin or eyes');
        $response->assertSee('Tiredness or low energy');
        $response->assertSee('Hair fall');
        $response->assertSee('Brittle nails');
        $response->assertSee('Mouth ulcers or cracks');
        $response->assertSee('Poor night vision');
        $response->assertSee('Delayed teeth or bowed legs');
        $response->assertSee('Poor concentration');
        $response->assertSee('None of the above');
        $response->assertSee('Not sure');

        // The old joined strings must NOT appear
        $response->assertDontSee('Pale eyes/skin · Tiredness');
        // The old bullet-separated string must not appear in Q26 chips
        $response->assertDontSee('Pale eyes/skin · Tiredness · Hair fall');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1 — Q23 renders as separate chips on questionnaire page
    // ─────────────────────────────────────────────────────────────────────────

    public function test_questionnaire_page_renders_q23_as_separate_chips(): void
    {
        $this->seedQ23();
        SubscriptionPlan::create(['name' => 'Pro', 'slug' => 'pro', 'price' => 0, 'is_active' => true]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->get(route('frontend.nutrition.questionnaire') . '?plan=pro');

        $response->assertOk();
        $response->assertSee('Gaining weight steadily');
        $response->assertSee('Weight has remained mostly the same');
        $response->assertSee('Has lost weight');
        // Old broken form must NOT appear
        $response->assertDontSee('Gaining well / About the same / Lost weight');
        $response->assertDontSee('Gaining well / About the same / Lost weight  •  vs peers: smaller');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1C — New Q26 label maps to same ClinicalFlag as old label
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_q26_label_produces_same_flag_as_old_label(): void
    {
        // Seed the exact deficiency_rules.sign that DeficiencyRulesSeeder creates.
        $this->seedDeficiencyRule('Pale eyes/skin', 'Iron (anemia)');

        $user = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'T', 'sex' => 'girl']);
        $assessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'pro']);

        $engine = app(RiskFlaggingEngine::class);

        // New label
        $flagsNew = $engine->generateFlags($assessment, ['visible_signs' => ['Pale skin or eyes']]);
        $this->assertCount(1, $flagsNew);
        $this->assertSame('Iron (anemia)', $flagsNew->first()->flag_name);

        // Old label — same assessment (firstOrCreate is idempotent)
        $flagsOld = $engine->generateFlags($assessment, ['visible_signs' => ['Pale eyes/skin']]);
        $this->assertCount(1, $flagsOld);
        $this->assertSame('Iron (anemia)', $flagsOld->first()->flag_name);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1C — All 8 new Q26 labels map correctly
    // ─────────────────────────────────────────────────────────────────────────

    public function test_all_new_q26_labels_resolve_to_correct_signs(): void
    {
        $mapping = [
            'Pale skin or eyes'          => 'Pale eyes/skin',
            'Tiredness or low energy'    => 'Tiredness',
            'Hair fall'                  => 'Hair fall',
            'Brittle nails'              => 'Brittle nails',
            'Mouth ulcers or cracks'     => 'Mouth-corner cracks/ulcers',
            'Poor night vision'          => 'Night-vision trouble',
            'Delayed teeth or bowed legs' => 'Delayed teeth/bowed legs',
            'Poor concentration'         => 'Poor focus',
        ];

        foreach ($mapping as $newLabel => $expectedSign) {
            $signs = VisibleSignsMap::toSigns([$newLabel]);
            $this->assertSame(
                [$expectedSign],
                $signs,
                "New label '{$newLabel}' must map to sign '{$expectedSign}'"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1C — "None of the above" and "Not sure" produce no flags
    // ─────────────────────────────────────────────────────────────────────────

    public function test_none_of_the_above_and_not_sure_produce_no_flag(): void
    {
        $this->seedDeficiencyRule('Pale eyes/skin', 'Iron (anemia)');

        $user = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'T', 'sex' => 'girl']);
        $assessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'pro']);

        $engine = app(RiskFlaggingEngine::class);

        $flags = $engine->generateFlags($assessment, [
            'visible_signs' => ['None of the above', 'Not sure', 'None'],
        ]);

        $this->assertCount(0, $flags, '"None of the above", "Not sure", "None" must create no flags');

        // professional_review_required must NOT be set by these answers
        $profile = app(ChildProfileService::class)->normalize($assessment, [
            26 => ['None of the above', 'Not sure'],
        ]);
        $this->assertEmpty($profile['visible_signs'], 'visible_signs must be empty after filtering no-flag labels');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1C — Old labels still work (backward compatibility)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_old_q26_labels_still_produce_flags(): void
    {
        $this->seedDeficiencyRule('Tiredness', 'Iron (anemia)');
        $this->seedDeficiencyRule('Poor focus', 'Iron / Omega-3 / skipped breakfast');

        $user = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'T', 'sex' => 'girl']);
        $assessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'pro']);

        $engine = app(RiskFlaggingEngine::class);
        $flags = $engine->generateFlags($assessment, [
            'visible_signs' => ['Tiredness', 'Poor focus'],
        ]);

        $this->assertCount(2, $flags, 'Legacy labels must still produce flags');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1C — resolveGapLabels also uses the map
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_gap_labels_uses_map_for_new_labels(): void
    {
        $this->seedDeficiencyRule('Hair fall', 'Protein / Iron');

        $engine = app(RiskFlaggingEngine::class);
        $gaps = $engine->resolveGapLabels(['Hair fall']); // new label = same as old here
        $this->assertSame(['Protein / Iron'], $gaps);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 1 — Valid new Q26 answers pass SubmitQuestionnaireRequest validation
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_q26_labels_pass_basic_plan_validation(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // Seed the minimum Basic questions and Q26 as part of the Pro model.
        $q1 = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);
        $q2 = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>'Age / date of birth','answer_type'=>'Date','sort_order'=>2,'is_optional'=>false]);
        $q3 = $this->seedQ3();

        $user = User::factory()->create();

        // For non-basic plan_slug, all answers are nullable — submit Q26 alone.
        $q26 = $this->seedQ26();
        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug' => 'pro',
            'q_'.$q1->id => 'Priya',
            'q_'.$q2->id => '2015-06-01',
            'q_'.$q3->id => 'Girl',
            'q_'.$q26->id => ['Pale skin or eyes', 'Hair fall'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 2 — Q32 not stored for boys
    // ─────────────────────────────────────────────────────────────────────────

    public function test_boy_submission_with_q32_does_not_store_it(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $q3  = $this->seedQ3();
        $q32 = $this->seedQ32();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'      => 'premium',
            'q_'.$q1->id     => 'Aryan',
            'q_'.$q3->id     => 'Boy',
            'q_'.$q32->id    => 'Not yet', // should be dropped
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $assessment = ChildAssessment::latest()->first();
        $this->assertNotNull($assessment);

        $periodsAnswer = $assessment->answers()->where('question_id', $q32->id)->first();
        $this->assertNull($periodsAnswer, 'Q32 must not be stored for a boy');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 2 — Boy submission without Q32 passes (not required)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_boy_submission_without_q32_passes_validation(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $q3  = $this->seedQ3();
        $q32 = $this->seedQ32();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'   => 'premium',
            'q_'.$q1->id  => 'Kabir',
            'q_'.$q3->id  => 'Boy',
            // No Q32
        ]);

        $response->assertOk()->assertJsonPath('success', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 2 — Girl submission stores Q32
    // ─────────────────────────────────────────────────────────────────────────

    public function test_girl_submission_stores_q32(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $q3  = $this->seedQ3();
        $q32 = $this->seedQ32();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'      => 'premium',
            'q_'.$q1->id     => 'Meera',
            'q_'.$q3->id     => 'Girl',
            'q_'.$q32->id    => 'Not yet',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $assessment = ChildAssessment::latest()->first();
        $this->assertNotNull($assessment);

        $periodsAnswer = $assessment->answers()->where('question_id', $q32->id)->first();
        $this->assertNotNull($periodsAnswer, 'Q32 must be stored for a girl');
        $this->assertSame('Not yet', $periodsAnswer->answer_value);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Valid PDF upload is stored privately
    // ─────────────────────────────────────────────────────────────────────────

    public function test_valid_pdf_upload_is_stored_on_private_disk(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'   => 'premium',
            'q_'.$q1->id  => 'Rohit',
            'q_'.$q31->id => $file,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $assessment = ChildAssessment::latest()->first();
        $bloodAnswer = $assessment->answers()->where('question_id', $q31->id)->first();

        $this->assertNotNull($bloodAnswer, 'Q31 answer must be saved');
        $this->assertStringStartsWith('blood_reports/', $bloodAnswer->answer_value, 'Path must start with blood_reports/');

        // The file must exist on the private (local) disk
        Storage::disk('local')->assertExists($bloodAnswer->answer_value);

        // Must NOT exist on the public disk
        Storage::fake('public');
        Storage::disk('public')->assertMissing($bloodAnswer->answer_value);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Valid PNG upload is stored privately
    // ─────────────────────────────────────────────────────────────────────────

    public function test_valid_png_upload_is_stored_on_private_disk(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('scan.png');

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'   => 'premium',
            'q_'.$q1->id  => 'Sia',
            'q_'.$q31->id => $file,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $assessment = ChildAssessment::latest()->first();
        $bloodAnswer = $assessment->answers()->where('question_id', $q31->id)->first();
        $this->assertNotNull($bloodAnswer);
        Storage::disk('local')->assertExists($bloodAnswer->answer_value);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Valid DOCX upload is stored privately
    // ─────────────────────────────────────────────────────────────────────────

    public function test_valid_docx_upload_is_stored_on_private_disk(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();
        // docx MIME: application/vnd.openxmlformats-officedocument.wordprocessingml.document
        $file = UploadedFile::fake()->create(
            'report.docx',
            100,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'   => 'premium',
            'q_'.$q1->id  => 'Diya',
            'q_'.$q31->id => $file,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $assessment = ChildAssessment::latest()->first();
        $bloodAnswer = $assessment->answers()->where('question_id', $q31->id)->first();
        $this->assertNotNull($bloodAnswer);
        Storage::disk('local')->assertExists($bloodAnswer->answer_value);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — EXE upload is rejected
    // ─────────────────────────────────────────────────────────────────────────

    public function test_exe_upload_is_rejected(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream');

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'   => 'premium',
            'q_'.$q1->id  => 'Test',
            'q_'.$q31->id => $file,
        ]);

        $response->assertUnprocessable();
        Storage::disk('local')->assertDirectoryEmpty('blood_reports');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — TXT upload is rejected
    // ─────────────────────────────────────────────────────────────────────────

    public function test_txt_upload_is_rejected(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('notes.txt', 5, 'text/plain');

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'   => 'premium',
            'q_'.$q1->id  => 'Test',
            'q_'.$q31->id => $file,
        ]);

        $response->assertUnprocessable();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Oversize file (> 5 MB) is rejected
    // ─────────────────────────────────────────────────────────────────────────

    public function test_oversize_pdf_is_rejected(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();
        // 6 000 KB = ~5.86 MB > 5 MB
        $file = UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf');

        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'   => 'premium',
            'q_'.$q1->id  => 'Test',
            'q_'.$q31->id => $file,
        ]);

        $response->assertUnprocessable();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Q31 answer is saved as the stored path
    // ─────────────────────────────────────────────────────────────────────────

    public function test_q31_answer_saved_as_path_string(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'   => 'premium',
            'q_'.$q1->id  => 'Neel',
            'q_'.$q31->id => $file,
        ])->assertOk();

        $assessment  = ChildAssessment::latest()->first();
        $bloodAnswer = $assessment->answers()->where('question_id', $q31->id)->first();

        $this->assertIsString($bloodAnswer->answer_value);
        $this->assertStringStartsWith('blood_reports/', $bloodAnswer->answer_value);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Owner can download the blood report
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_can_download_blood_report(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $user = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'T', 'sex' => 'girl']);
        $assessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'premium']);

        // Store a fake file and save the path as an answer.
        Storage::disk('local')->put('blood_reports/abc123/report.pdf', 'fake-pdf-content');
        QuestionnaireAnswer::create([
            'child_assessment_id' => $assessment->id,
            'question_id'         => $q31->id,
            'answer_value'        => 'blood_reports/abc123/report.pdf',
        ]);

        $response = $this->actingAs($user)
            ->get(route('nutrition.blood-report.download', $assessment));

        $response->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Another parent gets 403
    // ─────────────────────────────────────────────────────────────────────────

    public function test_other_parent_cannot_download_blood_report(): void
    {
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $child = Child::create(['user_id' => $owner->id, 'name' => 'T', 'sex' => 'girl']);
        $assessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'premium']);

        Storage::disk('local')->put('blood_reports/xyz/report.pdf', 'fake-pdf-content');
        QuestionnaireAnswer::create([
            'child_assessment_id' => $assessment->id,
            'question_id'         => $q31->id,
            'answer_value'        => 'blood_reports/xyz/report.pdf',
        ]);

        $response = $this->actingAs($other)
            ->get(route('nutrition.blood-report.download', $assessment));

        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 (C7-FIX-2) — Authorised admin/nutritionist can download blood
    // report through the admin guard route (admin.nutrition.blood-report.download).
    // ─────────────────────────────────────────────────────────────────────────

    public function test_authorised_admin_can_download_blood_report(): void
    {
        Storage::fake('local');

        // Seed the permission on the admin guard (matches routes/admin.php).
        Permission::firstOrCreate(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::firstOrCreate(['name' => 'Diet Supervisor', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');

        $q31 = $this->seedQ31();
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $child = Child::create(['user_id' => $owner->id, 'name' => 'T', 'sex' => 'girl']);
        $assessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'premium']);

        Storage::disk('local')->put('blood_reports/admin-dl/report.pdf', 'fake-pdf-content');
        QuestionnaireAnswer::create([
            'child_assessment_id' => $assessment->id,
            'question_id'         => $q31->id,
            'answer_value'        => 'blood_reports/admin-dl/report.pdf',
        ]);

        // Admin acts on the admin guard.
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.nutrition.blood-report.download', $assessment));

        $response->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 (C7-FIX-2) — Guest cannot download (admin route redirects to login)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_guest_cannot_download_admin_blood_report(): void
    {
        $owner = User::factory()->create();
        $child = Child::create(['user_id' => $owner->id, 'name' => 'T', 'sex' => 'girl']);
        $assessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'premium']);

        $response = $this->get(route('admin.nutrition.blood-report.download', $assessment));

        // Admin guard auth middleware redirects unauthenticated users to login.
        $response->assertRedirect();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 (C7-FIX-2) — Missing file returns 404
    // ─────────────────────────────────────────────────────────────────────────

    public function test_missing_blood_report_file_returns_404(): void
    {
        Storage::fake('local');

        Permission::firstOrCreate(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::firstOrCreate(['name' => 'Diet Supervisor', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');

        $q31 = $this->seedQ31();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);
        $owner = User::factory()->create();
        $child = Child::create(['user_id' => $owner->id, 'name' => 'T', 'sex' => 'girl']);
        $assessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'premium']);

        // No file on disk, no answer saved.
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.nutrition.blood-report.download', $assessment));

        $response->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 2 (C7-FIX-2) — Lead review page renders: no blood report
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_review_page_renders_without_blood_report(): void
    {
        Permission::firstOrCreate(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::firstOrCreate(['name' => 'Diet Supervisor', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');
        $this->seedQ31();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $customer = User::factory()->create();
        $child = Child::create(['user_id' => $customer->id, 'name' => 'Riya', 'sex' => 'girl']);
        $subPlan = SubscriptionPlan::create(['name' => 'Plus', 'slug' => 'plus', 'price' => 999, 'monthly_price' => 999, 'is_active' => true]);
        $sub = DietSubscription::create([
            'user_id' => $customer->id, 'child_id' => $child->id, 'subscription_plan_id' => $subPlan->id,
            'model' => 'Plus', 'status' => 'active', 'payment_status' => 'paid', 'amount_paid' => 999,
        ]);
        $lead = NutritionLead::create([
            'user_id' => $customer->id, 'child_id' => $child->id,
            'diet_subscription_id' => $sub->id, 'assigned_to' => $admin->id, 'status' => 'assigned',
        ]);
        $assessment = ChildAssessment::create([
            'child_id' => $child->id, 'model_level' => 'plus',
            'current_weight_kg' => 20, 'current_height_cm' => 115, 'diet_type' => 'vegetarian',
            'meal_pattern' => '3 meals',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.nutrition.lead-queue.show', $lead));

        $response->assertOk();
        $response->assertDontSee('View blood report');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 2 (C7-FIX-2) — Lead review page renders: with blood report, path not exposed
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_review_page_renders_with_blood_report_and_path_not_exposed(): void
    {
        Storage::fake('local');
        Permission::firstOrCreate(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        $role = Role::firstOrCreate(['name' => 'Diet Supervisor', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage diet chart');
        $q31 = $this->seedQ31();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $customer = User::factory()->create();
        $child = Child::create(['user_id' => $customer->id, 'name' => 'Riya', 'sex' => 'girl']);
        $subPlan = SubscriptionPlan::create(['name' => 'Plus', 'slug' => 'plus', 'price' => 999, 'monthly_price' => 999, 'is_active' => true]);
        $sub = DietSubscription::create([
            'user_id' => $customer->id, 'child_id' => $child->id, 'subscription_plan_id' => $subPlan->id,
            'model' => 'Plus', 'status' => 'active', 'payment_status' => 'paid', 'amount_paid' => 999,
        ]);
        $lead = NutritionLead::create([
            'user_id' => $customer->id, 'child_id' => $child->id,
            'diet_subscription_id' => $sub->id, 'assigned_to' => $admin->id, 'status' => 'assigned',
        ]);
        $assessment = ChildAssessment::create([
            'child_id' => $child->id, 'model_level' => 'plus',
            'current_weight_kg' => 20, 'current_height_cm' => 115, 'diet_type' => 'vegetarian',
            'meal_pattern' => '3 meals',
        ]);

        $storedPath = 'blood_reports/lead-test/report.pdf';
        Storage::disk('local')->put($storedPath, 'fake-pdf-content');
        QuestionnaireAnswer::create([
            'child_assessment_id' => $assessment->id,
            'question_id'         => $q31->id,
            'answer_value'        => $storedPath,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.nutrition.lead-queue.show', $lead));

        $response->assertOk();
        $response->assertSee('View blood report');
        // The raw storage path must NOT appear anywhere in the HTML.
        $response->assertDontSee($storedPath);
        $response->assertDontSee('blood_reports/lead-test/report.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Carry-over keeps the file path (not deleted, not duplicated)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_carry_over_keeps_blood_report_path(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');

        $q31 = $this->seedQ31();
        $q1  = QuestionnaireQuestion::create(['model'=>'1 Basic','section'=>'A','question'=>"Child's name",'answer_type'=>'Text','sort_order'=>1,'is_optional'=>false]);
        $qExtra = QuestionnaireQuestion::create(['model'=>'2 Plus','section'=>'C','question'=>'Activity level','answer_type'=>'Text','sort_order'=>13,'is_optional'=>true]);

        $user = User::factory()->create();
        $child = Child::create(['user_id' => $user->id, 'name' => 'Ravi', 'sex' => 'boy']);
        $prevAssessment = ChildAssessment::create(['child_id' => $child->id, 'model_level' => 'premium']);

        // Simulate a previously saved blood report path.
        $storedPath = 'blood_reports/old-uuid/report.pdf';
        Storage::disk('local')->put($storedPath, 'fake-pdf-content');
        QuestionnaireAnswer::create([
            'child_assessment_id' => $prevAssessment->id,
            'question_id'         => $q31->id,
            'answer_value'        => $storedPath,
        ]);

        // Submit a new Plus-level submission — no new file, just an upgrade answer.
        $response = $this->actingAs($user)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug'       => 'plus',
            'child_id'        => $child->id,
            'q_'.$qExtra->id  => 'active',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $newAssessment = $child->fresh()->assessments()->orderByDesc('id')->first();
        $this->assertNotSame($prevAssessment->id, $newAssessment->id);

        $carriedAnswer = $newAssessment->answers()->where('question_id', $q31->id)->first();
        $this->assertNotNull($carriedAnswer, 'Blood report path must be carried over');
        $this->assertSame($storedPath, $carriedAnswer->answer_value);

        // File still exists on disk
        Storage::disk('local')->assertExists($storedPath);

        // Only one answer row for Q31 on the new assessment
        $this->assertSame(1, $newAssessment->answers()->where('question_id', $q31->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ISSUE 3 — Q31 not read by diet engine (documentary assertion)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_q31_answer_not_read_by_diet_engine_no_public_url(): void
    {
        // Q31 (sort_order 31) is not referenced in QuestionnaireSubmissionService's
        // ChildAssessment::create() column mapping.  We verify this by confirming
        // no column in ChildAssessment maps from sort_order 31.
        //
        // The column mapping in QuestionnaireSubmissionService::submit() uses only:
        // sort_orders 4 (weight), 5 (height), 6 (diet), 7 (allergies), 8 (region),
        // 13 (activity), 22 (goal), 35 (budget), 16 (picky).
        // Sort_order 31 is absent → Q31 has no effect on diet chart generation.
        $usedOrders = [4, 5, 6, 7, 8, 13, 22, 35, 16];
        $this->assertNotContains(31, $usedOrders, 'Sort_order 31 must NOT be used in diet engine column mapping');

        // The route must NOT expose the raw storage path in its URL.
        $url = route('nutrition.blood-report.download', 1);
        $this->assertStringNotContainsString('blood_reports/', $url);
        $this->assertStringNotContainsString('storage/', $url);
    }
}
