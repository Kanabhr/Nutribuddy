@extends('layouts.admin')
@php
  $title = 'Assessment Questions';
  $subTitle = 'Assessment / Questions';
  $sectionKeys = $questions->keys()->values();
  // Restore the active tab from the ?tab= query param (set by controller redirects)
  $requestedTab = request('tab', '');
  $activeTabIdx = $sectionKeys->search($requestedTab);
  if ($activeTabIdx === false)
    $activeTabIdx = 0;
@endphp

@section('content')

  {{-- PAGE HEADER --}}
  <div class="aqt-header">
    <div class="aqt-header-left">
    </div>
    <button class="aqt-btn-add" onclick="openAddModal()" id="btn-add-question">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19" />
        <line x1="5" y1="12" x2="19" y2="12" />
      </svg>
      Add Question
    </button>
  </div>

  {{-- Flash Messages --}}
  @if(session('success'))
    <div class="aqt-alert success" id="aqt-flash">✅ {{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="aqt-alert error" id="aqt-flash">❌ {{ session('error') }}</div>
  @endif

  @if($questions->isEmpty())
    <div class="aqt-empty">
      <div class="aqt-empty-icon">🩺</div>
      <h3>No questions yet</h3>
      <p>Create your first assessment question to get started.</p>
      <button class="aqt-btn-add" onclick="openAddModal()">+ Add First Question</button>
    </div>
  @else

    {{-- TABS NAVIGATION --}}
    <div class="aqt-tabs-wrap">
      <div class="aqt-tabs" id="aqt-tabs" role="tablist">
        @foreach($sectionKeys as $i => $sec)
          <button class="aqt-tab {{ $i === $activeTabIdx ? 'active' : '' }}" role="tab" id="tab-btn-{{ $i }}"
            data-tab="{{ $i }}" data-section="{{ $sec }}" aria-selected="{{ $i === $activeTabIdx ? 'true' : 'false' }}"
            onclick="switchTab({{ $i }})">
            <span class="aqt-tab-name" data-section="{{ $sec }}">{{ $sec }}</span>
            <span class="aqt-tab-badge">{{ $questions[$sec]->count() }}</span>
          </button>
        @endforeach
      </div>
      <div class="aqt-tabs-line"></div>
    </div>

    {{-- TAB PANELS --}}
    @foreach($sectionKeys as $i => $sec)
      @php $sectionQuestions = $questions[$sec]; @endphp
      <div class="aqt-panel {{ $i === $activeTabIdx ? 'active' : '' }}" id="tab-panel-{{ $i }}" role="tabpanel">
        <div class="aqt-panel-header">
          <span class="aqt-panel-count">
            <iconify-icon icon="solar:list-bold"></iconify-icon>
            {{ $sectionQuestions->count() }} question(s) in this section
          </span>
        </div>

        <div class="aqt-cards" id="cards-panel-{{ $i }}">
          @foreach($sectionQuestions as $question)
            <div class="aqt-card {{ $question->is_active ? '' : 'aqt-inactive' }}" id="qcard-admin-{{ $question->id }}">
              <div class="aqt-card-top">
                <div class="aqt-card-left">
                  <span class="aqt-drag-handle" title="Drag to reorder">⠿</span>
                  <div class="aqt-card-text">
                    <div class="aqt-card-title">{{ $question->title }}</div>
                    @if($question->description)
                      <div class="aqt-card-desc">{{ $question->description }}</div>
                    @endif
                  </div>
                </div>
                <div class="aqt-card-actions">
                  <button class="aqt-toggle {{ $question->is_active ? 'active' : 'inactive' }}"
                    onclick="toggleActive({{ $question->id }}, this)" title="{{ $question->is_active ? 'Disable' : 'Enable' }}">
                    {{ $question->is_active ? '✓ Active' : '✗ Inactive' }}
                  </button>
                  <a href="{{ route('admin.assessment.questions.edit', $question) }}" class="aqt-icon-btn edit" title="Edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </a>
                  <form action="{{ route('admin.assessment.questions.destroy', $question) }}" method="POST"
                    onsubmit="return confirm('Delete this question and all its options? This cannot be undone.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="aqt-icon-btn delete" title="Delete">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        <path d="M10 11v6" />
                        <path d="M14 11v6" />
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                      </svg>
                    </button>
                  </form>
                </div>
              </div>
              <div class="aqt-options">
                @foreach($question->options as $opt)
                  <div class="aqt-opt">
                    <span class="aqt-opt-text">{{ $opt->option_text }}</span>
                    <span class="aqt-opt-score">+{{ $opt->score }} pts</span>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach

  @endif

  {{-- ADD QUESTION MODAL --}}
  <div class="aqt-backdrop" id="addModal" onclick="closeAddModal(event)">
    <div class="aqt-modal">
      <div class="aqt-modal-hdr">
        <div class="aqt-modal-hdr-icon"><iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon></div>
        <h3>Add New Question</h3>
        <button class="aqt-modal-close" onclick="closeModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>
      <form action="{{ route('admin.assessment.questions.store') }}" method="POST">
        @csrf
        <div class="aqt-modal-body">
          <div class="aqt-field">
            <label>Question Title <span class="req">*</span></label>
            <textarea name="title" class="aqt-ctrl" rows="2" required
              placeholder="e.g. How many servings of vegetables does your child eat per day?">{{ old('title') }}</textarea>
          </div>
          <div class="aqt-field">
            <label>Description / Hint <span class="aqt-optional">(optional)</span></label>
            <input type="text" name="description" class="aqt-ctrl" placeholder="Short help text shown below the question"
              value="{{ old('description') }}">
          </div>
          <div class="aqt-field-row">
            <div class="aqt-field" style="flex:1">
              <label>Section <span class="req">*</span></label>
              <select name="section" class="aqt-ctrl" required id="modal-section-input">
                @foreach($sections as $sec)
                  <option value="{{ $sec }}" {{ old('section') == $sec ? 'selected' : '' }}>{{ $sec }}</option>
                @endforeach
              </select>
            </div>
            <div class="aqt-field" style="width:110px;flex-shrink:0">
              <label>Sort Order</label>
              <input type="number" name="sort_order" class="aqt-ctrl" value="{{ old('sort_order', 0) }}" min="0">
            </div>
          </div>
          <div class="aqt-field">
            <label>Answer Options <span class="req">*</span></label>
            <p class="aqt-hint">At least 2 options required. Score = points for that choice (0 = weakest).</p>
            <div id="optionsContainer">
              <div class="aqt-opt-row">
                <input type="text" name="options[0][option_text]" class="aqt-ctrl" placeholder="Option text" required>
                <input type="number" name="options[0][score]" class="aqt-score" placeholder="Score" min="0" max="99"
                  required>
                <input type="hidden" name="options[0][sort_order]" value="0">
                <button type="button" class="aqt-rem-opt" onclick="removeOption(this)" disabled>✕</button>
              </div>
              <div class="aqt-opt-row">
                <input type="text" name="options[1][option_text]" class="aqt-ctrl" placeholder="Option text" required>
                <input type="number" name="options[1][score]" class="aqt-score" placeholder="Score" min="0" max="99"
                  required>
                <input type="hidden" name="options[1][sort_order]" value="1">
                <button type="button" class="aqt-rem-opt" onclick="removeOption(this)">✕</button>
              </div>
            </div>
            <button type="button" class="aqt-add-opt" onclick="addOption()">+ Add Another Option</button>
          </div>
        </div>
        <div class="aqt-modal-ftr">
          <button type="button" class="aqt-btn-cancel" onclick="closeModal()">Cancel</button>
          <button type="submit" class="aqt-btn-add">Save Question</button>
        </div>
      </form>
    </div>
  </div>

  <link rel="stylesheet" href="{{ asset('css/admin-assessment.css') }}">

  <script>
    window.assessmentRenameUrl = '{{ route("admin.assessment.sections.rename") }}';
</script>
<script src="{{ asset('js/admin-assessment.js') }}"></script>

@endsection