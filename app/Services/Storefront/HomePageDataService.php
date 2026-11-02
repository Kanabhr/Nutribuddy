<?php

namespace App\Services\Storefront;

use App\Models\Ingredient;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Schema;

class HomePageDataService
{
    /** @return array{featuredProducts: \Illuminate\Support\Collection, featuredIngredients: \Illuminate\Support\Collection, basicDietQuestions: \Illuminate\Support\Collection, dietPlans: \Illuminate\Support\Collection} */
    public function get(): array
    {
        $featuredProducts = collect();
        if ($this->productTablesAreReady()) {
            try {
                $featuredProducts = Product::where('is_active', true)
                    ->with(['primaryImage', 'category', 'images', 'reviews', 'taxRate', 'variants.inventory', 'variants.product.taxRate'])
                    ->latest()
                    ->limit(3)
                    ->get();
            } catch (\Throwable) {
                $featuredProducts = collect();
            }
        }

        $featuredIngredients = collect();
        if ($this->ingredientTablesAreReady()) {
            try {
                $featuredIngredients = Ingredient::where('is_active', true)
                    ->where('is_featured', true)
                    ->with('benefits')
                    ->orderBy('sort_order')
                    ->get();
            } catch (\Throwable) {
                $featuredIngredients = collect();
            }
        }

        $basicDietQuestions = QuestionnaireQuestion::query()
            ->where('model', '1 Basic')
            ->orderBy('sort_order')
            ->get(['id', 'section', 'question', 'answer_type', 'options_scale', 'only_ask_if', 'is_optional']);
        $dietPlans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'level', 'monthly_price', 'quarterly_price', 'annual_price', 'features']);

        return compact('featuredProducts', 'featuredIngredients', 'basicDietQuestions', 'dietPlans');
    }

    private function productTablesAreReady(): bool
    {
        try {
            return Schema::hasTable('products')
                && Schema::hasTable('categories')
                && Schema::hasTable('product_images')
                && Schema::hasTable('product_reviews');
        } catch (\Throwable) {
            return false;
        }
    }

    private function ingredientTablesAreReady(): bool
    {
        try {
            return Schema::hasTable('ingredients')
                && Schema::hasTable('ingredient_benefits');
        } catch (\Throwable) {
            return false;
        }
    }
}
