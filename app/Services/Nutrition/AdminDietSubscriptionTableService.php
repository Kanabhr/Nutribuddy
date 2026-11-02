<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Builds bounded, filtered admin pages for diet subscription records. */
class AdminDietSubscriptionTableService
{
    /** @return array{draw:int,recordsTotal:int,recordsFiltered:int,data:array<int,array<string,mixed>>} */
    public function paginate(Request $request): array
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'payment_status' => ['nullable', 'string', 'max:30'],
            'plan_id' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'search.value' => ['nullable', 'string', 'max:100'],
        ]);

        $query = DietSubscription::query()->with([
            'user:id,name,email',
            'subscriptionPlan:id,name',
        ]);
        $recordsTotal = (clone $query)->count();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (! empty($filters['plan_id'])) {
            $query->where('subscription_plan_id', $filters['plan_id']);
        }
        $startDate = ! empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate = ! empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;
        if ($startDate && $endDate && $startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $search = trim((string) data_get($filters, 'search.value', ''));
        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->whereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('child', fn ($childQuery) => $childQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subscriptionPlan', fn ($planQuery) => $planQuery->where('name', 'like', "%{$search}%"))
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('razorpay_order_id', 'like', "%{$search}%")
                    ->orWhere('razorpay_payment_id', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();
        $columns = ['user_id', 'user_id', 'subscription_plan_id', 'amount_paid', 'status', 'payment_status', 'starts_at', 'expires_at', 'id'];
        $orderIndex = (int) data_get($request->all(), 'order.0.column', 8);
        $orderColumn = $columns[$orderIndex] ?? 'id';
        $direction = data_get($request->all(), 'order.0.dir') === 'asc' ? 'asc' : 'desc';
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));

        $rows = $query->orderBy($orderColumn, $direction)
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(fn (DietSubscription $subscription) => [
                'id' => $subscription->id,
                'user' => $subscription->user?->name ?? 'Guest',
                'email' => $subscription->user?->email ?? '',
                'plan' => $subscription->model ?: ($subscription->subscriptionPlan?->name ?? 'Unknown'),
                'amount_paid' => (float) $subscription->amount_paid,
                'status' => (string) $subscription->status,
                'payment_status' => (string) $subscription->payment_status,
                'starts_at' => $subscription->starts_at?->format('d M Y') ?? 'N/A',
                'expires_at' => $subscription->expires_at?->format('d M Y') ?? 'N/A',
            ])
            ->all();

        return [
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ];
    }
}
