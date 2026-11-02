<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\DietPlanItem;
use App\Models\Nutrition\FoodMaster;

class VerifyDietChartIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:diet-chart-integration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify all diet chart display components are properly integrated';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verifying Diet Chart Integration...');
        $this->newLine();

        // Check 1: Models exist and are loadable
        $this->check('Models exist and can be instantiated');
        try {
            $child = new Child();
            $assessment = new ChildAssessment();
            $plan = new DietPlan();
            $meal = new DietPlanMeal();
            $item = new DietPlanItem();
            $food = new FoodMaster();
            $this->completeCheck();
        } catch (\Exception $e) {
            $this->failCheck("Model loading failed: " . $e->getMessage());
            return 1;
        }

        // Check 2: Relationships are defined
        $this->check('Model relationships defined correctly');
        try {
            $reflectionMethods = [
                'App\Models\Nutrition\Child' => ['assessments', 'user'],
                'App\Models\Nutrition\ChildAssessment' => ['child', 'dietPlans', 'answers', 'clinicalFlags'],
                'App\Models\Nutrition\DietPlan' => ['assessment', 'meals'],
                'App\Models\Nutrition\DietPlanMeal' => ['plan', 'items'],
                'App\Models\Nutrition\DietPlanItem' => ['meal', 'food'],
                'App\Models\Nutrition\FoodMaster' => ['nutrients'],
            ];

            foreach ($reflectionMethods as $class => $methods) {
                $reflection = new \ReflectionClass($class);
                foreach ($methods as $method) {
                    if (!$reflection->hasMethod($method)) {
                        throw new \Exception("Method '{$method}' not found in {$class}");
                    }
                }
            }
            $this->completeCheck();
        } catch (\Exception $e) {
            $this->failCheck("Relationship check failed: " . $e->getMessage());
            return 1;
        }

        // Check 3: Database tables exist
        $this->check('Database tables exist');
        try {
            $tables = [
                'children',
                'child_assessments',
                'diet_plans',
                'diet_plan_meals',
                'diet_plan_items',
                'food_master',
                'food_nutrients',
            ];

            $schema = \Illuminate\Support\Facades\Schema::class;
            foreach ($tables as $table) {
                if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    throw new \Exception("Table '{$table}' does not exist");
                }
            }
            $this->completeCheck();
        } catch (\Exception $e) {
            $this->failCheck("Database check failed: " . $e->getMessage());
            return 1;
        }

        // Check 4: Routes are registered
        $this->check('Routes are registered');
        try {
            $routes = \Illuminate\Support\Facades\Route::getRoutes();
            $routeNames = collect($routes)->pluck('name')->toArray();

            $expectedRoutes = [
                'child.diet-chart.show',
                'child.diet-chart.regenerate',
                'child.diet-chart.download-pdf',
            ];

            foreach ($expectedRoutes as $routeName) {
                if (!in_array($routeName, $routeNames)) {
                    throw new \Exception("Route '{$routeName}' not registered");
                }
            }
            $this->completeCheck();
        } catch (\Exception $e) {
            $this->failCheck("Route check failed: " . $e->getMessage());
            return 1;
        }

        // Check 5: Controller class exists and is loadable
        $this->check('Controller class exists and is loadable');
        try {
            $controller = app(\App\Http\Controllers\Frontend\Nutrition\DietChartDisplayController::class);
            $this->completeCheck();
        } catch (\Exception $e) {
            $this->failCheck("Controller loading failed: " . $e->getMessage());
            return 1;
        }

        // Check 6: View template exists
        $this->check('View template exists');
        try {
            $viewPath = resource_path('views/child-profile/diet-chart-display.blade.php');
            if (!file_exists($viewPath)) {
                throw new \Exception("View file not found at {$viewPath}");
            }
            $this->completeCheck();
        } catch (\Exception $e) {
            $this->failCheck("View check failed: " . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('✅ All integration checks passed!');
        $this->info('');
        $this->info('To test the integration:');
        $this->line('1. Create a child profile: POST /child-profile');
        $this->line('2. Visit diet chart: GET /child/{childId}/diet-chart');
        $this->line('3. Generate a plan: POST /child/{childId}/diet-chart/regenerate');
        $this->newLine();

        return 0;
    }

    private function check(string $description): void
    {
        $this->line("  ⏳ {$description}...");
    }

    private function completeCheck(): void
    {
        $this->line("\033[1A\033[K  ✅ {$this->currentCheck()}");
    }

    private function failCheck(string $reason): void
    {
        $this->line("\033[1A\033[K  ❌ {$this->currentCheck()}");
        $this->error("     Reason: {$reason}");
    }

    private function currentCheck(): string
    {
        // Extract last checked item (this is approximate)
        return 'Check';
    }
}
