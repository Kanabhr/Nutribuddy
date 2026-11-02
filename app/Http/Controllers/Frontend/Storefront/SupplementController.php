<?php

namespace App\Http\Controllers\Frontend\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class SupplementController extends Controller
{
    public function index(): View
    {
        $child = request()->user()->children()->latest()->first();
        $plan = $child?->assessments()->latest()->first()?->dietPlan;
        $ids = $plan?->plan_data['supplement_product_ids'] ?? [];
        $products = Product::with('primaryImage')->where('is_active', true)->when($ids, fn ($query) => $query->whereIn('id', $ids))->latest()->get();

        return view('frontend.account.supplements', compact('child', 'products'));
    }
}
