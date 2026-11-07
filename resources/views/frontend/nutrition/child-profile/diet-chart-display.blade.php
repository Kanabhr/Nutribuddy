@extends('layouts.main')

@section('content')
    @php
        /*
         * $formattedPlan is the canonical list produced by DietChartDisplayService::formatPlan().
         * Each entry: ['index','label','date','week','meals']
         * $sevenDayPlan keeps old callers working (day-name keyed array).
         *
         * For display we use $formattedPlan. When absent (very old cached view calls),
         * fall back to the old $sevenDayPlan shape rebuilt as a single-week list.
         */
        $planDays   = $formattedPlan ?? [];
        $totalDays  = count($planDays);
        $weekCount  = $totalDays > 0 ? (intdiv($totalDays - 1, 7) + 1) : 1;
        $multiWeek  = $weekCount > 1;

        $allSlots = ['Breakfast', 'Morning Snack', 'Lunch', 'Evening Snack', 'Snack', 'Dinner'];
        $slotTimes = [
            'Breakfast'     => '7 AM',
            'Morning Snack' => '10 AM',
            'Lunch'         => '12 PM',
            'Evening Snack' => '4 PM',
            'Snack'         => '4 PM',
            'Dinner'        => '7 PM',
        ];
    @endphp
    <div class="diet-chart-container">
        {{-- HERO --}}
        <div class="diet-chart-hero">
            <div class="hero-content">
                @if($hasPlan && $totalDays > 0)
                    <h1>{{ $totalDays }}-Day Personalized Meal Plan</h1>
                @else
                    <h1>Personalized Meal Plan</h1>
                @endif
                <p>A personalized food rhythm for {{ $child->name }}.</p>
            </div>
            <div class="hero-stats">
                @if($hasPlan && $totalDays > 0)
                    <div class="stat-card">
                        <span class="stat-value">{{ $totalDays }}</span>
                        <span class="stat-label">Days</span>
                    </div>
                @endif
                <div class="stat-card active">
                    <span class="stat-value">Active</span>
                    <span class="stat-label">Plan</span>
                </div>
            </div>
        </div>

        @if($hasPlan)
            {{-- MEAL PLAN SECTION --}}
            <div class="meal-plan-section">
                <div class="plan-header">
                    <h2>Meal Plan</h2>
                    <div class="plan-meta">
                        <span class="child-name">{{ $child->name }}</span>
                        <span class="child-age">{{ $child->age }} years</span>
                        <span class="diet-type">{{ ucfirst($assessment->diet_type ?? 'N/A') }} Diet</span>
                        <span class="region">{{ ucfirst($assessment->region ?? 'N/A') }}</span>
                    </div>
                </div>

                @if(filled($dietPlan->review_notes))
                    <aside class="nutritionist-remark" aria-label="Note from your nutritionist">
                        <div class="nutritionist-remark-icon"><i class="ri-chat-quote-line" aria-hidden="true"></i></div>
                        <div>
                            <span class="nutritionist-remark-label">A note from your nutritionist</span>
                            <p>{{ $dietPlan->review_notes }}</p>
                            @if($dietPlan->reviewed_at)
                                <time datetime="{{ $dietPlan->reviewed_at->toIso8601String() }}">Reviewed {{ $dietPlan->reviewed_at->format('d M Y') }}</time>
                            @endif
                        </div>
                    </aside>
                @endif

                {{-- WEEK TABS — shown only when the plan spans more than one week --}}
                @if($multiWeek)
                    <div class="week-tabs" role="tablist" aria-label="Week navigation">
                        @for($w = 1; $w <= $weekCount; $w++)
                            <button
                                class="week-tab {{ $w === 1 ? 'active' : '' }}"
                                data-week="{{ $w }}"
                                role="tab"
                                aria-selected="{{ $w === 1 ? 'true' : 'false' }}"
                                aria-controls="week-panel-{{ $w }}"
                            >Week {{ $w }}</button>
                        @endfor
                    </div>
                @endif

                {{-- One panel per week --}}
                @for($w = 1; $w <= $weekCount; $w++)
                    @php $weekDays = array_filter($planDays, fn($d) => $d['week'] === $w); @endphp
                    <div
                        class="week-panel"
                        id="week-panel-{{ $w }}"
                        role="tabpanel"
                        aria-labelledby="week-tab-{{ $w }}"
                        style="{{ $w === 1 ? '' : 'display:none;' }}"
                    >
                        {{-- DAY TABS --}}
                        <div class="week-days-nav" role="tablist" aria-label="Day navigation week {{ $w }}">
                            @foreach($weekDays as $dayData)
                                @php $isFirst = ($dayData['index'] === array_values($weekDays)[0]['index']); @endphp
                                <button
                                    class="day-tab {{ $isFirst ? 'active' : '' }}"
                                    data-day-id="day-{{ $dayData['index'] }}"
                                    data-week="{{ $w }}"
                                    role="tab"
                                    aria-selected="{{ $isFirst ? 'true' : 'false' }}"
                                    aria-controls="day-{{ $dayData['index'] }}"
                                >
                                    <span class="day-label">{{ strtoupper(substr($dayData['label'], 0, 3)) }}</span>
                                    <span class="day-date">{{ \Carbon\Carbon::parse($dayData['date'])->format('d M') }}</span>
                                </button>
                            @endforeach
                        </div>

                        {{-- DAY PANELS --}}
                        @foreach($weekDays as $dayData)
                            @php $isFirst = ($dayData['index'] === array_values($weekDays)[0]['index']); @endphp
                            <div
                                class="day-meals"
                                id="day-{{ $dayData['index'] }}"
                                style="{{ $isFirst ? '' : 'display:none;' }}"
                                role="tabpanel"
                            >
                                <div class="day-title-section">
                                    <h3>{{ $dayData['label'] }}, {{ \Carbon\Carbon::parse($dayData['date'])->format('d M Y') }}</h3>
                                </div>

                                @foreach($allSlots as $slot)
                                    @if(isset($dayData['meals'][$slot]))
                                        @php $mealData = $dayData['meals'][$slot]; @endphp
                                        <div class="meal-slot">
                                            <div class="meal-time">{{ $slotTimes[$slot] ?? '' }}</div>
                                            <div class="meal-content">
                                                <div class="slot-name">{{ $slot }}</div>
                                                @foreach($mealData['items'] as $item)
                                                    <div class="meal-item">
                                                        <div class="meal-dish">
                                                            <h4>{{ $item['food_name'] ?? 'N/A' }}</h4>
                                                            @if(!empty($item['regional_name']))
                                                                <p class="regional-name">{{ $item['regional_name'] }}</p>
                                                            @endif
                                                            <p class="meal-portion">{{ $item['portion_g'] ?? 'N/A' }}g serving</p>
                                                        </div>
                                                        <div class="meal-nutrients">
                                                            <div class="nutrient">
                                                                <span class="n-value">{{ number_format($item['nutrients']['energy_kcal'] ?? 0, 0) }}</span>
                                                                <span class="n-label">kcal</span>
                                                            </div>
                                                            <div class="nutrient">
                                                                <span class="n-value">{{ number_format($item['nutrients']['protein_g'] ?? 0, 1) }}</span>
                                                                <span class="n-label">g protein</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if(!empty($item['pairing_notes']))
                                                        <div class="pairing-note">
                                                            <span class="note-icon">💡</span>
                                                            <span class="note-text">
                                                                @foreach($item['pairing_notes'] as $note)
                                                                    {{ $note }}<br>
                                                                @endforeach
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endforeach

                                                @if(isset($mealData['total_nutrients']))
                                                    <div class="meal-total">
                                                        <div class="total-label">Slot Total</div>
                                                        <div class="total-values">
                                                            <div class="total-item">
                                                                <span class="label">Energy</span>
                                                                <span class="value">{{ number_format($mealData['total_nutrients']['energy_kcal'] ?? 0, 0) }} kcal</span>
                                                            </div>
                                                            <div class="total-item">
                                                                <span class="label">Protein</span>
                                                                <span class="value">{{ number_format($mealData['total_nutrients']['protein_g'] ?? 0, 1) }}g</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endfor
            </div>

            {{-- NUTRIENT GAPS SECTION --}}
            <div class="nutrient-gaps-section">
                <h2>Detected Nutrient Gaps</h2>
                <div class="gaps-grid">
                    @foreach($nutrientGaps as $nutrient => $gap)
                        @php
                            $catClass = strtolower(str_replace(' ', '-', $gap['gap_category'] ?? ''));
                        @endphp
                        <div class="gap-card gap-{{ $catClass }}">
                            <div class="gap-header">
                                <h4>{{ ucfirst(str_replace('_', ' ', $nutrient)) }}</h4>
                                <span class="gap-badge">{{ $gap['gap_category'] ?? 'Unknown' }}</span>
                            </div>
                            <div class="gap-details">
                                <div class="gap-info">
                                    <span class="info-label">Target</span>
                                    <span class="info-value">{{ number_format($gap['target'] ?? 0, 2) }}</span>
                                </div>
                                <div class="gap-info">
                                    <span class="info-label">Achieved</span>
                                    <span class="info-value">{{ number_format($gap['absorbed_amount'] ?? 0, 2) }}</span>
                                </div>
                                <div class="gap-info">
                                    <span class="info-label">Adequacy</span>
                                    <span class="info-value">{{ number_format($gap['adequacy_percent'] ?? 0, 1) }}%</span>
                                </div>
                            </div>
                            <div class="gap-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ min($gap['adequacy_percent'] ?? 0, 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- CLINICAL TIPS --}}
            @if(!empty($clinicalTips))
                <div class="clinical-tips-section">
                    <h2>Nutrition Tips for {{ $child->name }}</h2>
                    <div class="tips-grid">
                        @foreach($clinicalTips as $tip)
                            <div class="tip-card">
                                <div class="tip-header">
                                    <h4>{{ $tip['benefit'] ?? 'Nutrition Tip' }}</h4>
                                </div>
                                <div class="tip-combination">
                                    <strong>Combination:</strong> {{ $tip['combination'] ?? 'N/A' }}
                                </div>
                                @if(!empty($tip['science_reason']))
                                    <div class="tip-science">
                                        <strong>Why it works:</strong> {{ $tip['science_reason'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ACTION BUTTONS --}}
            <div class="plan-actions">
                <button class="btn btn-primary" id="downloadPdf">📥 Download Plan as PDF</button>
                <button class="btn btn-secondary" id="sharePlan">🔗 Share Plan</button>
                <button class="btn btn-warning" id="updatePlan">♻️ Regenerate Plan</button>
            </div>

        @else
            {{-- NO PLAN --}}
            <div class="no-plan-container">
                <div class="no-plan-icon">
                    <img src="{{ asset('img/no-plan.svg') }}" alt="No plan">
                </div>
                <h2>No Active Diet Plan</h2>
                <p>{{ $child->name }} doesn't have an active meal plan yet.</p>

                @if($purchasedPlans > 0)
                    <div class="plan-actions">
                        <button class="btn btn-primary" id="generateNewPlan">
                            ✨ Generate New Personalized Plan
                        </button>
                        <p class="info-text">You have {{ $purchasedPlans }} plan(s) remaining</p>
                    </div>
                @else
                    <div class="purchase-prompt">
                        <div class="prompt-icon">🛒</div>
                        <h3>Purchase a Plan to Get Started</h3>
                        <p>Create a personalized meal plan tailored to {{ $child->name }}'s nutritional needs, preferences, and health goals.</p>
                        <div class="purchase-info">
                            <div class="info-item"><span class="check">✓</span> Personalized based on health profile</div>
                            <div class="info-item"><span class="check">✓</span> Regional cuisine preferences</div>
                            <div class="info-item"><span class="check">✓</span> Allergy-safe meal options</div>
                            <div class="info-item"><span class="check">✓</span> Clinical tips & guidance</div>
                        </div>
                        <a href="{{ route('product') }}" class="btn btn-main btn-lg">Browse Plans &amp; Pricing →</a>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <style>
        .diet-chart-container { max-width: 1200px; margin: 0 auto; padding: 20px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

        /* HERO */
        .diet-chart-hero { background: linear-gradient(135deg, #7B2CBF 0%, #5A189A 100%); color: white; padding: 40px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; box-shadow: 0 10px 40px rgba(123,44,191,.2); }
        .hero-content h1 { font-size: 32px; font-weight: 700; margin: 0 0 8px; }
        .hero-content p  { font-size: 16px; opacity: .9; margin: 0; }
        .hero-stats { display: flex; gap: 20px; }
        .stat-card { background: rgba(255,255,255,.1); padding: 20px; border-radius: 12px; text-align: center; min-width: 120px; backdrop-filter: blur(10px); }
        .stat-card.active { background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.5); }
        .stat-value { display: block; font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .stat-label { display: block; font-size: 12px; text-transform: uppercase; opacity: .8; }

        /* PLAN SECTION */
        .meal-plan-section { background: white; border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .plan-header { margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; }
        .plan-header h2 { font-size: 24px; font-weight: 700; margin: 0 0 15px; }
        .plan-meta { display: flex; gap: 20px; flex-wrap: wrap; }
        .plan-meta span { background: #f5f5f5; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; color: #666; }

        /* NUTRITIONIST REMARK */
        .nutritionist-remark { display: flex; align-items: flex-start; gap: 14px; margin: 0 0 24px; padding: 18px 20px; border: 1px solid #cce8da; border-left: 4px solid #168a64; border-radius: 14px; background: linear-gradient(120deg,#f1fbf6,#fbfefc); }
        .nutritionist-remark-icon { display: grid; place-items: center; flex: 0 0 38px; width: 38px; height: 38px; border-radius: 12px; color: #168a64; background: #ddf4e8; font-size: 20px; }
        .nutritionist-remark-label { display: block; margin-bottom: 5px; color: #168a64; font-size: 12px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .nutritionist-remark p { margin: 0; color: #24483f; line-height: 1.6; white-space: pre-line; }
        .nutritionist-remark time { display: block; margin-top: 8px; color: #71817b; font-size: 12px; }

        /* WEEK TABS */
        .week-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .week-tab { background: #f9f9f9; border: 2px solid transparent; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all .2s; }
        .week-tab.active, .week-tab:hover { background: #7B2CBF; color: white; border-color: #7B2CBF; }

        /* DAY TABS */
        .week-days-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 2px solid #f0f0f0; }
        .day-tab { background: #f9f9f9; border: 2px solid transparent; padding: 12px 10px; border-radius: 12px; cursor: pointer; transition: all .3s; text-align: center; font-size: 12px; font-weight: 600; min-width: 64px; }
        .day-tab:hover { background: #f0f0f0; border-color: #7B2CBF; }
        .day-tab.active { background: #7B2CBF; color: white; border-color: #7B2CBF; }
        .day-label { display: block; font-weight: 700; margin-bottom: 4px; }
        .day-date  { display: block; font-size: 10px; opacity: .8; }

        /* DAY MEALS */
        .day-meals { animation: fadeIn .3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .day-title-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
        .day-title-section h3 { font-size: 22px; font-weight: 700; margin: 0; }

        /* MEAL SLOT */
        .meal-slot { display: flex; gap: 20px; margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 12px; border-left: 4px solid #7B2CBF; }
        .meal-time { font-weight: 700; color: #7B2CBF; font-size: 14px; min-width: 60px; text-align: center; }
        .meal-content { flex: 1; }
        .slot-name { font-size: 13px; font-weight: 700; color: #7B2CBF; text-transform: uppercase; margin-bottom: 12px; letter-spacing: .05em; }

        /* MEAL ITEM */
        .meal-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; }
        .meal-item:last-of-type { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .meal-dish h4 { font-size: 16px; font-weight: 600; margin: 0 0 4px; color: #333; }
        .regional-name { font-size: 12px; color: #999; font-style: italic; margin: 2px 0; }
        .meal-portion { font-size: 12px; color: #666; margin: 4px 0 0; }
        .meal-nutrients { display: flex; gap: 20px; }
        .nutrient { display: flex; flex-direction: column; align-items: center; }
        .n-value { font-size: 16px; font-weight: 700; color: #7B2CBF; }
        .n-label { font-size: 11px; color: #999; text-transform: uppercase; }

        /* PAIRING NOTE */
        .pairing-note { background: #FEF3E6; border-left: 3px solid #FF9900; padding: 12px 15px; border-radius: 6px; margin: 12px 0; display: flex; gap: 10px; font-size: 13px; color: #666; }
        .note-icon { flex-shrink: 0; }

        /* MEAL TOTAL */
        .meal-total { background: white; border-radius: 8px; padding: 12px; margin-top: 12px; }
        .total-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #999; margin-bottom: 8px; }
        .total-values { display: flex; gap: 20px; }
        .total-item { display: flex; flex-direction: column; }
        .total-item .label { font-size: 11px; color: #999; }
        .total-item .value { font-size: 14px; font-weight: 700; color: #333; }

        /* GAPS */
        .nutrient-gaps-section { background: white; border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .nutrient-gaps-section h2 { font-size: 24px; font-weight: 700; margin-bottom: 25px; }
        .gaps-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap: 20px; }
        .gap-card { padding: 20px; border-radius: 12px; background: #f9f9f9; border-left: 4px solid #ccc; }
        .gap-card.gap-met { border-left-color: #4CAF50; background: #F1F8E9; }
        .gap-card.gap-partial-gap { border-left-color: #FF9800; background: #FFF3E0; }
        .gap-card.gap-significant-gap { border-left-color: #FF5722; background: #FFEBEE; }
        .gap-card.gap-critical-gap { border-left-color: #F44336; background: #FFEBEE; }
        .gap-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .gap-header h4 { font-size: 14px; font-weight: 600; margin: 0; }
        .gap-badge { background: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .gap-details { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .gap-info { display: flex; flex-direction: column; }
        .info-label { font-size: 11px; color: #999; text-transform: uppercase; margin-bottom: 4px; }
        .info-value { font-size: 14px; font-weight: 700; color: #333; }
        .gap-progress { margin-top: 15px; }
        .progress-bar { height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg,#7B2CBF,#D81C7F); border-radius: 3px; transition: width .3s; }

        /* CLINICAL TIPS */
        .clinical-tips-section { background: white; border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .clinical-tips-section h2 { font-size: 24px; font-weight: 700; margin-bottom: 25px; }
        .tips-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(300px,1fr)); gap: 20px; }
        .tip-card { background: linear-gradient(135deg,#F8F9FA 0%,#F0F0F0 100%); padding: 20px; border-radius: 12px; border-left: 4px solid #7B2CBF; }
        .tip-card h4 { font-size: 16px; font-weight: 600; margin: 0 0 12px; color: #333; }
        .tip-combination { font-size: 13px; margin-bottom: 12px; line-height: 1.5; }
        .tip-combination strong { color: #7B2CBF; display: block; margin-bottom: 4px; }
        .tip-science { font-size: 13px; color: #666; line-height: 1.5; }
        .tip-science strong { color: #333; display: block; margin-bottom: 4px; }

        /* NO PLAN */
        .no-plan-container { background: white; border-radius: 16px; padding: 60px 40px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,.08); margin-bottom: 30px; }
        .no-plan-icon { margin-bottom: 30px; }
        .no-plan-icon img { max-width: 150px; height: auto; opacity: .8; }
        .no-plan-container h2 { font-size: 28px; font-weight: 700; margin: 20px 0 10px; }
        .no-plan-container > p { font-size: 16px; color: #666; margin-bottom: 30px; }
        .purchase-prompt { background: #F8F9FA; border: 2px dashed #7B2CBF; padding: 40px 30px; border-radius: 16px; margin-top: 30px; }
        .prompt-icon { font-size: 48px; margin-bottom: 20px; }
        .purchase-prompt h3 { font-size: 22px; font-weight: 700; margin: 0 0 10px; color: #333; }
        .purchase-prompt > p { font-size: 15px; color: #666; margin-bottom: 25px; }
        .purchase-info { text-align: left; display: inline-block; margin-bottom: 30px; max-width: 350px; }
        .info-item { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; font-size: 14px; }
        .check { background: #4CAF50; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }

        /* ACTION BUTTONS */
        .plan-actions { display: flex; gap: 15px; justify-content: center; margin-top: 30px; flex-wrap: wrap; }
        .btn { padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all .3s; }
        .btn-primary { background: #7B2CBF; color: white; }
        .btn-primary:hover { background: #5A189A; box-shadow: 0 4px 15px rgba(123,44,191,.3); }
        .btn-secondary { background: #E8E8E8; color: #333; }
        .btn-secondary:hover { background: #D0D0D0; }
        .btn-warning { background: #FF9800; color: white; }
        .btn-warning:hover { background: #F57C00; }
        .btn-main { background: linear-gradient(135deg,#7B2CBF,#D81C7F); color: white; padding: 15px 40px; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-main:hover { box-shadow: 0 6px 20px rgba(123,44,191,.3); color: white; }
        .btn-lg { min-width: 250px; }
        .info-text { font-size: 13px; color: #666; margin-top: 15px; }

        @media (max-width: 768px) {
            .diet-chart-hero { flex-direction: column; text-align: center; gap: 20px; }
            .hero-stats { width: 100%; justify-content: center; }
            .week-days-nav { grid-template-columns: repeat(auto-fit,minmax(70px,1fr)); }
            .meal-slot { flex-direction: column; }
            .meal-item { flex-direction: column; align-items: flex-start; }
            .gaps-grid { grid-template-columns: 1fr; }
            .plan-actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>

    <script>
        // Week tab navigation
        document.querySelectorAll('.week-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                const w = this.dataset.week;
                document.querySelectorAll('.week-tab').forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
                document.querySelectorAll('.week-panel').forEach(p => { p.style.display = 'none'; });
                this.classList.add('active');
                this.setAttribute('aria-selected','true');
                document.getElementById('week-panel-' + w).style.display = '';
            });
        });

        // Day tab navigation (delegated so it works for all week panels)
        document.addEventListener('click', function (e) {
            const tab = e.target.closest('.day-tab');
            if (!tab) return;
            const week  = tab.dataset.week;
            const dayId = tab.dataset.dayId;
            const panel = document.getElementById('week-panel-' + week);
            if (!panel) return;

            panel.querySelectorAll('.day-tab').forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
            panel.querySelectorAll('.day-meals').forEach(d => { d.style.display = 'none'; });

            tab.classList.add('active');
            tab.setAttribute('aria-selected','true');
            const dayEl = document.getElementById(dayId);
            if (dayEl) dayEl.style.display = '';
        });

        document.getElementById('downloadPdf')?.addEventListener('click', function () {
            alert('PDF download functionality coming soon!');
        });

        document.getElementById('sharePlan')?.addEventListener('click', function () {
            const text = `Check out {{ $child->name }}'s personalized meal plan on NutriBuddy!`;
            if (navigator.share) {
                navigator.share({ title: 'NutriBuddy Meal Plan', text, url: window.location.href });
            } else {
                alert('Sharing coming soon!');
            }
        });

        document.getElementById('updatePlan')?.addEventListener('click', function () {
            if (confirm('Generate a new personalized plan?')) {
                window.location.href = '{{ route("child.diet-chart.regenerate", $child->id) }}';
            }
        });

        document.getElementById('generateNewPlan')?.addEventListener('click', function () {
            window.location.href = '{{ route("frontend.nutrition.questionnaire.child", $child) }}';
        });
    </script>
@endsection
