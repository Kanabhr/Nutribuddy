<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Nutrition\DietChartGeneratorService;
use App\Services\Nutrition\TargetEngine;
use App\Services\Nutrition\FoodFilterPipeline;
use App\Services\Nutrition\MealBuilder;
use App\Services\Nutrition\NutritionCalculator;
use App\Services\Nutrition\AdequacyAnalyzer;
use App\Services\Nutrition\ComboEngine;
use App\Services\Nutrition\RiskFlaggingEngine;
use App\Services\Nutrition\AssessmentEngine;
use App\Services\Nutrition\RuleBasedDietPlanEngine;

class NutritionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register TargetEngine as singleton
        $this->app->singleton(TargetEngine::class, function ($app) {
            return new TargetEngine();
        });

        // Register FoodFilterPipeline
        $this->app->singleton(FoodFilterPipeline::class, function ($app) {
            return new FoodFilterPipeline();
        });

        // Register MealBuilder
        $this->app->singleton(MealBuilder::class, function ($app) {
            return new MealBuilder();
        });

        // Register NutritionCalculator
        $this->app->singleton(NutritionCalculator::class, function ($app) {
            return new NutritionCalculator();
        });

        // Register AdequacyAnalyzer
        $this->app->singleton(AdequacyAnalyzer::class, function ($app) {
            return new AdequacyAnalyzer();
        });

        // Register ComboEngine (assuming it exists)
        $this->app->singleton(ComboEngine::class, function ($app) {
            return new ComboEngine();
        });

        // Register RiskFlaggingEngine
        $this->app->singleton(RiskFlaggingEngine::class, function ($app) {
            return new RiskFlaggingEngine();
        });

        // Register AssessmentEngine
        $this->app->singleton(AssessmentEngine::class, function ($app) {
            return new AssessmentEngine();
        });

        // Register DietChartGeneratorService with all dependencies
        $this->app->singleton(DietChartGeneratorService::class, function ($app) {
            return new DietChartGeneratorService(
                $app->make(RuleBasedDietPlanEngine::class),
                $app->make(\App\Services\Nutrition\DietLifecycleNotificationService::class),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration files if needed
        // $this->publishes([
        //     __DIR__.'/../Config/nutrition.php' => config_path('nutrition.php'),
        // ]);
    }
}
