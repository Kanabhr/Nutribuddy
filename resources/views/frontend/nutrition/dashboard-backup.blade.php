@extends('layouts.user-panel')
@section('title', 'Nutrition — NutriBuddy')
@section('panel-page-class', 'panel-nutrition')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nutrition.css') }}?v={{ time() }}">
@endpush

@section('panel-content')
<div class="ud-main">
    <div class="page">
        <div class="nutrition-container">

            <!-- Date Selector -->
            <div class="nutri-date-selector">
                @php $today = \Carbon\Carbon::today(); @endphp
                @for ($i = 0; $i < 5; $i++)
                    @php
                        $date = $today->copy()->addDays($i);
                        $isActive = $i === 0;
                    @endphp
                    <div class="nutri-date-item {{ $isActive ? 'active' : '' }}">
                        <span class="nutri-date-day">{{ $date->format('D') }}</span>
                        <span class="nutri-date-num">{{ $date->format('j') }}</span>
                    </div>
                @endfor
            </div>

            <!-- Tabs -->
            <div class="nutri-tabs">
                <div class="nutri-tab active" data-target="tab-food">Food View</div>
                <div class="nutri-tab" data-target="tab-nutri">Nutri View</div>
                <div class="nutri-tab" data-target="tab-calories">Daily Calories</div>
                <div class="nutri-tab" data-target="tab-insights">Insights & Progress</div>
            </div>

            <!-- TAB 1: FOOD VIEW -->
            <div id="tab-food" class="nutri-tab-content active">

                @php
                    $targetCals    = isset($todayTarget) ? ($todayTarget['energy_kcal'] ?? 2100) : 2100;
                    $targetProtein = isset($todayTarget) ? ($todayTarget['protein_g'] ?? 152) : 152;
                    $targetFat     = isset($todayTarget) ? ($todayTarget['fat_g'] ?? 61) : 61;
                    $currentDayForRing = date('l');
                    $dayPlanForRing = isset($sevenDayPlan) ? ($sevenDayPlan[$currentDayForRing] ?? ($sevenDayPlan['Monday'] ?? [])) : [];

                    // No carbohydrate target is stored in the ICMR target table; compare intake to today's planned foods.
                    $targetCarbs = 0;
                    $plannedCarbFoodIds = [];
                    foreach ($dayPlanForRing as $slot) {
                        if (!isset($slot['items'])) continue;
                        foreach ($slot['items'] as $itm) {
                            $fid = (int) ($itm['food_id'] ?? 0);
                            if ($fid <= 0 || isset($plannedCarbFoodIds[$fid])) continue;
                            $plannedCarbFoodIds[$fid] = true;
                            $targetCarbs += (float) ($itm['nutrients']['carb_g'] ?? 0);
                        }
                    }
                    $targetCarbs = round($targetCarbs, 1);

                    // Normalize consumed IDs to integers
                    $consumedIds = array_map('intval', (array) ($consumedItems ?? []));

                    // Sum consumed macros from plan (server-side — used as fallback ONLY)
                    $initialConsumedCals = 0;
                    $initialConsumedProtein = 0;
                    $initialConsumedCarbs = 0;
                    $initialConsumedFat = 0;

                    $seenFoodIds = [];
                    foreach ($dayPlanForRing as $slot) {
                        if (!isset($slot['items'])) continue;
                        foreach ($slot['items'] as $itm) {
                            $fid = (int) ($itm['food_id'] ?? 0);
                            if ($fid <= 0) continue;
                            if (in_array($fid, $consumedIds, true) && !isset($seenFoodIds[$fid])) {
                                $seenFoodIds[$fid] = true;
                                $initialConsumedCals    += (float) ($itm['nutrients']['energy_kcal'] ?? 0);
                                $initialConsumedProtein += (float) ($itm['nutrients']['protein_g'] ?? 0);
                                $initialConsumedCarbs   += (float) ($itm['nutrients']['carb_g'] ?? 0);
                                $initialConsumedFat     += (float) ($itm['nutrients']['fat_g'] ?? 0);
                            }
                        }
                    }

                    $initialConsumedCals    = round($initialConsumedCals);
                    $initialConsumedProtein = round($initialConsumedProtein, 1);
                    $initialConsumedCarbs   = round($initialConsumedCarbs, 1);
                    $initialConsumedFat     = round($initialConsumedFat, 1);

                    // Percentages for initial SSR render
                    $pctProtein = $targetProtein > 0 ? min(100, ($initialConsumedProtein / $targetProtein) * 100) : 0;
                    $pctCarbs   = $targetCarbs   > 0 ? min(100, ($initialConsumedCarbs   / $targetCarbs)   * 100) : 0;
                    $pctFat     = $targetFat     > 0 ? min(100, ($initialConsumedFat     / $targetFat)     * 100) : 0;

                    $ringCircumference = 251.3;
                    $calsPct = $targetCals > 0 ? min(1, $initialConsumedCals / $targetCals) : 0;
                    $ringOffset = $ringCircumference * (1 - $calsPct);
                    $ringColor = $calsPct >= 0.99 ? '#22c55e' : ($calsPct >= 0.5 ? '#22c55e' : ($calsPct > 0 ? '#f59e0b' : '#e5e7eb'));
                @endphp

                <div class="nutri-card">
                    <div class="cal-summary-grid">
                        <div class="cal-ring-container"
                             data-target-cals="{{ $targetCals }}"
                             data-consumed-cals="{{ $initialConsumedCals }}"
                             data-target-protein="{{ $targetProtein }}"
                             data-target-carbs="{{ $targetCarbs }}"
                             data-target-fat="{{ $targetFat }}"
                             data-consumed-protein="{{ $initialConsumedProtein }}"
                             data-consumed-carbs="{{ $initialConsumedCarbs }}"
                             data-consumed-fat="{{ $initialConsumedFat }}">
                            <svg viewBox="0 0 100 100" class="cal-ring-svg">
                                <circle cx="50" cy="50" r="40" class="cal-ring-bg"
                                        fill="none" stroke="#e5e7eb" stroke-width="10"></circle>
                                <circle cx="50" cy="50" r="40" class="cal-ring-progress"
                                        fill="none"
                                        stroke="{{ $ringColor }}"
                                        stroke-width="10"
                                        stroke-linecap="round"
                                        style="stroke-dasharray: {{ $ringCircumference }}; stroke-dashoffset: {{ $ringOffset }}; transition: stroke-dashoffset 0.5s ease, stroke 0.3s ease; transform: rotate(-90deg); transform-origin: 50% 50%;"></circle>
                            </svg>
                            <div class="cal-ring-text">
                                <span class="cal-ring-val" id="calsConsumed">{{ $initialConsumedCals }}</span>
                                <span class="cal-ring-lbl">of {{ $targetCals }}</span>
                            </div>
                        </div>
                        <div class="macro-bars">
                            <div style="font-weight: 800; font-size: 0.9rem; margin-bottom: 4px;">
                                <span id="calsRemaining" style="color: var(--pk);">{{ max(0, $targetCals - $initialConsumedCals) }}</span> kcal remaining
                            </div>
                            <div class="macro-bar-item">
                                <div class="macro-bar-labels">
                                    <span class="macro-name">Protein</span>
                                    <span class="macro-val" id="proteinVal">{{ $initialConsumedProtein }} / {{ $targetProtein }}g</span>
                                </div>
                                <div class="macro-track"><div class="macro-fill protein" id="proteinFill" style="width: {{ $pctProtein }}%"></div></div>
                            </div>
                            <div class="macro-bar-item">
                                <div class="macro-bar-labels">
                                    <span class="macro-name">Carbs planned</span>
                                    <span class="macro-val" id="carbsVal">{{ $initialConsumedCarbs }} / {{ $targetCarbs }}g</span>
                                </div>
                                <div class="macro-track"><div class="macro-fill carbs" id="carbsFill" style="width: {{ $pctCarbs }}%"></div></div>
                            </div>
                            <div class="macro-bar-item">
                                <div class="macro-bar-labels">
                                    <span class="macro-name">Fats</span>
                                    <span class="macro-val" id="fatVal">{{ $initialConsumedFat }} / {{ $targetFat }}g</span>
                                </div>
                                <div class="macro-track"><div class="macro-fill fats" id="fatFill" style="width: {{ $pctFat }}%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Meals -->
                @php
                    $currentDay = date('l');
                    $dayPlan = isset($sevenDayPlan) ? ($sevenDayPlan[$currentDay] ?? ($sevenDayPlan['Monday'] ?? [])) : [];
                @endphp

                @if(!empty($dayPlan))
                    @foreach($dayPlan as $slotName => $data)
                        @php
                            $slotId = strtolower(str_replace(' ', '-', $slotName));
                            $slotKcalTotal    = round($data['total_nutrients']['energy_kcal'] ?? 0);
                            $slotProteinTotal = round($data['total_nutrients']['protein_g'] ?? 0);

                            $slotKcalConsumed    = 0;
                            $slotProteinConsumed = 0;
                            $slotSeenIds = [];
                            if (!empty($data['items'])) {
                                foreach ($data['items'] as $itm) {
                                    $fid = (int) ($itm['food_id'] ?? 0);
                                    if ($fid <= 0) continue;
                                    if (in_array($fid, $consumedIds, true) && !isset($slotSeenIds[$fid])) {
                                        $slotSeenIds[$fid] = true;
                                        $slotKcalConsumed    += (float) ($itm['nutrients']['energy_kcal'] ?? 0);
                                        $slotProteinConsumed += (float) ($itm['nutrients']['protein_g'] ?? 0);
                                    }
                                }
                            }
                            $slotKcalConsumed = round($slotKcalConsumed);
                            $slotProteinConsumed = round($slotProteinConsumed, 1);
                        @endphp
                        <div class="meal-section" id="meal-{{ $slotId }}"
                             data-meal-total-cals="{{ $slotKcalTotal }}"
                             data-meal-total-protein="{{ $slotProteinTotal }}">
                            <div class="meal-header">
                                <div class="meal-info">
                                    <h3>{{ $slotName }}</h3>
                                    <div class="meal-meta">
                                        <span class="meal-consumed-kcal">{{ $slotKcalConsumed }}</span> /
                                        <span class="meal-total-kcal">{{ $slotKcalTotal }}</span> kcal ·
                                        <span class="meal-consumed-protein">{{ $slotProteinConsumed }}</span> /
                                        <span class="meal-total-protein">{{ $slotProteinTotal }}</span>g Protein
                                    </div>
                                </div>
                                <button class="meal-swap-btn" type="button" data-alternatives="{{ json_encode($data['alternatives'] ?? []) }}" data-meal-id="{{ $data['meal_id'] ?? '' }}">Swap</button>
                            </div>
                            <div class="food-list">
                                @foreach($data['items'] as $item)
                                    @php
                                        $cals = round($item['nutrients']['energy_kcal'] ?? 0);
                                        $p = round($item['nutrients']['protein_g'] ?? 0, 1);
                                        $c = round($item['nutrients']['carb_g'] ?? 0, 1);
                                        $f = round($item['nutrients']['fat_g'] ?? 0, 1);
                                        $portion = $item['portion_g'] . 'g';
                                        $isConsumed = in_array((int)($item['food_id'] ?? 0), $consumedIds, true);
                                    @endphp
                                    <div class="food-item meal-item {{ $isConsumed ? 'meal-taken' : '' }}"
                                         data-cals="{{ $cals }}"
                                         data-p="{{ $p }}"
                                         data-c="{{ $c }}"
                                         data-f="{{ $f }}"
                                         data-child-id="{{ $child->id ?? '' }}"
                                         data-meal-id="{{ $data['meal_id'] ?? '' }}"
                                         data-food-id="{{ $item['food_id'] ?? '' }}"
                                         data-consumed="{{ $isConsumed ? '1' : '0' }}"
                                         style="cursor: pointer;">
                                        <div>
                                            <div class="food-name">{{ $item['food_name'] }}</div>
                                            <div class="food-details">{{ $portion }} · P: {{ $p }}g · C: {{ $c }}g · F: {{ $f }}g</div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 16px;">
                                            <div class="meal-check"></div>
                                            <div class="food-cal">{{ $cals }} Kcal</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="meal-section">
                        <div class="meal-header">
                            <div class="meal-info">
                                <h3>No Diet Plan Found</h3>
                                <div class="meal-meta">Your plan is being reviewed or hasn't been generated yet.</div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            <!-- TAB 2: NUTRI VIEW -->
            <div id="tab-nutri" class="nutri-tab-content">
                @php
                    $nutriDays = is_array($sevenDayPlan ?? null) ? $sevenDayPlan : [];
                    $selectedNutriDay = array_key_exists(date('l'), $nutriDays)
                        ? date('l')
                        : (array_key_first($nutriDays) ?? null);
                @endphp

                @if($nutriDays)
                    <div class="nv-view">
                        <div class="nv-heading">
                            <div>
                                <span class="nv-eyebrow">Weekly meal plan</span>
                                <h2>Nutrition by day</h2>
                                <p>Choose a day, then open a food to inspect its full nutrient breakdown.</p>
                            </div>
                            <span class="nv-plan-label">Planned values</span>
                        </div>

                        <div class="nv-day-switcher" role="tablist" aria-label="Meal plan days">
                            @foreach($nutriDays as $dayName => $dayMeals)
                                <button type="button" class="nv-day-button {{ $dayName === $selectedNutriDay ? 'active' : '' }}"
                                        id="nv-day-tab-{{ strtolower($dayName) }}" role="tab"
                                        aria-selected="{{ $dayName === $selectedNutriDay ? 'true' : 'false' }}"
                                        aria-controls="nv-day-panel-{{ strtolower($dayName) }}"
                                        data-nv-day="{{ $dayName }}">
                                    <span>{{ substr($dayName, 0, 3) }}</span>
                                    <small>{{ count($dayMeals) }} {{ count($dayMeals) === 1 ? 'meal' : 'meals' }}</small>
                                </button>
                            @endforeach
                        </div>

                        @foreach($nutriDays as $dayName => $dayMeals)
                            @php
                                $dayTotals = [];
                                $dayFoodCount = 0;
                                foreach ($dayMeals as $mealData) {
                                    foreach ($mealData['total_nutrients'] ?? [] as $key => $value) {
                                        $dayTotals[$key] = ($dayTotals[$key] ?? 0) + (float) $value;
                                    }
                                    $dayFoodCount += count($mealData['items'] ?? []);
                                }
                            @endphp
                            <section class="nv-day-panel" id="nv-day-panel-{{ strtolower($dayName) }}"
                                     role="tabpanel" aria-labelledby="nv-day-tab-{{ strtolower($dayName) }}"
                                     data-nv-panel="{{ $dayName }}" {{ $dayName === $selectedNutriDay ? '' : 'hidden' }}>
                                <div class="nv-day-summary">
                                    <div>
                                        <span class="nv-eyebrow">{{ $dayName }}</span>
                                        <h3>{{ $dayFoodCount }} planned {{ $dayFoodCount === 1 ? 'food' : 'foods' }}</h3>
                                    </div>
                                    <div class="nv-day-kcal">{{ round($dayTotals['energy_kcal'] ?? 0) }} <span>kcal planned</span></div>
                                </div>

                                <div class="nv-macro-grid">
                                    <div class="nv-macro-item nv-macro-energy"><span>Energy</span><strong>{{ round($dayTotals['energy_kcal'] ?? 0) }} <small>kcal</small></strong></div>
                                    <div class="nv-macro-item nv-macro-carbs"><span>Carbohydrates</span><strong>{{ round($dayTotals['carb_g'] ?? 0, 1) }} <small>g</small></strong></div>
                                    <div class="nv-macro-item nv-macro-protein"><span>Protein</span><strong>{{ round($dayTotals['protein_g'] ?? 0, 1) }} <small>g</small></strong></div>
                                    <div class="nv-macro-item nv-macro-fat"><span>Total fat</span><strong>{{ round($dayTotals['fat_g'] ?? 0, 1) }} <small>g</small></strong></div>
                                </div>

                                <div class="nv-meal-list">
                                    @forelse($dayMeals as $slotName => $mealData)
                                        <article class="nv-meal-card">
                                            <header class="nv-meal-heading">
                                                <h3>{{ $slotName }}</h3>
                                                <span>{{ round($mealData['total_nutrients']['energy_kcal'] ?? 0) }} kcal</span>
                                            </header>
                                            <div class="nv-food-list">
                                                @forelse($mealData['items'] ?? [] as $food)
                                                    <button type="button" class="nv-food-button"
                                                            data-nv-food-name="{{ $food['food_name'] ?? 'Food' }}"
                                                            data-nv-food-portion="{{ $food['portion_g'] ?? '' }}"
                                                            data-nv-food-nutrients="{{ json_encode($food['nutrients'] ?? [], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">
                                                        <span class="nv-food-copy">
                                                            <strong>{{ $food['food_name'] ?? 'Food' }}</strong>
                                                            <small>{{ $food['portion_g'] ?? '' }}{{ is_numeric($food['portion_g'] ?? null) ? ' g' : '' }} · P {{ round($food['nutrients']['protein_g'] ?? 0, 1) }}g · C {{ round($food['nutrients']['carb_g'] ?? 0, 1) }}g</small>
                                                        </span>
                                                        <span class="nv-food-energy">{{ round($food['nutrients']['energy_kcal'] ?? 0) }} kcal <span aria-hidden="true">›</span></span>
                                                    </button>
                                                @empty
                                                    <p class="nv-empty-meal">No foods listed for this meal.</p>
                                                @endforelse
                                            </div>
                                        </article>
                                    @empty
                                        <p class="nv-empty-meal">No meals are available for this day.</p>
                                    @endforelse
                                </div>
                            </section>
                        @endforeach
                    </div>
                @else
                    <div class="nutri-card nv-empty-state">
                        <h2>Nutrition details unavailable</h2>
                        <p>A meal plan is not available to display yet.</p>
                    </div>
                @endif
                </div>
            </div>

            <!-- TAB 3: DAILY CALORIES -->
            <div id="tab-calories" class="nutri-tab-content">
                @php
                    $dailyPlanSummary = [];
                    foreach (($sevenDayPlan ?? []) as $planDay => $planMeals) {
                        $totals = ['energy_kcal' => 0, 'protein_g' => 0, 'carb_g' => 0, 'fat_g' => 0];
                        foreach ($planMeals as $planMeal) {
                            foreach ($totals as $nutrient => $value) {
                                $totals[$nutrient] += (float) ($planMeal['total_nutrients'][$nutrient] ?? 0);
                            }
                        }
                        $dailyPlanSummary[$planDay] = $totals;
                    }
                    $maxPlannedEnergy = max(array_merge(
                        [1, (float) $targetCals],
                        array_map(fn ($totals) => (float) $totals['energy_kcal'], array_values($dailyPlanSummary)),
                        array_map(fn ($goal) => (float) ($goal['energy_kcal'] ?? 0), array_values($dailyTargets ?? []))
                    ));
                    $todayConsumedCals = (float) ($initialConsumedCals ?? 0);
                    $todayCaloriesProgress = $targetCals > 0 ? min(100, ($todayConsumedCals / $targetCals) * 100) : 0;
                    $todayCaloriesRemaining = max(0, $targetCals - $todayConsumedCals);
                    $todayPlanMacros = $dailyPlanSummary[date('l')] ?? $dailyPlanSummary[array_key_first($dailyPlanSummary) ?? ''] ?? [];
                @endphp

                <div class="dc-view">
                    <div class="dc-heading">
                        <div>
                            <span class="dc-eyebrow">{{ date('l') }} · Today</span>
                            <h2>Daily nutrition</h2>
                            <p>Your logged intake compared with today’s calorie goal, plus the actual weekly meal plan.</p>
                        </div>
                    </div>

                    <section class="dc-today-panel" aria-labelledby="dc-today-title">
                        <div class="dc-today-topline">
                            <div>
                                <span class="dc-eyebrow">Calories consumed</span>
                                <h3 id="dc-today-title"><span id="dailyCaloriesConsumed">{{ round($todayConsumedCals) }}</span> <small>of {{ round($targetCals) }} kcal</small></h3>
                            </div>
                            <div class="dc-calorie-status" id="dailyCaloriesStatus">
                                @if($todayConsumedCals > $targetCals)
                                    {{ round($todayConsumedCals - $targetCals) }} kcal over goal
                                @else
                                    {{ round($todayCaloriesRemaining) }} kcal remaining
                                @endif
                            </div>
                        </div>
                        <div class="dc-progress-track" role="progressbar" aria-label="Calories consumed today" aria-valuemin="0" aria-valuemax="{{ round($targetCals) }}" aria-valuenow="{{ round($todayConsumedCals) }}">
                            <div class="dc-progress-fill" id="dailyCaloriesProgress" style="width: {{ $todayCaloriesProgress }}%;"></div>
                        </div>
                        <div class="dc-progress-caption"><span>0 kcal</span><span>Goal: {{ round($targetCals) }} kcal</span></div>
                    </section>

                    <section class="dc-macro-section" aria-label="Today's consumed and planned macronutrients">
                        <div class="dc-section-heading">
                            <h3>Today's macros</h3>
                            <span>Consumed · planned</span>
                        </div>
                        <div class="dc-macro-grid">
                            <article class="dc-macro-card dc-macro-protein">
                                <span>Protein</span>
                                <strong><span id="dailyProteinConsumed">{{ round($initialConsumedProtein ?? 0, 1) }}</span>g</strong>
                                <small>{{ round($todayPlanMacros['protein_g'] ?? 0, 1) }}g planned</small>
                            </article>
                            <article class="dc-macro-card dc-macro-carbs">
                                <span>Carbohydrates</span>
                                <strong><span id="dailyCarbsConsumed">{{ round($initialConsumedCarbs ?? 0, 1) }}</span>g</strong>
                                <small>{{ round($todayPlanMacros['carb_g'] ?? 0, 1) }}g planned</small>
                            </article>
                            <article class="dc-macro-card dc-macro-fat">
                                <span>Total fat</span>
                                <strong><span id="dailyFatConsumed">{{ round($initialConsumedFat ?? 0, 1) }}</span>g</strong>
                                <small>{{ round($todayPlanMacros['fat_g'] ?? 0, 1) }}g planned</small>
                            </article>
                        </div>
                    </section>

                    <section class="dc-week-section" aria-labelledby="dc-week-title">
                        <div class="dc-section-heading">
                            <div>
                                <h3 id="dc-week-title">Planned calories by day</h3>
                                <p>Totals are calculated from the foods in each day's plan.</p>
                            </div>
                            <span class="dc-goal-key"><i></i> Daily goal</span>
                        </div>
                        @forelse($dailyPlanSummary as $planDay => $totals)
                            @php
                                $plannedPct = min(100, ($totals['energy_kcal'] / $maxPlannedEnergy) * 100);
                                $dayGoal = (float) ($dailyTargets[$planDay]['energy_kcal'] ?? $targetCals);
                                $dayGoalPct = min(100, ($dayGoal / $maxPlannedEnergy) * 100);
                            @endphp
                            <div class="dc-week-row {{ $planDay === date('l') ? 'is-today' : '' }}">
                                <div class="dc-week-label">
                                    <strong>{{ substr($planDay, 0, 3) }}</strong>
                                    <span>{{ round($totals['energy_kcal']) }} / {{ round($dayGoal) }} kcal</span>
                                </div>
                                <div class="dc-week-track">
                                    <div class="dc-week-bar" style="width: {{ $plannedPct }}%;"></div>
                                    <span class="dc-goal-marker" style="left: {{ $dayGoalPct }}%;" aria-hidden="true"></span>
                                </div>
                                <div class="dc-week-macros">P {{ round($totals['protein_g'], 1) }}g · C {{ round($totals['carb_g'], 1) }}g · F {{ round($totals['fat_g'], 1) }}g</div>
                            </div>
                        @empty
                            <p class="dc-empty">No daily meal-plan totals are available yet.</p>
                        @endforelse
                    </section>
                </div>
            </div>

            <!-- TAB 4: INSIGHTS & PROGRESS -->
            <div id="tab-insights" class="nutri-tab-content">
                <div class="nutri-card">
                    <h3 style="font-family: 'Fredoka One', cursive; font-size: 1.2rem; margin-bottom: 20px;">Growth Signal</h3>
                    <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                        <button class="chart-filter-btn active" style="padding: 6px 16px; font-size: 0.8rem; border-radius: 20px; border: 1.5px solid var(--border); background: var(--pkl); color: var(--pk); font-weight: 800; cursor: pointer;">1M</button>
                        <button class="chart-filter-btn" style="padding: 6px 16px; font-size: 0.8rem; border-radius: 20px; border: 1.5px solid var(--border); background: #fff; color: var(--muted); font-weight: 800; cursor: pointer;">3M</button>
                        <button class="chart-filter-btn" style="padding: 6px 16px; font-size: 0.8rem; border-radius: 20px; border: 1.5px solid var(--border); background: #fff; color: var(--muted); font-weight: 800; cursor: pointer;">1Y</button>
                    </div>
                    <div class="growth-chart" style="height: 180px; background: linear-gradient(to bottom, var(--pul), #fff); border-radius: 12px; border: 1.5px solid var(--border); position: relative; overflow: hidden; display: flex; align-items: flex-end;">
                        <svg viewBox="0 0 100 40" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                            <path d="M0 40 L0 30 Q10 35 20 20 T40 15 T60 25 T80 5 T100 10 L100 40 Z" fill="var(--pu)" opacity="0.3"></path>
                            <path d="M0 30 Q10 35 20 20 T40 15 T60 25 T80 5 T100 10" fill="none" stroke="var(--pu)" stroke-width="2"></path>
                        </svg>
                        <div style="position: absolute; left: 20%; bottom: 45%; width: 10px; height: 10px; background: #fff; border: 2px solid var(--pu); border-radius: 50%;"></div>
                        <div style="position: absolute; left: 60%; bottom: 35%; width: 10px; height: 10px; background: #fff; border: 2px solid var(--pu); border-radius: 50%;"></div>
                        <div style="position: absolute; left: 80%; bottom: 85%; width: 10px; height: 10px; background: #fff; border: 2px solid var(--pu); border-radius: 50%; box-shadow: 0 0 10px var(--pu);"></div>
                    </div>
                    <div style="text-align: center; margin-top: 16px; font-weight: 700; color: var(--dk);">
                        Muscle Mass <span style="color: var(--pk);">+2.4 kg</span> this month
                    </div>
                </div>

                <div class="nutri-card" style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div>
                            <h3 style="font-family: 'Fredoka One', cursive; font-size: 1.2rem; margin: 0 0 4px 0;">Quarterly Check-In</h3>
                            <div style="font-size: 0.85rem; color: var(--muted); font-weight: 700;">Update your child's latest measurements</div>
                        </div>
                        <span style="background: var(--orl); color: var(--or); padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;">Update</span>
                    </div>

                    <form id="checkInForm" data-child-id="{{ $child->id ?? '' }}">
                        @csrf
                        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
                            <div style="display: flex; gap: 12px;">
                                <div style="flex: 1;">
                                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--dk); margin-bottom: 6px;">Current Weight (kg) *</label>
                                    <input type="number" step="0.1" name="weight_kg" id="weight_kg" required
                                           value="{{ $assessment->current_weight_kg ?? '' }}"
                                           placeholder="e.g. 52.5"
                                           style="width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 0.9rem; background: var(--cr); outline: none;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--dk); margin-bottom: 6px;">Current Height (cm) *</label>
                                    <input type="number" step="0.1" name="height_cm" id="height_cm" required
                                           value="{{ $assessment->current_height_cm ?? '' }}"
                                           placeholder="e.g. 162"
                                           style="width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 0.9rem; background: var(--cr); outline: none;">
                                </div>
                            </div>
                            <div style="display: flex; gap: 12px;">
                                <div style="flex: 1;">
                                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--dk); margin-bottom: 6px;">Body Fat (%)</label>
                                    <input type="number" step="0.1" name="body_fat" id="body_fat" placeholder="e.g. 15"
                                           style="width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 0.9rem; background: var(--cr); outline: none;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--dk); margin-bottom: 6px;">Waist (inches)</label>
                                    <input type="number" step="0.1" name="waist_inches" id="waist_inches" placeholder="e.g. 32"
                                           style="width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 0.9rem; background: var(--cr); outline: none;">
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--dk); margin-bottom: 6px;">Notes / How are you feeling?</label>
                                <textarea name="notes" id="notes" rows="3" placeholder="Any updates on your energy levels or progress..."
                                          style="width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 0.9rem; background: var(--cr); resize: none; outline: none;"></textarea>
                            </div>
                        </div>
                        <button type="submit" id="checkInSubmitBtn"
                                style="width: 100%; padding: 14px; background: var(--dk); color: #fff; border: none; border-radius: 12px; font-weight: 800; font-size: 0.9rem; cursor: pointer; transition: all 0.2s;"
                                onmouseover="this.style.transform='scale(1.02)'"
                                onmouseout="this.style.transform='scale(1)'">
                            Submit Check-in Data
                        </button>
                        <p id="checkInMessage" style="text-align: center; margin-top: 10px; font-weight: 700; font-size: 0.85rem; display: none;"></p>
                    </form>
                </div>
            </div>

            <!-- Assessment Section (bottom) -->
            @php
                $initialWeight = isset($assessment) ? ($assessment->initial_weight_kg ?? $assessment->current_weight_kg ?? 0) : 0;
                $initialHeight = isset($assessment) ? ($assessment->initial_height_cm ?? $assessment->current_height_cm ?? 0) : 0;
                $currentWeight = isset($assessment) ? ($assessment->current_weight_kg ?? 0) : 0;
                $currentHeight = isset($assessment) ? ($assessment->current_height_cm ?? 0) : 0;
                $targetWeight = $currentWeight > 0 ? round($currentWeight * 1.15, 1) : 0;
                $targetHeight = $currentHeight > 0 ? round($currentHeight * 1.05, 1) : 0;
                $weightMin = max(1, min($initialWeight, $currentWeight, $targetWeight) - 5);
                $weightMax = max($initialWeight, $currentWeight, $targetWeight) + 5;
                $heightMin = max(1, min($initialHeight, $currentHeight, $targetHeight) - 5);
                $heightMax = max($initialHeight, $currentHeight, $targetHeight) + 5;
                $weightRange = max(1, $weightMax - $weightMin);
                $heightRange = max(1, $heightMax - $heightMin);
                $currWpct = (($currentWeight - $weightMin) / $weightRange) * 100;
                $currHpct = (($currentHeight - $heightMin) / $heightRange) * 100;
                $planState = 'pending';
                if (isset($latestPlan) && $latestPlan) {
                    if ($latestPlan->status === 'active' && $latestPlan->review_status === 'approved') {
                        $planState = 'approved';
                    }
                }
            @endphp

            <div class="nutri-card" style="margin-top: 30px; margin-bottom: 24px; border: 2px solid var(--pu); box-shadow: 0 8px 20px rgba(132, 94, 194, 0.1);">
                <div style="margin-bottom: 24px;">
                    <h2 style="font-family: 'Nunito', sans-serif; color: var(--dk); font-size: 1.25rem; font-weight: 600; margin-bottom: 8px; line-height: 1.3;">Here's what {{ $child->name ?? 'your child' }} needs — and where they are</h2>
                    <p style="font-size: 0.9rem; color: var(--muted); font-weight: 500;">Worked out from current measurements</p>
                </div>

                <h3 style="font-family: 'Nunito', sans-serif; margin-bottom: 16px; color: var(--dk); font-size: 1rem; font-weight: 600;">Healthy targets for their age</h3>

                <div style="background: #fff; border: 1.5px solid var(--border); border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <div style="font-weight: 600; color: var(--dk); font-size: 1rem;">📏 Height</div>
                        <div style="background: #ecfccb; color: #4d7c0f; font-weight: 700; font-size: 0.8rem; padding: 4px 12px; border-radius: 20px;">On track ✓</div>
                    </div>
                    <div style="position: relative; height: 16px; margin-bottom: 8px; border-radius: 8px; background: linear-gradient(to right, #a3e635, #fef08a);">
                        <div style="position: absolute; left: {{ $currHpct }}%; top: -6px; bottom: -6px; width: 4px; background: #1e293b; border-radius: 2px; transform: translateX(-50%); z-index: 2;"></div>
                    </div>
                    <div style="position: relative; height: 24px; font-weight: 600; color: var(--muted); font-size: 0.85rem;">
                        <span style="position: absolute; left: 0;">{{ $heightMin }} cm</span>
                        <span style="position: absolute; left: {{ $currHpct }}%; transform: translateX(-50%); color: var(--dk); font-weight: 600;">{{ $child->name ?? 'Child' }} {{ $currentHeight }} cm</span>
                        <span style="position: absolute; right: 0;">{{ $heightMax }} cm</span>
                    </div>
                </div>

                <div style="background: #fff; border: 1.5px solid var(--border); border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <div style="font-weight: 600; color: var(--dk); font-size: 1rem;">⚖️ Weight</div>
                        <div style="background: #ecfccb; color: #4d7c0f; font-weight: 700; font-size: 0.8rem; padding: 4px 12px; border-radius: 20px;">On track ✓</div>
                    </div>
                    <div style="position: relative; height: 16px; margin-bottom: 8px; border-radius: 8px; background: linear-gradient(to right, #a3e635, #fef08a);">
                        <div style="position: absolute; left: {{ $currWpct }}%; top: -6px; bottom: -6px; width: 4px; background: #1e293b; border-radius: 2px; transform: translateX(-50%); z-index: 2;"></div>
                    </div>
                    <div style="position: relative; height: 24px; font-weight: 600; color: var(--muted); font-size: 0.85rem;">
                        <span style="position: absolute; left: 0;">{{ $weightMin }} kg</span>
                        <span style="position: absolute; left: {{ $currWpct }}%; transform: translateX(-50%); color: var(--dk); font-weight: 600;">{{ $child->name ?? 'Child' }} {{ $currentWeight }} kg</span>
                        <span style="position: absolute; right: 0;">{{ $weightMax }} kg</span>
                    </div>
                </div>

                @php
                    $calsPct = $targetCals > 0 ? min(100, ($initialConsumedCals / $targetCals) * 100) : 0;
                    $energyStateBadge = $calsPct < 80 ? 'Needs a boost' : 'On track ✓';
                    $energyStateColor = $calsPct < 80 ? '#fef3c7' : '#ecfccb';
                    $energyStateText = $calsPct < 80 ? '#b45309' : '#4d7c0f';
                @endphp
                <div style="background: #fff; border: 1.5px solid var(--border); border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <div style="font-weight: 600; color: var(--dk); font-size: 1rem;">⚡ Daily energy</div>
                        <div id="energyBadge" style="background: {{ $energyStateColor }}; color: {{ $energyStateText }}; font-weight: 700; font-size: 0.8rem; padding: 4px 12px; border-radius: 20px;">{{ $energyStateBadge }}</div>
                    </div>
                    <div style="position: relative; height: 16px; margin-bottom: 12px; border-radius: 8px; background: #e5e7eb; overflow: hidden;">
                        <div id="energyBarFill" style="position: absolute; left: 0; top: 0; bottom: 0; width: {{ $calsPct }}%; background: var(--or); border-radius: 8px; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 0.85rem;">
                        <span style="color: var(--muted);">Now ≈ <span style="color: var(--dk); font-weight: 700;" id="calsConsumedEnergyCard">{{ $initialConsumedCals }}</span> <span style="color: var(--dk); font-weight: 700;">kcal</span></span>
                        <span style="color: var(--muted);">Needs <span style="color: var(--dk); font-weight: 700;">{{ $targetCals }} kcal</span></span>
                    </div>
                </div>

                @if($planState !== 'approved')
                    <div style="display: block; text-align: center; width: 100%; padding: 16px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; border-radius: 14px; font-family: 'Fredoka One', cursive; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(251, 191, 36, 0.35);">
                        ⏳ Pending Approval
                    </div>
                    <p style="text-align: center; font-size: 0.85rem; color: var(--muted); margin-top: 10px; font-weight: 600;">
                        Your diet plan is under review by our nutritionist. It will be generated soon.
                    </p>
                @endif
            </div>

        </div>
    </div>
