<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreChildCheckInRequest;
use App\Models\Nutrition\Child;
use App\Services\Nutrition\MealLoggingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChildCheckInController extends Controller
{
    public function __construct(private readonly MealLoggingService $mealLogging) {}

    public function saveCheckIn(StoreChildCheckInRequest $request)
    {
        try {
            $data = $request->validated();

            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $child = Child::where('id', $data['child_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$child) {
                return response()->json(['success' => false, 'message' => 'Child not found'], 403);
            }

            $lastAssessment = $child->assessments()->latest()->first();

            if (!$lastAssessment) {
                return response()->json(['success' => false, 'message' => 'No previous assessment found'], 404);
            }

            $newAssessment = $this->mealLogging->saveCheckIn($lastAssessment, $data);

            return response()->json([
                'success' => true,
                'message' => 'Check-in saved successfully!',
                'assessment_id' => $newAssessment->id,
                'current_weight' => $newAssessment->current_weight_kg,
                'current_height' => $newAssessment->current_height_cm,
            ]);

        } catch (\Exception $e) {
            Log::error('Check-In Save Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving check-in'], 500);
        }
    }

}
