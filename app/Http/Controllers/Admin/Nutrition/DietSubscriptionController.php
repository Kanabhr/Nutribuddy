<?php

namespace App\Http\Controllers\Admin\Nutrition;

use App\Http\Controllers\Controller;
use App\Models\DietSubscription;
use App\Models\SubscriptionPlan;
use App\Services\Nutrition\AdminDietSubscriptionTableService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DietSubscriptionController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceWritesDisabled;
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index()
    {
        $plans = SubscriptionPlan::query()->orderBy('name')->get(['id', 'name']);
        $statuses = DietSubscription::query()->select('status')->distinct()->orderBy('status')->pluck('status');
        $paymentStatuses = DietSubscription::query()->select('payment_status')->distinct()->orderBy('payment_status')->pluck('payment_status');

        return view('admin.nutrition.diet-subscriptions.index', compact('plans', 'statuses', 'paymentStatuses'));
    }

    public function data(Request $request, AdminDietSubscriptionTableService $table): JsonResponse
    {
        return response()->json($table->paginate($request));
    }

    public function show($id)
    {
        $subscription = DietSubscription::with(['user', 'subscriptionPlan', 'child'])->findOrFail($id);
        $assessments = $subscription->child
            ? $subscription->child->assessments()->with('answers.question')->latest()->paginate(5)
            : collect();

        return view('admin.nutrition.diet-subscriptions.show', compact('subscription', 'assessments'));
    }
}
