<?php

namespace Tests\Feature;

use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the null answer_value NOT NULL constraint crash.
 *
 * Bug: submitting the questionnaire without answering an optional question
 * (e.g. Q9 notes) caused SQLSTATE[23000] 1048 because
 * QuestionnaireSubmissionService::submit called updateOrCreate with
 * answer_value => null on a NOT NULL json column.
 *
 * Fix: skip any answer whose value is null, '' or an empty array in the
 * foreach loop that persists answers.
 *
 * Note: Q4 weight and Q5 height were previously treated as optional here,
 * but are now required "always asked" questions (see SubmitQuestionnaireRequest).
 * A genuinely optional question (Q9) is used to keep this regression covered.
 */
class QuestionnaireNullAnswerRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed only the questions submitted in these tests.
     * Labels must contain 'weight' and 'height' so the FormRequest recognises
     * those two as nullable fields (see SubmitQuestionnaireRequest::rules()).
     * Reuses the same DB::table pattern as DietChartPipelineRegressionTest.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Questions 1-8, matching real seeder metadata so the FormRequest
        // builds the correct nullable / required rules for each field.
        // Q9 is a genuinely OPTIONAL question (is_optional = true) used to
        // verify that empty optional answers are still skipped — height (Q5)
        // and weight (Q4) are now required "always asked" questions.
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
    // (a) Submitting with Q9 (optional) empty must return success and
    //     must NOT create a questionnaire_answers row for it.  Height (Q5)
    //     and weight (Q4) are now required, so they must be provided and
    //     their rows must exist.
    // ------------------------------------------------------------------
    public function test_submit_with_empty_optional_answers_returns_success_and_skips_null_rows(): void
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
                'q_4'       => '22',            // weight - required, now provided
                'q_5'       => '120 cm',         // height - required, now provided
                'q_6'       => 'Veg',
                'q_7'       => ['None'],
                'q_8'       => ['North'],
                'q_9'       => '',               // optional question - empty, must be skipped
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $assessmentId = $response->json('assessment_id');

        // Required height and weight rows ARE saved now.
        $this->assertDatabaseHas('questionnaire_answers', [
            'child_assessment_id' => $assessmentId,
            'question_id'         => 4,
        ]);
        $this->assertDatabaseHas('questionnaire_answers', [
            'child_assessment_id' => $assessmentId,
            'question_id'         => 5,
        ]);

        // No row for Q9 (the genuinely optional question) — empty answers are
        // still skipped so the NOT NULL constraint is never violated.
        $this->assertDatabaseMissing('questionnaire_answers', [
            'child_assessment_id' => $assessmentId,
            'question_id'         => 9,
        ]);
    }

    // ------------------------------------------------------------------
    // (b) Submitting with all answers provided still saves every answer.
    // ------------------------------------------------------------------
    public function test_submit_with_all_answers_saves_all_rows(): void
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

        // All 8 questions must have a row.
        foreach (range(1, 8) as $questionId) {
            $this->assertDatabaseHas('questionnaire_answers', [
                'child_assessment_id' => $assessmentId,
                'question_id'         => $questionId,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // (c) Re-submitting for an existing child with Q9 (optional) empty must
    //     keep the Q9 answer that was copied forward from the previous
    //     assessment (do not delete or overwrite it with null).
    // ------------------------------------------------------------------
    public function test_resubmit_with_empty_optional_preserves_previous_answer(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $parent = User::factory()->create();

        // --- First submission: provide Q9 (optional) and valid height/weight ---
        $first = $this->actingAs($parent)->postJson(
            route('api.nutrition.questionnaire.submit'),
            [
                'plan_slug' => 'basic',
                'q_1'       => 'Kabir',
                'q_2'       => '2017-07-20',
                'q_3'       => 'Boy',
                'q_4'       => '22',
                'q_5'       => '120 cm',
                'q_6'       => 'Veg',
                'q_7'       => ['None'],
                'q_8'       => ['North'],
                'q_9'       => 'Likes bananas',
            ]
        );

        $first->assertStatus(200);
        $firstAssessmentId = $first->json('assessment_id');
        $childId           = $first->json('child_id');

        // Confirm Q9 was saved in the first assessment.
        $this->assertDatabaseHas('questionnaire_answers', [
            'child_assessment_id' => $firstAssessmentId,
            'question_id'         => 9,
        ]);

        // --- Second submission: send Q9 empty (should not overwrite the copied-forward answer) ---
        $second = $this->actingAs($parent)->postJson(
            route('api.nutrition.questionnaire.submit'),
            [
                'plan_slug' => 'basic',
                'child_id'  => $childId,
                'q_1'       => 'Kabir',
                'q_2'       => '2017-07-20',
                'q_3'       => 'Boy',
                'q_4'       => '23',
                'q_5'       => '121 cm',
                'q_6'       => 'Veg',
                'q_7'       => ['None'],
                'q_8'       => ['North'],
                'q_9'       => '',              // empty - should not overwrite the copied-forward answer
            ]
        );

        $second->assertStatus(200);
        $secondAssessmentId = $second->json('assessment_id');

        // The new assessment must have a Q9 row (copied from the previous assessment,
        // not deleted because the new submission sent an empty value).
        $this->assertDatabaseHas('questionnaire_answers', [
            'child_assessment_id' => $secondAssessmentId,
            'question_id'         => 9,
        ]);
    }
}