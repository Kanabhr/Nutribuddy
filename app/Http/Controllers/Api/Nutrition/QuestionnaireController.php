<?php

namespace App\Http\Controllers\Api\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Nutrition\SubmitQuestionnaireRequest;
use App\Models\Nutrition\Child;
use App\Services\Nutrition\AssessmentEngine;
use App\Services\Nutrition\QuestionnaireSubmissionService;
use Illuminate\Support\Facades\Log;

class QuestionnaireController extends Controller
{
    public function __construct(protected AssessmentEngine $engine)
    {
    }

    /**
     * Get the dynamic questionnaire for a specific child.
     */
    public function getQuestions($childId)
    {
        $child = Child::findOrFail($childId);
        // Assuming one active assessment for now
        $assessment = $child->assessments()->latest()->first();
        
        if (!$assessment) {
            return response()->json(['message' => 'No assessment found'], 404);
        }

        $questions = $this->engine->getQuestionsForChild($child, $assessment);
        
        return response()->json([
            'model_level' => $assessment->model_level,
            'questions' => $questions
        ]);
    }

    /**
     * Submit initial answers and create child.
     */
    public function submitInitialAnswers(SubmitQuestionnaireRequest $request, QuestionnaireSubmissionService $submissions)
    {
        try {
            $result = $submissions->submit($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                ...$result,
                'message' => 'Assessment saved. Complete payment to generate the diet chart for review.'
            ]);

        } catch (\Throwable $e) {
            Log::error('submitInitialAnswers failed.', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to save the assessment. Please try again.',
            ], 500);
        }
    }
}
