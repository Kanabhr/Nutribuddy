<?php

namespace Database\Seeders;

use App\Models\AssessmentOption;
use App\Models\AssessmentQuestion;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    /**
     * Seed the assessment questionnaire.
     *
     * Scoring guide used throughout:
     *   3 = Excellent / optimal behaviour
     *   2 = Good / adequate
     *   1 = Needs minor improvement
     *   0 = Needs significant improvement
     */
    public function run(): void
    {
        // Clear existing data to avoid duplicates on re-seed.
        AssessmentOption::query()->delete();
        AssessmentQuestion::query()->delete();

        $questionnaire = [

            // ── 1. NUTRITION ───────────────────────────────────────────────
            [
                'section'     => 'Nutrition',
                'title'       => 'How many servings of fresh fruits or vegetables does your child eat each day?',
                'description' => '1 serving ≈ 1 medium fruit or ½ cup of vegetables.',
                'sort_order'  => 1,
                'options'     => [
                    ['text' => '4 or more servings', 'score' => 3],
                    ['text' => '2–3 servings',        'score' => 2],
                    ['text' => '1 serving',            'score' => 1],
                    ['text' => 'None or rarely',       'score' => 0],
                ],
            ],
            [
                'section'     => 'Nutrition',
                'title'       => 'How often does your child consume dairy products (milk, curd, cheese, paneer)?',
                'description' => 'Dairy is a key source of calcium for growing children.',
                'sort_order'  => 2,
                'options'     => [
                    ['text' => 'Every day (2+ servings)',   'score' => 3],
                    ['text' => 'Most days (1 serving)',     'score' => 2],
                    ['text' => 'A few times a week',        'score' => 1],
                    ['text' => 'Rarely or never',           'score' => 0],
                ],
            ],
            [
                'section'     => 'Nutrition',
                'title'       => 'How much water / healthy fluids does your child drink per day?',
                'description' => null,
                'sort_order'  => 3,
                'options'     => [
                    ['text' => '6–8 glasses (water + natural juices)', 'score' => 3],
                    ['text' => '4–5 glasses',                          'score' => 2],
                    ['text' => '2–3 glasses',                          'score' => 1],
                    ['text' => 'Less than 2 glasses',                  'score' => 0],
                ],
            ],
            [
                'section'     => 'Nutrition',
                'title'       => 'How often does your child eat ultra-processed or junk food (chips, sugary drinks, instant noodles)?',
                'description' => null,
                'sort_order'  => 4,
                'options'     => [
                    ['text' => 'Never or once a month',    'score' => 3],
                    ['text' => 'Once or twice a week',     'score' => 2],
                    ['text' => '3–4 times a week',         'score' => 1],
                    ['text' => 'Every day',                'score' => 0],
                ],
            ],
            [
                'section'     => 'Nutrition',
                'title'       => 'Does your child eat a proper breakfast every morning?',
                'description' => null,
                'sort_order'  => 5,
                'options'     => [
                    ['text' => 'Yes, every day — a balanced meal',  'score' => 3],
                    ['text' => 'Yes, most days',                    'score' => 2],
                    ['text' => 'Sometimes — only on weekends',      'score' => 1],
                    ['text' => 'Rarely or skips breakfast often',   'score' => 0],
                ],
            ],

            // ── 2. SLEEP ───────────────────────────────────────────────────
            [
                'section'     => 'Sleep',
                'title'       => 'How many hours does your child sleep on a typical night?',
                'description' => 'Recommended: 9–11 hours for ages 6–13.',
                'sort_order'  => 1,
                'options'     => [
                    ['text' => '9–11 hours',        'score' => 3],
                    ['text' => '8 hours',            'score' => 2],
                    ['text' => '6–7 hours',          'score' => 1],
                    ['text' => 'Less than 6 hours',  'score' => 0],
                ],
            ],
            [
                'section'     => 'Sleep',
                'title'       => 'Does your child have a consistent bedtime routine?',
                'description' => null,
                'sort_order'  => 2,
                'options'     => [
                    ['text' => 'Yes, goes to bed at the same time every night', 'score' => 3],
                    ['text' => 'Mostly consistent with a few variations',       'score' => 2],
                    ['text' => 'Sometimes — no fixed routine',                  'score' => 1],
                    ['text' => 'No routine at all',                             'score' => 0],
                ],
            ],
            [
                'section'     => 'Sleep',
                'title'       => 'How often does your child use screens (phone/tablet/TV) within 1 hour of bedtime?',
                'description' => null,
                'sort_order'  => 3,
                'options'     => [
                    ['text' => 'Never',              'score' => 3],
                    ['text' => 'Rarely (once a week)', 'score' => 2],
                    ['text' => '3–4 nights a week', 'score' => 1],
                    ['text' => 'Every night',        'score' => 0],
                ],
            ],

            // ── 3. PHYSICAL ACTIVITY ───────────────────────────────────────
            [
                'section'     => 'Physical Activity',
                'title'       => 'How much physical activity / outdoor play does your child get each day?',
                'description' => 'WHO recommends at least 60 min of moderate-to-vigorous activity daily.',
                'sort_order'  => 1,
                'options'     => [
                    ['text' => '60+ minutes every day',         'score' => 3],
                    ['text' => '30–60 minutes most days',       'score' => 2],
                    ['text' => 'Less than 30 minutes per day',  'score' => 1],
                    ['text' => 'Very little or no activity',    'score' => 0],
                ],
            ],
            [
                'section'     => 'Physical Activity',
                'title'       => 'How many hours per day does your child spend watching screens (excluding school)?',
                'description' => null,
                'sort_order'  => 2,
                'options'     => [
                    ['text' => 'Less than 1 hour',  'score' => 3],
                    ['text' => '1–2 hours',          'score' => 2],
                    ['text' => '3–4 hours',          'score' => 1],
                    ['text' => 'More than 4 hours',  'score' => 0],
                ],
            ],
            [
                'section'     => 'Physical Activity',
                'title'       => 'Is your child enrolled in any sport, dance, yoga, or structured physical activity?',
                'description' => null,
                'sort_order'  => 3,
                'options'     => [
                    ['text' => 'Yes, attends regularly (3+ times per week)', 'score' => 3],
                    ['text' => 'Yes, occasionally (1–2 times per week)',     'score' => 2],
                    ['text' => 'Not currently but plays freely outside',     'score' => 1],
                    ['text' => 'No structured or free-play activity',        'score' => 0],
                ],
            ],

            // ── 4. MENTAL WELLNESS ─────────────────────────────────────────
            [
                'section'     => 'Mental Wellness',
                'title'       => 'How would you describe your child\'s general mood most days?',
                'description' => null,
                'sort_order'  => 1,
                'options'     => [
                    ['text' => 'Happy, calm, and curious',       'score' => 3],
                    ['text' => 'Generally positive with some ups and downs', 'score' => 2],
                    ['text' => 'Often cranky, anxious, or irritable', 'score' => 1],
                    ['text' => 'Frequently sad, withdrawn, or upset', 'score' => 0],
                ],
            ],
            [
                'section'     => 'Mental Wellness',
                'title'       => 'How often does your child spend quality time with family (meals together, play, conversation)?',
                'description' => null,
                'sort_order'  => 2,
                'options'     => [
                    ['text' => 'Every day',                   'score' => 3],
                    ['text' => 'Most days (4–5 times a week)', 'score' => 2],
                    ['text' => 'A few times a week',          'score' => 1],
                    ['text' => 'Rarely',                      'score' => 0],
                ],
            ],
            [
                'section'     => 'Mental Wellness',
                'title'       => 'Does your child express themselves freely and communicate when they are stressed or upset?',
                'description' => null,
                'sort_order'  => 3,
                'options'     => [
                    ['text' => 'Yes, openly and comfortably', 'score' => 3],
                    ['text' => 'Usually, with some prompting', 'score' => 2],
                    ['text' => 'Rarely — tends to bottle up emotions', 'score' => 1],
                    ['text' => 'Never — completely closed off', 'score' => 0],
                ],
            ],

            // ── 5. HYGIENE & HABITS ────────────────────────────────────────
            [
                'section'     => 'Hygiene & Habits',
                'title'       => 'How consistent is your child with brushing teeth twice daily and washing hands before meals?',
                'description' => null,
                'sort_order'  => 1,
                'options'     => [
                    ['text' => 'Always — without reminders', 'score' => 3],
                    ['text' => 'Usually — with occasional reminders', 'score' => 2],
                    ['text' => 'Sometimes — needs regular prompting', 'score' => 1],
                    ['text' => 'Rarely or refuses', 'score' => 0],
                ],
            ],
            [
                'section'     => 'Hygiene & Habits',
                'title'       => 'How often does your child fall sick (cold, fever, stomach issues) in a quarter?',
                'description' => null,
                'sort_order'  => 2,
                'options'     => [
                    ['text' => 'Rarely — 0–1 times', 'score' => 3],
                    ['text' => '2 times',             'score' => 2],
                    ['text' => '3–4 times',           'score' => 1],
                    ['text' => 'More than 4 times',   'score' => 0],
                ],
            ],

        ];

        foreach ($questionnaire as $qData) {
            $question = AssessmentQuestion::create([
                'title'       => $qData['title'],
                'description' => $qData['description'],
                'section'     => $qData['section'],
                'sort_order'  => $qData['sort_order'],
                'is_active'   => true,
            ]);

            foreach ($qData['options'] as $i => $opt) {
                AssessmentOption::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['text'],
                    'score'       => $opt['score'],
                    'sort_order'  => $i,
                ]);
            }
        }

        $this->command->info('Assessment questionnaire seeded: ' . count($questionnaire) . ' questions across 5 sections.');
    }
}
