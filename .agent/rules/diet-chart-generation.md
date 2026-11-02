# Diet Chart Generation Engine Rules

This rule defines the architectural standards, safety pipelines, and data flows for diet chart creation across **Nutribuddy**.

---

## 1. Generation Architecture & Pipeline

Diet charts are generated using a multi-stage pipeline across both **Deterministic (Rule-Based)** and **AI-Assisted** engines:

```
[Child Assessment / Questionnaire Answers]
                   │
                   ▼
       [ChildProfileService] (Normalization, BMI, Medical Triage)
                   │
                   ▼
          [TargetEngine] (ICMR Targets by Age & Sex)
                   │
                   ▼
       [FoodFilterPipeline] (Allergens, Diet Type, Age Compatibility)
                   │
                   ├───────────────────────────────────┐
                   ▼                                   ▼
      [RuleBasedDietPlanEngine]              [DietChartGenerator]
    (Scoring, Repetition Penalty,              (PromptBuilder + LLM)
     Nutrient Gap & Plates)                            │
                   │                                   │
                   └─────────────────┬─────────────────┘
                                     ▼
                      [DietPlan & DietPlanMeals]
                     (Review Status: pending_review)
```

### Core Services
- `App\Services\Nutrition\ChildProfileService`: Normalizes answers, computes BMI, determines review necessity for medical conditions.
- `App\Services\Nutrition\TargetEngine`: Fetches ICMR recommended dietary allowances (`IcmrNutritionTarget`) based on age and sex.
- `App\Services\Nutrition\FoodFilterPipeline`: Hard filters foods from `food_masters` based on allergens (dairy, nuts, gluten, egg), diet restrictions (`vegan`, `jain`, `veg`, `non-veg`), and age boundaries.
- `App\Services\Nutrition\RuleBasedDietPlanEngine`: 7-day meal slot scheduling (`Breakfast`, `Morning Snack`, `Lunch`, `Evening Snack`, `Dinner`), weighted ranking, repeat gap enforcement, and nutrient gap analysis.
- `App\Services\Nutrition\DietChartGenerator`: Builds structured AI prompts via `AIPromptBuilder` and queries the LLM with fallback mock data.

---

## 2. Strict Safety & Allergy Constraints (Non-Negotiable)

1. **Safety Before Scoring:** A food rejected by `FoodFilterPipeline` must **never** reach the candidate pool or ranking engine.
2. **Allergen Exclusions:**
   - `dairy` -> `contains_dairy = false`
   - `nuts` -> `contains_nuts = false`
   - `gluten` -> `contains_gluten = false`
   - `egg` -> `contains_egg = false`
3. **Diet Type Strictness:**
   - `vegan`: Must verify `is_vegan = true` and `contains_dairy = false`, `contains_egg = false`, `contains_honey = false`.
   - `jain`: Must satisfy `is_jain_ok = true`.
   - `vegetarian`: Must verify `is_veg = true` and `contains_egg = false`.
4. **Medical Condition Triage:**
   - Any assessment indicating non-trivial medical conditions or high nutritional risks must automatically set `professional_review_required = true` on the `DietPlan` and `ChildAssessment`.
   - Initial status must always remain `pending_review` until a licensed nutritionist approves or modifies the chart.

---

## 3. Meal Slots & Variety Algorithms

1. **Meal Slots by Pattern:**
   - `3 meals`: `['Breakfast', 'Lunch', 'Dinner']`
   - `2 + snacks`: `['Breakfast', 'Morning Snack', 'Lunch', 'Evening Snack']`
   - `default`: `['Breakfast', 'Morning Snack', 'Lunch', 'Evening Snack', 'Dinner']`
2. **Repetition Penalty:**
   - Enforce repeat gap (`repeat_gap_days` from `FoodMaster`, default: 3 days).
   - Penalize candidate score by -20 if dish was served within its repeat threshold to guarantee weekly variety.
3. **Regional & Preference Affinity:**
   - Add a +5 scoring bonus when `food->region` matches any selected region in the child's profile, and up to +5 for high `kid_accept_1_5` rating.

---

## 4. Persisting Plans & Nutrient Gap Audit

1. **Versioning:** Increment version dynamically (`$assessment->dietPlans()->max('version') + 1`) when generating updates.
2. **Nutrient Tracking:**
   - Always calculate and store `daily_nutrients` and `nutrient_gaps` against ICMR targets (Energy/Calories, Protein, Iron, Calcium, Fiber, Vitamin A, Vitamin C, Zinc).
   - Log generation debug metadata (candidate scores, reasons, and eligible food count) in `plan_data['generation_debug']`.
