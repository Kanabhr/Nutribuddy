@extends('layouts.user-panel')
@section('title', 'Health Scores')



@push('styles')
<link rel="stylesheet" href="{{ asset('css/child-profile.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush


@section('panel-content')

@php
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $slotsPerDay = $dietPlan?->meals->count() ? max(1, (int) ($dietPlan->meals->count() / 7)) : 5;
    $mealsByDay = $dietPlan?->meals->sortBy('sort_order')->values()->chunk($slotsPerDay) ?? collect();
@endphp

<!-- NAV -->
<nav class="nav">
  <!-- <a href="#" class="nav-logo">
    <div class="nav-logo-dot">🌿</div>
    NutriBuddy <span class="nav-logo-sub">KIDS</span>
  </a> -->
  <div class="nav-r">
    <button class="btn-nav-back" id="navBack" hidden onclick="backToProfile()">← Back to Profile</button>
  </div>
</nav>

<!-- ═══ VIEW: PROFILE ═══ -->
@if($child)
<div class="view on" id="vProfile">
<main class="shell">

  <div class="a1">
    <div class="ph">
      <h1 class="ph-title" id="profTitle">{{ $child->name }}'s Profile</h1>
    </div>
    <p class="ph-meta" id="profMeta">
      {{ \Carbon\Carbon::parse($child->dob)->age }} years <span class="ph-meta-dot">·</span>
      {{ ucfirst($child->sex) }} <span class="ph-meta-dot">·</span>
      {{ $assessment->diet_type ?? 'Vegetarian' }} <span class="ph-meta-dot">·</span>
      {{ $assessment->region ?? 'India' }}
    </p>
  </div>

  <!-- METRICS -->
  <div class="tiles a2">
    <div class="tile"><div class="tv cg" id="scoreVal">{{ $assessment->health_score ?? 0 }}</div><div class="tl">Health Score</div></div>
    <div class="tile"><div class="tv co" id="weightTile">{{ $assessment->current_weight_kg ?? '0' }} <span class="tu">kg</span></div><div class="tl">Weight</div></div>
    <div class="tile"><div class="tv cp" id="heightTile">{{ $assessment->current_height_cm ?? '0' }} <span class="tu">cm</span></div><div class="tl">Height</div></div>
    <div class="tile"><div class="tv cr">{{ $assessment->times_sick_last_quarter ?? '0' }}<span class="tu">×</span></div><div class="tl">Sick This Qtr</div></div>
  </div>

  <div class="tab-bar a3">
    <button class="tab-btn on" onclick="switchTab(this,'tProfile')">Profile</button>
  </div>

  <!-- Profile tab -->
  <div id="tProfile" class="tp on a4">
    <div class="pcols">
      <div class="ic">
        @if($subscription)
          <div class="ir"><span class="ik">Current plan</span><span class="iv gr">{{ $subscription->subscriptionPlan?->name ?? $subscription->model }}</span></div>
          <div class="ir"><span class="ik">Plan invoice</span><a class="iv gr" href="{{ route('diet-subscriptions.invoice-download', $subscription) }}">Download PDF</a></div>
        @endif
        <div class="ir"><span class="ik">Age phase</span><span class="iv" id="ptPhase">Phase {{ \Carbon\Carbon::parse($child->dob)->age <= 6 ? '1' : (\Carbon\Carbon::parse($child->dob)->age <= 9 ? '2' : '3') }}</span></div>
        <div class="ir"><span class="ik">Diet type</span><span class="iv" id="ptDiet">{{ $assessment->diet_type ?? 'Vegetarian' }}</span></div>
        <div class="ir"><span class="ik">Picky level</span><span class="iv" id="ptPicky">{{ $assessment->picky_level ?? 'Slightly picky' }}</span></div>
        <div class="ir"><span class="ik">Allergies</span><span class="iv" id="ptAllergies">{{ $assessment->allergies ? implode(', ', (array) $assessment->allergies) : 'None' }}</span></div>
        <div class="ir"><span class="ik">Activity</span><span class="iv" id="ptActivity">{{ $assessment->activity_level ?? 'Moderately active' }}</span></div>
        <div class="ir"><span class="ik">Region</span><span class="iv" id="ptRegion">{{ $assessment->region ?? 'India' }}</span></div>
        <div class="ir"><span class="ik">Current season</span><span class="iv">{{ $assessment->current_season ?? 'Year-round' }}</span></div>
        <div class="ir"><span class="ik">Plan</span><span class="iv gr" id="ptPlan">{{ ucfirst($assessment->model_level ?? 'champion') }} ✓</span></div>
      </div>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="gap-card">
          <div class="gap-head">Detected Nutrient Gaps</div>
          @php
            $flags = $assessment?->clinicalFlags()->orderedByPriority()->get() ?? collect([]);
          @endphp
          @if($flags->count() > 0)
            @foreach($flags as $flag)
              <div class="gap-row">
                <span class="gn">{{ $flag->flag_name }}</span>
                <span class="gb {{ $flag->priority === 'high' ? 'crit' : ($flag->priority === 'medium' ? 'gap' : ($flag->priority === 'low' ? 'part' : 'met')) }}">
                  @if($flag->priority === 'high')
                    Critical
                  @elseif($flag->priority === 'medium')
                    Gap
                  @elseif($flag->priority === 'low')
                    Partial
                  @else
                    Met ✓
                  @endif
                </span>
              </div>
            @endforeach
          @else
            <div class="gap-row"><span class="gn">Vitamin D</span><span class="gb crit">Critical</span></div>
            <div class="gap-row"><span class="gn">Omega-3</span><span class="gb gap">Gap</span></div>
            <div class="gap-row"><span class="gn">Iron</span><span class="gb gap">Gap</span></div>
            <div class="gap-row"><span class="gn">Calcium</span><span class="gb part">Partial</span></div>
            <div class="gap-row"><span class="gn">Vitamin C</span><span class="gb met">Met ✓</span></div>
          @endif
        </div>
      </div>
    </div>
  </div>





