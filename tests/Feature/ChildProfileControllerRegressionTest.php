<?php

namespace Tests\Feature;

use App\Models\Nutrition\Child;
use App\Models\User;
use App\Services\Nutrition\DietChartGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildProfileControllerRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_create_a_child_profile_with_existing_response_contract(): void
    {
        $parent = User::factory()->create();
        $generator = \Mockery::mock(DietChartGeneratorService::class);
        $generator->shouldReceive('generateDietChart')->once()->andReturn(['success' => false]);
        $this->app->instance(DietChartGeneratorService::class, $generator);

        $this->actingAs($parent)->postJson(route('child-profile.store'), [
            'name' => 'Mira',
            'age' => 7,
            'sex' => 'girl',
            'height' => 121,
            'weight' => 23,
            'region' => 'North',
            'diet' => 'vegetarian',
            'picky' => 'moderate',
        ])->assertOk()->assertJsonStructure(['success', 'message', 'child_id', 'assessment_id']);

        $this->assertDatabaseHas('children', ['user_id' => $parent->id, 'name' => 'Mira']);
        $this->assertDatabaseCount('child_assessments', 1);
    }
}
