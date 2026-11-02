<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nutrition\QuestionnaireQuestion;

class QuestionnaireQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = '{
  "title": "4-Model Questionnaire — designed by clinical value",
  "description": "Cumulative: each model keeps all earlier questions. Filter by model or section.",
  "models": [
    {
      "model": "1 Basic",
      "sections": [
        {
          "section": "A · Child & Growth",
          "questions": [
            {
              "id": 1,
              "question": "Child\'s name",
              "answer_type": "Text",
              "options_scale": "—",
              "clinical_reason": "Identity",
              "shapes_chart": "Chart label",
              "only_ask_if": "always"
            },
            {
              "id": 2,
              "question": "Age / date of birth",
              "answer_type": "Date",
              "options_scale": "2–15 yrs",
              "clinical_reason": "Sets every nutrient target",
              "shapes_chart": "ICMR RDA row",
              "only_ask_if": "always"
            },
            {
              "id": 3,
              "question": "Sex",
              "answer_type": "Choice",
              "options_scale": "Boy / Girl",
              "clinical_reason": "Needs split by sex from 10 yrs (iron, energy)",
              "shapes_chart": "ICMR RDA row",
              "only_ask_if": "always"
            },
            {
              "id": 4,
              "question": "Current weight",
              "answer_type": "Number (kg)",
              "options_scale": "e.g. 22",
              "clinical_reason": "Sizes energy/protein; with height = growth status",
              "shapes_chart": "Energy & protein scaling, BMI",
              "only_ask_if": "always"
            },
            {
              "id": 5,
              "question": "Current height",
              "answer_type": "Number (cm)",
              "options_scale": "e.g. 120",
              "clinical_reason": "Under/healthy/over vs ICMR",
              "shapes_chart": "BMI-for-age status",
              "only_ask_if": "always"
            }
          ]
        },
        {
          "section": "B · Food Frame",
          "questions": [
            {
              "id": 6,
              "question": "Diet type",
              "answer_type": "Choice",
              "options_scale": "Veg / Vegan / Non-Veg / Eggetarian / Jain",
              "clinical_reason": "Defines the whole eligible pool; Jain = no root veg",
              "shapes_chart": "Diet Type + Egg + Region rules",
              "only_ask_if": "always"
            },
            {
              "id": 7,
              "question": "Allergies & intolerances",
              "answer_type": "Multi",
              "options_scale": "Dairy/Lactose / Nuts & seeds / Gluten-wheat / Egg / None",
              "clinical_reason": "SAFETY — and lactose changes the calcium plan",
              "shapes_chart": "Dairy, Nuts/Seeds, Gluten, Egg filters",
              "only_ask_if": "always"
            },
            {
              "id": 8,
              "question": "Region / cuisine liked",
              "answer_type": "Multi",
              "options_scale": "North / West / East / South / No preference",
              "clinical_reason": "Familiar food gets eaten, not wasted",
              "shapes_chart": "Region column",
              "only_ask_if": "always"
            },
            {
              "id": 9,
              "question": "Usual meal pattern",
              "answer_type": "Choice",
              "options_scale": "3 meals + 2 snacks / 3 meals / 2 + snacks",
              "clinical_reason": "How many slots to fill",
              "shapes_chart": "Slot structure",
              "only_ask_if": "always"
            },
            {
              "id": 10,
              "question": "Milk & dairy taken per day",
              "answer_type": "Choice",
              "options_scale": "0 / 1 / 2 / 3+ glasses",
              "clinical_reason": "Baseline calcium & protein already covered",
              "shapes_chart": "Calcium baseline",
              "only_ask_if": "diet ≠ Vegan"
            },
            {
              "id": 11,
              "question": "Cooking oil / medium at home",
              "answer_type": "Choice",
              "options_scale": "Mustard / Groundnut / Coconut / Refined / Ghee-mix",
              "clinical_reason": "Fat quality & regional fit",
              "shapes_chart": "Fat/oil in portions",
              "only_ask_if": "always"
            },
            {
              "id": 12,
              "question": "Any diagnosed condition or medicine?",
              "answer_type": "Yes/No → text",
              "options_scale": "e.g. asthma, thyroid, none",
              "clinical_reason": "Safety; some conditions change the plan",
              "shapes_chart": "Clinical guardrail",
              "only_ask_if": "always"
            }
          ]
        }
      ]
    },
    {
      "model": "2 Plus",
      "sections": [
        {
          "section": "C · Lifestyle",
          "questions": [
            {
              "id": 13,
              "question": "Activity level",
              "answer_type": "Choice",
              "options_scale": "Low / Moderate / Active / Sporty",
              "clinical_reason": "Adjusts calories to real output",
              "shapes_chart": "Energy (PA multiplier)",
              "only_ask_if": "always"
            },
            {
              "id": 14,
              "question": "Screen time",
              "answer_type": "Choice",
              "options_scale": "<1 / 1–2 / 3–4 / 5+ hrs a day",
              "clinical_reason": "High screen ↔ grazing, poor appetite, weight risk",
              "shapes_chart": "Snack & portion strategy",
              "only_ask_if": "always"
            },
            {
              "id": 15,
              "question": "Sleep at night",
              "answer_type": "Choice",
              "options_scale": "<7 / 7–8 / 9–10 / 10+ hrs",
              "clinical_reason": "Growth hormone peaks in deep sleep; poor sleep stalls growth",
              "shapes_chart": "Flags growth risk; meal timing",
              "only_ask_if": "always"
            },
            {
              "id": 16,
              "question": "Appetite",
              "answer_type": "Choice",
              "options_scale": "Good / Variable / Poor",
              "clinical_reason": "Decides portion size vs nutrient density",
              "shapes_chart": "Portion & energy-density logic",
              "only_ask_if": "always"
            }
          ]
        },
        {
          "section": "D · What They Actually Eat",
          "questions": [
            {
              "id": 17,
              "question": "Favourite / must-have foods",
              "answer_type": "Text (optional)",
              "options_scale": "e.g. paneer, idli, banana",
              "clinical_reason": "Anchor the plan on foods they\'ll eat",
              "shapes_chart": "Food Name prefer",
              "only_ask_if": "optional"
            },
            {
              "id": 18,
              "question": "Disliked / refused foods",
              "answer_type": "Text (optional)",
              "options_scale": "e.g. lauki, karela",
              "clinical_reason": "Never plan refusals",
              "shapes_chart": "Food Name exclude",
              "only_ask_if": "optional"
            },
            {
              "id": 19,
              "question": "Food-frequency snapshot",
              "answer_type": "Grid (rarely→daily)",
              "options_scale": "Fruit · Veg · Dal/pulses · Egg/Non-veg · Milk/curd · Junk/fried · Sugary drinks",
              "clinical_reason": "THE core dietician read — shows the real gap vs target",
              "shapes_chart": "Prioritises which nutrients to push/cut",
              "only_ask_if": "always"
            },
            {
              "id": 20,
              "question": "Water intake",
              "answer_type": "Choice",
              "options_scale": "<3 / 4–5 / 6–8 glasses a day",
              "clinical_reason": "Low water ↔ constipation, poor appetite",
              "shapes_chart": "Fluid & fibre planning",
              "only_ask_if": "always"
            },
            {
              "id": 21,
              "question": "Eating out / tiffin",
              "answer_type": "Choice",
              "options_scale": "Rarely / Weekly / Most days",
              "clinical_reason": "Real-world adherence & hidden calories",
              "shapes_chart": "Plan realism & swaps",
              "only_ask_if": "optional"
            }
          ]
        }
      ]
    },
    {
      "model": "3 Pro",
      "sections": [
        {
          "section": "E · Goal",
          "questions": [
            {
              "id": 22,
              "question": "Main goal",
              "answer_type": "Choice",
              "options_scale": "Height & strength / Healthy weight gain / Weight management / Immunity / Brain & focus / Fix picky eating",
              "clinical_reason": "Focuses the whole plan",
              "shapes_chart": "Tips filter + gap priority",
              "only_ask_if": "always"
            }
          ]
        },
        {
          "section": "F · Clinical Signs & Gut",
          "questions": [
            {
              "id": 23,
              "question": "Growth trend (last 6 months)",
              "answer_type": "Choice",
              "options_scale": "Gaining well / About the same / Lost weight  •  vs peers: smaller / average / larger",
              "clinical_reason": "Trajectory matters more than one reading",
              "shapes_chart": "Escalation / calorie push",
              "only_ask_if": "always"
            },
            {
              "id": 24,
              "question": "Bowel habit",
              "answer_type": "Choice",
              "options_scale": "Normal / Constipation / Loose / Gas",
              "clinical_reason": "Very common; drives fibre, water, curd",
              "shapes_chart": "Fibre & probiotic emphasis",
              "only_ask_if": "always"
            },
            {
              "id": 25,
              "question": "How often falls ill",
              "answer_type": "Choice",
              "options_scale": "Rarely / Few times a year / Often (monthly)",
              "clinical_reason": "Frequent illness ↔ zinc/Vit C/immunity gap",
              "shapes_chart": "Immunity tips + micros",
              "only_ask_if": "always"
            },
            {
              "id": 26,
              "question": "Visible signs you notice",
              "answer_type": "Multi",
              "options_scale": "Pale eyes/skin · Tiredness · Hair fall · Brittle nails · Mouth-corner cracks/ulcers · Night-vision trouble · Delayed teeth/bowed legs · Poor focus · None",
              "clinical_reason": "Parent-visible clues to iron/Vit D/B-complex/Vit A/zinc gaps",
              "shapes_chart": "Flags likely deficiency → targeted foods + \'test\' nudge",
              "only_ask_if": "always"
            },
            {
              "id": 27,
              "question": "Sunlight / outdoor time",
              "answer_type": "Choice",
              "options_scale": "<15 / 15–30 / 30+ min a day",
              "clinical_reason": "Vitamin D is widely deficient even in sunny India",
              "shapes_chart": "Vit D strategy (food + sun + flag)",
              "only_ask_if": "always"
            },
            {
              "id": 28,
              "question": "Deworming in last 6 months?",
              "answer_type": "Choice",
              "options_scale": "Yes / No / Not sure",
              "clinical_reason": "Worm load quietly causes anemia & poor growth in India",
              "shapes_chart": "Anemia risk weighting; advise deworming",
              "only_ask_if": "always"
            },
            {
              "id": 29,
              "question": "Currently on any supplement?",
              "answer_type": "Yes/No → which",
              "options_scale": "e.g. iron syrup, multivitamin, none",
              "clinical_reason": "Avoids double-dosing; shows what\'s already covered",
              "shapes_chart": "Adjusts gap-fill & supplement flag",
              "only_ask_if": "always"
            }
          ]
        }
      ]
    },
    {
      "model": "4 Premium",
      "sections": [
        {
          "section": "G · History & Labs",
          "questions": [
            {
              "id": 30,
              "question": "Birth history",
              "answer_type": "Short form",
              "options_scale": "Birth weight · Term/Preterm · Breastfed (mo)",
              "clinical_reason": "Early history shapes the growth curve",
              "shapes_chart": "Growth-curve context (esp. <5 yrs)",
              "only_ask_if": "child age < 6 yrs"
            },
            {
              "id": 31,
              "question": "Latest blood report",
              "answer_type": "Numbers (optional)",
              "options_scale": "Hb · Ferritin · Vitamin D · B12 · TSH",
              "clinical_reason": "Turns \'maybe low\' into a measured gap",
              "shapes_chart": "Precise gap → honest supplement dosing",
              "only_ask_if": "only if report available"
            },
            {
              "id": 32,
              "question": "Periods started? (girls)",
              "answer_type": "Choice",
              "options_scale": "Not yet / Yes → age started",
              "clinical_reason": "Menarche sharply raises iron need",
              "shapes_chart": "Iron target uplift",
              "only_ask_if": "girl, age ≥ 9"
            },
            {
              "id": 33,
              "question": "Growth-spurt / puberty stage (teens)",
              "answer_type": "Choice",
              "options_scale": "Pre / Early / Mid / Late",
              "clinical_reason": "Spurt = peak energy, protein, calcium",
              "shapes_chart": "Target uplift during spurt",
              "only_ask_if": "age ≥ 10"
            }
          ]
        },
        {
          "section": "H · Monitoring & Context",
          "questions": [
            {
              "id": 34,
              "question": "Family history",
              "answer_type": "Multi",
              "options_scale": "Diabetes / Obesity / Thyroid / Deficiency / None",
              "clinical_reason": "Shapes prevention for the years ahead",
              "shapes_chart": "Long-term guidance & cautions",
              "only_ask_if": "always"
            },
            {
              "id": 35,
              "question": "Monthly food budget band",
              "answer_type": "Choice",
              "options_scale": "Economy / Moderate / Flexible",
              "clinical_reason": "A plan must be affordable to be followed",
              "shapes_chart": "Food choices within budget",
              "only_ask_if": "always"
            },
            {
              "id": 36,
              "question": "Monthly growth update",
              "answer_type": "Numbers",
              "options_scale": "New weight & height",
              "clinical_reason": "Tracks velocity; is the plan working?",
              "shapes_chart": "Re-run targets + trend chart",
              "only_ask_if": "monthly reminder"
            },
            {
              "id": 37,
              "question": "Your biggest worry (free text)",
              "answer_type": "Text",
              "options_scale": "—",
              "clinical_reason": "The human context a form can\'t code",
              "shapes_chart": "Routed to nutritionist",
              "only_ask_if": "optional"
            }
          ]
        }
      ]
    }
  ]
}';

        $data = json_decode($json, true);

        // Delete all old data just to be safe (ignoring foreign keys)
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        QuestionnaireQuestion::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        foreach ($data['models'] as $modelData) {
            $modelName = $modelData['model']; // "1 Basic"
            foreach ($modelData['sections'] as $sectionData) {
                $sectionName = $sectionData['section']; // "A · Child & Growth"
                foreach ($sectionData['questions'] as $q) {
                    QuestionnaireQuestion::create([
                        'model' => $modelName,
                        'section' => $sectionName,
                        'question' => $q['question'],
                        'answer_type' => $q['answer_type'],
                        'options_scale' => $q['options_scale'],
                        'clinical_reason' => $q['clinical_reason'],
                        'chart_effect' => $q['shapes_chart'],
                        'only_ask_if' => $q['only_ask_if'],
                        'sort_order' => $q['id'],
                        'is_optional' => strtolower($q['only_ask_if']) === 'optional'
                    ]);
                }
            }
        }
    }
}
