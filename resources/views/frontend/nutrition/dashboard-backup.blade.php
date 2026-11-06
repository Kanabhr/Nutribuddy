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

            {{-- ── Week / day navigation ──────────────────────────────────────── --}}
            @php
                use Carbon\Carbon;

                $planStartDate = null;
                $planTotalDays = 7;
                $isBasicPlan   = true;

                if (isset($dietPlan) && $dietPlan) {
                    $pd = $dietPlan->plan_data;
                    if (is_string($pd)) { $pd = json_decode($pd, true) ?? []; }
                    $planStartDate = isset($pd['schedule']['start_date'])
                        ? Carbon::parse($pd['schedule']['start_date'])
                        : Carbon::parse($dietPlan->created_at)->startOfDay();
                    $planTotalDays = isset($pd['schedule']['days']) ? (int) $pd['schedule']['days'] : 7;
                    $isBasicPlan   = $planTotalDays <= 7;
                }

                // Group days into weeks of 7; last week may be shorter.
                $weekGroups = [];   // [ week_no => [ ['index'=>0,'date'=>Carbon,...], ... ] ]
                if ($planStartDate) {
                    for ($di = 0; $di < $planTotalDays; $di++) {
                        $d       = $planStartDate->copy()->addDays($di);
                        $weekNo  = intdiv($di, 7) + 1;
                        $weekGroups[$weekNo][] = ['index' => $di, 'date' => $d];
                    }
                }

                // Determine active week and active day index.
                $today          = Carbon::today();
                $activeWeekNo   = 1;
                $activeDayIndex = 0;

                if ($planStartDate) {
                    $diffDays = (int) $planStartDate->diffInDays($today, false);
                    if ($diffDays >= 0 && $diffDays < $planTotalDays) {
                        // Today is within the plan.
                        $activeDayIndex = $diffDays;
                        $activeWeekNo   = intdiv($diffDays, 7) + 1;
                    }
                    // else: outside plan → default to week 1, day 0 (already set).
                }

                // $planDays comes from NutritionPageService via DietChartDisplayService::formatPlan().
                // It is a flat list of day objects, one per plan day, keyed by numeric index:
                //   [0 => ['index'=>0,'label'=>'Monday','date'=>'…','week'=>1,'meals'=>[…]], …]
                // We index it by the 'index' field for O(1) lookup.
                $planDaysIndexed = [];
                foreach (($planDays ?? []) as $pd) {
                    $planDaysIndexed[$pd['index']] = $pd;
                }

                // Active day's weekday label (used in Daily Calories tab heading only — not for keying).
                $activeDayLabel = $planDaysIndexed[$activeDayIndex]['label'] ?? date('l');
            @endphp

            {{-- Week tabs — only shown for plans longer than 7 days --}}
            @if(!$isBasicPlan && count($weekGroups) > 1)
            <div class="nutri-week-tabs" id="weekTabBar">
                @foreach($weekGroups as $wn => $wdays)
                    <button type="button"
                            class="nutri-week-tab {{ $wn === $activeWeekNo ? 'active' : '' }}"
                            data-week="{{ $wn }}">
                        Week {{ $wn }}
                    </button>
                @endforeach
            </div>
            @endif

            {{-- Date chips — one per day in the active week --}}
            @if($planStartDate)
            <div class="nutri-date-selector" id="dateSelectorBar">
                @foreach($weekGroups as $wn => $wdays)
                    @foreach($wdays as $wd)
                        <div class="nutri-date-item
                                    {{ $wd['index'] === $activeDayIndex ? 'active' : '' }}
                                    {{ $wn !== $activeWeekNo ? 'nutri-date-hidden' : '' }}"
                             data-day-index="{{ $wd['index'] }}"
                             data-week="{{ $wn }}"
                             data-day-label="{{ $wd['date']->format('l') }}">
                            <span class="nutri-date-day">{{ $wd['date']->format('D') }}</span>
                            <span class="nutri-date-num">{{ $wd['date']->format('j') }}</span>
                        </div>
                    @endforeach
                @endforeach
            </div>
            @else
            {{-- Fallback: plain 5-day strip when there is no plan yet --}}
            <div class="nutri-date-selector" id="dateSelectorBar">
                @for ($i = 0; $i < 5; $i++)
                    @php $fd = $today->copy()->addDays($i); @endphp
                    <div class="nutri-date-item {{ $i === 0 ? 'active' : '' }}">
                        <span class="nutri-date-day">{{ $fd->format('D') }}</span>
                        <span class="nutri-date-num">{{ $fd->format('j') }}</span>
                    </div>
                @endfor
            </div>
            @endif

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
                    // todayTarget is null when no ICMR row covers this child at all.
                    // Each nutrient is resolved independently by NutritionPageService:
                    // a target that is missing or <= 0 is stored as null ("not
                    // available") for that nutrient only, so only its bar/ring is
                    // hidden while the others keep showing.
                    $hasTargets    = isset($todayTarget) && is_array($todayTarget);
                    $energyTarget  = $hasTargets ? ($todayTarget['energy_kcal'] ?? null) : null;
                    $proteinTarget = $hasTargets ? ($todayTarget['protein_g']   ?? null) : null;
                    $fatTarget     = $hasTargets ? ($todayTarget['fat_g']       ?? null) : null;

                    $hasEnergy  = $energyTarget  !== null && (float) $energyTarget  > 0;
                    $hasProtein = $proteinTarget !== null && (float) $proteinTarget > 0;
                    $hasFat     = $fatTarget     !== null && (float) $fatTarget     > 0;

                    $targetCals    = $hasEnergy  ? (float) $energyTarget  : 0;
                    $targetProtein = $hasProtein ? (float) $proteinTarget : 0;
                    $targetFat     = $hasFat     ? (float) $fatTarget     : 0;

                    $currentDayForRing = $activeDayIndex;
                    // Get the active day's meal slots from the formatPlan() indexed list.
                    $dayPlanForRing = $planDaysIndexed[$currentDayForRing]['meals'] ?? [];

                    // Carbs: no ICMR target exists; sum planned foods only (shown as plain number).
                    $plannedCarbs = 0;
                    $plannedCarbFoodIds = [];
                    foreach ($dayPlanForRing as $slot) {
                        if (!isset($slot['items'])) continue;
                        foreach ($slot['items'] as $itm) {
                            $fid = (int) ($itm['food_id'] ?? 0);
                            if ($fid <= 0 || isset($plannedCarbFoodIds[$fid])) continue;
                            $plannedCarbFoodIds[$fid] = true;
                            $plannedCarbs += (float) ($itm['nutrients']['carb_g'] ?? 0);
                        }
                    }
                    $plannedCarbs = round($plannedCarbs, 1);

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

                    // Percentages for initial SSR render — only meaningful when a
                    // given nutrient's target is available. Each is computed
                    // independently so a missing nutrient never divides by zero.
                    $pctProtein = $hasProtein ? min(100, ($initialConsumedProtein / $targetProtein) * 100) : 0;
                    $pctFat     = $hasFat     ? min(100, ($initialConsumedFat     / $targetFat)     * 100) : 0;

                    $ringCircumference = 251.3;
                    $calsPct   = $hasEnergy ? min(1, $initialConsumedCals / $targetCals) : 0;
                    $ringOffset = $ringCircumference * (1 - $calsPct);
                    $ringColor  = $calsPct >= 0.99 ? '#22c55e' : ($calsPct >= 0.5 ? '#22c55e' : ($calsPct > 0 ? '#f59e0b' : '#e5e7eb'));
                @endphp

                <div class="nutri-card">
                    @if($hasTargets)
                    <div class="cal-summary-grid">
                        @if($hasEnergy)
                        <div class="cal-ring-container"
                             data-target-cals="{{ $targetCals }}"
                             data-consumed-cals="{{ $initialConsumedCals }}"
                             data-target-protein="{{ $hasProtein ? $targetProtein : 0 }}"
                             data-target-fat="{{ $hasFat ? $targetFat : 0 }}"
                             data-consumed-protein="{{ $initialConsumedProtein }}"
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
                        @else
                        {{-- Energy target not available: no ring SVG. A container is still
                             rendered so nutrition.js can read the protein/fat targets. --}}
                        <div class="cal-ring-container cal-ring-unavailable"
                             data-target-cals="0"
                             data-target-protein="{{ $hasProtein ? $targetProtein : 0 }}"
                             data-target-fat="{{ $hasFat ? $targetFat : 0 }}"
                             data-consumed-protein="{{ $initialConsumedProtein }}"
                             data-consumed-fat="{{ $initialConsumedFat }}">
                            <div class="cal-ring-text">
                                <span class="cal-ring-val" id="calsConsumed">{{ $initialConsumedCals }}</span>
                                <span class="cal-ring-lbl">kcal · target not available</span>
                            </div>
                        </div>
                        @endif
                        <div class="macro-bars">
                            @if($hasEnergy)
                            <div style="font-weight: 800; font-size: 0.9rem; margin-bottom: 4px;">
                                <span id="calsRemaining" style="color: var(--pk);">{{ max(0, $targetCals - $initialConsumedCals) }}</span> kcal remaining
                            </div>
                            @else
                            <div style="font-weight: 800; font-size: 0.9rem; margin-bottom: 4px; color: var(--muted);">
                                Energy target not available
                            </div>
                            @endif
                            <div class="macro-bar-item">
                                <div class="macro-bar-labels">
                                    <span class="macro-name">Protein</span>
                                    @if($hasProtein)
                                    <span class="macro-val" id="proteinVal">{{ $initialConsumedProtein }} / {{ $targetProtein }}g</span>
                                    @else
                                    <span class="macro-val" id="proteinVal">{{ $initialConsumedProtein }}g · target not available</span>
                                    @endif
                                </div>
                                @if($hasProtein)
                                <div class="macro-track"><div class="macro-fill protein" id="proteinFill" style="width: {{ $pctProtein }}%"></div></div>
                                @endif
                            </div>
                            {{-- Carbs: no ICMR target — show planned total as plain info only --}}
                            <div class="macro-bar-item">
                                <div class="macro-bar-labels">
                                    <span class="macro-name">Carbs planned</span>
                                    <span class="macro-val" id="carbsVal">{{ $plannedCarbs }}g</span>
                                </div>
                            </div>
                            <div class="macro-bar-item">
                                <div class="macro-bar-labels">
                                    <span class="macro-name">Fats</span>
                                    @if($hasFat)
                                    <span class="macro-val" id="fatVal">{{ $initialConsumedFat }} / {{ $targetFat }}g</span>
                                    @else
                                    <span class="macro-val" id="fatVal">{{ $initialConsumedFat }}g · target not available</span>
                                    @endif
                                </div>
                                @if($hasFat)
                                <div class="macro-track"><div class="macro-fill fats" id="fatFill" style="width: {{ $pctFat }}%"></div></div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @else
                    {{-- No ICMR target covers this child at all: show consumed values without progress bars --}}
                    <div style="padding: 12px 0; font-size: 0.9rem; color: var(--muted); font-weight: 600;">
                        Target not available — showing planned values only.
                    </div>
                    <div class="macro-bars">
                        <div class="macro-bar-item">
                            <div class="macro-bar-labels">
                                <span class="macro-name">Calories</span>
                                <span class="macro-val" id="calsConsumed">{{ $initialConsumedCals }} kcal</span>
                            </div>
                        </div>
                        <div class="macro-bar-item">
                            <div class="macro-bar-labels">
                                <span class="macro-name">Protein</span>
                                <span class="macro-val" id="proteinVal">{{ $initialConsumedProtein }}g</span>
                            </div>
                        </div>
                        <div class="macro-bar-item">
                            <div class="macro-bar-labels">
                                <span class="macro-name">Carbs planned</span>
                                <span class="macro-val" id="carbsVal">{{ $plannedCarbs }}g</span>
                            </div>
                        </div>
                        <div class="macro-bar-item">
                            <div class="macro-bar-labels">
                                <span class="macro-name">Fats</span>
                                <span class="macro-val" id="fatVal">{{ $initialConsumedFat }}g</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Meals -->
                @php
                    $allDaysForFoodView = isset($planDays) && is_array($planDays) ? $planDays : [];
                    // Active day index for initial render.
                @endphp

                @if(!empty($allDaysForFoodView))
                    @foreach($allDaysForFoodView as $fdDay)
                        @php
                            $fdDayIndex   = (int) $fdDay['index'];
                            $fdDaySlots   = $fdDay['meals'];
                            $isFoodDayActive = ($fdDayIndex === $activeDayIndex);
                            // Ticks (consumed items) belong to today's date only.
                            // For another date we show nothing ticked and display a note.
                            $fdDayIsToday = ($fdDay['date'] ?? null) === Carbon::today()->toDateString();
                        @endphp
                        <div class="food-day-panel{{ $isFoodDayActive ? ' active' : '' }}"
                             data-food-day="{{ $fdDayIndex }}"
                             data-is-today="{{ $fdDayIsToday ? '1' : '0' }}">
                        @if(!$fdDayIsToday)
                        <div class="nutri-tracking-note" style="padding: 8px 12px; margin-bottom: 12px; background: #f3f4f6; border-radius: 10px; font-size: 0.8rem; color: var(--muted); font-weight: 600;">
                            📋 Tracking is available for today only. Tap a food to log it when it's today's plan day.
                        </div>
                        @endif
                        @foreach($fdDaySlots as $slotName => $data)
                        @php
                            $slotId = strtolower(str_replace(' ', '-', 'day' . $fdDayIndex . '-' . $slotName));
                            $slotKcalTotal    = round($data['total_nutrients']['energy_kcal'] ?? 0);
                            $slotProteinTotal = round($data['total_nutrients']['protein_g'] ?? 0);

                            $slotKcalConsumed    = 0;
                            $slotProteinConsumed = 0;
                            $slotSeenIds = [];
                            if ($isFoodDayActive && !empty($data['items'])) {
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
                                        $portion = (string) ($item['portion_g'] ?? '');
                                        // Avoid double-unit: only append 'g' when the value is
                                        // a plain number (old plans stored it as e.g. "150g").
                                        if (is_numeric($portion)) {
                                            $portion .= 'g';
                                        }
                                        // Only mark as consumed for the active day (consumed items are today-scoped)
                                        $isConsumed = $isFoodDayActive && in_array((int)($item['food_id'] ?? 0), $consumedIds, true);
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
                        </div>{{-- /.food-day-panel --}}
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
                    // Build a flat indexed array from the formatPlan() day list so the nutri view
                    // can iterate all plan days regardless of length.
                    $nutriDays = isset($planDays) && is_array($planDays) ? $planDays : [];
                    // Default selection: today's plan day index if it is within the plan, else 0.
                    $selectedNutriIndex = $activeDayIndex;
                    $allIndices = array_column($nutriDays, 'index');
                    if (empty($nutriDays) || !in_array($selectedNutriIndex, $allIndices, true)) {
                        $selectedNutriIndex = $nutriDays[0]['index'] ?? 0;
                    }
                @endphp

                @if($nutriDays)
                    <div class="nv-view">
                        <div class="nv-heading">
                            <div>
                                <span class="nv-eyebrow">Meal plan</span>
                                <h2>Nutrition by day</h2>
                                <p>Choose a day, then open a food to inspect its full nutrient breakdown.</p>
                            </div>
                            <span class="nv-plan-label">Planned values</span>
                        </div>

                        <div class="nv-day-switcher" role="tablist" aria-label="Meal plan days">
                            @foreach($nutriDays as $nvDay)
                                @php $nvIdx = $nvDay['index']; $nvLabel = $nvDay['label']; @endphp
                                <button type="button" class="nv-day-button {{ $nvIdx === $selectedNutriIndex ? 'active' : '' }}"
                                        id="nv-day-tab-{{ $nvIdx }}" role="tab"
                                        aria-selected="{{ $nvIdx === $selectedNutriIndex ? 'true' : 'false' }}"
                                        aria-controls="nv-day-panel-{{ $nvIdx }}"
                                        data-nv-day="{{ $nvIdx }}">
                                    <span>{{ substr($nvLabel, 0, 3) }}</span>
                                    <small>{{ count($nvDay['meals']) }} {{ count($nvDay['meals']) === 1 ? 'meal' : 'meals' }}</small>
                                </button>
                            @endforeach
                        </div>

                        @foreach($nutriDays as $nvDay)
                            @php
                                $nvIdx     = $nvDay['index'];
                                $nvLabel   = $nvDay['label'];
                                $dayMeals  = $nvDay['meals'];
                                $dayTotals = [];
                                $dayFoodCount = 0;
                                foreach ($dayMeals as $mealData) {
                                    foreach ($mealData['total_nutrients'] ?? [] as $key => $value) {
                                        $dayTotals[$key] = ($dayTotals[$key] ?? 0) + (float) $value;
                                    }
                                    $dayFoodCount += count($mealData['items'] ?? []);
                                }
                            @endphp
                            <section class="nv-day-panel" id="nv-day-panel-{{ $nvIdx }}"
                                     role="tabpanel" aria-labelledby="nv-day-tab-{{ $nvIdx }}"
                                     data-nv-panel="{{ $nvIdx }}" {{ $nvIdx === $selectedNutriIndex ? '' : 'hidden' }}>
                                <div class="nv-day-summary">
                                    <div>
                                        <span class="nv-eyebrow">{{ $nvLabel }} · Day {{ $nvIdx + 1 }}</span>
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
                    foreach (($planDays ?? []) as $planDay) {
                        $totals = ['energy_kcal' => 0, 'protein_g' => 0, 'carb_g' => 0, 'fat_g' => 0];
                        foreach ($planDay['meals'] as $planMeal) {
                            foreach ($totals as $nutrient => $value) {
                                $totals[$nutrient] += (float) ($planMeal['total_nutrients'][$nutrient] ?? 0);
                            }
                        }
                        $dailyPlanSummary[$planDay['date']] = array_merge($totals, [
                            '_label' => $planDay['label'],
                            '_index' => $planDay['index'],
                        ]);
                    }
                    // Goal line: use todayTarget's energy value if available, else omit marker.
                    $dailyGoalKcal = $hasEnergy ? $targetCals : null;
                    $maxPlannedEnergy = max(array_merge(
                        [$dailyGoalKcal ?? 1],
                        array_map(fn ($totals) => (float) $totals['energy_kcal'], array_values($dailyPlanSummary))
                    ));
                    $todayConsumedCals = (float) ($initialConsumedCals ?? 0);
                    $todayCaloriesProgress = $hasEnergy ? min(100, ($todayConsumedCals / $targetCals) * 100) : 0;
                    $todayCaloriesRemaining = $hasEnergy ? max(0, $targetCals - $todayConsumedCals) : null;
                    // Today's plan macros: look up by today's date string (canonical key).
                    $todayDateKey    = now()->toDateString();
                    $todayPlanMacros = $dailyPlanSummary[$todayDateKey] ?? $dailyPlanSummary[array_key_first($dailyPlanSummary) ?? ''] ?? [];
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
                                <h3 id="dc-today-title">
                                    <span id="dailyCaloriesConsumed">{{ round($todayConsumedCals) }}</span>
                                    @if($hasEnergy)
                                        <small>of {{ round($targetCals) }} kcal</small>
                                    @else
                                        <small>kcal</small>
                                    @endif
                                </h3>
                            </div>
                            <div class="dc-calorie-status" id="dailyCaloriesStatus">
                                @if($hasEnergy)
                                    @if($todayConsumedCals > $targetCals)
                                        {{ round($todayConsumedCals - $targetCals) }} kcal over goal
                                    @else
                                        {{ round($todayCaloriesRemaining) }} kcal remaining
                                    @endif
                                @else
                                    Target not available
                                @endif
                            </div>
                        </div>
                        @if($hasEnergy)
                        <div class="dc-progress-track" role="progressbar" aria-label="Calories consumed today" aria-valuemin="0" aria-valuemax="{{ round($targetCals) }}" aria-valuenow="{{ round($todayConsumedCals) }}">
                            <div class="dc-progress-fill" id="dailyCaloriesProgress" style="width: {{ $todayCaloriesProgress }}%;"></div>
                        </div>
                        <div class="dc-progress-caption"><span>0 kcal</span><span>Goal: {{ round($targetCals) }} kcal</span></div>
                        @endif
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
                        @forelse($dailyPlanSummary as $dcDate => $totals)
                            @php
                                $plannedPct  = min(100, ($totals['energy_kcal'] / $maxPlannedEnergy) * 100);
                                $dayGoal     = $dailyGoalKcal;
                                $dayGoalPct  = ($dayGoal !== null) ? min(100, ($dayGoal / $maxPlannedEnergy) * 100) : null;
                                $dcIsToday   = ($dcDate === now()->toDateString());
                                $dcLabel     = $totals['_label'] ?? substr($dcDate, -5); // "Mon" label from formatPlan
                            @endphp
                            <div class="dc-week-row {{ $dcIsToday ? 'is-today' : '' }}">
                                <div class="dc-week-label">
                                    <strong>{{ substr($dcLabel, 0, 3) }}</strong>
                                    <span>{{ round($totals['energy_kcal']) }}{{ $dayGoal !== null ? ' / '.round($dayGoal) : '' }} kcal</span>
                                </div>
                                <div class="dc-week-track">
                                    <div class="dc-week-bar" style="width: {{ $plannedPct }}%;"></div>
                                    @if($dayGoalPct !== null)
                                    <span class="dc-goal-marker" style="left: {{ $dayGoalPct }}%;" aria-hidden="true"></span>
                                    @endif
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
                $initialWeight = isset($assessment) ? ($assessment->initial_weight_kg ?? $assessment->current_weight_kg ?? null) : null;
                $initialHeight = isset($assessment) ? ($assessment->initial_height_cm ?? $assessment->current_height_cm ?? null) : null;
                $currentWeight = isset($assessment) ? ($assessment->current_weight_kg ?? null) : null;
                $currentHeight = isset($assessment) ? ($assessment->current_height_cm ?? null) : null;

                // Weight badge & range — only computable when all values are present and > 0.
                $weightAvailable = ($currentWeight !== null && $currentWeight > 0);
                if ($weightAvailable) {
                    $targetWeight = round($currentWeight * 1.15, 1);
                    $initW        = $initialWeight > 0 ? $initialWeight : $currentWeight;
                    $weightMin    = round(min($initW, $currentWeight, $targetWeight) - 5, 1);
                    $weightMax    = round(max($initW, $currentWeight, $targetWeight) + 5, 1);
                    $weightRange  = max(0.1, $weightMax - $weightMin);
                    $currWpct     = max(0, min(100, (($currentWeight - $weightMin) / $weightRange) * 100));
                    // Badge: below / on track / above relative to initial + target corridor.
                    if ($currentWeight < $initW * 0.97) {
                        $weightBadgeText  = 'Below range';
                        $weightBadgeBg    = '#fef3c7';
                        $weightBadgeColor = '#b45309';
                    } elseif ($currentWeight > $targetWeight * 1.03) {
                        $weightBadgeText  = 'Above range';
                        $weightBadgeBg    = '#fee2e2';
                        $weightBadgeColor = '#dc2626';
                    } else {
                        $weightBadgeText  = 'On track ✓';
                        $weightBadgeBg    = '#ecfccb';
                        $weightBadgeColor = '#4d7c0f';
                    }
                }

                // Height badge & range.
                $heightAvailable = ($currentHeight !== null && $currentHeight > 0);
                if ($heightAvailable) {
                    $targetHeight = round($currentHeight * 1.05, 1);
                    $initH        = ($initialHeight !== null && $initialHeight > 0) ? $initialHeight : $currentHeight;
                    $heightMin    = round(min($initH, $currentHeight, $targetHeight) - 5, 1);
                    $heightMax    = round(max($initH, $currentHeight, $targetHeight) + 5, 1);
                    $heightRange  = max(0.1, $heightMax - $heightMin);
                    $currHpct     = max(0, min(100, (($currentHeight - $heightMin) / $heightRange) * 100));
                    // Badge: below expected growth corridor vs initial.
                    if ($initH > 0 && $currentHeight < $initH * 0.98) {
                        $heightBadgeText  = 'Below range';
                        $heightBadgeBg    = '#fef3c7';
                        $heightBadgeColor = '#b45309';
                    } elseif ($initH > 0 && $currentHeight > $targetHeight * 1.02) {
                        $heightBadgeText  = 'Above range';
                        $heightBadgeBg    = '#fee2e2';
                        $heightBadgeColor = '#dc2626';
                    } else {
                        $heightBadgeText  = 'On track ✓';
                        $heightBadgeBg    = '#ecfccb';
                        $heightBadgeColor = '#4d7c0f';
                    }
                }

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

                {{-- Height card --}}
                <div style="background: #fff; border: 1.5px solid var(--border); border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <div style="font-weight: 600; color: var(--dk); font-size: 1rem;">📏 Height</div>
                        @if($heightAvailable)
                        <div style="background: {{ $heightBadgeBg }}; color: {{ $heightBadgeColor }}; font-weight: 700; font-size: 0.8rem; padding: 4px 12px; border-radius: 20px;">{{ $heightBadgeText }}</div>
                        @endif
                    </div>
                    @if($heightAvailable)
                    <div style="position: relative; height: 16px; margin-bottom: 8px; border-radius: 8px; background: linear-gradient(to right, #a3e635, #fef08a);">
                        <div style="position: absolute; left: {{ $currHpct }}%; top: -6px; bottom: -6px; width: 4px; background: #1e293b; border-radius: 2px; transform: translateX(-50%); z-index: 2;"></div>
                    </div>
                    <div style="position: relative; height: 24px; font-weight: 600; color: var(--muted); font-size: 0.85rem;">
                        <span style="position: absolute; left: 0;">{{ $heightMin }} cm</span>
                        <span style="position: absolute; left: {{ $currHpct }}%; transform: translateX(-50%); color: var(--dk); font-weight: 600;">{{ $child->name ?? 'Child' }} {{ $currentHeight }} cm</span>
                        <span style="position: absolute; right: 0;">{{ $heightMax }} cm</span>
                    </div>
                    @else
                    <div style="font-size: 0.85rem; color: var(--muted); font-weight: 600;">Not provided</div>
                    @endif
                </div>

                {{-- Weight card --}}
                <div style="background: #fff; border: 1.5px solid var(--border); border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <div style="font-weight: 600; color: var(--dk); font-size: 1rem;">⚖️ Weight</div>
                        @if($weightAvailable)
                        <div style="background: {{ $weightBadgeBg }}; color: {{ $weightBadgeColor }}; font-weight: 700; font-size: 0.8rem; padding: 4px 12px; border-radius: 20px;">{{ $weightBadgeText }}</div>
                        @endif
                    </div>
                    @if($weightAvailable)
                    <div style="position: relative; height: 16px; margin-bottom: 8px; border-radius: 8px; background: linear-gradient(to right, #a3e635, #fef08a);">
                        <div style="position: absolute; left: {{ $currWpct }}%; top: -6px; bottom: -6px; width: 4px; background: #1e293b; border-radius: 2px; transform: translateX(-50%); z-index: 2;"></div>
                    </div>
                    <div style="position: relative; height: 24px; font-weight: 600; color: var(--muted); font-size: 0.85rem;">
                        <span style="position: absolute; left: 0;">{{ $weightMin }} kg</span>
                        <span style="position: absolute; left: {{ $currWpct }}%; transform: translateX(-50%); color: var(--dk); font-weight: 600;">{{ $child->name ?? 'Child' }} {{ $currentWeight }} kg</span>
                        <span style="position: absolute; right: 0;">{{ $weightMax }} kg</span>
                    </div>
                    @else
                    <div style="font-size: 0.85rem; color: var(--muted); font-weight: 600;">Not provided</div>
                    @endif
                </div>

                @php
                    $calsPct = $hasEnergy ? min(100, ($initialConsumedCals / $targetCals) * 100) : null;
                    $energyStateBadge = ($calsPct === null) ? null : ($calsPct < 80 ? 'Needs a boost' : 'On track ✓');
                    $energyStateColor = ($calsPct === null) ? '#f3f4f6' : ($calsPct < 80 ? '#fef3c7' : '#ecfccb');
                    $energyStateText  = ($calsPct === null) ? '#6b7280' : ($calsPct < 80 ? '#b45309' : '#4d7c0f');
                @endphp
                <div style="background: #fff; border: 1.5px solid var(--border); border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <div style="font-weight: 600; color: var(--dk); font-size: 1rem;">⚡ Daily energy</div>
                        @if($energyStateBadge !== null)
                        <div id="energyBadge" style="background: {{ $energyStateColor }}; color: {{ $energyStateText }}; font-weight: 700; font-size: 0.8rem; padding: 4px 12px; border-radius: 20px;">{{ $energyStateBadge }}</div>
                        @endif
                    </div>
                    @if($hasEnergy)
                    <div style="position: relative; height: 16px; margin-bottom: 12px; border-radius: 8px; background: #e5e7eb; overflow: hidden;">
                        <div id="energyBarFill" style="position: absolute; left: 0; top: 0; bottom: 0; width: {{ $calsPct }}%; background: var(--or); border-radius: 8px; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 0.85rem;">
                        <span style="color: var(--muted);">Now ≈ <span style="color: var(--dk); font-weight: 700;" id="calsConsumedEnergyCard">{{ $initialConsumedCals }}</span> <span style="color: var(--dk); font-weight: 700;">kcal</span></span>
                        <span style="color: var(--muted);">Needs <span style="color: var(--dk); font-weight: 700;">{{ $targetCals }} kcal</span></span>
                    </div>
                    @else
                    <div style="font-size: 0.85rem; color: var(--muted); font-weight: 600;">Target not available</div>
                    @endif
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

{{-- Inline JS for week/day navigation --}}
<script>
(function () {
    'use strict';

    var weekTabBar    = document.getElementById('weekTabBar');
    var dateSelectorBar = document.getElementById('dateSelectorBar');
    if (!dateSelectorBar) return;

    /**
     * Show only chips for the given weekNo; hide all others.
     * Also mark the active week tab button.
     */
    function switchWeek(weekNo) {
        // Update date chips visibility
        var chips = dateSelectorBar.querySelectorAll('.nutri-date-item');
        chips.forEach(function (chip) {
            var chipWeek = parseInt(chip.dataset.week || '1', 10);
            if (chipWeek === weekNo) {
                chip.classList.remove('nutri-date-hidden');
            } else {
                chip.classList.add('nutri-date-hidden');
            }
        });

        // Update week tab active state
        if (weekTabBar) {
            weekTabBar.querySelectorAll('.nutri-week-tab').forEach(function (btn) {
                btn.classList.toggle('active', parseInt(btn.dataset.week, 10) === weekNo);
            });
        }
    }

    /**
     * Activate a date chip by day_index.
     * Dispatches a nutriDayChanged event carrying the numeric index so that
     * nutrition.js can switch the correct food-day-panel (keyed by index, not
     * weekday name) without any coupling to plan length.
     */
    function switchDay(dayIndex, weekNo) {
        var chips = dateSelectorBar.querySelectorAll('.nutri-date-item[data-day-index]');
        chips.forEach(function (chip) {
            chip.classList.toggle('active', parseInt(chip.dataset.dayIndex, 10) === dayIndex);
        });

        // Emit a custom event so nutrition.js can react without coupling.
        document.dispatchEvent(new CustomEvent('nutriDayChanged', {
            detail: { dayIndex: dayIndex, weekNo: weekNo }
        }));
    }

    // Week tab clicks
    if (weekTabBar) {
        weekTabBar.addEventListener('click', function (e) {
            var btn = e.target.closest('.nutri-week-tab');
            if (!btn) return;
            var wn = parseInt(btn.dataset.week, 10);
            switchWeek(wn);
            // Select the first chip in the new week
            var firstChip = dateSelectorBar.querySelector('.nutri-date-item[data-week="' + wn + '"]');
            if (firstChip) {
                var di = parseInt(firstChip.dataset.dayIndex || '0', 10);
                switchDay(di, wn);
            }
        });
    }

    // Date chip clicks
    dateSelectorBar.addEventListener('click', function (e) {
        var chip = e.target.closest('.nutri-date-item[data-day-index]');
        if (!chip) return;
        var di = parseInt(chip.dataset.dayIndex, 10);
        var wn = parseInt(chip.dataset.week || '1', 10);
        switchDay(di, wn);
    });
})();
</script>

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