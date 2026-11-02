<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ecommerce\AttributeRequest;
use App\Models\Attribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttributeController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.ecommerce.attributes.index', [
            'attributes' => Attribute::orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function store(AttributeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Attribute::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'values' => $this->parseValues($validated['values_text']),
            'position' => $validated['position'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Attribute created successfully.');
    }

    public function update(AttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        $validated = $request->validated();

        $attribute->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'values' => $this->parseValues($validated['values_text']),
            'position' => $validated['position'] ?? $attribute->position,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $attribute->delete();

        return back()->with('success', 'Attribute deleted successfully.');
    }

    private function parseValues(string $values): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', $values))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
