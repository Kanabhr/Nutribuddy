<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Controllers\Admin\Dashboard\DashboardExportController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardExportRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::create(['name' => 'view dashboard', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Dashboard Viewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('view dashboard');
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ])->assignRole($role), 'admin');
    }

    public function test_dashboard_export_downloads_an_xlsx_workbook(): void
    {
        $this->assertSame(
            DashboardExportController::class,
            \Illuminate\Support\Facades\Route::getRoutes()->getByName('admin.dashboard.export')->getActionName(),
        );

        $this->get(route('admin.dashboard.export'))
            ->assertOk()
            ->assertDownload();
    }
}
