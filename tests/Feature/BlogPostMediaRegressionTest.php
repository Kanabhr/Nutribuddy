<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BlogPostMediaRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Storage::fake('public');
    }

    public function test_blog_post_image_is_replaced_without_deleting_it_before_the_update(): void
    {
        $admin = $this->contentManager();
        $post = $this->createPost(['featured_image' => 'blog-posts/old.jpg']);
        Storage::disk('public')->put('blog-posts/old.jpg', 'old image');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.ecommerce.blog-posts.update', $post), [
                'title' => 'Updated article',
                'content' => 'Updated content',
                'status' => 'draft',
                'featured_image' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertRedirect();

        $post->refresh();
        $this->assertSame('updated-article', $post->slug);
        $this->assertNotSame('blog-posts/old.jpg', $post->featured_image);
        Storage::disk('public')->assertMissing('blog-posts/old.jpg');
        Storage::disk('public')->assertExists($post->featured_image);
    }

    public function test_permanently_deleting_a_trashed_post_removes_its_featured_image(): void
    {
        $admin = $this->contentManager();
        $post = $this->createPost(['featured_image' => 'blog-posts/removed.jpg']);
        $post->delete();
        Storage::disk('public')->put('blog-posts/removed.jpg', 'image');

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.ecommerce.blog-posts.force-destroy', $post->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing('blog-posts/removed.jpg');
    }

    private function contentManager(): User
    {
        Permission::create(['name' => 'manage content', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Content Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage content');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }

    /** @param array<string, mixed> $attributes */
    private function createPost(array $attributes = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'title' => 'Original article',
            'slug' => 'original-article',
            'content' => 'Original content',
            'status' => 'draft',
        ], $attributes));
    }
}
