<?php

namespace App\Services\Nutrition;

use App\Models\NutritionLead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Assigns nutrition leads to active admin nutritionists. */
class NutritionLeadAssignmentService
{
    public function assign(NutritionLead $lead, int $userId): void
    {
        DB::transaction(function () use ($lead, $userId): void {
            $this->ensureNutritionist($userId);

            $lockedLead = NutritionLead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedLead->status === 'completed', 422, 'Completed leads cannot be reassigned.');

            $lockedLead->update([
                'assigned_to' => $userId,
                'assigned_at' => now(),
                'status' => $lockedLead->status === 'awaiting_review' ? 'awaiting_review' : 'assigned',
            ]);
        });
    }

    /** @param array<int, int> $leadIds */
    public function bulkAssign(array $leadIds, int $userId): int
    {
        return DB::transaction(function () use ($leadIds, $userId): int {
            $this->ensureNutritionist($userId);

            $leads = NutritionLead::query()
                ->whereIn('id', $leadIds)
                ->lockForUpdate()
                ->get(['id', 'status']);

            abort_unless($leads->count() === count($leadIds), 422, 'One or more selected leads are no longer available. Refresh the table and try again.');
            abort_if($leads->contains('status', 'completed'), 422, 'Completed leads cannot be reassigned. Refresh the table and select active leads.');

            $assignedAt = now();
            $reviewLeadIds = $leads->where('status', 'awaiting_review')->modelKeys();
            $assignableLeadIds = $leads->where('status', '!=', 'awaiting_review')->modelKeys();

            if ($assignableLeadIds !== []) {
                NutritionLead::query()->whereIn('id', $assignableLeadIds)->update([
                    'assigned_to' => $userId,
                    'assigned_at' => $assignedAt,
                    'status' => 'assigned',
                ]);
            }

            if ($reviewLeadIds !== []) {
                NutritionLead::query()->whereIn('id', $reviewLeadIds)->update([
                    'assigned_to' => $userId,
                    'assigned_at' => $assignedAt,
                ]);
            }

            return $leads->count();
        });
    }

    private function ensureNutritionist(int $userId): void
    {
        $isActiveAdmin = User::query()
            ->whereKey($userId)
            ->where('role', 'admin')
            ->where('is_active', true)
            ->exists();

        abort_unless($isActiveAdmin, 422, 'The selected user does not have diet-chart access.');
    }
}
