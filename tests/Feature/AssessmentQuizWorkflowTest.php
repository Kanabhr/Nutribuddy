<?php

namespace Tests\Feature;

use App\Models\AssessmentOption;
use App\Models\AssessmentQuestion;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AssessmentQuizWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_public_questionnaire_page_loads_with_valid_plan(): void
    {
        SubscriptionPlan::create([
            'name' => 'Basic Assessment',
            'slug' => 'basic',
            'price' => 499,
            'is_active' => true,
        ]);

        $response = $this->get(route('frontend.nutrition.questionnaire', ['plan' => 'basic']));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.nutrition.questionnaire');
        $response->assertViewHas(['plan', 'cycle', 'questions']);
    }

    public function test_public_questionnaire_redirects_if_plan_missing(): void
    {
        $response = $this->get(route('frontend.nutrition.questionnaire'));

        $response->assertRedirect(route('diet_chart'));
        $response->assertSessionHas('error');
    }

    public function test_child_questionnaire_uses_a_path_parameter_and_checks_ownership(): void
    {
        SubscriptionPlan::create([
            'name' => 'Basic Assessment',
            'slug' => 'basic',
            'price' => 499,
            'is_active' => true,
        ]);
        $owner = User::factory()->create();
        $otherParent = User::factory()->create();
        $child = Child::create(['user_id' => $owner->id, 'name' => 'Owned child', 'sex' => 'girl']);
        ChildAssessment::create([
            'child_id' => $child->id,
            'model_level' => 'basic',
            'current_weight_kg' => 15,
            'current_height_cm' => 95,
            'diet_type' => 'vegetarian',
        ]);

        $this->actingAs($owner)
            ->get(route('frontend.nutrition.questionnaire.child', $child))
            ->assertOk()
            ->assertViewHas('child', fn ($viewChild) => $viewChild->is($child));

        $this->actingAs($otherParent)
            ->get(route('frontend.nutrition.questionnaire.child', $child))
            ->assertNotFound();
    }

    public function test_admin_with_permission_can_view_assessment_questions(): void
    {
        Permission::create(['name' => 'view assessment', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Quiz Admin', 'guard_name' => 'admin']);
        $role->givePermissionTo('view assessment');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.assessment.questions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.assessment.questions.index');
        $response->assertViewHas(['questions', 'sections']);
    }

    public function test_admin_can_toggle_assessment_question_status(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        Permission::create(['name' => 'manage assessment', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Quiz Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage assessment');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $question = AssessmentQuestion::create([
            'title' => 'How many hours does your child sleep?',
            'section' => 'Daily Routine & Sleep',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.assessment.questions.toggle', $question));

        $response->assertSuccessful();
        $this->assertDatabaseHas('assessment_questions', [
            'id' => $question->id,
            'is_active' => false,
        ]);
    }

    public function test_assessment_question_and_option_mutations_persist_together(): void
    {
        Permission::create(['name' => 'manage assessment', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Assessment Editor', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage assessment');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.assessment.questions.store'), [
                'title' => 'How often does your child exercise?',
                'section' => 'Growth & Physical Development',
                'options' => [
                    ['option_text' => 'Rarely', 'score' => 1],
                    ['option_text' => 'Daily', 'score' => 5],
                ],
            ])
            ->assertRedirect();

        $question = AssessmentQuestion::with('options')->firstOrFail();
        $this->assertTrue($question->is_active);
        $this->assertCount(2, $question->options);
        $rarely = $question->options->firstWhere('option_text', 'Rarely');
        $daily = $question->options->firstWhere('option_text', 'Daily');

        $this->patch(route('admin.assessment.questions.update', $question), [
            'title' => 'How often does your child exercise each week?',
            'section' => 'Growth & Physical Development',
            'options' => [
                ['id' => $rarely->id, 'option_text' => 'Once', 'score' => 2],
                ['id' => $daily->id, '_delete' => true, 'option_text' => 'Daily', 'score' => 5],
                ['option_text' => 'Several times', 'score' => 4],
            ],
        ])->assertRedirect();

        $question->refresh()->load('options');
        $this->assertSame('How often does your child exercise each week?', $question->title);
        $this->assertCount(2, $question->options);
        $this->assertSame('Once', $question->options->firstWhere('id', $rarely->id)->option_text);
        $this->assertFalse($question->options->contains('id', $daily->id));
        $this->assertTrue($question->options->contains('option_text', 'Several times'));
    }

    public function test_failed_assessment_score_write_rolls_back_the_attempt_and_answers(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('The rollback assertion uses a SQLite trigger.');
        }

        $user = User::factory()->create();
        $question = AssessmentQuestion::create([
            'title' => 'How often does your child exercise?',
            'section' => 'Growth & Physical Development',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $option = AssessmentOption::create([
            'question_id' => $question->id,
            'option_text' => 'Daily',
            'score' => 5,
            'sort_order' => 1,
        ]);
        DB::unprepared("CREATE TRIGGER fail_assessment_scores BEFORE INSERT ON assessment_section_scores BEGIN SELECT RAISE(ABORT, 'score write failed'); END");

        try {
            app(AssessmentService::class)->calculateAndStore($user, [$question->id => $option->id]);
            $this->fail('The assessment score insert should fail.');
        } catch (QueryException) {
            $this->assertDatabaseCount('assessment_attempts', 0);
            $this->assertDatabaseCount('assessment_answers', 0);
            $this->assertDatabaseCount('assessment_section_scores', 0);
        }
    }

    public function test_admin_with_permission_can_view_nutrition_questions_list(): void
    {
        Permission::create(['name' => 'view diet chart', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Nutrition Admin', 'guard_name' => 'admin']);
        $role->givePermissionTo('view diet chart');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.nutrition.questions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.nutrition.questions.index');
        $response->assertViewHas('groupedQuestions');
    }
}
