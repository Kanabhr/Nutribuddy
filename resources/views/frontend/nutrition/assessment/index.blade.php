@extends('layouts.user-panel')
@section('title', 'Health Assessment — NutriBuddy Kids')
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
        <span class="it-title">Health Assessment</span>
        <div style="width:40px"></div>
    </div>

  <div class="page">

    {{-- PAGE HEADER --}}
    <div class="page-header fade-in d1">
      <div class="page-header-left">
        <h1>🩺 Child Health Assessment</h1>
        <p>Answer every question honestly to get the most accurate health score for your child.</p>
      </div>
    </div>

    @if($totalQuestions === 0)
      <div class="asmnt-empty-state" style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 20px; border: 1.5px solid #ede8f5; margin-top: 24px;">
        <div style="font-size: 4rem; margin-bottom: 16px;">⏳</div>
        <h2 style="font-family: 'Fredoka One', cursive; color: #1a0040; margin-bottom: 12px; font-size: 1.8rem;">Questions Coming Soon!</h2>
        <p style="color: #666; font-size: 1.05rem; max-width: 500px; margin: 0 auto;">We are currently preparing our new health assessment. Please check back later!</p>
      </div>
    @else

    {{-- QUIZ FORM --}}
    <form id="quizForm" action="{{ route('user.assessment.store') }}" method="POST" class="quiz-form">
      @csrf

      {{-- Progress Bar --}}
      <div class="quiz-progress-wrap fade-in d2">
        <div class="quiz-progress-header">
          <span class="quiz-progress-label" id="progressLabel">Section 1 of {{ count($questionsGrouped) }}</span>
          <span class="quiz-progress-pct" id="progressPct">0%</span>
        </div>
        <div class="quiz-progress-track">
          <div class="quiz-progress-fill" id="progressFill" style="width:0%"></div>
        </div>
      </div>

      {{-- Section Cards --}}
      <div class="quiz-sections-wrap fade-in d3" id="quizWrap">

        @php 
          $sIndex = 0; 
          $totalSections = count($questionsGrouped);
          $qIndex = 0;
        @endphp
        
        @foreach($questionsGrouped as $section => $sectionQuestions)
          @php $sIndex++; @endphp
          <div class="quiz-section-card {{ $sIndex === 1 ? 'active' : '' }}"
               id="scard-{{ $sIndex }}"
               data-sindex="{{ $sIndex }}"
               data-section="{{ $section }}"
               style="display: {{ $sIndex === 1 ? 'block' : 'none' }};">

            <div class="quiz-card-header" style="margin-bottom: 24px;">
              <span class="quiz-section-badge" style="font-size: 1.2rem; padding: 8px 16px; background: #fdf2f8; color: #db2777; border-radius: 8px; font-weight: 900;">{{ $section }}</span>
            </div>

            <div class="quiz-section-questions">
              @php $sectionMaxScore = 0; @endphp
              @foreach($sectionQuestions as $question)
                @php 
                  $qIndex++; 
                  $sectionMaxScore += $question->options->max('score');
                @endphp
                
                <div class="quiz-question-block" id="qblock-{{ $qIndex }}" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #eee;">
                  <p class="quiz-question-text" style="font-size: 1.1rem; margin-bottom: 12px; color: #1a0040;">
                    <span style="color: #ff4d8f; font-weight: 900;">Q{{ $loop->iteration }}.</span> {{ $question->title }}
                  </p>
                  
                  @if($question->description)
                    <p class="quiz-question-hint" style="margin-bottom: 16px; color: #666; font-size: 0.95rem;">{{ $question->description }}</p>
                  @endif

                  <div class="quiz-options-wrap" style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach($question->options as $option)
                      <label class="quiz-option" for="opt-{{ $question->id }}-{{ $option->id }}" style="display: flex; align-items: center; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: 0.2s;">
                        <input type="radio"
                               id="opt-{{ $question->id }}-{{ $option->id }}"
                               name="answers[{{ $question->id }}]"
                               value="{{ $option->id }}"
                               class="quiz-radio"
                               data-qindex="{{ $qIndex }}"
                               data-sindex="{{ $sIndex }}"
                               data-score="{{ $option->score }}"
                               onchange="onOptionSelect({{ $qIndex }})"
                               style="margin-right: 12px; width: 18px; height: 18px; accent-color: #ff4d8f;">
                        <span class="quiz-option-text" style="font-size: 1rem; color: #444;">{{ $option->option_text }}</span>
                      </label>
                    @endforeach
                  </div>

                  <div class="quiz-validation-msg" id="qerr-{{ $qIndex }}" style="display:none; color: #dc2626; margin-top: 10px; font-weight: 700; font-size: 0.9rem;">
                    ⚠️ Please select an answer to continue.
                  </div>
                </div>
              @endforeach
            </div>

            {{-- Intermediate Score Display --}}
            <div class="quiz-section-score-display" id="score-display-{{ $sIndex }}" style="display: none; background: #fdf2f8; border: 2px solid #fbcfe8; padding: 24px; border-radius: 12px; margin-bottom: 24px; text-align: center;">
              <h3 style="color: #be185d; margin: 0 0 12px; font-family: 'Fredoka One', cursive; font-size: 1.5rem;">{{ $section }} Score</h3>
              <div style="font-size: 3rem; font-weight: 900; color: #db2777; line-height: 1;" id="score-val-{{ $sIndex }}">0%</div>
              <p style="color: #9d174d; margin: 12px 0 0; font-size: 1.05rem; font-weight: 600;">Great job! Move on to the next section.</p>
            </div>

            {{-- Section Navigation --}}
            <div class="quiz-nav" style="display: flex; justify-content: flex-end; margin-top: 20px;">
              <button type="button" class="quiz-btn-next" id="btn-calc-{{ $sIndex }}" onclick="calculateSection({{ $sIndex }}, {{ $sectionMaxScore }})" style="background: linear-gradient(135deg, #ff4d8f, #7c3aed); color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; cursor: pointer; font-size: 1.05rem;">
                Calculate Section Score
              </button>
              
              @if($sIndex < $totalSections)
                <button type="button" class="quiz-btn-next" id="btn-next-{{ $sIndex }}" onclick="nextSection({{ $sIndex }})" style="display: none; background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; cursor: pointer; font-size: 1.05rem;">
                  Next Section ➔
                </button>
              @else
                <button type="submit" class="quiz-btn-submit" id="btn-submit-{{ $sIndex }}" style="display:none; background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; cursor: pointer; font-size: 1.05rem;" onclick="confirmSubmit(event)">
                  Submit Full Assessment ➔
                </button>
              @endif
            </div>

          </div>
        @endforeach

      </div>{{-- /quiz-sections-wrap --}}

    </form>
    @endif

  </div>{{-- /page --}}
