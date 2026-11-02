<?php

namespace App\Http\Controllers\Admin\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nutrition\ApproveLeadDietPlanRequest;
use App\Http\Requests\Admin\Nutrition\UpdateLeadDietPlanRequest;
use App\Http\Requests\Admin\Nutrition\UpdateLeadProductsRequest;
use App\Models\Nutrition\DietPlan;
use App\Models\NutritionLead;
use App\Models\User;
use App\Services\Nutrition\NutritionistLeadQueueTableService;
use App\Services\Nutrition\NutritionLeadReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NutritionistQueueController extends Controller
{
    public function __construct(private readonly NutritionLeadReviewService $leadReview) {}

    public function index(): View
    {
        $isSuperAdmin = auth('admin')->user()->hasRole('Super Admin', 'admin');
        $nutritionists = $isSuperAdmin
            ? User::query()->where('role', 'admin')->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.nutrition.leads.queue', compact('isSuperAdmin', 'nutritionists'));
    }

    public function data(Request $request, NutritionistLeadQueueTableService $table): JsonResponse
    {
        return response()->json($table->paginate($request, auth('admin')->user()));
    }

    public function show(NutritionLead $lead): View
    {
        abort_unless($this->canReview($lead), 403);
        return view('admin.nutrition.leads.show', $this->leadReview->buildReviewData($lead));
    }

    public function update(UpdateLeadDietPlanRequest $request, NutritionLead $lead, DietPlan $dietPlan): RedirectResponse
    {
        abort_unless($this->canReview($lead), 403);
        abort_unless($dietPlan->assessment?->child_id === $lead->child_id, 404);
        abort_unless($this->leadReview->updateDietPlan($dietPlan, $request->validated()), 422, 'Selected food is not safe or suitable for this meal slot.');
        return back()->with('success', 'Diet chart updated and ready for approval.');
    }

    public function generate(NutritionLead $lead): RedirectResponse
    {
        abort_unless($this->canReview($lead), 403);
        try {
            $result = $this->leadReview->generateChart($lead);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        if (! ($result['success'] ?? false)) return back()->with('error', $result['message'] ?? 'Diet chart generation failed.');

        $lead->update(['status' => 'awaiting_review']);
        return back()->with('success', 'Rule-based diet chart generated and ready for review.');
    }

    public function updateProducts(UpdateLeadProductsRequest $request, NutritionLead $lead, DietPlan $dietPlan): RedirectResponse
    {
        abort_unless($this->canReview($lead) && $dietPlan->assessment?->child_id === $lead->child_id, 404);
        $this->leadReview->updateRecommendedProducts($dietPlan, $request->validated());

        return back()->with('success', 'Recommended products saved.');
    }

    public function approve(ApproveLeadDietPlanRequest $request, NutritionLead $lead, DietPlan $dietPlan): RedirectResponse
    {
        abort_unless($this->canReview($lead), 403);
        abort_unless($dietPlan->assessment?->child_id === $lead->child_id, 404);
        $this->leadReview->approve($lead, $dietPlan, (int) auth('admin')->id(), $request->validated());

        return redirect()->route('admin.nutrition.lead-queue.index')->with('success', 'Diet chart approved and released to the customer.');
    }

    private function canReview(NutritionLead $lead): bool
    {
        return auth('admin')->user()->hasRole('Super Admin', 'admin') || $lead->assigned_to === auth('admin')->id();
    }
}
