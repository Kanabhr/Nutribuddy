<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\CreateChildProfileRequest;
use App\Models\DietSubscription;
use App\Models\Nutrition\DietPlan;
use App\Services\Nutrition\ChildProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChildProfileController extends Controller
{
    public function userPanelShow(Request $request)
    {
        $child = $request->user()->children()->latest()->first();
        if (! $child) {
            return redirect()->route('child-profile.create');
        }

        $assessment = $child->assessments()->latest()->first();
        $dietPlan = $assessment
            ? DietPlan::with('meals.items.food')->where('child_assessment_id', $assessment->id)->latest()->first()
            : null;
        $subscription = DietSubscription::with('subscriptionPlan')
            ->where('child_id', $child->id)->where('payment_status', 'paid')->latest()->first();

        return view('frontend.nutrition.child-profile.profile', compact('child', 'dietPlan', 'assessment', 'subscription'));
    }

    public function create()
    {
        if (request()->user()->children()->exists()) {
            return redirect()->route('child-profile');
        }

        return view('frontend.nutrition.child-profile.create');
    }

    public function store(CreateChildProfileRequest $request, ChildProfileService $profiles): JsonResponse
    {
        try {
            ['child' => $child, 'assessment' => $assessment] = $profiles->createProfile(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Child profile created successfully!',
                'child_id' => $child->id,
                'assessment_id' => $assessment->id,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Child profile creation failed.', ['user_id' => $request->user()->id, 'exception' => $exception]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create the profile. Please try again.',
            ], 500);
        }
    }
}
