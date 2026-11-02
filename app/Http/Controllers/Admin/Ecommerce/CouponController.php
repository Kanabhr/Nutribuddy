<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Marketing\CouponRequest;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class CouponController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.ecommerce.coupons.index', [
            'coupons' => Coupon::with('user')->latest()->get(),
            'users' => User::select('id', 'name', 'email', 'phone')->where('role', 'customer')->get(),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['starts_at'] = $this->normalizeDateInput($validated['starts_at'] ?? null, false);
        $validated['ends_at'] = $this->normalizeDateInput($validated['ends_at'] ?? null, true);

        Coupon::create($validated);

        return back()->with('success', 'Coupon created successfully.');
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validated();

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['starts_at'] = $this->normalizeDateInput($validated['starts_at'] ?? null, false);
        $validated['ends_at'] = $this->normalizeDateInput($validated['ends_at'] ?? null, true);

        $coupon->update($validated);

        return back()->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return back()->with('success', 'Coupon deleted successfully.');
    }

    private function normalizeDateInput(?string $value, bool $endOfDay = false): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value, config('app.timezone'));

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }
}