</main>
</div>
@endif

<!-- ═══ VIEW: EDIT/ONBOARDING ═══ -->
<div class="view @if(!$child) on @endif" id="vEdit">
<main class="ob-shell">

  <!-- STEP 1 -->
  <div class="os on" id="s1">
    <div class="osb">
      <span class="ob-badge">Step 1 of 5</span>
      <div class="ob-lines"><div class="ol active"></div><div class="ol"></div><div class="ol"></div><div class="ol"></div><div class="ol"></div></div>
    </div>
    <h1 class="oh1">Tell us about your child</h1>
    <p class="osub">We'll personalise everything to their exact profile</p>
    <div class="sec-err" id="e1">⚠️ Please fill in all required fields before continuing.</div>

    <div class="gr-row">
      <button class="gbtn sel" data-g="boy" onclick="selG(this)"><span>👦</span><span class="gl">Boy</span></button>
      <button class="gbtn" data-g="girl" onclick="selG(this)"><span>👧</span><span class="gl">Girl</span></button>
      <button class="gbtn" data-g="other" onclick="selG(this)"><span>🧒</span><span class="gl">Other</span></button>
    </div>

    <div class="ff">
      <label class="fl-label">Child's Name <span class="req">*</span></label>
      <input class="nb-i" id="oName" type="text" placeholder="e.g. Arjun" value="Arjun" oninput="clrE(this,'eName')"/>
      <div class="emsg" id="eName">⚠ Name is required.</div>
    </div>

    <div class="fr">
      <div class="fc2">
        <label class="fl-label">Age <span class="req">*</span></label>
        <div class="sw"><select class="nb-s" id="oAge" onchange="autoPhase();clrE(this,'eAge')">
          <option value="">Select age</option>
          <option value="4">4 years</option><option value="5" selected>5 years</option>
          <option value="6">6 years</option><option value="7">7 years</option>
          <option value="8">8 years</option><option value="9">9 years</option>
          <option value="10">10 years</option><option value="11">11 years</option>
          <option value="12">12 years</option><option value="13">13 years</option>
          <option value="14">14 years</option><option value="15">15 years</option>
          <option value="16">16 years</option><option value="17">17 years</option>
        </select></div>
        <div class="emsg" id="eAge">⚠ Please select an age.</div>
      </div>
      <div class="fc2">
        <label class="fl-label">Birth Month</label>
        <div class="sw"><select class="nb-s" id="oMonth">
          <option>January</option><option>February</option><option>March</option>
          <option>April</option><option>May</option><option>June</option>
          <option>July</option><option>August</option><option>September</option>
          <option>October</option><option>November</option><option selected>December</option>
        </select></div>
      </div>
    </div>

    <div class="fr">
      <div class="fc2">
        <label class="fl-label">Height (cm) <span class="req">*</span></label>
        <input class="nb-i" id="oHeight" type="number" placeholder="106" value="106" min="50" max="220" oninput="clrE(this,'eHeight')"/>
        <div class="emsg" id="eHeight">⚠ Enter valid height (50–220 cm).</div>
      </div>
      <div class="fc2">
        <label class="fl-label">Weight (kg) <span class="req">*</span></label>
        <input class="nb-i" id="oWeight" type="number" placeholder="18" value="18" min="5" max="150" oninput="clrE(this,'eWeight')"/>
        <div class="emsg" id="eWeight">⚠ Enter valid weight (5–150 kg).</div>
      </div>
    </div>

    <div class="fr">
      <div class="fc2">
        <label class="fl-label">City</label>
        <input class="nb-i" id="oCity" type="text" placeholder="Ahmedabad" value="Ahmedabad"/>
      </div>
      <div class="fc2">
        <label class="fl-label">Region <span class="req">*</span></label>
        <div class="sw"><select class="nb-s" id="oRegion" onchange="clrE(this,'eRegion')">
          <option value="">Select region</option>
          <option selected>Gujarat / West India</option>
          <option>Maharashtra / West India</option>
          <option>Punjab / North India</option>
          <option>Tamil Nadu / South India</option>
          <option>Karnataka / South India</option>
          <option>West Bengal / East India</option>
          <option>Rajasthan / North India</option>
          <option>Uttar Pradesh / North India</option>
          <option>Madhya Pradesh / Central India</option>
        </select></div>
        <div class="emsg" id="eRegion">⚠ Please select a region.</div>
      </div>
    </div>

    <div class="ppr">
      <button class="pp sel" data-p="1" onclick="selP(this)">Phase 1<span>4–6 yrs</span></button>
      <button class="pp" data-p="2" onclick="selP(this)">Phase 2<span>7–9 yrs</span></button>
      <button class="pp" data-p="3" onclick="selP(this)">Phase 3<span>10–12 yrs</span></button>
      <button class="pp" data-p="4" onclick="selP(this)">Phase 4<span>13–15 yrs</span></button>
      <button class="pp" data-p="5" onclick="selP(this)">Phase 5<span>16–17 yrs</span></button>
    </div>
    <div class="pnote" id="pNote"><strong>Phase 1 — Early childhood (4–6 yrs)</strong><br>Calcium bone foundation · Iron for brain · Probiotics for gut immunity · Soft textures, mild flavours</div>

    <div class="step-actions">
      <button class="btn-co" onclick="goTo(2)">Continue <span class="arr">→</span></button>
      <button class="btn-secondary" type="button" onclick="backToProfile()">← Back to Profile</button>
    </div>
  </div>

  <!-- STEP 2 -->
  <div class="os" id="s2">
    <div class="osb">
      <button class="btn-back" onclick="goTo(1)">← Back</button>
      <span class="ob-badge">Step 2 of 5</span>
      <div class="ob-lines" style="flex:1"><div class="ol done"></div><div class="ol active"></div><div class="ol"></div><div class="ol"></div><div class="ol"></div></div>
    </div>
    <h1 class="oh1">What worries you most?</h1>
    <p class="osub">Select all that apply</p>
    <div class="sec-err" id="e2">⚠️ Please select at least one concern to continue.</div>
    <div class="ccl">
      <div class="cc sel" onclick="togCC(this)"><span class="ci">🤧</span><div><div class="cn">Gets sick too often</div><div class="cde">Frequent colds, cough, fever — low immunity</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">🧠</span><div><div class="cn">Focus &amp; memory issues</div><div class="cde">Difficulty concentrating, poor academics</div></div></div>
      <div class="cc sel" onclick="togCC(this)"><span class="ci">📏</span><div><div class="cn">Height / weight concerns</div><div class="cde">Not growing as expected for age</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">🫁</span><div><div class="cn">Digestive issues</div><div class="cde">Constipation, bloating, irregular stomach</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">⚡</span><div><div class="cn">Low energy / always tired</div><div class="cde">Fatigue, lacks enthusiasm</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">✨</span><div><div class="cn">Skin &amp; hair issues</div><div class="cde">Dry skin, rashes, eczema</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">😴</span><div><div class="cn">Sleep problems</div><div class="cde">Trouble falling or staying asleep</div></div></div>
    </div>
    <div class="step-actions">
      <button class="btn-co" onclick="goTo(3)">Continue <span class="arr">→</span></button>
      <button class="btn-secondary" type="button" onclick="backToProfile()">← Back to Profile</button>
    </div>
  </div>

  <!-- STEP 3 -->
  <div class="os" id="s3">
    <div class="osb">
      <button class="btn-back" onclick="goTo(2)">← Back</button>
      <span class="ob-badge">Step 3 of 5</span>
      <div class="ob-lines" style="flex:1"><div class="ol done"></div><div class="ol done"></div><div class="ol active"></div><div class="ol"></div><div class="ol"></div></div>
    </div>
    <h1 class="oh1">What does <span id="nd3">Arjun</span> eat?</h1>
    <div class="sec-err" id="e3">⚠️ Please select a diet type and picky level.</div>
    <span class="sec-l">Diet Type <span class="req">*</span></span>
    <div class="dg">
      <div class="dc sel" onclick="selO(this,'.dc','sel')"><span>🌱</span><span class="dn2">Pure vegetarian</span></div>
      <div class="dc" onclick="selO(this,'.dc','sel')"><span>🥚</span><span class="dn2">Veg + eggs</span></div>
      <div class="dc" onclick="selO(this,'.dc','sel')"><span>🍗</span><span class="dn2">Non-vegetarian</span></div>
      <div class="dc" onclick="selO(this,'.dc','sel')"><span>🙏</span><span class="dn2">Jain diet</span></div>
    </div>
    <span class="sec-l">Picky Level <span class="req">*</span></span>
    <div class="pl">
      <div class="pc" onclick="selO(this,'.pc','sel')"><span>😋</span><span class="pn">Eats most things</span></div>
      <div class="pc sel" onclick="selO(this,'.pc','sel')"><span>🤔</span><span class="pn">Slightly picky</span></div>
      <div class="pc" onclick="selO(this,'.pc','sel')"><span>😤</span><span class="pn">Very picky eater</span></div>
    </div>
    <span class="sec-l">Allergies</span>
    <div class="pt">
      <button class="ptl sel" onclick="togP(this)">None</button>
      <button class="ptl" onclick="togP(this)">Dairy</button>
      <button class="ptl" onclick="togP(this)">Gluten</button>
      <button class="ptl" onclick="togP(this)">Nuts</button>
      <button class="ptl" onclick="togP(this)">Soy</button>
      <button class="ptl" onclick="togP(this)">Eggs</button>
    </div>
    <span class="sec-l">Home Cooking Frequency</span>
    <div class="ff"><div class="sw"><select class="nb-s" id="oCook">
      <option selected>Always (100%)</option>
      <option>Almost always (90%+)</option>
      <option>Mostly (70–89%)</option>
      <option>Sometimes (50–69%)</option>
      <option>Rarely (less than 50%)</option>
    </select></div></div>
    <div class="step-actions">
      <button class="btn-co" onclick="goTo(4)">Continue <span class="arr">→</span></button>
      <button class="btn-secondary" type="button" onclick="backToProfile()">← Back to Profile</button>
    </div>
  </div>

  <!-- STEP 4 -->
  <div class="os" id="s4">
    <div class="osb">
      <button class="btn-back" onclick="goTo(3)">← Back</button>
      <span class="ob-badge">Step 4 of 5</span>
      <div class="ob-lines" style="flex:1"><div class="ol done"></div><div class="ol done"></div><div class="ol done"></div><div class="ol active"></div><div class="ol"></div></div>
    </div>
    <h1 class="oh1"><span id="nd4">Arjun</span>'s Daily Lifestyle</h1>
    <div class="sec-err" id="e4">⚠️ Please select an activity level.</div>
    <span class="sec-l">Activity Level <span class="req">*</span></span>
    <div class="ll">
      <div class="lc" onclick="selO(this,'.lc','sel')"><span>🏃</span><div><div class="lcn">Very active</div><div class="lcd">Outdoor sports, runs around a lot</div></div></div>
      <div class="lc sel" onclick="selO(this,'.lc','sel')"><span>🚶</span><div><div class="lcn">Moderately active</div><div class="lcd">Some play, PE at school</div></div></div>
      <div class="lc" onclick="selO(this,'.lc','sel')"><span>📱</span><div><div class="lcn">Mostly sedentary</div><div class="lcd">Screen time, indoor activities</div></div></div>
    </div>
    <span class="sec-l">Current Supplements</span>
    <div class="pt" id="supplRow">
      <button class="ptl sel" onclick="togP(this)">None</button>
      <button class="ptl" onclick="togP(this)">Vitamin D</button>
      <button class="ptl" onclick="togP(this)">Iron</button>
      <button class="ptl" onclick="togP(this)">Calcium</button>
      <button class="ptl" onclick="togP(this)">Chyawanprash</button>
    </div>
    <span class="sec-l">Upcoming Events</span>
    <div class="pt">
      <button class="ptl sel" onclick="togP(this)">Nothing specific</button>
      <button class="ptl" onclick="togP(this)">Exams in 2–3 months</button>
      <button class="ptl" onclick="togP(this)">Sports season</button>
      <button class="ptl" onclick="togP(this)">Illness recovery</button>
    </div>
    <div class="fr" style="margin-top:4px">
      <div class="fc2"><label class="fl-label">School Timing</label><div class="sw"><select class="nb-s"><option>Regular (9–3pm)</option><option>Morning (7–1pm)</option><option>Afternoon (12–6pm)</option><option>Home schooled</option></select></div></div>
      <div class="fc2"><label class="fl-label">Sun Exposure</label><div class="sw"><select class="nb-s"><option>Occasional outdoor</option><option>Daily 30+ min outdoor</option><option>Mostly indoors</option><option>School outdoor only</option></select></div></div>
    </div>
    <div class="step-actions">
      <button class="btn-co" onclick="goTo(5)" style="margin-top:16px">Continue <span class="arr">→</span></button>
      <button class="btn-secondary" type="button" onclick="backToProfile()">← Back to Profile</button>
    </div>
  </div>

  <!-- STEP 5 -->
  <div class="os" id="s5">
    <div class="osb">
      <button class="btn-back" onclick="goTo(4)">← Back</button>
      <span class="ob-badge">Step 5 of 5</span>
      <div class="ob-lines" style="flex:1"><div class="ol done"></div><div class="ol done"></div><div class="ol done"></div><div class="ol done"></div><div class="ol active"></div></div>
    </div>
    <h1 class="oh1">3 months from now…</h1>
    <p class="osub">What does success look like for <span id="nd5">Arjun</span>?</p>
    <div class="sec-err" id="e5">⚠️ Please select at least one goal.</div>
    <div class="gcl">
      <div class="gc sel" onclick="togG(this)"><span class="gci">🛡️</span><div><div class="gcn">Falls sick less often</div><div class="gcd">Build strong lasting immunity</div></div></div>
      <div class="gc" onclick="togG(this)"><span class="gci">🧠</span><div><div class="gcn">Better focus and school performance</div><div class="gcd">Sharper memory, calm sustained energy</div></div></div>
      <div class="gc" onclick="togG(this)"><span class="gci">📏</span><div><div class="gcn">Healthy weight gain and growth</div><div class="gcd">Reach target height, stronger bones</div></div></div>
      <div class="gc" onclick="togG(this)"><span class="gci">⚡</span><div><div class="gcn">More energy and stamina</div><div class="gcd">Active, enthusiastic, less tired</div></div></div>
      <div class="gc" onclick="togG(this)"><span class="gci">🌿</span><div><div class="gcn">Balanced overall nutrition</div><div class="gcd">Fill all gaps, build lifelong food foundation</div></div></div>
    </div>
    <div class="gnote">
      <div class="gnote-h">🌿 What NutriBuddy will generate</div>
      <div class="gnote-b">A 7-day meal plan tailored to <span id="nd5b">Arjun</span>'s region, season, age, and concerns — with Ayurvedic wisdom, step-by-step recipes, supplement schedule, and expert nutritionist chat.</div>
    </div>
    <div class="step-actions">
      <button class="btn-co or" id="genBtn" onclick="goTo(6)">Generate <span id="nd5c">Arjun</span>'s Plan <span class="arr">→</span></button>
      <button class="btn-secondary" type="button" onclick="backToProfile()">← Back to Profile</button>
    </div>
  </div>

  <!-- STEP 6 -->
  <div class="os" id="s6">
    <div class="ph2">
      <div class="ph2-em">🎉</div>
      <div class="ph2-t"><span id="nd6">Arjun</span>'s plan is ready!</div>
      <div class="ph2-s">3 concerns detected — immunity, growth &amp; focus mapped to Ayurvedic foods</div>
      <div class="ph2-tags">
        <span class="ph2-tag">🛡️ Immunity</span><span class="ph2-tag">📏 Growth</span>
        <span class="ph2-tag">🌿 Ayurvedic</span><span class="ph2-tag">💬 Expert Chat</span>
      </div>
    </div>
    <div class="rda-row">
      <div class="rda-c"><div class="rda-v cg" id="rda1">0%</div><div class="rda-l">Immunity RDA</div></div>
      <div class="rda-c"><div class="rda-v co" id="rda2">0%</div><div class="rda-l">Growth RDA</div></div>
      <div class="rda-c"><div class="rda-v cp" id="rda3">0%</div><div class="rda-l">Brain RDA</div></div>
    </div>
    <div class="plan-unlock">Unlock Your Full Plan</div>
    <div class="pgrid">
      <div class="pcard">
        <div class="pname">Seedling</div>
        <div class="pprice">₹199<span class="pprice-sm"> /mo</span></div>
        <ul class="pfeat"><li>7-day meal plan</li><li>50+ recipes</li><li>Tiffin planner</li><li>Forum access</li></ul>
        <button class="btn-pl out" onclick="pickPlan(this,'Seedling')">Choose Seedling</button>
      </div>
      <div class="pcard best">
        <div class="pbest">Best for <span class="cname">Arjun</span></div>
        <div class="pname">Champion</div>
        <div class="pprice">₹499<span class="pprice-sm"> /mo</span></div>
        <ul class="pfeat"><li>Everything in Seedling</li><li>30 expert chat msgs/mo</li><li>Nutritionist Q&amp;A</li><li>Community leaderboard</li><li>Health score dashboard</li></ul>
        <button class="btn-pl sol" onclick="pickPlan(this,'Champion')">Start 7-day trial →</button>
      </div>
      <div class="pcard">
        <div class="pname">SuperKid</div>
        <div class="pprice">₹999<span class="pprice-sm"> /mo</span></div>
        <ul class="pfeat"><li>Unlimited expert chat</li><li>1:1 video nutritionist</li><li>Growth tracking</li><li>Leaderboard boosts</li><li>15% product discount</li></ul>
        <button class="btn-pl out" onclick="pickPlan(this,'SuperKid')">Choose SuperKid</button>
      </div>
    </div>
    <div class="pnote2">Cancel anytime · 7-day free trial · No card required</div>
    <button class="btn-sp" onclick="saveReturn()">✓ Save &amp; View Updated Profile →</button>
  </div>

</main>
</div>

<div class="toast" id="toast"></div>

@push('scripts')
<script src="{{ asset('js/child-profile.js') }}"></script>
@endpush
@endsection
