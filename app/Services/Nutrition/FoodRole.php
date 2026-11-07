<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\FoodMaster;

/**
 * Classifies a food as MAIN or SIDE based solely on the foods.food_group column.
 * No dish names are used.
 *
 * MAIN:  food_group tokens include "cereal", OR the group is exactly "Mixed".
 * SIDE:  everything else.
 * SOLE_BANNED: groups that must never be the sole dish of Lunch or Dinner.
 *              (Condiment, Salad, Soup, Dairy/Salad)
 */
class FoodRole
{
    /** Groups that are never allowed as the sole dish of Lunch or Dinner. */
    private const SOLE_BANNED_TOKENS = ['condiment', 'salad', 'soup'];

    /**
     * Tokenise a food_group string by splitting on any non-letter character.
     * E.g. "Cereal+Veg" → ['cereal', 'veg']
     *      "Dairy/Salad" → ['dairy', 'salad']
     *      "Mixed"       → ['mixed']
     *
     * @return string[]  lower-case letter-only tokens
     */
    public static function tokens(string $group): array
    {
        $parts = preg_split('/[^a-zA-Z]+/', $group, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_map('strtolower', $parts ?: []));
    }

    /**
     * Is this food a MAIN dish?
     *
     * A food is MAIN when its token list contains "cereal",
     * or when its group is exactly "Mixed" (case-insensitive).
     */
    public static function isMain(FoodMaster $food): bool
    {
        $group  = (string) ($food->food_group ?? '');
        $tokens = self::tokens($group);

        if (in_array('mixed', $tokens, true) && count($tokens) === 1) {
            return true;
        }

        return in_array('cereal', $tokens, true);
    }

    /**
     * Is this food a SIDE dish?
     * All foods that are not MAIN are SIDE.
     */
    public static function isSide(FoodMaster $food): bool
    {
        return ! self::isMain($food);
    }

    /**
     * Is this food banned as the sole dish of Lunch or Dinner?
     *
     * Returns true for: Condiment, Salad, Soup, Dairy/Salad
     * (any food_group whose token set contains "condiment", "salad", or "soup").
     */
    public static function isSoleBanned(FoodMaster $food): bool
    {
        $tokens = self::tokens((string) ($food->food_group ?? ''));
        foreach (self::SOLE_BANNED_TOKENS as $banned) {
            if (in_array($banned, $tokens, true)) {
                return true;
            }
        }
        return false;
    }
}
