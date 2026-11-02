<?php

namespace App\Http\Controllers\Admin\Assessment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assessment\ReorderAssessmentQuestionsRequest;
use App\Http\Requests\Admin\Assessment\StoreAssessmentQuestionRequest;
use App\Http\Requests\Admin\Assessment\UpdateAssessmentQuestionRequest;
use App\Models\AssessmentQuestion;
use App\Services\Assessment\AssessmentQuestionManagementService;
use Illuminate\View\View;

class AssessmentQuestionController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function __construct(private readonly AssessmentQuestionManagementService $questions) {}

    // ─── List ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $questions = AssessmentQuestion::with('options')
            ->orderBy('section')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('section');

        // Unique sections list for the form dropdown (strictly the 6 sectors).
        $sections = collect(array_keys(\App\Services\AssessmentService::SECTORS));

        return view('admin.assessment.questions.index', compact('questions', 'sections'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(StoreAssessmentQuestionRequest $request)
    {
        $data = $request->validated();
        $this->questions->create($data);

        return redirect()
            ->route('admin.assessment.questions.index', ['tab' => trim($data['section'])])
            ->with('success', 'Question created successfully.');
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(AssessmentQuestion $question): View
    {
        $question->load('options');
        $sections = collect(array_keys(\App\Services\AssessmentService::SECTORS));

        return view('admin.assessment.questions.edit', compact('question', 'sections'));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(UpdateAssessmentQuestionRequest $request, AssessmentQuestion $question)
    {
        $data = $request->validated();
        $this->questions->update($question, $data);

        return redirect()
            ->route('admin.assessment.questions.index', ['tab' => trim($data['section'])])
            ->with('success', 'Question updated successfully.');
    }

    // ─── Destroy ─────────────────────────────────────────────────────────────

    public function destroy(AssessmentQuestion $question)
    {
        $section = $question->section;
        $this->questions->delete($question);

        return redirect()
            ->route('admin.assessment.questions.index', ['tab' => $section])
            ->with('success', 'Question deleted successfully.');
    }

    // ─── Toggle Active ────────────────────────────────────────────────────────

    public function toggleActive(AssessmentQuestion $question)
    {
        $isActive = $this->questions->toggleActive($question);

        return response()->json([
            'success'   => true,
            'is_active' => $isActive,
        ]);
    }

    // ─── Reorder ─────────────────────────────────────────────────────────────

    public function reorder(ReorderAssessmentQuestionsRequest $request)
    {
        $this->questions->reorder($request->validated('order'));

        return response()->json(['success' => true]);
    }

    // ─── Rename Section ───────────────────────────────────────────────────────

}
