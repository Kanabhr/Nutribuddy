<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProfileAndSideSectionMediaRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Storage::fake('public');
    }

    public function test_profile_update_preserves_json_contract_and_replaces_avatar(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/old.jpg']);
        Storage::disk('public')->put('avatars/old.jpg', 'old avatar');

        $response = $this->actingAs($user)->postJson(route('personal-info.update'), [
            'first_name' => 'Ritik',
            'last_name' => 'Sharma',
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ]);

        $response->assertOk()->assertJsonStructure(['success', 'message', 'avatar_url']);
        $user->refresh();
        $this->assertSame('Ritik Sharma', $user->name);
        Storage::disk('public')->assertMissing('avatars/old.jpg');
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_side_section_update_saves_settings_and_replaces_logo(): void
    {
        Permission::create(['name' => 'manage administration', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Settings Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage administration');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);
        Setting::set('side_section_logo', 'settings/old.jpg', 'side_section');
        Storage::disk('public')->put('settings/old.jpg', 'old logo');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.side-section.update'), [
                'contact_number' => '1234567890',
                'email' => 'hello@example.com',
                'address' => 'Main street',
                'description' => 'About us',
                'social_platform' => ['instagram'],
                'social_url' => ['https://instagram.com/example'],
                'logo' => UploadedFile::fake()->image('new-logo.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Side section settings updated successfully.');

        $logo = Setting::query()->where('key', 'side_section_logo')->value('value');
        $this->assertNotSame('settings/old.jpg', $logo);
        $this->assertSame('1234567890', Setting::query()->where('key', 'side_section_contact_number')->value('value'));
        $this->assertSame(
            [['platform' => 'instagram', 'url' => 'https://instagram.com/example']],
            json_decode(Setting::query()->where('key', 'side_section_social_links')->value('value'), true),
        );
        Storage::disk('public')->assertMissing('settings/old.jpg');
        Storage::disk('public')->assertExists($logo);
    }
}
