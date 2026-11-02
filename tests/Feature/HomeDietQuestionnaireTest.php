<?php

namespace Tests\Feature;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeDietQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_basic_questions_and_only_active_plan_pricing(): void
    {
        QuestionnaireQuestion::create([
            'model' => '1 Basic',
            'section' => 'A · Child & Growth',
            'question' => "Child's name",
            'answer_type' => 'Text',
            'options_scale' => '—',
            'only_ask_if' => 'always',
            'is_optional' => false,
            'sort_order' => 1,
        ]);
        QuestionnaireQuestion::create([
            'model' => '1 Basic',
            'section' => 'A · Child & Growth',
            'question' => 'Current weight',
            'answer_type' => 'Number (kg)',
            'options_scale' => 'e.g. 22',
            'is_optional' => false,
            'sort_order' => 4,
        ]);
        QuestionnaireQuestion::create([
            'model' => '1 Basic',
            'section' => 'A · Child & Growth',
            'question' => 'Current height',
            'answer_type' => 'Number (cm)',
            'options_scale' => 'e.g. 120',
            'is_optional' => false,
            'sort_order' => 5,
        ]);
        QuestionnaireQuestion::create([
            'model' => '2 Plus',
            'section' => 'Additional questions',
            'question' => 'Plus-only question',
            'answer_type' => 'Text',
            'is_optional' => true,
            'sort_order' => 13,
        ]);

        SubscriptionPlan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price' => 0,
            'monthly_price' => 199,
            'quarterly_price' => 499,
            'annual_price' => null,
            'level' => 'basic',
            'is_active' => true,
            'features' => ['Two-day preview'],
        ]);
        SubscriptionPlan::create([
            'name' => 'Hidden plan',
            'slug' => 'hidden',
            'price' => 99,
            'is_active' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee("Child's name")
            ->assertSee('method="post"', false)
            ->assertSee('onsubmit="event.preventDefault(); return false;"', false)
            ->assertDontSee('action="http://127.0.0.1:8000/api/nutrition/questionnaire/submit"', false)
            ->assertSee('id="homeDietShowPlans" class="btn-next"', false)
            ->assertSee('home-diet-questionnaire.js')
            ->assertSee('home-diet-plan-card', false)
            ->assertSee('data-home-billing-cycle="monthly"', false)
            ->assertSee('data-home-billing-cycle="quarterly"', false)
            ->assertSee('data-home-billing-cycle="annually"', false)
            ->assertDontSee('data-plan-cycle=', false)
            ->assertSee("defaultPaymentGateway: 'cashfree'", false)
            ->assertSee('id="homeDietBack"', false)
            ->assertDontSee('Secure checkout powered by Cashfree')
            ->assertDontSee('Plan price is confirmed securely at checkout.')
            ->assertDontSee('value="razorpay"', false)
            ->assertSee('Two-day preview')
            ->assertSee('₹199')
            ->assertSee('data-home-height-unit')
            ->assertSee('Enter weight in kilograms.')
            ->assertDontSee('Plus-only question')
            ->assertDontSee('Hidden plan')
            ->assertViewHas('basicDietQuestions', fn ($questions) => $questions->count() === 3)
            ->assertViewHas('dietPlans', fn ($plans) => $plans->count() === 1);
    }

    public function test_homepage_basic_answers_use_the_existing_assessment_submission_flow(): void
    {
        $questions = collect([
            [1, "Child's name", 'Text', null],
            [2, 'Age / date of birth', 'Date', null],
            [3, 'Sex', 'Choice', 'Boy / Girl'],
            [4, 'Current weight', 'Number (kg)', null],
            [5, 'Current height', 'Number (cm)', null],
            [6, 'Diet type', 'Choice', 'Veg / Vegan / Non-Veg / Eggetarian / Jain'],
            [7, 'Allergies', 'Multi', 'Dairy/Lactose / Nuts & seeds / Gluten-wheat / Egg / None'],
        ])->map(fn ($item) => QuestionnaireQuestion::create([
            'model' => '1 Basic',
            'section' => 'Basic',
            'question' => $item[1],
            'answer_type' => $item[2],
            'options_scale' => $item[3],
            'sort_order' => $item[0],
            'is_optional' => false,
        ]));
        $parent = User::factory()->create();

        $response = $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug' => 'basic',
            'cycle' => 'monthly',
            'q_'.$questions[0]->id => 'Aarav',
            'q_'.$questions[1]->id => '2018-04-12',
            'q_'.$questions[2]->id => 'Boy',
            'q_'.$questions[3]->id => '22.5',
            'q_'.$questions[4]->id => '5ft 7in (170cm)',
            'q_'.$questions[5]->id => 'Veg',
            // Keep compatibility with the older browser payload that used [] in the JSON key.
            'q_'.$questions[6]->id.'[]' => ['Dairy/Lactose', 'Nuts & seeds'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $assessment = ChildAssessment::with('answers')->where('child_id', $response->json('child_id'))->firstOrFail();
        $this->assertSame('basic', $assessment->model_level);
        $this->assertSame('Aarav', $assessment->child->name);
        $this->assertSame(170.0, (float) $assessment->current_height_cm);
        $this->assertSame(22.5, (float) $assessment->current_weight_kg);
        $this->assertSame(['Dairy/Lactose', 'Nuts & seeds'], $assessment->answers->firstWhere('question_id', $questions[6]->id)->answer_value);
    }

    public function test_basic_submission_rejects_missing_or_out_of_range_answers_without_saving(): void
    {
        QuestionnaireQuestion::create([
            'model' => '1 Basic', 'section' => 'Basic', 'question' => "Child's name",
            'answer_type' => 'Text', 'sort_order' => 1, 'is_optional' => false,
        ]);
        QuestionnaireQuestion::create([
            'model' => '1 Basic', 'section' => 'Basic', 'question' => 'Age / date of birth',
            'answer_type' => 'Date', 'sort_order' => 2, 'is_optional' => false,
        ]);
        QuestionnaireQuestion::create([
            'model' => '1 Basic', 'section' => 'Basic', 'question' => 'Diet type',
            'answer_type' => 'Choice', 'options_scale' => 'Veg / Vegan', 'sort_order' => 6, 'is_optional' => false,
        ]);
        $parent = User::factory()->create();

        $this->actingAs($parent)->postJson(route('api.nutrition.questionnaire.submit'), [
            'plan_slug' => 'basic', 'q_1' => 'Aarav', 'q_2' => 'not-a-date', 'q_6' => 'Meat',
        ])->assertUnprocessable()->assertJsonValidationErrors(['q_2', 'q_6']);

        $this->assertDatabaseCount('children', 0);
        $this->assertDatabaseCount('child_assessments', 0);
    }
}
