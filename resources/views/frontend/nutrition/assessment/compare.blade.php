@extends('layouts.user-panel')
@section('title', 'Compare Assessments — NutriBuddy Kids')
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
        <span class="it-title">Assessment Compare</span>
        <div style="width:40px"></div>
    </div>

  <div class="page">

    {{-- PAGE HEADER --}}
    <div class="page-header fade-in d1">
      <div class="page-header-left">
        <h1>⚖️ Assessment Comparison</h1>
        <p>See how your child's health score changed between the two most recent attempts.</p>
      </div>
      <div class="page-header-right">
        <a href="{{ route('user.assessment.history') }}" class="asmnt-btn-outline" style="text-decoration:none;">
          ← Back to History
        </a>
      </div>
    </div>

    {{-- OVERALL COMPARISON --}}
    @php
      $ov   = $comparison['overall'];
      $diff = $ov['diff_percentage'];
    @endphp
    <div class="asmnt-compare-overall fade-in d2">
      <div class="asmnt-compare-side prev">
        <div class="asmnt-cmp-label">Previous Attempt</div>
        <div class="asmnt-cmp-date">{{ $previous->completed_at?->format('d M Y') }}</div>
        <div class="asmnt-cmp-score">{{ $ov['previous_score'] }}</div>
        <div class="asmnt-cmp-pct">{{ number_format($ov['previous_percentage'], 1) }}%</div>
        <span class="asmnt-level-badge {{ $previous->resultLevelClass() }} sm">
          {{ $previous->resultLevelEmoji() }} {{ $previous->result_level }}
        </span>
      </div>

      <div class="asmnt-compare-vs">
        <div class="asmnt-vs-circle {{ $diff >= 0 ? 'up' : 'down' }}">
          {{ $diff >= 0 ? '↑' : '↓' }}
          {{ abs($diff) }}%
        </div>
        <div class="asmnt-vs-label">{{ $diff >= 0 ? 'Improved' : 'Declined' }}</div>
      </div>

      <div class="asmnt-compare-side latest">
        <div class="asmnt-cmp-label">Latest Attempt</div>
        <div class="asmnt-cmp-date">{{ $latest->completed_at?->format('d M Y') }}</div>
        <div class="asmnt-cmp-score">{{ $ov['current_score'] }}</div>
        <div class="asmnt-cmp-pct">{{ number_format($ov['current_percentage'], 1) }}%</div>
        <span class="asmnt-level-badge {{ $latest->resultLevelClass() }} sm">
          {{ $latest->resultLevelEmoji() }} {{ $latest->result_level }}
        </span>
      </div>
    </div>

    {{-- SECTION-BY-SECTION COMPARISON --}}
    <div class="asmnt-section-head fade-in d3">
      <h3>📊 Section Comparison</h3>
    </div>

    <div class="asmnt-compare-sections fade-in d4">
      @php
        $sectionIcons = [
          'Nutrition'         => '🥗',
          'Sleep'             => '😴',
          'Physical Activity' => '🏃',
          'Mental Wellness'   => '🧠',
          'Hygiene & Habits'  => '🧼',
        ];
      @endphp
      @foreach($comparison['sections'] as $section => $data)
        @php
          $icon    = $sectionIcons[$section] ?? '📋';
          $sdiff   = $data['diff'];
          $prevPct = $data['previous_percentage'];
          $curPct  = $data['current_percentage'];
        @endphp
        <div class="asmnt-compare-section-card">
          <div class="asmnt-cmp-sec-header">
            <span class="asmnt-cmp-sec-icon">{{ $icon }}</span>
            <span class="asmnt-cmp-sec-name">{{ $section }}</span>
            <span class="asmnt-cmp-sec-diff {{ $sdiff >= 0 ? 'up' : 'down' }}">
              {{ $sdiff >= 0 ? '+' : '' }}{{ $sdiff }} pts
            </span>
          </div>

          {{-- Previous bar --}}
          <div class="asmnt-cmp-bar-row">
            <span class="asmnt-cmp-bar-lbl">Previous</span>
            <div class="asmnt-cmp-bar-track">
              <div class="asmnt-cmp-bar-fill prev" style="width:{{ $prevPct }}%"></div>
            </div>
            <span class="asmnt-cmp-bar-val">{{ $data['previous_score'] }}/{{ $data['max_score'] }}</span>
          </div>

          {{-- Current bar --}}
          <div class="asmnt-cmp-bar-row">
            <span class="asmnt-cmp-bar-lbl">Latest</span>
            <div class="asmnt-cmp-bar-track">
              <div class="asmnt-cmp-bar-fill latest" style="width:{{ $curPct }}%"></div>
            </div>
            <span class="asmnt-cmp-bar-val">{{ $data['current_score'] }}/{{ $data['max_score'] }}</span>
          </div>
        </div>
      @endforeach
    </div>

    {{-- ACTIONS --}}
    <div class="asmnt-cta-row fade-in d5">
      <a href="{{ route('user.assessment.result', $latest) }}" class="asmnt-btn-primary">View Latest Result</a>
      <a href="{{ route('user.assessment.index') }}" class="asmnt-btn-outline">Retake Assessment</a>
    </div>

  </div>{{-- /page --}}
</div>{{-- /ud-main --}}
@endsection
