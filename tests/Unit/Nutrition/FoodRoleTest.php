<?php

namespace Tests\Unit\Nutrition;

use App\Models\Nutrition\FoodMaster;
use App\Services\Nutrition\FoodRole;
use Tests\TestCase;

/**
 * FoodRoleTest — tests food classification from food_group column only.
 *
 * MAIN: tokens include "cereal", or group is exactly "Mixed".
 * SIDE: everything else.
 * SOLE_BANNED: Condiment, Salad, Soup (or any group whose tokens include
 *              those words, e.g. "Dairy/Salad").
 */
class FoodRoleTest extends TestCase
{
    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeFood(string $foodGroup): FoodMaster
    {
        $food = new FoodMaster();
        $food->setRawAttributes(['food_group' => $foodGroup]);
        return $food;
    }

    // ------------------------------------------------------------------
    // Token helper
    // ------------------------------------------------------------------

    public function test_tokens_splits_on_non_letter_characters(): void
    {
        $this->assertSame(['cereal', 'veg'], FoodRole::tokens('Cereal+Veg'));
        $this->assertSame(['veg', 'cereal'], FoodRole::tokens('Veg+Cereal'));
        $this->assertSame(['pulse', 'cereal'], FoodRole::tokens('Pulse+Cereal'));
        $this->assertSame(['dairy', 'salad'], FoodRole::tokens('Dairy/Salad'));
        $this->assertSame(['mixed'], FoodRole::tokens('Mixed'));
        $this->assertSame(['vegetable'], FoodRole::tokens('Vegetable'));
    }

    // ------------------------------------------------------------------
    // MAIN foods
    // ------------------------------------------------------------------

    public function test_cereal_group_is_main(): void
    {
        $this->assertTrue(FoodRole::isMain($this->makeFood('Cereal')));
    }

    public function test_cereal_plus_veg_is_main(): void
    {
        $this->assertTrue(FoodRole::isMain($this->makeFood('Cereal+Veg')));
    }

    public function test_veg_plus_cereal_is_main(): void
    {
        $this->assertTrue(FoodRole::isMain($this->makeFood('Veg+Cereal')));
    }

    public function test_pulse_plus_cereal_is_main(): void
    {
        $this->assertTrue(FoodRole::isMain($this->makeFood('Pulse+Cereal')));
    }

    public function test_mixed_group_is_main(): void
    {
        $this->assertTrue(FoodRole::isMain($this->makeFood('Mixed')));
    }

    // ------------------------------------------------------------------
    // SIDE foods
    // ------------------------------------------------------------------

    public function test_vegetable_group_is_side(): void
    {
        $this->assertTrue(FoodRole::isSide($this->makeFood('Vegetable')));
    }

    public function test_pulse_plus_veg_is_side(): void
    {
        $this->assertTrue(FoodRole::isSide($this->makeFood('Pulse+Veg')));
    }

    public function test_dairy_plus_veg_is_side(): void
    {
        $this->assertTrue(FoodRole::isSide($this->makeFood('Dairy+Veg')));
    }

    public function test_pulse_group_is_side(): void
    {
        $this->assertTrue(FoodRole::isSide($this->makeFood('Pulse')));
    }

    public function test_vegetable_plus_pulse_is_side(): void
    {
        $this->assertTrue(FoodRole::isSide($this->makeFood('Vegetable+Pulse')));
    }

    // ------------------------------------------------------------------
    // isMain and isSide are mutually exclusive
    // ------------------------------------------------------------------

    public function test_main_is_not_side(): void
    {
        $mains = ['Cereal', 'Cereal+Veg', 'Veg+Cereal', 'Pulse+Cereal', 'Mixed'];
        foreach ($mains as $group) {
            $food = $this->makeFood($group);
            $this->assertTrue(FoodRole::isMain($food), "{$group} must be MAIN");
            $this->assertFalse(FoodRole::isSide($food), "{$group} must not be SIDE");
        }
    }

    public function test_side_is_not_main(): void
    {
        $sides = ['Vegetable', 'Pulse+Veg', 'Dairy+Veg', 'Pulse', 'Vegetable+Pulse'];
        foreach ($sides as $group) {
            $food = $this->makeFood($group);
            $this->assertFalse(FoodRole::isMain($food), "{$group} must not be MAIN");
            $this->assertTrue(FoodRole::isSide($food), "{$group} must be SIDE");
        }
    }

    // ------------------------------------------------------------------
    // SOLE_BANNED foods
    // ------------------------------------------------------------------

    public function test_condiment_is_sole_banned(): void
    {
        $this->assertTrue(FoodRole::isSoleBanned($this->makeFood('Condiment')));
    }

    public function test_salad_is_sole_banned(): void
    {
        $this->assertTrue(FoodRole::isSoleBanned($this->makeFood('Salad')));
    }

    public function test_soup_is_sole_banned(): void
    {
        $this->assertTrue(FoodRole::isSoleBanned($this->makeFood('Soup')));
    }

    public function test_dairy_salad_is_sole_banned(): void
    {
        $this->assertTrue(FoodRole::isSoleBanned($this->makeFood('Dairy/Salad')));
    }

    public function test_cereal_is_not_sole_banned(): void
    {
        $this->assertFalse(FoodRole::isSoleBanned($this->makeFood('Cereal')));
    }

    public function test_vegetable_is_not_sole_banned(): void
    {
        $this->assertFalse(FoodRole::isSoleBanned($this->makeFood('Vegetable')));
    }
}
