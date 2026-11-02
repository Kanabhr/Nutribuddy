<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ToggleMealItemRequest;
use App\Models\Nutrition\Child;
use App\Services\Nutrition\MealLoggingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MealItemController extends Controller
{
    public function __construct(private readonly MealLoggingService $mealLogging) {}

    public function toggleItem(ToggleMealItemRequest $request)
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

            $log = $this->mealLogging->toggleMealItem($child, (int) $user->id, $data);

            return response()->json([
                'success' => true,
                'is_consumed' => $log->is_consumed,
                'food_id' => $data['food_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('Toggle Item Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving'], 500);
        }
    }}
