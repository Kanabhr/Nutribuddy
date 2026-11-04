<?php

namespace App\Support;

use App\Models\Nutrition\Child;
use App\Models\User;

/**
 * Single source of truth for "what does each plan include?".
 * Source: Kids_Diet_4_Subscription_Models.xlsx (Plans & Features tab).
 */
class PlanAccess
{
    /** Plan slug => rank. Plans are cumulative: a higher rank includes everything below it. */
    public const RANK = ['basic' => 1, 'plus' => 2, 'pro' => 3, 'premium' => 4];

    /** Rank => value stored in questionnaire_questions.model */
    public const QUESTION_MODELS = [1 => '1 Basic', 2 => '2 Plus', 3 => '3 Pro', 4 => '4 Premium'];

    /** Rank => cumulative number of questions (12 / 21 / 29 / 37). */
    public const QUESTION_LIMITS = [1 => 12, 2 => 21, 3 => 29, 4 => 37];

    /** Feature => minimum plan rank that includes it. */
    public const FEATURES = [
        'diet_chart'          => 1,
        'growth_report'       => 1,
        'portions_activity'   => 2,
        'likes_dislikes'      => 2,
        'seven_day_variety'   => 2,
        'lifestyle_tips'      => 2,
        'health_score'        => 3,
        'deficiency_check'    => 3,
        'goal_plan'           => 3,
        'shopping_list'       => 3,
        'full_recipes'        => 3,
        'recipe_library'      => 3,
        'goal_recipes'        => 3,
        'gut_immunity'        => 3,
        'supplement_advice'   => 3,
        'monthly_tracking'    => 4,
        'budget_planning'     => 4,
        'seasonal_packs'      => 4,
        'custom_recipes'      => 4,
        'blood_report'        => 4,
        'puberty_adjustments' => 4,
        'family_history'      => 4,
        'nutritionist_chat'   => 4,
    ];

    public static function rank(?string $slug): int
    {
        return self::RANK[strtolower((string) $slug)] ?? 0;
    }

    public static function allows(?string $slug, string $feature): bool
    {
        $min = self::FEATURES[$feature] ?? null;

        return $min !== null && self::rank($slug) >= $min;
    }

    /** Questionnaire "model" values a plan must answer (cumulative). */
    public static function questionModels(?string $slug): array
    {
        $rank = max(1, self::rank($slug));

        return array_values(array_filter(
            self::QUESTION_MODELS,
            fn (int $r) => $r <= $rank,
            ARRAY_FILTER_USE_KEY
        ));
    }

    public static function questionLimit(?string $slug): int
    {
        return self::QUESTION_LIMITS[max(1, self::rank($slug))];
    }

    /** Slug of the highest plan among the given DietSubscription rows (needs ->subscriptionPlan loaded). */
    public static function highestSlug(iterable $subscriptions): ?string
    {
        $best = null;
        $bestRank = 0;

        foreach ($subscriptions as $subscription) {
            $slug = $subscription->subscriptionPlan?->slug;
            if (self::rank($slug) > $bestRank) {
                $best = $slug;
                $bestRank = self::rank($slug);
            }
        }

        return $best;
    }

    public static function slugForChild(Child $child): ?string
    {
        return self::highestSlug($child->dietSubscriptions()->usable()->with('subscriptionPlan')->get());
    }

    public static function slugForUser(User $user): ?string
    {
        return self::highestSlug($user->dietSubscriptions()->usable()->with('subscriptionPlan')->get());
    }
}
