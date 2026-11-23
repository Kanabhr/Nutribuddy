<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use App\Services\Payments\CashfreeSubscriptionService;
use Illuminate\Console\Command;
use Throwable;

class SyncCashfreePlansCommand extends Command
{
    protected $signature = 'cashfree:sync-plans {--dump} {--status=} {--confirm=}';
    protected $description = 'Sync subscription plans from NutriBuddy database to Cashfree Subscriptions API';

    public function handle(CashfreeSubscriptionService $cashfree): int
    {
        if ($dietSubId = $this->option('confirm')) {
            $dietPaymentService = app(\App\Services\Nutrition\DietPaymentService::class);
            $res = $dietPaymentService->confirmCashfreeReturn(null, $dietSubId);
            $this->line(json_encode($res, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        if ($subId = $this->option('status')) {
            $sub = $cashfree->getSubscription($subId);
            $this->line(json_encode($sub, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        if ($this->option('dump')) {
            $plan = $cashfree->getPlan('plus_monthly');
            $this->line(json_encode($plan, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('Starting Cashfree subscription plans synchronization...');

        $plans = SubscriptionPlan::where('slug', '!=', 'basic')
            ->where('is_active', true)
            ->get();

        if ($plans->isEmpty()) {
            $this->warn('No paid active plans found in subscription_plans.');
            return self::SUCCESS;
        }

        $cycles = [
            'monthly' => [
                'field' => 'monthly_price',
                'name_suffix' => 'Monthly',
                'interval_type' => 'MONTH',
                'intervals' => 1,
                'max_cycles' => 60,
            ],
            'quarterly' => [
                'field' => 'quarterly_price',
                'name_suffix' => 'Quarterly',
                'interval_type' => 'MONTH',
                'intervals' => 3,
                'max_cycles' => 20,
            ],
            'annually' => [
                'field' => 'annual_price',
                'name_suffix' => 'Annually',
                'interval_type' => 'YEAR',
                'intervals' => 1,
                'max_cycles' => 10,
            ],
        ];

        foreach ($plans as $plan) {
            foreach ($cycles as $cycleKey => $cycleConfig) {
                $amount = (float) $plan->{$cycleConfig['field']};
                if ($amount <= 0) {
                    continue;
                }

                $planId = strtolower($plan->slug . '_' . $cycleKey . '_' . (int) $amount);
                $planName = ucwords(str_replace('_', ' ', $plan->slug . ' ' . $cycleKey . ' ' . (int) $amount));

                $this->line("Checking plan: <comment>{$planId}</comment> (₹{$amount})...");

                try {
                    // Check if already registered on Cashfree
                    $existing = null;
                    try {
                        $existing = $cashfree->getPlan($planId);
                    } catch (Throwable) {
                        $existing = null;
                    }

                    if ($existing && !empty($existing['plan_id'])) {
                        $this->info("Plan [{$planId}] already exists on Cashfree.");
                    } else {
                        $payload = [
                            'plan_id' => $planId,
                            'plan_name' => $planName,
                            'plan_type' => 'PERIODIC',
                            'plan_currency' => 'INR',
                            'plan_recurring_amount' => round($amount, 2),
                            'plan_max_amount' => round($amount, 2),
                            'plan_max_cycles' => $cycleConfig['max_cycles'],
                            'plan_intervals' => $cycleConfig['intervals'],
                            'plan_interval_type' => $cycleConfig['interval_type'],
                            'plan_note' => "NutriBuddy {$planName}",
                        ];

                        $cashfree->createPlan($payload);
                        $this->info("Created plan [{$planId}] successfully on Cashfree!");
                    }

                    // Save default cashfree_plan_id on the monthly variant
                    if ($cycleKey === 'monthly') {
                        $plan->updateQuietly([
                            'cashfree_plan_id' => $planId,
                            'interval_type' => $cycleConfig['interval_type'],
                            'intervals' => $cycleConfig['intervals'],
                            'max_cycles' => $cycleConfig['max_cycles'],
                        ]);
                    }

                } catch (Throwable $e) {
                    $this->error("Failed to sync [{$planId}]: " . $e->getMessage());
                }
            }
        }

        $this->info('Cashfree plans synchronization completed.');
        return self::SUCCESS;
    }
}
