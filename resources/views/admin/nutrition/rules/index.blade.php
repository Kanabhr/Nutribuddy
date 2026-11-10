@extends('layouts.admin')

@php($title = 'Diet Generation Rules')
@php($subTitle = 'How the active diet chart generator builds and reviews a plan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/diet-generation-rules.css') }}?v={{ filemtime(public_path('css/diet-generation-rules.css')) }}">
@endpush

@section('content')
    <main class="diet-rules-page">
        <header class="diet-rules-hero">
            <div class="diet-rules-hero-copy">
                <span class="diet-rules-eyebrow"><i class="ri-shield-star-line" aria-hidden="true"></i> Nutrition engine guide</span>
                <h1>How a diet chart is created</h1>
                <p>The active generator builds a seven-day draft from a child’s assessment, the food catalog, and configured nutrition targets. A nutritionist reviews each draft before it reaches the family.</p>
                <div class="diet-rules-tags" aria-label="Key process safeguards">
                    <span><i class="ri-shield-check-line" aria-hidden="true"></i> Safety filters first</span>
                    <span><i class="ri-calendar-check-line" aria-hidden="true"></i> Seven-day schedule</span>
                    <span><i class="ri-user-star-line" aria-hidden="true"></i> Nutritionist approval</span>
                </div>
            </div>
            <div class="diet-rules-hero-icon" aria-hidden="true"><i class="ri-restaurant-2-line"></i></div>
        </header>

        <section class="diet-rules-flow" aria-labelledby="diet-rules-flow-heading">
            <div class="diet-rules-section-heading">
                <div><span class="diet-rules-kicker">Generation flow</span><h2 id="diet-rules-flow-heading">From assessment to family view</h2></div>
                <p>Each stage feeds the next; the review gate stays in place for every generated chart.</p>
            </div>
            <ol class="diet-flow-steps">
                <li><span>01</span><strong>Assessment</strong><small>Answers and child profile</small></li>
                <li><span>02</span><strong>Normalize</strong><small>Diet, allergies, needs</small></li>
                <li><span>03</span><strong>Set targets</strong><small>Age and sex based RDA</small></li>
                <li><span>04</span><strong>Filter foods</strong><small>Diet and allergy checks</small></li>
                <li><span>05</span><strong>Rank choices</strong><small>Nutrient fit and variety</small></li>
                <li><span>06</span><strong>Build a week</strong><small>Meals from Monday to Sunday</small></li>
                <li><span>07</span><strong>Review & publish</strong><small>Nutritionist approval</small></li>
            </ol>
        </section>

        <div class="diet-rules-grid">
            <section class="diet-rule-card diet-rule-card-safety" aria-labelledby="diet-safety-heading">
                <div class="diet-rule-card-heading">
                    <span class="diet-rule-icon"><i class="ri-shield-check-line" aria-hidden="true"></i></span>
                    <div><span class="diet-rules-kicker">Stage 1 · Hard exclusions</span><h2 id="diet-safety-heading">Diet and allergy safety</h2></div>
                </div>
                <p class="diet-rule-intro">These checks remove foods from the candidate pool before scoring.</p>
                <ul class="diet-rule-list">
                    <li><strong>Vegan</strong><span>Requires the vegan food flag and excludes dairy, egg, and honey.</span></li>
                    <li><strong>Jain</strong><span>Only foods marked as Jain suitable remain eligible.</span></li>
                    <li><strong>Vegetarian</strong><span>Requires the vegetarian flag and excludes foods containing egg.</span></li>
                    <li><strong>Eggetarian</strong><span>Excludes foods marked non-vegetarian or grouped as meat, fish, or poultry.</span></li>
                    <li><strong>Non-vegetarian</strong><span>Does not add a diet-type exclusion; allergy and meal-slot checks still apply.</span></li>
                    <li><strong>Allergies</strong><span>Dairy, nuts, gluten, and egg flags exclude matching foods.</span></li>
                </ul>
            </section>

            <section class="diet-rule-card" aria-labelledby="diet-schedule-heading">
                <div class="diet-rule-card-heading">
                    <span class="diet-rule-icon is-amber"><i class="ri-time-line" aria-hidden="true"></i></span>
                    <div><span class="diet-rules-kicker">Stage 2 · Meal structure</span><h2 id="diet-schedule-heading">Slots and weekly schedule</h2></div>
                </div>
                <p class="diet-rule-intro">A food must match its meal slot or list the slot as another suitable use. Snack occasions match catalog entries tagged “snack.”</p>
                <div class="diet-pattern-list">
                    <div><strong>3 meals</strong><span>Breakfast · Lunch · Dinner</span></div>
                    <div><strong>2 + snacks</strong><span>Breakfast · Morning snack · Lunch · Evening snack</span></div>
                    <div><strong>Default</strong><span>Breakfast · Morning snack · Lunch · Evening snack · Dinner</span></div>
                </div>
                <p class="diet-rule-footnote"><i class="ri-information-line" aria-hidden="true"></i> The plan covers Monday through Sunday. The generator matches meal slots; meal times are not set during generation.</p>
            </section>

            <section class="diet-rule-card" aria-labelledby="diet-target-heading">
                <div class="diet-rule-card-heading">
                    <span class="diet-rule-icon is-blue"><i class="ri-line-chart-line" aria-hidden="true"></i></span>
                    <div><span class="diet-rules-kicker">Stage 3 · Nutrition</span><h2 id="diet-target-heading">Targets and nutrient gaps</h2></div>
                </div>
                <p class="diet-rule-intro">Configured ICMR targets are selected by the child’s age and sex. If that row is unavailable, the engine tries an age-matched “all” row, then falls back to the first configured target. It tracks 17 nutrients for each day:</p>
                <div class="diet-nutrient-chips" aria-label="Tracked nutrients">
                    <span>Energy</span><span>Protein</span><span>Fat</span><span>Fiber</span><span>Calcium</span><span>Iron</span><span>Zinc</span><span>Magnesium</span><span>Vitamin A</span><span>Vitamin C</span><span>Vitamin D</span><span>B1</span><span>B2</span><span>B3</span><span>B6</span><span>Folate</span><span>B12</span>
                </div>
                <p class="diet-rule-footnote"><i class="ri-information-line" aria-hidden="true"></i> Nutrient totals and gaps are saved with the plan for review. When a nutrient has already reached its target, it adds no further gap score; the engine has no separate upper-limit medical guard.</p>
            </section>

            <section class="diet-rule-card" aria-labelledby="diet-scoring-heading">
                <div class="diet-rule-card-heading">
                    <span class="diet-rule-icon is-purple"><i class="ri-equalizer-2-line" aria-hidden="true"></i></span>
                    <div><span class="diet-rules-kicker">Stage 4 · Candidate ranking</span><h2 id="diet-scoring-heading">How foods are ranked</h2></div>
                </div>
                <p class="diet-rule-intro">Eligible foods start with a +15 base score, then receive adjustments for nutrition fit and variety.</p>
                <div class="diet-score-list">
                    <div><span>Nutrient gap fit</span><strong>Up to +30</strong><small>Scaled by the remaining daily target</small></div>
                    <div><span>Region match</span><strong>+5</strong><small>Matches any selected preferred region</small></div>
                    <div><span>Kid acceptance</span><strong>Up to +5</strong><small>Based on the catalog rating</small></div>
                    <div><span>Cooking medium match</span><strong>+5</strong><small>When it appears in the food method</small></div>
                    <div><span>Recent repetition</span><strong>−20</strong><small>Within that food’s repeat gap; default 3 days</small></div>
                    <div><span>Dairy preference</span><strong>−4</strong><small>For dairy foods when the profile reports 2+ glasses</small></div>
                </div>
                <p class="diet-rule-footnote"><i class="ri-shuffle-line" aria-hidden="true"></i> Selection is weighted across the top five ranked choices. A food inside its repeat gap is avoided when another candidate is available.</p>
            </section>

            <section class="diet-rule-card" aria-labelledby="diet-clinical-heading">
                <div class="diet-rule-card-heading">
                    <span class="diet-rule-icon is-rose"><i class="ri-heart-pulse-line" aria-hidden="true"></i></span>
                    <div><span class="diet-rules-kicker">Stage 5 · Clinical review</span><h2 id="diet-clinical-heading">Flags and approval gate</h2></div>
                </div>
                <ul class="diet-rule-list compact">
                    <li><strong>Medical conditions</strong><span>A non-empty condition other than “none,” “no,” “n/a,” or “nil” requires professional review.</span></li>
                    <li><strong>Visible signs</strong><span>Signs matching configured deficiency rules create clinical flags and require review.</span></li>
                    <li><strong>Initial state</strong><span>Generated plans start as pending review; only an authorized nutritionist can approve them.</span></li>
                    <li><strong>Family visibility</strong><span>The family sees the chart after it is approved and published.</span></li>
                </ul>
            </section>

            <section class="diet-rule-card" aria-labelledby="diet-record-heading">
                <div class="diet-rule-card-heading">
                    <span class="diet-rule-icon is-green"><i class="ri-file-list-3-line" aria-hidden="true"></i></span>
                    <div><span class="diet-rules-kicker">Audit trail</span><h2 id="diet-record-heading">What is saved</h2></div>
                </div>
                <ul class="diet-rule-list compact">
                    <li><strong>Plan version</strong><span>Each generated plan increments the version for its assessment.</span></li>
                    <li><strong>Daily nutrition</strong><span>Daily totals and target gaps are stored with the plan.</span></li>
                    <li><strong>Generation details</strong><span>The plan records its normalized profile, eligible food count, chosen meals, and top candidate scores.</span></li>
                    <li><strong>Final check</strong><span>Before saving, selected foods are checked against the eligible-food set.</span></li>
                </ul>
            </section>
        </div>

        <aside class="diet-rules-boundaries" aria-labelledby="diet-boundaries-heading">
            <div class="diet-boundaries-heading">
                <span class="diet-boundaries-icon"><i class="ri-alert-line" aria-hidden="true"></i></span>
                <div><span class="diet-rules-kicker">Current implementation notes</span><h2 id="diet-boundaries-heading">Important boundaries in the active generator</h2></div>
            </div>
            <ul>
                <li><strong>Food age limits:</strong> the active filter does not apply per-food age minimums or maximums. Age is used to look up nutrition targets.</li>
                <li><strong>Disliked foods:</strong> the generator now reads questionnaire answer 18 and excludes disliked foods by name and food group; a per-slot fallback keeps every meal slot filled, and a food-group safeguard (our own rule, based on the ICMR-NIN note that cereal:legume:milk should be about 3:1:2.5; not verified, awaiting nutritionist confirmation) restores a protected food group if the dislikes would remove it entirely. Favourite foods (answer 17) receive a small ranking boost but do not override variety or allergy rules.</li>
                <li><strong>Lactose:</strong> a dairy allergy and a lactose intolerance are both handled the same way: foods marked as containing dairy are excluded. This is strict by default; a nutritionist may relax it case by case.</li>
                <li><strong>Incomplete allergen data:</strong> an unset or empty allergen flag is treated as unsafe for that allergy, so the food is excluded. Complete the food catalog allergy flags so foods are not excluded unnecessarily.</li>
            </ul>
        </aside>
    </main>
@endsection