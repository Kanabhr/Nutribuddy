# Seeder Notes — Part 1 Reference Data

## Files added / changed

| File | Action |
|---|---|
| `database/seeders/IcmrTargetsSeeder.php` | New — seeds `icmr_nutrition_targets` |
| `database/seeders/DeficiencyRulesSeeder.php` | New — seeds `deficiency_rules` |
| `database/seeders/NutritionTipsSeeder.php` | New — seeds `nutrition_tips` (35 rows) |
| `database/seeders/DatabaseSeeder.php` | Updated — calls the four new seeders |
| `database/seeders/FoodSeeder.php` | Neutralised — safe no-op with explanation |
| `app/Services/Nutrition/ComboEngine.php` | Rewritten — goal/flag → category mapping |
| `app/Services/Nutrition/RiskFlaggingEngine.php` | Updated — rejects "None", adds `resolveGapLabels()` |

---

## Needs nutritionist review

### 1. Q26 sign → CSV row mapping (DeficiencyRulesSeeder)

The questionnaire Q26 has 8 short option texts.  Each was mapped to the
closest row in the Deficiency Signs CSV.  This mapping is a clinical
judgement call and **must be confirmed by a nutritionist**.

| Q26 option (exact, used as `sign`) | CSV row matched | Likely gap seeded |
|---|---|---|
| Pale eyes/skin | "Pale inner eyelids/skin, tired, breathless on play" | Iron (anemia) |
| Tiredness | "Pale inner eyelids/skin, tired, breathless on play" | Iron (anemia) |
| Hair fall | "Hair fall, thin brittle nails" | Protein / Iron |
| Brittle nails | "Hair fall, thin brittle nails" | Protein / Iron |
| Mouth-corner cracks/ulcers | "Cracks at mouth corners, sore tongue, ulcers" | B-complex (B2/B3/B12) |
| Night-vision trouble | "Night-vision trouble, dry eyes" | Vitamin A |
| Delayed teeth/bowed legs | "Bowed legs, delayed teeth/walking, bone or leg pain" | Vitamin D (rickets) |
| Poor focus | "Poor concentration, irritable, low energy at school" | Iron / Omega-3 / skipped breakfast |
| None | — (no rule seeded) | — |

**Tiredness** was mapped to the iron row (same CSV row as pale eyes/skin).
It could alternatively — or additionally — map to the "Poor concentration"
row (Iron / Omega-3).  A nutritionist should decide whether a second
deficiency_rules row with `sign = 'Tiredness'` pointing to that gap is
warranted.

### 2. CSV rows with no Q26 match (skipped, not seeded)

These CSV rows have no corresponding Q26 option and were intentionally
not seeded.  If Q26 is extended in future, these can be added:

| CSV "What you notice" | Likely gap |
|---|---|
| Falls ill often, cuts heal slowly | Zinc / Vitamin C |
| White spots on nails, poor appetite, slow growth | Zinc |
| Hard/infrequent stools, tummy pain | Fibre + water |
| Excess belly fat, breathless, low activity | Energy imbalance |

### 3. Goal → Tip-category mapping (ComboEngine)

Defined in `ComboEngine::GOAL_CATEGORY_MAP`.  The questionnaire goal
labels (Q22) do not match the CSV tip categories one-to-one.  The mapping
below should be reviewed:

| Questionnaire goal (Q22) | Tip categories assigned |
|---|---|
| Height & strength | Strength & Muscle, Bones & Calcium |
| Healthy weight gain | Energy & Growth, Strength & Muscle |
| Weight management | Healthy Weight, Overall |
| Immunity | Immunity |
| Brain & focus | Brain & Focus |
| Fix picky eating | Energy & Growth, Digestion & Gut, Overall |

### 4. Deficiency flag → Tip-category mapping (ComboEngine)

Defined in `ComboEngine::FLAG_CATEGORY_MAP`.

| Deficiency flag (likely_gap) | Tip categories assigned |
|---|---|
| Iron (anemia) | Iron & Blood |
| Protein / Iron | Iron & Blood, Strength & Muscle |
| B-complex (B2/B3/B12) | Overall |
| Vitamin A | Eyes & Vision |
| Vitamin D (rickets) | Bones & Calcium |
| Iron / Omega-3 / skipped breakfast | Iron & Blood, Brain & Focus |

---

## Null / missing values

| Field | Reason |
|---|---|
| `icmr_nutrition_targets.fiber_g` | Not present in the ICMR-NIN 2020 CSV. **Not written by IcmrTargetsSeeder** — existing rows keep their current value; new rows get the column default (0). |
| `icmr_nutrition_targets.vitamin_d_mcg` | Not present in the CSV. Same treatment as `fiber_g` — not written on UPDATE, column default (0) on INSERT. |
| `icmr_nutrition_targets.iodine` | No column exists in the table; CSV value skipped. |
| `deficiency_foods.*` | FK points to `food_master` (legacy table). No rows seeded until the food_master SQL import runs. |
| `nutrition_tips.key_nutrient` | No column in the migration; folded into `science_reason` text for traceability. |

---

## ICMR age-band coverage

All 9 bands from the CSV are seeded (1-3, 4-6, 7-9, 10-12 boy, 10-12 girl,
13-15 boy, 13-15 girl, 16-18 boy, 16-18 girl).

The **existing row** seeded by migration `2026_09_09_000001` (4-6 / all) has
`protein_g=16`, `fat_g=0`, `fiber_g=20`, `vitamin_d_mcg=15`.
`IcmrTargetsSeeder` will UPDATE that row with the canonical CSV values
(`protein_g=15.9`, `fat_g=25`) but will **not touch** `fiber_g` or
`vitamin_d_mcg` — those columns are excluded from the upsert values payload,
so `fiber_g=20` and `vitamin_d_mcg=15` are preserved exactly as the migration
left them.

## NutritionTipsSeeder — sex column check

The CSV `Sex` column uses "Both", "Boys", "Girls".  All 35 rows in
`NutritionTipsSeeder::tips()` have already been translated to the enum values
required by `nutrition_tips.sex`:

| CSV value | Stored as |
|---|---|
| Both  | `'all'` |
| Boys  | `'boy'` |
| Girls | `'girl'` |

No raw "Both"/"Boys"/"Girls" strings appear in the seeder data array.

---

## deficiency_foods — next steps

`deficiency_foods` links a `deficiency_rules` row to a `food_master` row.
`food_master` is populated by the SQL import (not this seeder).  After the
import:

1. Query `food_master` for each food name in the CSV "Foods to add" column.
2. Insert rows into `deficiency_foods` with `rule_id` and `food_id`.
3. For foods not found in `food_master`, log them for the nutrition team.

---

## phpMyAdmin row-count verification

```sql
-- ICMR targets: should be >= 2 (the spec minimum; 9 rows seeded here)
SELECT COUNT(*) AS icmr_rows FROM icmr_nutrition_targets;

-- Deficiency rules: should be > 0 (8 rows seeded)
SELECT COUNT(*) AS rule_rows FROM deficiency_rules;

-- Nutrition tips: should be 35
SELECT COUNT(*) AS tip_rows FROM nutrition_tips;

-- Verify every deficiency_rules.sign appears in Q26 options_scale
-- (QuestionnaireQuestionsSeeder stores Q26 with sort_order = 26)
SELECT dr.sign,
       CASE WHEN qq.options_scale LIKE CONCAT('%', dr.sign, '%')
            THEN 'MATCH' ELSE 'MISSING' END AS q26_check
FROM deficiency_rules dr
JOIN questionnaire_questions qq ON qq.sort_order = 26
ORDER BY dr.sign;
```
