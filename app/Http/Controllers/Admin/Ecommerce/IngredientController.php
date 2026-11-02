<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ecommerce\IngredientRequest;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Services\Ecommerce\IngredientManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IngredientController extends Controller
{
    public function index(): View
    {
        return view('admin.ecommerce.ingredients.index', [
            'ingredients' => Ingredient::with(['category', 'benefits'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.ecommerce.ingredients.create', [
            'categories' => IngredientCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'allProducts' => \App\Models\Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(IngredientRequest $request, IngredientManagementService $ingredients): RedirectResponse
    {
        $ingredients->create($request->validated(), $request->file('icon'));

        return redirect()->route('admin.ecommerce.ingredients.index')->with('success', 'Ingredient created successfully.');
    }

    public function show(Ingredient $ingredient): View
    {
        return view('admin.ecommerce.ingredients.show', [
            'ingredient' => $ingredient->load(['category', 'benefits']),
        ]);
    }

    public function edit(Ingredient $ingredient): View
    {
        return view('admin.ecommerce.ingredients.edit', [
            'ingredient' => $ingredient->load(['benefits', 'products']),
            'categories' => IngredientCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'allProducts' => \App\Models\Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(IngredientRequest $request, Ingredient $ingredient, IngredientManagementService $ingredients): RedirectResponse
    {
        $ingredients->update($ingredient, $request->validated(), $request->file('icon'));

        return redirect()->route('admin.ecommerce.ingredients.index')->with('success', 'Ingredient updated successfully.');
    }

    public function destroy(Ingredient $ingredient, IngredientManagementService $ingredients): RedirectResponse
    {
        $ingredients->delete($ingredient);

        return back()->with('success', 'Ingredient deleted successfully.');
    }
}
