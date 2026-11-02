@extends('layouts.main')

@section('content')
    <div class="diet-chart-container">
        <!-- HERO SECTION: Child Info + Plan Status -->
        <div class="diet-chart-hero">
            <div class="hero-content">
                <h1>7-Day Personalized Meal Plan</h1>
                <p>A personalized weekly food rhythm for {{ $child->name }}.</p>
            </div>
            
            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-value">7</span>
                    <span class="stat-label">Days</span>
                </div>
                <div class="stat-card active">
                    <span class="stat-value">Active</span>
                    <span class="stat-label">Plan</span>
                </div>
            </div>
        </div>

        @if($hasPlan)
            <!-- MEAL PLAN SECTION -->
            <div class="meal-plan-section">
                <div class="plan-header">
                    <h2>Meal Plan</h2>
                    <div class="plan-meta">
                        <span class="child-name">{{ $child->name }}</span>
                        <span class="child-age">{{ $child->age }} years</span>
                        <span class="diet-type">{{ ucfirst($assessment->diet_type ?? 'N/A') }} Diet</span>
                        <span class="region">{{ ucfirst($assessment->region ?? 'N/A') }} India</span>
                    </div>
                </div>

                @if (filled($dietPlan->review_notes))
                    <aside class="nutritionist-remark" aria-label="Note from your nutritionist">
                        <div class="nutritionist-remark-icon"><i class="ri-chat-quote-line" aria-hidden="true"></i></div>
                        <div>
                            <span class="nutritionist-remark-label">A note from your nutritionist</span>
                            <p>{{ $dietPlan->review_notes }}</p>
                            @if ($dietPlan->reviewed_at)
                                <time datetime="{{ $dietPlan->reviewed_at->toIso8601String() }}">Reviewed {{ $dietPlan->reviewed_at->format('d M Y') }}</time>
                            @endif
                        </div>
                    </aside>
                @endif

                <!-- WEEKLY NAVIGATION -->
                <div class="week-days-nav">
                    <button class="day-tab active" data-day="Monday" data-day-lower="monday">
                        <span class="day-label">MON</span>
                        <span class="day-focus">Immunity</span>
                    </button>
                    <button class="day-tab" data-day="Tuesday" data-day-lower="tuesday">
                        <span class="day-label">TUE</span>
                        <span class="day-focus">Growth</span>
                    </button>
                    <button class="day-tab" data-day="Wednesday" data-day-lower="wednesday">
                        <span class="day-label">WED</span>
                        <span class="day-focus">Brain</span>
                    </button>
                    <button class="day-tab" data-day="Thursday" data-day-lower="thursday">
                        <span class="day-label">THU</span>
                        <span class="day-focus">Immunity</span>
                    </button>
                    <button class="day-tab" data-day="Friday" data-day-lower="friday">
                        <span class="day-label">FRI</span>
                        <span class="day-focus">Energy</span>
                    </button>
                    <button class="day-tab" data-day="Saturday" data-day-lower="saturday">
                        <span class="day-label">SAT</span>
                        <span class="day-focus">Bone</span>
                    </button>
                    <button class="day-tab" data-day="Sunday" data-day-lower="sunday">
                        <span class="day-label">SUN</span>
                        <span class="day-focus">Rest</span>
                    </button>
                </div>

                <!-- DAILY MEAL DISPLAY -->
                <div class="daily-meals-container">
                    @php $dayIndex = 0; @endphp
                    @foreach($sevenDayPlan as $dayName => $meals)
                        <div class="day-meals" id="day-{{ strtolower($dayName) }}" style="display: {{ $dayIndex === 0 ? 'block' : 'none' }};">
                            <div class="day-title-section">
                                <h3>{{ $dayName }}</h3>
                                <span class="day-tag">{{ $dayName === 'Monday' ? 'GROWTH & BONES' : 'DAILY NUTRITION' }}</span>
                            </div>

                            <!-- MEAL SLOTS: 7 AM, 12 PM, 4 PM, 7 PM -->
                            @php
                                $mealTimes = [
                                    'Breakfast' => '7 AM',
                                    'Lunch' => '12 PM',
                                    'Snack' => '4 PM',
                                    'Dinner' => '7 PM'
                                ];
                            @endphp

                            @foreach($mealTimes as $slot => $time)
                                @if(isset($meals[$slot]))
                                    <div class="meal-slot">
                                        <div class="meal-time">{{ $time }}</div>
                                        <div class="meal-content">
                                            @if(isset($meals[$slot]['items']))
                                                @foreach($meals[$slot]['items'] as $item)
                                                    <div class="meal-item">
                                                        <div class="meal-dish">
                                                            <h4>{{ $item['food_name'] ?? 'N/A' }}</h4>
                                                            @if(isset($item['regional_name']))
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

                                                    {{-- Clinical Pairing Notes --}}
                                                    @if(isset($item['pairing_notes']) && !empty($item['pairing_notes']))
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
                                            @endif

                                            {{-- Total Meal Nutrients --}}
                                            @if(isset($meals[$slot]['total_nutrients']))
                                                <div class="meal-total">
                                                    <div class="total-label">Daily Total</div>
                                                    <div class="total-values">
                                                        <div class="total-item">
                                                            <span class="label">Energy</span>
                                                            <span class="value">{{ number_format($meals[$slot]['total_nutrients']['energy_kcal'] ?? 0, 0) }} kcal</span>
                                                        </div>
                                                        <div class="total-item">
                                                            <span class="label">Protein</span>
                                                            <span class="value">{{ number_format($meals[$slot]['total_nutrients']['protein_g'] ?? 0, 1) }}g</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @php $dayIndex++; @endphp
                    @endforeach
                </div>
            </div>

            <!-- NUTRIENT GAPS SECTION -->
            <div class="nutrient-gaps-section">
                <h2>Detected Nutrient Gaps</h2>
                <div class="gaps-grid">
                    @foreach($nutrientGaps as $nutrient => $gap)
                        <div class="gap-card gap-{{ strtolower($gap['gap_category']) }}">
                            <div class="gap-header">
                                <h4>{{ ucfirst(str_replace('_', ' ', $nutrient)) }}</h4>
                                <span class="gap-badge">{{ $gap['gap_category'] }}</span>
                            </div>

                            <div class="gap-details">
                                <div class="gap-info">
                                    <span class="info-label">Target:</span>
                                    <span class="info-value">{{ number_format($gap['target'], 2) }}</span>
                                </div>
                                <div class="gap-info">
                                    <span class="info-label">Achieved:</span>
                                    <span class="info-value">{{ number_format($gap['absorbed_amount'], 2) }}</span>
                                </div>
                                <div class="gap-info">
                                    <span class="info-label">Adequacy:</span>
                                    <span class="info-value">{{ number_format($gap['adequacy_percent'], 1) }}%</span>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="gap-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ min($gap['adequacy_percent'], 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- CLINICAL TIPS SECTION -->
            @if(isset($clinicalTips) && !empty($clinicalTips))
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
                                @if(isset($tip['science_reason']))
                                    <div class="tip-science">
                                        <strong>Why it works:</strong> {{ $tip['science_reason'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- DOWNLOAD & SHARE -->
            <div class="plan-actions">
                <button class="btn btn-primary" id="downloadPdf">
                    📥 Download Plan as PDF
                </button>
                <button class="btn btn-secondary" id="sharePlan">
                    🔗 Share Plan
                </button>
                <button class="btn btn-warning" id="updatePlan">
                    ♻️ Regenerate Plan
                </button>
            </div>

        @else
            <!-- NO PLAN MESSAGE -->
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
                        <p>Create a personalized 7-day meal plan tailored to {{ $child->name }}'s nutritional needs, preferences, and health goals.</p>
                        
                        <div class="purchase-info">
                            <div class="info-item">
                                <span class="check">✓</span> Personalized based on health profile
                            </div>
                            <div class="info-item">
                                <span class="check">✓</span> Regional cuisine preferences
                            </div>
                            <div class="info-item">
                                <span class="check">✓</span> Allergy-safe meal options
                            </div>
                            <div class="info-item">
                                <span class="check">✓</span> Clinical tips & guidance
                            </div>
                        </div>

                        <a href="{{ route('product') }}" class="btn btn-main btn-lg">
                            Browse Plans & Pricing →
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <style>
        .diet-chart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* HERO SECTION */
        .diet-chart-hero {
            background: linear-gradient(135deg, #7B2CBF 0%, #5A189A 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(123, 44, 191, 0.2);
        }

        .hero-content h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .hero-content p {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
        }

        .hero-stats {
            display: flex;
            gap: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            min-width: 120px;
            backdrop-filter: blur(10px);
        }

        .stat-card.active {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .stat-value {
            display: block;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.8;
        }

        /* MEAL PLAN SECTION */
        .meal-plan-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .plan-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
        }

        .nutritionist-remark {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin: 0 0 24px;
            padding: 18px 20px;
            border: 1px solid #cce8da;
            border-left: 4px solid #168a64;
            border-radius: 14px;
            background: linear-gradient(120deg, #f1fbf6, #fbfefc);
        }

        .nutritionist-remark-icon {
            display: grid;
            place-items: center;
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            color: #168a64;
            background: #ddf4e8;
            font-size: 20px;
        }

        .nutritionist-remark-label {
            display: block;
            margin-bottom: 5px;
            color: #168a64;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .nutritionist-remark p {
            margin: 0;
            color: #24483f;
            line-height: 1.6;
            white-space: pre-line;
        }

        .nutritionist-remark time {
            display: block;
            margin-top: 8px;
            color: #71817b;
            font-size: 12px;
        }

        .plan-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 15px 0;
        }

        .plan-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .plan-meta span {
            background: #f5f5f5;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #666;
        }

        /* WEEK DAYS NAVIGATION */
        .week-days-nav {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .day-tab {
            background: #f9f9f9;
            border: 2px solid transparent;
            padding: 15px 10px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
        }

        .day-tab:hover {
            background: #f0f0f0;
            border-color: #7B2CBF;
        }

        .day-tab.active {
            background: #7B2CBF;
            color: white;
            border-color: #7B2CBF;
        }

        .day-label {
            display: block;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .day-focus {
            display: block;
            font-size: 11px;
            opacity: 0.7;
        }

        /* DAILY MEALS */
        .day-meals {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .day-title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .day-title-section h3 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
        }

        .day-tag {
            background: #FFE5F1;
            color: #D81C7F;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* MEAL SLOT */
        .meal-slot {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 12px;
            border-left: 4px solid #7B2CBF;
        }

        .meal-time {
            font-weight: 700;
            color: #7B2CBF;
            font-size: 14px;
            min-width: 60px;
            text-align: center;
        }

        .meal-content {
            flex: 1;
        }

        /* MEAL ITEM */
        .meal-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .meal-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .meal-dish h4 {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 4px 0;
            color: #333;
        }

        .regional-name {
            font-size: 12px;
            color: #999;
            font-style: italic;
            margin: 2px 0;
        }

        .meal-portion {
            font-size: 12px;
            color: #666;
            margin: 4px 0 0 0;
        }

        .meal-nutrients {
            display: flex;
            gap: 20px;
        }

        .nutrient {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .n-value {
            font-size: 16px;
            font-weight: 700;
            color: #7B2CBF;
        }

        .n-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
        }

        /* PAIRING NOTES */
        .pairing-note {
            background: #FEF3E6;
            border-left: 3px solid #FF9900;
            padding: 12px 15px;
            border-radius: 6px;
            margin: 12px 0;
            display: flex;
            gap: 10px;
            font-size: 13px;
            color: #666;
        }

        .note-icon {
            flex-shrink: 0;
        }

        /* NUTRIENT GAPS SECTION */
        .nutrient-gaps-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .nutrient-gaps-section h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .gaps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .gap-card {
            padding: 20px;
            border-radius: 12px;
            background: #f9f9f9;
            border-left: 4px solid #ccc;
        }

        .gap-card.gap-met {
            border-left-color: #4CAF50;
            background: #F1F8E9;
        }

        .gap-card.gap-partial\ gap {
            border-left-color: #FF9800;
            background: #FFF3E0;
        }

        .gap-card.gap-significant\ gap {
            border-left-color: #FF5722;
            background: #FFEBEE;
        }

        .gap-card.gap-critical\ gap {
            border-left-color: #F44336;
            background: #FFEBEE;
        }

        .gap-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .gap-header h4 {
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }

        .gap-badge {
            background: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .gap-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .gap-info {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 700;
            color: #333;
        }

        .gap-progress {
            margin-top: 15px;
        }

        .progress-bar {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #7B2CBF, #D81C7F);
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        /* CLINICAL TIPS SECTION */
        .clinical-tips-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .clinical-tips-section h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .tip-card {
            background: linear-gradient(135deg, #F8F9FA 0%, #F0F0F0 100%);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #7B2CBF;
        }

        .tip-card h4 {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #333;
        }

        .tip-combination {
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .tip-combination strong {
            color: #7B2CBF;
            display: block;
            margin-bottom: 4px;
        }

        .tip-science {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        .tip-science strong {
            color: #333;
            display: block;
            margin-bottom: 4px;
        }

        /* NO PLAN CONTAINER */
        .no-plan-container {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .no-plan-icon {
            margin-bottom: 30px;
        }

        .no-plan-icon img {
            max-width: 150px;
            height: auto;
            opacity: 0.8;
        }

        .no-plan-container h2 {
            font-size: 28px;
            font-weight: 700;
            margin: 20px 0 10px 0;
        }

        .no-plan-container > p {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }

        /* PURCHASE PROMPT */
        .purchase-prompt {
            background: #F8F9FA;
            border: 2px dashed #7B2CBF;
            padding: 40px 30px;
            border-radius: 16px;
            margin-top: 30px;
        }

        .prompt-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .purchase-prompt h3 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #333;
        }

        .purchase-prompt > p {
            font-size: 15px;
            color: #666;
            margin-bottom: 25px;
        }

        .purchase-info {
            text-align: left;
            display: inline-block;
            margin-bottom: 30px;
            max-width: 350px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .check {
            background: #4CAF50;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ACTION BUTTONS */
        .plan-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #7B2CBF;
            color: white;
        }

        .btn-primary:hover {
            background: #5A189A;
            box-shadow: 0 4px 15px rgba(123, 44, 191, 0.3);
        }

        .btn-secondary {
            background: #E8E8E8;
            color: #333;
        }

        .btn-secondary:hover {
            background: #D0D0D0;
        }

        .btn-warning {
            background: #FF9800;
            color: white;
        }

        .btn-warning:hover {
            background: #F57C00;
        }

        .btn-main {
            background: linear-gradient(135deg, #7B2CBF, #D81C7F);
            color: white;
            padding: 15px 40px;
            font-size: 16px;
        }

        .btn-main:hover {
            box-shadow: 0 6px 20px rgba(123, 44, 191, 0.3);
        }

        .btn-lg {
            min-width: 250px;
        }

        .info-text {
            font-size: 13px;
            color: #666;
            margin-top: 15px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .diet-chart-hero {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .hero-stats {
                width: 100%;
                justify-content: center;
            }

            .week-days-nav {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            }

            .meal-slot {
                flex-direction: column;
            }

            .meal-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .gaps-grid {
                grid-template-columns: 1fr;
            }

            .plan-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>

    <script>
        // Day Tab Navigation
        document.querySelectorAll('.day-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const dayLower = this.dataset.dayLower;
                
                // Hide all days
                document.querySelectorAll('.day-meals').forEach(d => {
                    d.style.display = 'none';
                });
                
                // Remove active class
                document.querySelectorAll('.day-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Show selected day
                document.getElementById(`day-${dayLower}`).style.display = 'block';
                this.classList.add('active');
            });
        });

        // Download PDF
        document.getElementById('downloadPdf')?.addEventListener('click', function() {
            alert('PDF download functionality coming soon!');
            // Implement PDF generation here
        });

        // Share Plan
        document.getElementById('sharePlan')?.addEventListener('click', function() {
            const text = `Check out {{ $child->name }}'s personalized 7-day meal plan on NutriBuddy!`;
            if (navigator.share) {
                navigator.share({
                    title: 'NutriBuddy Meal Plan',
                    text: text,
                    url: window.location.href
                });
            } else {
                alert('Sharing coming soon!');
            }
        });

        // Regenerate Plan
        document.getElementById('updatePlan')?.addEventListener('click', function() {
            if (confirm('Generate a new personalized plan?')) {
                window.location.href = '{{ route("child.diet-chart.regenerate", $child->id) }}';
            }
        });

        // Generate New Plan
        document.getElementById('generateNewPlan')?.addEventListener('click', function() {
            window.location.href = '{{ route("frontend.nutrition.questionnaire.child", $child) }}';
        });
    </script>
@endsection
