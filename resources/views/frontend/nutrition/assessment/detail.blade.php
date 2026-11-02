@extends('layouts.user-panel')
@section('title', 'Assessment Detail — NutriBuddy Kids')
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
        <span class="it-title">Assessment Detail</span>
        <div style="width:40px"></div>
    </div>

  <div class="page">

    {{-- PAGE HEADER --}}
    <div class="page-header fade-in d1">
      <div class="page-header-left">
        <h1>🔍 Assessment Breakdown</h1>
        <p>
          Completed {{ $attempt->completed_at?->format('d M Y, g:i A') }} &nbsp;·&nbsp;
          <strong>{{ $attempt->total_score }} / {{ $attempt->max_score }} pts</strong> &nbsp;·&nbsp;
          <span class="asmnt-level-badge {{ $attempt->resultLevelClass() }} sm">
            {{ $attempt->resultLevelEmoji() }} {{ $attempt->result_level }}
          </span>
        </p>
      </div>
      <div class="page-header-right">
        <a href="{{ route('user.assessment.result', $attempt) }}" class="asmnt-btn-outline" style="text-decoration:none;">
          ← Back to Result
        </a>
      </div>
    </div>

    {{-- SECTION-BY-SECTION TABLES --}}
    @php
      $sectionIcons = [
        'Nutrition'         => '🥗',
        'Sleep'             => '😴',
        'Physical Activity' => '🏃',
        'Mental Wellness'   => '🧠',
        'Hygiene & Habits'  => '🧼',
      ];
      // Section score map for header
      $sectionScoreMap = $attempt->sectionScores->keyBy('section');
    @endphp

    @foreach($answersBySection as $section => $sectionAnswers)
      @php
        $icon = $sectionIcons[$section] ?? '📋';
        $ss   = $sectionScoreMap->get($section);
      @endphp

      <div class="asmnt-detail-section fade-in d2">
        <div class="asmnt-detail-sec-header">
          <span class="asmnt-detail-sec-icon">{{ $icon }}</span>
          <div class="asmnt-detail-sec-info">
            <span class="asmnt-detail-sec-name">{{ $section }}</span>
            @if($ss)
              <span class="asmnt-detail-sec-score">{{ $ss->score }} / {{ $ss->max_score }} pts &nbsp;·&nbsp; {{ number_format($ss->percentage, 0) }}%</span>
            @endif
          </div>
          @if($ss)
            <div class="asmnt-bar-track" style="flex:1;max-width:180px">
              <div class="asmnt-bar-fill" style="width:{{ $ss->percentage }}%;background:var(--pk)"></div>
            </div>
          @endif
        </div>

        <div class="asmnt-detail-table-wrap">
          <table class="asmnt-detail-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Question</th>
                <th>Your Answer</th>
                <th>Score</th>
                <th>Max Score</th>
              </tr>
            </thead>
            <tbody>
              @foreach($sectionAnswers as $qi => $answer)
                @php
                  $maxForQ = $answer->question->options->max('score');
                  $isMax   = $answer->obtained_score >= $maxForQ;
                @endphp
                <tr>
                  <td class="asmnt-dt-num">{{ $qi + 1 }}</td>
                  <td class="asmnt-dt-q">{{ $answer->question->title }}</td>
                  <td class="asmnt-dt-ans">{{ $answer->option->option_text }}</td>
                  <td>
                    <span class="asmnt-score-pill {{ $isMax ? 'max' : ($answer->obtained_score > 0 ? 'mid' : 'zero') }}">
                      +{{ $answer->obtained_score }}
                    </span>
                  </td>
                  <td class="asmnt-dt-max">{{ $maxForQ }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endforeach

    {{-- ACTIONS --}}
    <div class="asmnt-cta-row fade-in d5">
      <a href="{{ route('user.assessment.index') }}" class="asmnt-btn-primary">🔄 Retake Assessment</a>
      <a href="{{ route('user.assessment.history') }}" class="asmnt-btn-outline">📋 View History</a>
    </div>

  </div>{{-- /page --}}
</div>{{-- /ud-main --}}
@endsection
