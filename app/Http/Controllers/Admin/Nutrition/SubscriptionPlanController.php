<?php

namespace App\Http\Controllers\Admin\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nutrition\UpdateSubscriptionPlanRequest;
use App\Http\Requests\Admin\Nutrition\StoreSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use App\Services\Nutrition\SubscriptionPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function __construct(private readonly SubscriptionPlanService $subscriptionPlans) {}

    /** Display a listing of the diet service plans. */
    public function index(): View
    {
        return view('admin.nutrition.subscription-plans.index', $this->subscriptionPlans->listingData());
    }

    public function create(): View
    {
        return view('admin.diet-plans.create');
    }

    public function show(int $diet_plan): RedirectResponse
    {
        $this->subscriptionPlans->find($diet_plan);

        return redirect()->route('admin.ecommerce.diet-plans.index');
    }

    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        $this->subscriptionPlans->create(
            $request->validated(),
            $request->boolean('is_active'),
        );

        return redirect()->route('admin.ecommerce.diet-plans.index')
            ->with('success', 'Diet Plan created successfully.');
    }

    /** Show the form for editing the specified plan. */
    public function edit(int $diet_plan): View
    {
        return view('admin.nutrition.subscription-plans.edit', ['diet_plan' => $this->subscriptionPlans->find($diet_plan)]);
    }

    /** Update the specified plan in storage. */
    public function update(UpdateSubscriptionPlanRequest $request, int $diet_plan): RedirectResponse
    {
        $this->subscriptionPlans->update(
            $this->subscriptionPlans->find($diet_plan),
            $request->validated(),
            $request->boolean('is_active'),
        );

        return redirect()->route('admin.ecommerce.diet-plans.index')
            ->with('success', 'Diet Plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $diet_plan): RedirectResponse
    {
        if (!$this->subscriptionPlans->delete($diet_plan)) {
            return redirect()->route('admin.ecommerce.diet-plans.index')
                ->with('error', 'Plans with subscription history cannot be deleted.');
        }

        return redirect()->route('admin.ecommerce.diet-plans.index')
            ->with('success', 'Diet Plan deleted successfully.');
    }
}
