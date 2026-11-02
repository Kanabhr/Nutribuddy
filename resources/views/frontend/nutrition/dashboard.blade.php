@extends('layouts.user-panel')
@section('title', 'Nutrition — NutriBuddy')
@section('panel-page-class', 'panel-nutrition')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nutrition.css') }}?v={{ time() }}">
@endpush

@section('panel-content')
<div class="ud-main nutri-flow-bg">
    <div class="page">

        <!-- STEP 1: NEEDS & GENERATE -->
        <div id="flow-step-1" class="flow-step active">
            @php
                /* ============================================================
                   HEIGHT & WEIGHT — fetch from latest assessment, not child
                   ============================================================ */

                // 1. Prefer the latest assessment's CURRENT measurements
                $measureHeight = null;
                $measureWeight = null;

                if (isset($assessment)) {
                    $measureHeight = $assessment->current_height_cm
                                  ?? $assessment->height_cm
                                  ?? null;
                    $measureWeight = $assessment->current_weight_kg
                                  ?? $assessment->weight_kg
                                  ?? null;
                }

                // 2. Fallback to the child record
                if (!$measureHeight && isset($child->height)) {
                    $measureHeight = $child->height;
                }
                if (!$measureWeight && isset($child->weight)) {
                    $measureWeight = $child->weight;
                }

                // 3. Fallback to latestPlan / childAssessment if provided
                if (!$measureHeight && isset($latestPlan) && $latestPlan) {
                    $measureHeight = $latestPlan->current_height_cm ?? null;
                    $measureWeight = $latestPlan->current_weight_kg ?? null;
                }

                // 4. Final fallback — null
                $currentHeight = $measureHeight;
                $currentWeight = $measureWeight;

                /* ============================================================
                   AGE + GENDER aware healthy range calculation
                   ============================================================ */

                // Get child's age in years (prefer age column, fallback to DOB)
                $childAge = null;
                if (isset($child)) {
                    if (!empty($child->age)) {
                        // age might be a range string like "5-8" or an int
                        if (is_numeric($child->age)) {
                            $childAge = (int) $child->age;
                        } elseif (preg_match('/(\d+)/', $child->age, $m)) {
                            $childAge = (int) $m[1];
                        }
                    } elseif (!empty($child->dob)) {
                        $childAge = \Carbon\Carbon::parse($child->dob)->age;
                    }
                }

                // Gender
                $childGender = strtolower($child->gender ?? 'unisex');

                /* ============================================================
                   WHO-style healthy ranges (approximate but sensible)
                   Values are [min_cm, max_cm] and [min_kg, max_kg]
                   ============================================================ */
                $ageBands = [
                    // [age_min, age_max, height_cm[min,max], weight_kg[min,max]]
                    [0,  2,  [45,  90],  [3,   14]],
                    [3,  5,  [90,  112], [12,  22]],
                    [6,  8,  [112, 130], [18,  32]],
                    [9,  11, [130, 148], [26,  46]],
                    [12, 14, [145, 168], [36,  62]],
                    [15, 18, [155, 178], [45,  75]],
                ];

                $heightMin = 100; $heightMax = 150;
                $weightMin = 15;  $weightMax = 50;

                if ($childAge !== null) {
                    foreach ($ageBands as $band) {
                        if ($childAge >= $band[0] && $childAge <= $band[1]) {
                            $heightMin = $band[2][0];
                            $heightMax = $band[2][1];
                            $weightMin = $band[3][0];
                            $weightMax = $band[3][1];
                            break;
                        }
                    }
                }

                /* ============================================================
                   Compute marker percentages safely
                   ============================================================ */
                $heightRange = max(1, $heightMax - $heightMin);
                $weightRange = max(1, $weightMax - $weightMin);

                $currHpct = 50; // default center
                $currWpct = 50;

                if ($currentHeight !== null && $currentHeight > 0) {
                    $currHpct = (($currentHeight - $heightMin) / $heightRange) * 100;
                    $currHpct = max(3, min(97, $currHpct)); // clamp
                }

                if ($currentWeight !== null && $currentWeight > 0) {
                    $currWpct = (($currentWeight - $weightMin) / $weightRange) * 100;
                    $currWpct = max(3, min(97, $currWpct));
                }

                /* ============================================================
                   Status badge logic
                   ============================================================ */
                $heightStatus = 'On track ✓';
                $heightStatusClass = 'ok';

                if ($currentHeight !== null && $currentHeight > 0) {
                    if ($currentHeight < $heightMin) {
                        $heightStatus = 'Below range';
                        $heightStatusClass = 'low';
                    } elseif ($currentHeight > $heightMax) {
                        $heightStatus = 'Above range';
                        $heightStatusClass = 'high';
                    }
                }

                $weightStatus = 'On track ✓';
                $weightStatusClass = 'ok';

                if ($currentWeight !== null && $currentWeight > 0) {
                    if ($currentWeight < $weightMin) {
                        $weightStatus = 'Below range';
                        $weightStatusClass = 'low';
                    } elseif ($currentWeight > $weightMax) {
                        $weightStatus = 'Above range';
                        $weightStatusClass = 'high';
                    }
                }

                /* ============================================================
                   Target calories
                   ============================================================ */
                $targetCals = isset($todayTarget) ? ($todayTarget['energy_kcal'] ?? 1700) : 1700;

                /* ============================================================
                   Display labels — show unit if value exists
                   ============================================================ */
                $heightDisplay = $currentHeight ? $currentHeight . ' cm' : '—';
                $weightDisplay = $currentWeight ? $currentWeight . ' kg' : '—';

                $childName = $child->name ?? 'Your child';
            @endphp

            <div class="nf-card">
                <div class="nf-card-title">Here's what {{ $childName }} needs — and where they are</div>
                <div class="nf-card-sub">Worked out from current measurements</div>

                <div class="nf-section-title">Healthy targets for their age</div>

                <!-- HEIGHT -->
                <div class="nf-bar-card">
                    <div class="nf-bar-header">
                        <span class="nf-bar-label">📏 Height</span>
                        <span class="nf-badge nf-badge-{{ $heightStatusClass }}">{{ $heightStatus }}</span>
                    </div>
                    <div class="nf-bar-wrap">
                        <div class="nf-bar-track">
                            <div class="nf-bar-fill" style="width: {{ $currHpct }}%;"></div>
                            <div class="nf-bar-marker" style="left: {{ $currHpct }}%;"></div>
                        </div>
                        <div class="nf-bar-labels">
                            <span>{{ $heightMin }} cm</span>
                            <span class="nf-bar-current">{{ $childName }}: {{ $heightDisplay }}</span>
                            <span>{{ $heightMax }} cm</span>
                        </div>
                    </div>
                </div>

                <!-- WEIGHT -->
                <div class="nf-bar-card">
                    <div class="nf-bar-header">
                        <span class="nf-bar-label">⚖️ Weight</span>
                        <span class="nf-badge nf-badge-{{ $weightStatusClass }}">{{ $weightStatus }}</span>
                    </div>
                    <div class="nf-bar-wrap">
                        <div class="nf-bar-track">
                            <div class="nf-bar-fill" style="width: {{ $currWpct }}%;"></div>
                            <div class="nf-bar-marker" style="left: {{ $currWpct }}%;"></div>
                        </div>
                        <div class="nf-bar-labels">
                            <span>{{ $weightMin }} kg</span>
                            <span class="nf-bar-current">{{ $childName }}: {{ $weightDisplay }}</span>
                            <span>{{ $weightMax }} kg</span>
                        </div>
                    </div>
                </div>

                <button id="btn-generate-plan" class="nf-btn-primary">Generate a diet chart</button>
            </div>
        </div>

        <!-- STEP 2: LOADING SCREEN -->
        <div id="flow-step-2" class="flow-step">
            <div class="nf-loader-screen">
                <div class="nf-spinner"></div>
                <div class="nf-loader-title">Building {{ $childName }}'s<br>meal plan...</div>
                <div class="nf-check-list">
                    <div class="nf-check-item" id="load-step-1">
                        <div class="nf-check-icon">✓</div>
                        <div>Matching meals to their age</div>
                    </div>
                    <div class="nf-check-item" id="load-step-2">
                        <div class="nf-check-icon">✓</div>
                        <div>Applying your kitchen preferences</div>
                    </div>
                    <div class="nf-check-item" id="load-step-3">
                        <div class="nf-check-icon">✓</div>
                        <div>Removing foods they can't eat</div>
                    </div>
                    <div class="nf-check-item" id="load-step-4">
                        <div class="nf-check-icon">✓</div>
                        <div>Balancing {{ number_format($targetCals) }} kcal per day</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: WAITING FOR APPROVAL -->
        <div id="flow-step-3" class="flow-step">
            <div class="nf-approval-wrap">
                <div class="nf-approval-icon">⏳</div>
                <div class="nf-approval-title">Your plan is being reviewed</div>
                <div class="nf-approval-sub">Our nutritionist is personalising {{ $childName }}'s 7-day meal plan. You'll be notified once it's approved and ready.</div>

                <div class="nf-approval-steps">
                    <div class="nf-astep done">
                        <div class="nf-astep-dot">✓</div>
                        <div class="nf-astep-label">Plan generated</div>
                    </div>
                    <div class="nf-astep-line"></div>
                    <div class="nf-astep active">
                        <div class="nf-astep-dot">2</div>
                        <div class="nf-astep-label">Nutritionist review</div>
                    </div>
                    <div class="nf-astep-line"></div>
                    <div class="nf-astep">
                        <div class="nf-astep-dot">3</div>
                        <div class="nf-astep-label">Start eating!</div>
                    </div>
                </div>

                <a href="{{ route('nutrition.backup') }}" class="nf-btn-primary" style="display:block; text-align:center; text-decoration:none; margin-top: 30px;">
                    View Plan
                </a>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btn-generate-plan');
        const s1 = document.getElementById('flow-step-1');
        const s2 = document.getElementById('flow-step-2');
        const s3 = document.getElementById('flow-step-3');

        if (btn) {
            btn.addEventListener('click', function () {
                s1.classList.remove('active');
                s2.classList.add('active');
                window.scrollTo(0, 0);

                setTimeout(() => document.getElementById('load-step-1').classList.add('active'), 500);
                setTimeout(() => document.getElementById('load-step-2').classList.add('active'), 1500);
                setTimeout(() => document.getElementById('load-step-3').classList.add('active'), 2500);
                setTimeout(() => document.getElementById('load-step-4').classList.add('active'), 3500);

                setTimeout(() => {
                    s2.classList.remove('active');
                    s3.classList.add('active');
                    window.scrollTo(0, 0);
                }, 5000);
            });
        }
    });
</script>
@endpush
@endsection