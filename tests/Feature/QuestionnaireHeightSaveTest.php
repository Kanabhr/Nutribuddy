<?php

namespace Tests\Feature;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C8-FIX: height (sort_order 5, "Current height", Number (cm)) was not saved
 * for new children — questionnaire_answers had no row for it and
 * child_assessments.current_height_cm was NULL, while weight (sort_order 4)
 * was saved.
 *
 * These tests verify the fix end-to-end through the API:
 *  1. A valid height is saved to the questionnaire_answers row AND written to
 *     child_assessments.current_height_cm.
 *  2. A submission missing height (or weight) is rejected with a validation
 *     error and nothing is persisted.
 *  3. An out-of-range height is rejected by the sanity limits.
 *  4. Empty answers for a genuinely optional question are still skipped
 *     (the earlier empty-optional-answer bugfix is preserved).
 */
class QuestionnaireHeightSaveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed the Basic questionnaire questions 1-8 plus a genuinely optional
     * Q9, matching the real seeder metadata so SubmitQuestionnaireRequest
     * builds the correct required/nullable rules for each field.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $questions = [
            [1, "Child's name",          'Text',         null,                                                  'always',    false, 1],
            [2, 'Age / date of birth',    'Date',         '2-15 yrs',                                           'always',    false, 2],
            [3, 'Sex',                    'Choice',       'Boy / Girl',                                         'always',    false, 3],
            [4, 'Current weight',         'Number (kg)',  'e.g. 22',                                            'always',    false, 4],
            [5, 'Current height',         'Number (cm)',  'e.g. 120',                                           'always',    false, 5],
            [6, 'Diet type',              'Choice',       'Veg / Vegan / Non-Veg / Eggetarian / Jain',         'always',    false, 6],
            [7, 'Allergies',              'Multi',        'Dairy/Lactose / Nuts & seeds / Gluten-wheat / Egg / None', 'always', false, 7],
            [8, 'Region / cuisine liked', 'Multi',        'North / West / East / South / No preference',       'always',    false, 8],
            [9, 'Notes / preferences',    'Text',         null,                                                 'optional',  true,  9],
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
    }

    // ------------------------------------------------------------------
    // (1) Height is saved to questionnaire_answers AND current_height_cm.
    // ------------------------------------------------------------------
    public function test_height_is_saved_to_answer_and_current_height_cm(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $parent = User::factory()->create();

        $response = $this->actingAs($parent)->postJson(
            route('api.nutrition.questionnaire.submit'),
            [
                'plan_slug' => 'basic',
                'q_1'       => 'Mira',
                'q_2'       => '2018-04-12',
                'q_3'       => 'Girl',
                'q_4'       => '21.5',
                'q_5'       => '118 cm',
                'q_6'       => 'Veg',
                'q_7'       => ['None'],
                'q_8'       => ['South'],
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $assessmentId = $response->json('assessment_id');

        // Height answer row exists in questionnaire_answers.
        $this->assertDatabaseHas('questionnaire_answers', [
            'child_assessment_id' => $assessmentId,
            'question_id'         => 5,
        ]);

        // Height was written to child_assessments.current_height_cm.
        $assessment = ChildAssessment::find($assessmentId);
        $this->assertSame(118.0, (float) $assessment->current_height_cm);

        // Weight was written too (sanity check).
        $this->assertSame(21.5, (float) $assessment->current_weight_kg);
    }

    // ------------------------------------------------------------------
    // (2a) Missing height is rejected with a validation error and nothing
    //      is persisted.
    // ------------------------------------------------------------------
    public function test_missing_height_is_rejected_with_validation_error(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $parent = User::factory()->create();

        $response = $this->actingAs($parent)->postJson(
            route('api.nutrition.questionnaire.submit'),
            [
                'plan_slug' => 'basic',
                'q_1'       => 'Aarav',
                'q_2'       => '2019-05-15',
                'q_3'       => 'Boy',
                'q_4'       => '22',
                'q_5'       => '',              // height missing — must be rejected
                'q_6'       => 'Veg',
                'q_7'       => ['None'],
                'q_8'       => ['North'],
            ]
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['q_5']);

        // Nothing must have been saved.
        $this->assertDatabaseCount('children', 0);
        $this->assertDatabaseCount('child_assessments', 0);
    }

    // ------------------------------------------------------------------
    // (2b) Missing weight is also rejected (height AND weight are required).
    // ------------------------------------------------------------------
    public function test_missing_weight_is_rejected_with_validation_error(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $parent = User::factory()->create();

        $response = $this->actingAs($parent)->postJson(
            route('api.nutrition.questionnaire.submit'),
            [
                'plan_slug' => 'basic',
                'q_1'       => 'Aarav',
                'q_2'       => '2019-05-15',
                'q_3'       => 'Boy',
                'q_4'       => '',              // weight missing — must be rejected
                'q_5'       => '120 cm',
                'q_6'       => 'Veg',
                'q_7'       => ['None'],
                'q_8'       => ['North'],
            ]
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['q_4']);

        $this->assertDatabaseCount('children', 0);
        $this->assertDatabaseCount('child_assessments', 0);
    }

    // ------------------------------------------------------------------
    // (3) Out-of-range height is rejected by the sanity limits
    //     (30–250 cm stated in SubmitQuestionnaireRequest).
    // ------------------------------------------------------------------
    public function test_out_of_range_height_is_rejected(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $parent = User::factory()->create();

        $response = $this->actingAs($parent)->postJson(
            route('api.nutrition.questionnaire.submit'),
            [
                'plan_slug' => 'basic',
                'q_1'       => 'Aarav',
                'q_2'       => '2019-05-15',
                'q_3'       => 'Boy',
                'q_4'       => '22',
                'q_5'       => '10 cm',         // below the 30 cm sanity limit
                'q_6'       => 'Veg',
                'q_7'       => ['None'],
                'q_8'       => ['North'],
            ]
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['q_5']);
    }

    // ------------------------------------------------------------------
    // (4) Empty answers for a genuinely optional question (Q9) are still
    //     skipped — the earlier empty-optional-answer bugfix is preserved.
    // ------------------------------------------------------------------
    public function test_optional_empty_answer_is_still_skipped(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

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
                'q_9'       => '',               // optional — empty, must be skipped
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $assessmentId = $response->json('assessment_id');

        // No row for Q9 — empty optional answers are skipped (bugfix preserved).
        $this->assertDatabaseMissing('questionnaire_answers', [
            'child_assessment_id' => $assessmentId,
            'question_id'         => 9,
        ]);
    }
}
