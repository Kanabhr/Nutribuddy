@extends('layouts.user-panel')
@section('title', 'Assessment History — NutriBuddy Kids')
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
        <span class="it-title">Assessment History</span>
        <div style="width:40px"></div>
    </div>

  <div class="page">

    {{-- PAGE HEADER --}}
    <div class="page-header fade-in d1">
      <div class="page-header-left">
        <h1>📋 Assessment History</h1>
        <p>All your previous health assessments, newest first.</p>
      </div>
      <div class="page-header-right">
        <a href="{{ route('user.assessment.index') }}" class="asmnt-btn-primary" style="text-decoration:none;">
          + New Assessment
        </a>
      </div>
    </div>

    @if($attempts->isEmpty())
      {{-- Empty State --}}
      <div class="asmnt-empty fade-in d2">
        <div class="asmnt-empty-icon">🩺</div>
        <h3>No assessments yet</h3>
        <p>Complete your first child health assessment to see your results here.</p>
        <a href="{{ route('user.assessment.index') }}" class="asmnt-btn-primary">Start Assessment</a>
      </div>
    @else

      {{-- Summary Strip --}}
      @php
        $allAttempts  = $attempts->getCollection();
        $latestScore  = $allAttempts->first()->percentage;
        $highestScore = $allAttempts->max('percentage');
        $avgScore     = round($allAttempts->avg('percentage'), 1);
        $totalCount   = $attempts->total();
      @endphp
      <div class="asmnt-stat-strip fade-in d2">
        <div class="asmnt-stat-tile">
          <div class="asmnt-stat-num" style="color:var(--pk)">{{ number_format($latestScore, 1) }}%</div>
          <div class="asmnt-stat-lbl">Latest Score</div>
        </div>
        <div class="asmnt-stat-tile">
          <div class="asmnt-stat-num" style="color:var(--mn)">{{ number_format($highestScore, 1) }}%</div>
          <div class="asmnt-stat-lbl">Highest Score</div>
        </div>
        <div class="asmnt-stat-tile">
          <div class="asmnt-stat-num" style="color:var(--pu)">{{ $avgScore }}%</div>
          <div class="asmnt-stat-lbl">Average Score</div>
        </div>
        <div class="asmnt-stat-tile">
          <div class="asmnt-stat-num" style="color:var(--or)">{{ $totalCount }}</div>
          <div class="asmnt-stat-lbl">Total Attempts</div>
        </div>
      </div>

      @if($totalCount >= 2)
        <div class="asmnt-compare-cta fade-in d3">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Want to see progress?
          <a href="{{ route('user.assessment.compare') }}">Compare latest vs previous →</a>
        </div>
      @endif

      {{-- History Table --}}
      <div class="asmnt-table-card fade-in d3">
        <div class="asmnt-table-wrap">
          <table class="asmnt-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>Score</th>
                <th>Percentage</th>
                <th>Rating</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($attempts as $i => $attempt)
                <tr class="{{ $i === 0 ? 'asmnt-tr-latest' : '' }}">
                  <td>
                    {{ $attempts->firstItem() + $i }}
                    @if($i === 0 && $attempts->currentPage() === 1)
                      <span class="asmnt-latest-tag">Latest</span>
                    @endif
                  </td>
                  <td>
                    <div class="asmnt-date-cell">
                      <span>{{ $attempt->completed_at?->format('d M Y') }}</span>
                      <span class="asmnt-time">{{ $attempt->completed_at?->format('g:i A') }}</span>
                    </div>
                  </td>
                  <td>
                    <strong>{{ $attempt->total_score }}</strong>
                    <span class="asmnt-of">/ {{ $attempt->max_score }}</span>
                  </td>
                  <td>
                    <div class="asmnt-pct-cell">
                      <div class="asmnt-mini-bar">
                        <div class="asmnt-mini-fill" style="width:{{ $attempt->percentage }}%"></div>
                      </div>
                      <span>{{ number_format($attempt->percentage, 1) }}%</span>
                    </div>
                  </td>
                  <td>
                    <span class="asmnt-level-badge {{ $attempt->resultLevelClass() }} sm">
                      {{ $attempt->resultLevelEmoji() }} {{ $attempt->result_level }}
                    </span>
                  </td>
                  <td>
                    <div class="asmnt-action-btns">
                      <a href="{{ route('user.assessment.result', $attempt) }}" class="asmnt-action-btn view" title="View Result">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        View
                      </a>
                      <a href="{{ route('user.assessment.detail', $attempt) }}" class="asmnt-action-btn detail" title="Full Detail">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        Detail
                      </a>
                      <a href="{{ route('user.assessment.index') }}" class="asmnt-action-btn retake" title="Retake Assessment">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        Retake
                      </a>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Pagination --}}
        @if($attempts->hasPages())
          <div class="asmnt-pagination">
            {{ $attempts->links() }}
          </div>
        @endif
      </div>
    @endif

  </div>{{-- /page --}}
</div>{{-- /ud-main --}}
@endsection