</div>

<div class="nv-food-modal" id="nvFoodModal" aria-hidden="true">
    <div class="nv-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="nvFoodModalTitle" tabindex="-1">
        <div class="nv-modal-heading">
            <h2 id="nvFoodModalTitle"></h2>
            <button type="button" class="nv-modal-close" id="nvFoodModalClose" aria-label="Close nutrient details">&times;</button>
        </div>
        <p class="nv-modal-portion" id="nvFoodModalPortion"></p>
        <div class="nv-nutrient-groups" id="nvFoodNutrients"></div>
    </div>
</div>

<!-- Swap Food Modal -->
<div class="nutri-modal-overlay" id="swapFoodModal">
    <div class="nutri-modal-content">
        <div class="nutri-modal-header">
            <h2>Swap Dish</h2>
            <button class="close-modal" id="closeSwapModal">&times;</button>
        </div>
        <p style="color: var(--muted); font-size: 0.95rem; margin-bottom: 20px;">Select an alternative dish that matches your nutrition targets:</p>
        <div class="search-results" id="swap-candidates-list"></div>
    </div>
</div>

{{-- Inline JS for check-in --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('checkInForm');
    if (!form) return;

    const btn = document.getElementById('checkInSubmitBtn');
    const msgEl = document.getElementById('checkInMessage');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
               || form.querySelector('input[name="_token"]')?.value
               || '';

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const childId = form.getAttribute('data-child-id');
        if (!childId) {
            msgEl.style.display = 'block';
            msgEl.style.color = '#dc2626';
            msgEl.textContent = 'Child ID missing.';
            return;
        }

        const payload = {
            child_id: childId,
            weight_kg: document.getElementById('weight_kg').value,
            height_cm: document.getElementById('height_cm').value,
            body_fat: document.getElementById('body_fat').value || null,
            waist_inches: document.getElementById('waist_inches').value || null,
            notes: document.getElementById('notes').value || null,
        };

        btn.disabled = true;
        btn.textContent = 'Saving...';
        msgEl.style.display = 'none';

        try {
            const res = await fetch('/nutrition/check-in', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (data.success) {
                msgEl.style.display = 'block';
                msgEl.style.color = '#059669';
                msgEl.textContent = '✅ Check-in saved! Refreshing...';
                setTimeout(() => window.location.reload(), 900);
            } else {
                msgEl.style.display = 'block';
                msgEl.style.color = '#dc2626';
                msgEl.textContent = '❌ ' + (data.message || 'Save failed');
                btn.disabled = false;
                btn.textContent = 'Submit Check-in Data';
            }
        } catch (err) {
            console.error(err);
            msgEl.style.display = 'block';
            msgEl.style.color = '#dc2626';
            msgEl.textContent = '❌ Network error. Please try again.';
            btn.disabled = false;
            btn.textContent = 'Submit Check-in Data';
        }
    });
});
</script>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nutrition.js') }}?v={{ time() }}"></script>
@endpush