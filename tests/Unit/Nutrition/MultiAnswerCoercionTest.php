<?php

namespace Tests\Unit\Nutrition;

use App\Models\Nutrition\QuestionnaireQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that prepareForValidation() in SubmitQuestionnaireRequest wraps a single
 * posted string into a one-element array for every question whose answer_type contains
 * 'multi' (case-insensitive) or whose sort_order is 8 (the allergies question).
 */
class MultiAnswerCoercionTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Create and return a minimal QuestionnaireQuestion for the given attributes. */
    private function makeQuestion(array $overrides = []): QuestionnaireQuestion
    {
        return QuestionnaireQuestion::create(array_merge([
            'model'       => '1 Basic',
            'section'     => 'Basic',
            'question'    => 'Test question',
            'answer_type' => 'Text',
            'sort_order'  => 99,
            'is_optional' => true,
        ], $overrides));
    }

    /**
     * Run prepareForValidation on a mock request and return the resulting input.
     * We instantiate the real FormRequest via the container so that it has the
     * application bindings (DB, etc.) it needs.
     */
    private function prepare(array $payload): array
    {
        $request = \App\Http\Requests\Api\Nutrition\SubmitQuestionnaireRequest::create(
            uri: '/api/nutrition/questionnaire/submit',
            method: 'POST',
            parameters: $payload,
        );

        // Bind the request into the container and fire prepareForValidation.
        $this->app->instance(\App\Http\Requests\Api\Nutrition\SubmitQuestionnaireRequest::class, $request);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));

        // prepareForValidation is protected – invoke via the public validateResolved path
        // just enough to trigger it without full rule execution.
        (function () { $this->prepareForValidation(); })->call($request);

        return $request->all();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Tests
    // ──────────────────────────────────────────────────────────────────────────

    /** A single-string answer for a 'Multi' question is wrapped in an array. */
    public function test_single_string_for_multi_question_becomes_array(): void
    {
        $q = $this->makeQuestion(['answer_type' => 'Multi select', 'sort_order' => 7]);

        $result = $this->prepare(['q_'.$q->id => 'Dairy/Lactose']);

        $this->assertSame(['Dairy/Lactose'], $result['q_'.$q->id]);
    }

    /** An empty string for a 'multi' question becomes an empty array. */
    public function test_empty_string_for_multi_question_becomes_empty_array(): void
    {
        $q = $this->makeQuestion(['answer_type' => 'Multi', 'sort_order' => 7]);

        $result = $this->prepare(['q_'.$q->id => '']);

        $this->assertSame([], $result['q_'.$q->id]);
    }

    /** A string posted for sort_order=8 (regardless of answer_type) is wrapped. */
    public function test_single_string_for_sort_order_8_becomes_array(): void
    {
        $q = $this->makeQuestion(['answer_type' => 'Choice', 'sort_order' => 8]);

        $result = $this->prepare(['q_'.$q->id => 'Gluten']);

        $this->assertSame(['Gluten'], $result['q_'.$q->id]);
    }

    /** A value that is already an array is left unchanged. */
    public function test_array_answer_is_not_double_wrapped(): void
    {
        $q = $this->makeQuestion(['answer_type' => 'Multi', 'sort_order' => 7]);

        $result = $this->prepare(['q_'.$q->id => ['Dairy/Lactose', 'Nuts & seeds']]);

        $this->assertSame(['Dairy/Lactose', 'Nuts & seeds'], $result['q_'.$q->id]);
    }

    /** A plain-text (non-multi) question with a string answer is NOT wrapped. */
    public function test_string_for_non_multi_question_stays_as_string(): void
    {
        $q = $this->makeQuestion(['answer_type' => 'Text', 'sort_order' => 1]);

        $result = $this->prepare(['q_'.$q->id => 'Aarav']);

        $this->assertSame('Aarav', $result['q_'.$q->id]);
    }

    /** Case-insensitive: 'MULTI' in answer_type triggers coercion. */
    public function test_multi_coercion_is_case_insensitive(): void
    {
        $q = $this->makeQuestion(['answer_type' => 'MULTI SELECT', 'sort_order' => 5]);

        $result = $this->prepare(['q_'.$q->id => 'North']);

        $this->assertSame(['North'], $result['q_'.$q->id]);
    }
}
