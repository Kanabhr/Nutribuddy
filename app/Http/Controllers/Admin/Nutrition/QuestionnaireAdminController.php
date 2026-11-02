<?php

namespace App\Http\Controllers\Admin\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nutrition\StoreQuestionnaireQuestionRequest;
use App\Http\Requests\Admin\Nutrition\UpdateQuestionnaireQuestionRequest;
use App\Models\Nutrition\QuestionnaireQuestion;

class QuestionnaireAdminController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index()
    {
        $modelsByPlan = [
            '1 Basic' => ['1 Basic'],
            '2 Plus' => ['1 Basic', '2 Plus'],
            '3 Pro' => ['1 Basic', '2 Plus', '3 Pro'],
            '4 Premium' => ['1 Basic', '2 Plus', '3 Pro', '4 Premium'],
        ];
        $groupedQuestions = collect($modelsByPlan)->map(fn (array $models) => QuestionnaireQuestion::query()
            ->whereIn('model', $models)
            ->orderBy('sort_order')
            ->get(['id', 'model', 'section', 'question', 'answer_type', 'options_scale', 'only_ask_if', 'clinical_reason', 'chart_effect', 'is_optional', 'sort_order'])
            ->groupBy('section'));

        return view('admin.nutrition.questions.index', compact('groupedQuestions'));
    }

    public function create()
    {
        return view('admin.nutrition.questions.create');
    }

    public function store(StoreQuestionnaireQuestionRequest $request)
    {
        QuestionnaireQuestion::create($request->validated());
        return redirect()->route('admin.nutrition.questions.index')->with('success', 'Question created successfully.');
    }

    public function edit(QuestionnaireQuestion $question)
    {
        return view('admin.nutrition.questions.edit', compact('question'));
    }

    public function update(UpdateQuestionnaireQuestionRequest $request, QuestionnaireQuestion $question)
    {
        $question->update($request->validated());
        return redirect()->route('admin.nutrition.questions.index')->with('success', 'Question updated successfully.');
    }

}
