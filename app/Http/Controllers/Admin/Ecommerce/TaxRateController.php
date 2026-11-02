<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ecommerce\TaxRateRequest;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.ecommerce.tax-rates.index', [
            'taxRates' => TaxRate::orderBy('sort_order')->latest('id')->get(),
        ]);
    }

    public function store(TaxRateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['show_in_checkout'] = (bool) ($validated['show_in_checkout'] ?? false);

        TaxRate::create($validated);

        return back()->with('success', 'Tax rate created successfully.');
    }

    public function update(TaxRateRequest $request, TaxRate $taxRate): RedirectResponse
    {
        $validated = $request->validated();

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['show_in_checkout'] = (bool) ($validated['show_in_checkout'] ?? false);

        $taxRate->update($validated);

        return back()->with('success', 'Tax rate updated successfully.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        if ($taxRate->products()->exists()) {
            return back()->with('error', 'Cannot delete tax rate while products are assigned.');
        }

        $taxRate->delete();

        return back()->with('success', 'Tax rate deleted successfully.');
    }
}
