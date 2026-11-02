<?php

namespace App\Services\Nutrition;

use App\Models\NutritionLead;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Returns bounded server-side pages for the admin nutrition leads table. */
class AdminNutritionLeadTableService
{
    /** @return array{draw:int,recordsTotal:int,recordsFiltered:int,data:array<int,array<string,mixed>>} */
    public function paginate(Request $request): array
    {
        $filters = $request->validate([
            'search.value' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:new,assigned,awaiting_review,completed'],
            'assigned_to' => ['nullable', 'regex:/^(unassigned|[1-9][0-9]*)$/'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $query = NutritionLead::query()->with([
            'customer:id,name,email',
            'child:id,name',
            'subscription:id,subscription_plan_id,model,amount_paid',
            'subscription.subscriptionPlan:id,name',
            'assignee:id,name',
        ]);
        $recordsTotal = (clone $query)->count();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['assigned_to'])) {
            $filters['assigned_to'] === 'unassigned'
                ? $query->whereNull('assigned_to')
                : $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        $startDate = ! empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate = ! empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;
        if ($startDate && $endDate && $startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }
        if ($startDate) $query->where('created_at', '>=', $startDate);
        if ($endDate) $query->where('created_at', '<=', $endDate);

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
        $orderColumns = ['id', 'user_id', 'child_id', 'created_at', 'status', 'assigned_to', 'id'];
        $orderIndex = (int) data_get($request->all(), 'order.0.column', 3);
        $orderColumn = $orderColumns[$orderIndex] ?? 'created_at';
        $direction = data_get($request->all(), 'order.0.dir') === 'asc' ? 'asc' : 'desc';
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));

        $rows = $query->orderBy($orderColumn, $direction)
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(fn (NutritionLead $lead): array => [
                'id' => $lead->id,
                'customer' => $lead->customer?->name ?? 'Customer unavailable',
                'email' => $lead->customer?->email ?? '',
                'child' => $lead->child?->name ?? 'Profile pending',
                'plan' => $lead->subscription?->subscriptionPlan?->name ?? $lead->subscription?->model ?? 'Plan unavailable',
                'amount_paid' => (float) ($lead->subscription?->amount_paid ?? 0),
                'purchased_at' => $lead->created_at?->format('d M Y') ?? 'N/A',
                'status' => (string) $lead->status,
                'assigned_to' => $lead->assigned_to,
                'assignee' => $lead->assignee?->name ?? 'Unassigned',
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
