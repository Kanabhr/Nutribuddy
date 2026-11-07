<?php

namespace App\Console\Commands;

use App\Services\Checkout\StateCityService;
use Illuminate\Console\Command;

class TestDistrictFilter extends Command
{
    protected $signature = 'test:district-filter';
    protected $description = 'Test the district filter functionality';

    public function handle(StateCityService $service)
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('TESTING DISTRICT FILTER - COMPLETE FLOW');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Test 1: Load all states
        $this->info('TEST 1: Load all states');
        $this->info('───────────────────────');
        $states = $service->checkoutStates();
        $this->info("✓ States loaded: " . count($states));
        $this->info("First 5 states:");
        foreach (array_slice($states, 0, 5) as $s) {
            $this->line("  [{$s['code']}] {$s['name']}");
        }
        $this->newLine();

        // Test 2: Test districts for first 10 states
        $this->info('TEST 2: Load districts for first 10 states');
        $this->info('─────────────────────────────────────────');
        $working = 0;
        $failed = 0;

        foreach (array_slice($states, 0, 10) as $state) {
            $districts = $service->checkoutCitiesForState($state['code']);
            
            if (is_array($districts) && count($districts) > 0) {
                $this->line("<fg=green>✓</> {$state['name']} ({$state['code']}): " . count($districts) . " districts");
                $this->line("    Sample: " . implode(", ", array_slice($districts, 0, 2)));
                $working++;
            } else {
                $this->line("<fg=red>✗</> {$state['name']} ({$state['code']}): FAILED TO LOAD");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Result: <fg=green>$working/10 states working</>, <fg=red>$failed/10 states failed</>");
        $this->newLine();

        // Test 3: Full state listing
        $this->info('TEST 3: All states and their district counts');
        $this->info('───────────────────────────────────────────');
        $totalStates = 0;
        $successStates = 0;
        
        foreach ($states as $state) {
            $districts = $service->checkoutCitiesForState($state['code']);
            $count = is_array($districts) ? count($districts) : 0;
            $status = $count > 0 ? "✓" : "✗";
            $color = $count > 0 ? "green" : "red";
            $this->line("<fg=$color>$status</> [{$state['code']}] {$state['name']}: $count districts");
            $totalStates++;
            if ($count > 0) $successStates++;
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info("<fg=green>SUMMARY</>");
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info("Total States: <fg=green>$totalStates</>");
        $this->info("States with districts: <fg=green>$successStates</>");
        $this->info("States without districts: <fg=red>" . ($totalStates - $successStates) . "</>");

        if ($successStates === $totalStates) {
            $this->info("\n<fg=green>✅ ALL TESTS PASSED - DISTRICT FILTER IS WORKING!</>");
        } else {
            $this->warn("\n⚠️  Some states don't have districts loaded");
        }

        return 0;
    }
}
