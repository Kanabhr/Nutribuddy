<?php

namespace App\Services\Nutrition;

use App\Models\NutritionLead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Returns bounded, permission-scoped pages for the nutritionist lead queue. */
class NutritionistLeadQueueTableService
{
    /** @return array{draw:int,recordsTotal:int,recordsFiltered:int,data:array<int,array<string,mixed>>} */
    public function paginate(Request $request, User $admin): array
    {
        $isSuperAdmin = $admin->hasRole('Super Admin', 'admin');
        $filters = $request->validate([
            'search.value' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:new,assigned,awaiting_review,completed'],
            'assigned_to' => ['nullable', 'regex:/^(unassigned|[1-9][0-9]*)$/'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = NutritionLead::query()
            ->select(['id', 'user_id', 'child_id', 'diet_subscription_id', 'assigned_to', 'status', 'assigned_at'])
            ->with([
                'customer:id,name,email',
                'child:id,name',
                'subscription:id,subscription_plan_id,model',
                'subscription.subscriptionPlan:id,name',
            ]);

        if (! $isSuperAdmin) {
            $query->where('assigned_to', $admin->id);
        }

        $recordsTotal = (clone $query)->count();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if ($isSuperAdmin && ! empty($filters['assigned_to'])) {
            $filters['assigned_to'] === 'unassigned'
                ? $query->whereNull('assigned_to')
                : $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        $from = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $to = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : null;
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        if ($from) {
            $query->where('assigned_at', '>=', $from);
        }
        if ($to) {
            $query->where('assigned_at', '<=', $to);
        }

        $search = trim((string) data_get($filters, 'search.value', ''));
        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery
                    ->whereHas('customer', fn ($customerQuery) => $customerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('child', fn ($childQuery) => $childQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subscription', fn ($subscriptionQuery) => $subscriptionQuery
                        ->where('model', 'like', "%{$search}%")
                        ->orWhereHas('subscriptionPlan', fn ($planQuery) => $planQuery->where('name', 'like', "%{$search}%")));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = ['id', 'user_id', 'child_id', 'diet_subscription_id', 'assigned_at', 'status', 'id'];
        $orderIndex = (int) data_get($request->all(), 'order.0.column', 4);
        $orderColumn = $orderColumns[$orderIndex] ?? 'assigned_at';
        $direction = data_get($request->all(), 'order.0.dir') === 'asc' ? 'asc' : 'desc';
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));

        $rows = $query->orderBy($orderColumn, $direction)
            ->when($orderColumn !== 'id', fn ($ordered) => $ordered->orderByDesc('id'))
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(fn (NutritionLead $lead): array => [
                'id' => $lead->id,
                'customer' => $lead->customer?->name ?? 'Customer unavailable',
                'email' => $lead->customer?->email ?? '',
                'child' => $lead->child?->name ?? 'Profile pending',
                'plan' => $lead->subscription?->subscriptionPlan?->name ?? $lead->subscription?->model ?? 'Plan unavailable',
                'assigned_at' => $lead->assigned_at?->format('d M Y') ?? 'N/A',
                'status' => (string) $lead->status,
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
