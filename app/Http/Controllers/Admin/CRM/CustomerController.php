<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Admin\ResourceIndexFallbacks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CRM\CustomerRequest;
use App\Models\OrderReturn;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\Checkout\StateCityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    use ResourceIndexFallbacks;

    public function index(Request $request)
    {
        $query = User::where('role', 'customer')->with('customerAddresses');

        if ($request->filled('city')) {
            $query->whereHas('customerAddresses', function ($q) use ($request) {
                $q->where('city', 'like', '%'.$request->city.'%');
            });
        }

        if ($request->filled('state')) {
            $query->whereHas('customerAddresses', function ($q) use ($request) {
                $q->where('state', 'like', '%'.$request->state.'%');
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->get();

        return view('admin.crm.customers.index', compact('customers'));
    }

    /** Full customer profile: contact details, addresses, order history, spend, coins, subscriptions, reviews. */
    public function show(User $customer)
    {
        abort_unless($customer->role === 'customer', 404);

        $customer->load('customerAddresses');

        $allOrders = $customer->orders()
            ->get(['id', 'status', 'payment_status', 'grand_total', 'placed_at', 'created_at']);
        $liveOrders = $allOrders->where('status', '!=', 'cancelled');
        $paidOrders = $liveOrders->where('payment_status', 'paid');

        $firstOrderAt = $liveOrders->map(fn ($o) => $o->placed_at ?? $o->created_at)->filter()->min();
        $lastOrderAt = $liveOrders->map(fn ($o) => $o->placed_at ?? $o->created_at)->filter()->max();

        $stats = [
            'orders_placed' => $allOrders->count(),
            'orders_cancelled' => $allOrders->where('status', 'cancelled')->count(),
            // Lifetime spend = paid orders that are not cancelled. Refunds are not subtracted.
            'lifetime_spend' => (float) $paidOrders->sum('grand_total'),
            'average_order' => $paidOrders->count() > 0 ? (float) $paidOrders->avg('grand_total') : 0.0,
            'first_order_at' => $firstOrderAt,
            'last_order_at' => $lastOrderAt,
            'returns' => OrderReturn::whereIn('order_id', $allOrders->pluck('id'))->count(),
            'support_tickets' => $customer->supportTickets()->count(),
            'reviews' => ProductReview::where('user_id', $customer->id)->count(),
            'coins_earned' => (int) $customer->coinTransactions()->where('type', 'earned')->sum('amount'),
        ];

        $orders = $customer->orders()
            ->withCount('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $coinTransactions = $customer->coinTransactions()
            ->with('order:id,order_number')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $subscriptions = $customer->dietSubscriptions()
            ->with('subscriptionPlan:id,name')
            ->orderByDesc('id')
            ->get()
            ->each(function ($subscription) {
                // Same rule as DietSubscription::scopeUsable.
                $subscription->setAttribute('is_currently_active',
                    $subscription->status === 'active'
                    && in_array($subscription->payment_status, ['paid', 'free'], true)
                    && ($subscription->expires_at === null || $subscription->expires_at->isFuture())
                );
            });

        $reviews = ProductReview::where('user_id', $customer->id)
            ->with('product:id,name')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Addresses that were actually used for shipping, newest first, de-duplicated.
        $orderAddresses = $customer->orders()
            ->whereNotNull('shipping_address_line_1')
            ->orderByDesc('id')
            ->get(['shipping_name', 'shipping_phone', 'shipping_address_line_1', 'shipping_address_line_2', 'shipping_city', 'shipping_state', 'shipping_postal_code'])
            ->unique(fn ($o) => strtolower(trim($o->shipping_address_line_1.'|'.$o->shipping_postal_code)))
            ->take(5);

        return view('admin.crm.customers.show', compact(
            'customer', 'stats', 'orders', 'coinTransactions', 'subscriptions', 'reviews', 'orderAddresses'
        ));
    }

    public function store(CustomerRequest $request)
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->back()->with('success', 'Customer created successfully.');
    }

    public function update(CustomerRequest $request, User $customer)
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $customer->update($data);

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(User $customer)
    {
        if ($customer->role !== 'customer') {
            return redirect()->back()->with('error', 'Cannot delete admin users.');
        }

        $customer->delete();

        return redirect()->back()->with('success', 'Customer deleted successfully.');
    }

    public function getCities(Request $request, StateCityService $stateCityService)
    {
        $stateCode = $request->query('state');

        if (! $stateCode) {
            return response()->json(['cities' => []]);
        }

        $cities = $stateCityService->checkoutCitiesForState($stateCode);

        return response()->json(['cities' => $cities ?? []]);
    }
}
