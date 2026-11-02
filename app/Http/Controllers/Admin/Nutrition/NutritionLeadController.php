<?php

namespace App\Http\Controllers\Admin\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignNutritionLeadRequest;
use App\Http\Requests\Admin\BulkAssignNutritionLeadsRequest;
use App\Models\NutritionLead;
use App\Models\User;
use App\Services\Nutrition\AdminNutritionLeadTableService;
use App\Services\Nutrition\NutritionLeadAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NutritionLeadController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.nutrition.leads.index', ['nutritionists' => $this->nutritionists()]);
    }

    public function data(Request $request, AdminNutritionLeadTableService $table): JsonResponse
    {
        return response()->json($table->paginate($request));
    }

    public function assign(AssignNutritionLeadRequest $request, NutritionLead $lead, NutritionLeadAssignmentService $assignments): RedirectResponse|JsonResponse
    {
        $assignments->assign($lead, (int) $request->validated('assigned_to'));

        if ($request->expectsJson()) {
            $lead->refresh()->load('assignee');

            return response()->json([
                'message' => 'Lead assigned successfully.',
                'assigned_to' => $lead->assigned_to,
                'assignee' => $lead->assignee?->name ?? 'Unassigned',
                'status' => $lead->status,
            ]);
        }

        return back()->with('success', 'Lead assigned successfully.');
    }

    public function bulkAssign(BulkAssignNutritionLeadsRequest $request, NutritionLeadAssignmentService $assignments): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $updated = $assignments->bulkAssign($validated['lead_ids'], (int) $validated['assigned_to']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $updated . ' lead(s) assigned successfully.',
                'updated' => $updated,
            ]);
        }

        return back()->with('success', count($validated['lead_ids']) . ' lead(s) assigned successfully.');
    }

    private function nutritionists()
    {
        return User::where('role', 'admin')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

}