</div>{{-- /ud-main --}}

@push('scripts')
@if($totalQuestions > 0)
<script>
(function () {
  const totalSections = {{ $totalSections }};
  let currentSection = 1;
  let answered = {}; // { qIndex: true }

  const fill = document.getElementById('progressFill');
  const label = document.getElementById('progressLabel');
  const pct = document.getElementById('progressPct');

  function updateProgress() {
    const p = Math.round(((currentSection - 1) / totalSections) * 100);
    fill.style.width = p + '%';
    label.textContent = 'Section ' + currentSection + ' of ' + totalSections;
    pct.textContent = p + '%';
  }

  window.onOptionSelect = function (qIndex) {
    answered[qIndex] = true;
    document.getElementById('qerr-' + qIndex).style.display = 'none';
    
    // Highlight selected option by removing class from all then adding to checked
    const qBlock = document.getElementById('qblock-' + qIndex);
    qBlock.querySelectorAll('.quiz-option').forEach(o => {
      o.style.borderColor = '#e2e8f0';
      o.style.background = 'transparent';
    });
    const checked = qBlock.querySelector('input[type=radio]:checked');
    if (checked) {
      const parent = checked.closest('.quiz-option');
      parent.style.borderColor = '#ff4d8f';
      parent.style.background = '#fdf2f8';
    }
  };

  window.calculateSection = function(sIndex, maxScore) {
    // Validate all questions in this section
    const sectionCard = document.getElementById('scard-' + sIndex);
    const radios = sectionCard.querySelectorAll('.quiz-radio');
    
    // Get unique question indices in this section
    const sectionQIndices = new Set();
    radios.forEach(r => sectionQIndices.add(r.dataset.qindex));
    
    let allAnswered = true;
    sectionQIndices.forEach(qi => {
      if (!answered[qi]) {
        document.getElementById('qerr-' + qi).style.display = 'block';
        document.getElementById('qblock-' + qi).scrollIntoView({ behavior: 'smooth', block: 'center' });
        allAnswered = false;
      }
    });

    if (!allAnswered) return;

    // Calculate score
    let obtainedScore = 0;
    sectionCard.querySelectorAll('input[type=radio]:checked').forEach(r => {
      obtainedScore += parseInt(r.dataset.score);
    });

    let percentage = maxScore > 0 ? Math.round((obtainedScore / maxScore) * 100) : 0;
    
    // Show score display
    document.getElementById('score-val-' + sIndex).textContent = percentage + '%';
    document.getElementById('score-display-' + sIndex).style.display = 'block';
    
    // Swap buttons
    document.getElementById('btn-calc-' + sIndex).style.display = 'none';
    
    if (sIndex < totalSections) {
      document.getElementById('btn-next-' + sIndex).style.display = 'inline-flex';
    } else {
      document.getElementById('btn-submit-' + sIndex).style.display = 'inline-flex';
    }
  };

  window.nextSection = function(sIndex) {
    document.getElementById('scard-' + sIndex).style.display = 'none';
    currentSection = sIndex + 1;
    const nextCard = document.getElementById('scard-' + currentSection);
    nextCard.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    updateProgress();
  };

  window.confirmSubmit = function (event) {
    event.preventDefault();
    const btnSubmit = document.getElementById('btn-submit-' + totalSections);
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = 'Calculating...';
    document.getElementById('quizForm').submit();
  };

  // Restore state on back/reload
  document.querySelectorAll('.quiz-radio').forEach(radio => {
    if (radio.checked) {
      const qi = radio.dataset.qindex;
      answered[qi] = true;
      const parent = radio.closest('.quiz-option');
      parent.style.borderColor = '#ff4d8f';
      parent.style.background = '#fdf2f8';
    }
  });

  updateProgress();
})();
</script>
@endif
@endpush
@endsection
