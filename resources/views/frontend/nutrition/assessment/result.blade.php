@extends('layouts.user-panel')
@section('title', 'Assessment Result — NutriBuddy Kids')
@section('panel-page-class', 'panel-assessment')

@section('panel-content')
<div class="ud-main">
    {{-- ══ MOBILE TOPBAR ══ --}}
    <div class="inner-topbar" style="margin-bottom: 20px;">
        <button class="sidebar-toggle" onclick="toggleSidebar()" style="display:flex; align-items:center; justify-content:center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">Assessment Result</span>
        <div style="width:40px"></div>
    </div>

  <div class="page">

    @if(session('success'))
      <div class="asmnt-alert fade-in d1">✅ {{ session('success') }}</div>
    @endif

    {{-- RESULT HERO BANNER --}}
    <div class="asmnt-result-hero fade-in d1">
      <div class="asmnt-result-hero__left">
        <div class="asmnt-result-ring-wrap">
          <svg viewBox="0 0 120 120" width="140" height="140">
            <defs>
              <linearGradient id="resultRingGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="var(--pk)" />
                <stop offset="100%" stop-color="var(--pu)" />
              </linearGradient>
            </defs>
            <circle class="asmnt-ring-bg"   cx="60" cy="60" r="48" />
            <circle class="asmnt-ring-fill" cx="60" cy="60" r="48"
                    id="resultRingFill"
                    style="--pct: {{ $attempt->percentage }}" />
          </svg>
          <div class="asmnt-ring-inner">
            <div class="asmnt-ring-score" id="resultScoreNum">0</div>
            <div class="asmnt-ring-label">%</div>
          </div>
        </div>
      </div>

      <div class="asmnt-result-hero__right">
        <div class="asmnt-level-badge {{ $attempt->resultLevelClass() }}">
          {{ $attempt->resultLevelEmoji() }} {{ $attempt->result_level }}
        </div>
        <h2 class="asmnt-result-title">Health Assessment Complete!</h2>
        <p class="asmnt-result-sub">
          <strong>Overall Weighted Health Score: {{ number_format($attempt->percentage, 1) }}%</strong>
        </p>
        <p class="asmnt-result-date">
          Completed on {{ $attempt->completed_at?->format('d M Y, g:i A') }}
        </p>

        @if($previousAttempt)
          @php
            $diff = round($attempt->percentage - $previousAttempt->percentage, 1);
          @endphp
          <div class="asmnt-vs-prev {{ $diff >= 0 ? 'up' : 'down' }}">
            {{ $diff >= 0 ? '↑' : '↓' }}
            {{ abs($diff) }}% vs previous attempt
          </div>
        @endif

        <div class="asmnt-result-actions">
          <a href="{{ route('user.assessment.history') }}" class="asmnt-btn-outline">📋 History</a>
          @if($previousAttempt)
            <a href="{{ route('user.assessment.compare') }}" class="asmnt-btn-outline">⚖️ Compare</a>
          @endif
          <a href="{{ route('user.assessment.detail', $attempt) }}" class="asmnt-btn-outline">🔍 Full Detail</a>
          <a href="{{ route('user.assessment.index') }}" class="asmnt-btn-primary">🔄 Retake</a>
        </div>
      </div>
    </div>

    {{-- SECTION SCORES --}}
    <div class="asmnt-section-head fade-in d3">
      <h3>📊 Section Scores</h3>
      <p>See how your child performed in each health area.</p>
    </div>

    <div class="asmnt-section-grid fade-in d4">
      @foreach($attempt->sectionScores as $ss)
        @php
          $sectionIcons = [
            'Nutrition'        => '🥗',
            'Sleep'            => '😴',
            'Physical Activity'=> '🏃',
            'Mental Wellness'  => '🧠',
            'Hygiene & Habits' => '🧼',
          ];
          $icon = $sectionIcons[$ss->section] ?? '📋';
          $lvl  = match(true) {
            $ss->percentage >= 80 => 'excellent',
            $ss->percentage >= 60 => 'good',
            $ss->percentage >= 40 => 'average',
            default               => 'poor',
          };
        @endphp
        <div class="asmnt-section-card">
          <div class="asmnt-section-card__top">
            <div class="asmnt-section-icon">{{ $icon }}</div>
            <div class="asmnt-section-info">
              <div class="asmnt-section-name">{{ $ss->section }}</div>
              <div class="asmnt-section-score-txt">{{ $ss->score }} / {{ $ss->max_score }} pts</div>
            </div>
            <div class="asmnt-section-pct lvl-{{ $lvl }}">{{ number_format($ss->percentage, 0) }}%</div>
          </div>
          <div class="asmnt-bar-track">
            <div class="asmnt-bar-fill lvl-{{ $lvl }}" style="width:{{ $ss->percentage }}%"></div>
          </div>
          <div class="asmnt-section-level-tag lvl-tag-{{ $lvl }}">
            @if($lvl === 'excellent') 🌟 Excellent
            @elseif($lvl === 'good') 👍 Good
            @elseif($lvl === 'average') ⚠️ Average
            @else 🔴 Needs Improvement
            @endif
          </div>
        </div>
      @endforeach
    </div>

    {{-- CTA --}}
    <div class="asmnt-cta-row fade-in d5">
      <a href="{{ route('user.assessment.detail', $attempt) }}" class="asmnt-btn-primary">
        🔍 See Question-by-Question Breakdown
      </a>
    </div>

  </div>{{-- /page --}}
</div>{{-- /ud-main --}}

@push('scripts')
<script>
(function () {
  // Animated score counter.
  const el     = document.getElementById('resultScoreNum');
  const target = {{ $attempt->percentage }};
  const dur    = 1400;
  const step   = target / (dur / 16);
  let cur      = 0;
  function tick() {
    cur = Math.min(cur + step, target);
    el.textContent = Math.round(cur);
    if (cur < target) requestAnimationFrame(tick);
  }
  setTimeout(() => requestAnimationFrame(tick), 300);

  // Animate ring.
  const ring   = document.getElementById('resultRingFill');
  const pct    = {{ $attempt->percentage }};
  const circ   = 2 * Math.PI * 48;
  const dash   = circ * (1 - pct / 100);
  setTimeout(() => {
    ring.style.transition       = 'stroke-dashoffset 1.4s cubic-bezier(.4,0,.2,1)';
    ring.style.strokeDashoffset = dash;
  }, 300);
})();
</script>
@endpush
@endsection
