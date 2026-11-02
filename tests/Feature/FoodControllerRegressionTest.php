<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FoodControllerRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::create(['name' => 'view diet chart', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Food Viewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('view diet chart');
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ])->assignRole($role), 'admin');
    }

    public function test_food_data_endpoint_preserves_datatables_response_shape(): void
    {
        Food::create([
            'dish_id' => 'TEST-001',
            'dish_name' => 'Oatmeal',
            'food_group' => 'Cereal',
            'slot' => 'Breakfast',
            'diet_type' => 'Vegetarian',
            'is_veg' => 'Yes',
            'region' => 'North',
            'energy_kcal' => 150,
        ]);

        $this->getJson(route('admin.nutrition.foods.data', [
            'draw' => 3,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 2, 'dir' => 'asc']],
        ]))
            ->assertOk()
            ->assertJsonPath('draw', 3)
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.serial_number', 1)
            ->assertJsonPath('data.0.dish_name', 'Oatmeal')
            ->assertJsonPath('data.0.is_veg', '<span class="badge bg-success">Yes</span>');
    }

    public function test_food_data_rejects_unbounded_order_columns(): void
    {
        $this->getJson(route('admin.nutrition.foods.data', [
            'order' => [['column' => 80, 'dir' => 'asc']],
        ]))->assertUnprocessable()->assertJsonValidationErrors('order.0.column');
    }

    public function test_food_data_filters_on_the_server_and_preserves_total_counts(): void
    {
        Food::create(['dish_id' => 'FILTER-001', 'dish_name' => 'Oatmeal', 'food_group' => 'Cereal', 'slot' => 'Breakfast', 'diet_type' => 'Vegetarian', 'is_veg' => 'Yes', 'region' => 'North']);
        Food::create(['dish_id' => 'FILTER-002', 'dish_name' => 'Fish curry', 'food_group' => 'Protein', 'slot' => 'Dinner', 'diet_type' => 'Non-vegetarian', 'is_veg' => 'No', 'region' => 'South']);

        $this->getJson(route('admin.nutrition.foods.data', ['draw' => 4, 'start' => 0, 'length' => 10, 'slot' => 'Breakfast']))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.dish_id', 'FILTER-001');

        $this->getJson(route('admin.nutrition.foods.data', ['draw' => 5, 'start' => 1, 'length' => 1]))
            ->assertOk()
            ->assertJsonPath('data.0.serial_number', 2);
    }

    public function test_food_details_returns_the_complete_record_for_the_view_modal(): void
    {
        $food = Food::create(['dish_id' => 'DETAIL-001', 'dish_name' => 'Oatmeal', 'protein_g' => 4.5, 'contains_nuts' => 'No']);

        $this->getJson(route('admin.nutrition.foods.show', $food->id))
            ->assertOk()
            ->assertJsonPath('data.dish_id', 'DETAIL-001')
            ->assertJsonPath('data.protein_g', 4.5)
            ->assertJsonPath('data.contains_nuts', 'No');
    }

    public function test_bulk_csv_upload_inserts_valid_rows_and_reports_duplicate_and_invalid_rows(): void
    {
        Permission::create(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        auth('admin')->user()->givePermissionTo('manage diet chart');
        Food::create(['dish_id' => 'EXISTS-001', 'dish_name' => 'Existing']);
        $csv = "dish_id,dish_name,food_group,slot,diet_type,is_veg,region,energy_kcal\n"
            ."CSV-001,Poha,Cereal,Breakfast,Vegetarian,Yes,West,210\n"
            ."EXISTS-001,Existing copy,Cereal,Breakfast,Vegetarian,Yes,West,200\n"
            ."CSV-001,Repeated,Snack,Evening,Vegetarian,Yes,West,120\n"
            .",Missing ID,Cereal,Breakfast,Vegetarian,Yes,West,100\n";

        $this->postJson(route('admin.nutrition.foods.bulk-upload.store'), [
            'file' => UploadedFile::fake()->createWithContent('foods.csv', $csv),
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('failedCount', 3)
            ->assertJsonPath('duplicateCount', 2);

        $this->assertDatabaseHas('foods', ['dish_id' => 'CSV-001', 'dish_name' => 'Poha']);
        $this->assertDatabaseCount('foods', 2);
    }

    public function test_view_only_admin_cannot_delete_foods(): void
    {
        $food = Food::create(['dish_id' => 'SAFE-001', 'dish_name' => 'Protected']);

        $this->deleteJson(route('admin.nutrition.foods.destroy', $food->id))->assertForbidden();
        $this->assertDatabaseHas('foods', ['id' => $food->id]);
    }

    public function test_food_used_in_a_diet_chart_cannot_be_deleted(): void
    {
        Permission::create(['name' => 'manage diet chart', 'guard_name' => 'admin']);
        auth('admin')->user()->givePermissionTo('manage diet chart');
        $food = Food::create(['dish_id' => 'IN-PLAN-001', 'dish_name' => 'Oatmeal']);
        $now = now();
        $childId = DB::table('children')->insertGetId([
            'user_id' => auth('admin')->id(),
            'name' => 'Test child',
            'sex' => 'boy',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $assessmentId = DB::table('child_assessments')->insertGetId([
            'child_id' => $childId,
            'model_level' => 'basic',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $planId = DB::table('diet_plans')->insertGetId([
            'child_assessment_id' => $assessmentId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $mealId = DB::table('diet_plan_meals')->insertGetId([
            'diet_plan_id' => $planId,
            'meal_slot' => 'Breakfast',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('diet_plan_items')->insert([
            'diet_plan_meal_id' => $mealId,
            'food_id' => $food->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->deleteJson(route('admin.nutrition.foods.destroy', $food->id))
            ->assertStatus(409)
            ->assertJsonPath('success', false);
        $this->assertDatabaseHas('foods', ['id' => $food->id]);
        $this->assertDatabaseHas('diet_plan_items', ['food_id' => $food->id]);
    }
}
