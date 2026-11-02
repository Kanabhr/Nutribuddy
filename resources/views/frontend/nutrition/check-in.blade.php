@extends('layouts.user-panel')
@section('title', 'Quarterly Check-in — NutriBuddy Kids')
@section('panel-page-class', 'panel-check-in')

@section('panel-content')

{{-- ══ MOBILE TOPBAR ══ --}}
<div class="inner-topbar">
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
    </button>
    <span class="it-title">Quarterly Check-in</span>
    <div style="width:36px"></div>
</div>

{{-- ══ PAGE ══ --}}
<div class="ci-page">

    {{-- ── Page Header ── --}}
    <div class="ci-page-head ci-anim-1">
        <h1 class="ci-title">Quarterly Check-in</h1>
        <p class="ci-subtitle">Update Arjun's data — his plan regenerates based on your answers</p>
    </div>

    {{-- ── Main Form Card ── --}}
    <form class="ci-form ci-anim-2" id="checkInForm" onsubmit="handleCheckinSubmit(event)">

        {{-- Row 1: Weight + Height --}}
        <div class="ci-form-row ci-two-col">
            <div class="ci-field-group">
                <label class="ci-label" for="ci-weight">CURRENT WEIGHT (KG)</label>
                <input  class="ci-input" id="ci-weight" type="number" step="0.1" min="5" max="100"
                        value="18" placeholder="e.g. 18">
            </div>
            <div class="ci-field-group">
                <label class="ci-label" for="ci-height">CURRENT HEIGHT (CM)</label>
                <input  class="ci-input" id="ci-height" type="number" step="0.5" min="50" max="200"
                        value="106" placeholder="e.g. 106">
            </div>
        </div>

        {{-- Times Sick --}}
        <div class="ci-field-group">
            <label class="ci-label" for="ci-sick">TIMES SICK LAST 3 MONTHS</label>
            <select class="ci-input ci-select" id="ci-sick">
                <option value="0">0 times — great!</option>
                <option value="1" selected>1 time</option>
                <option value="2">2 times</option>
                <option value="3">3 times</option>
                <option value="4plus">4+ times</option>
            </select>
        </div>

        {{-- Focus & School Performance --}}
        <div class="ci-field-group">
            <label class="ci-label" for="ci-focus">FOCUS &amp; SCHOOL PERFORMANCE</label>
            <select class="ci-input ci-select" id="ci-focus">
                <option value="much-better">Much better</option>
                <option value="slightly-better" selected>Slightly better</option>
                <option value="same">About the same</option>
                <option value="slightly-worse">Slightly worse</option>
                <option value="much-worse">Much worse</option>
            </select>
        </div>

        {{-- Energy Level --}}
        <div class="ci-field-group">
            <label class="ci-label" for="ci-energy">ENERGY LEVEL</label>
            <select class="ci-input ci-select" id="ci-energy">
                <option value="very-high">Very high — runs around all day</option>
                <option value="high">High energy</option>
                <option value="same" selected>About the same</option>
                <option value="low">Lower than before</option>
                <option value="very-low">Tired often</option>
            </select>
        </div>

        {{-- New Life Events --}}
        <div class="ci-field-group">
            <label class="ci-label">NEW LIFE EVENTS?</label>
            <div class="ci-chips" id="ci-chips">
                <button type="button" class="ci-chip ci-chip-active" data-val="nothing">Nothing major</button>
                <button type="button" class="ci-chip" data-val="sport">Joined sports</button>
                <button type="button" class="ci-chip" data-val="exams">Exams coming</button>
                <button type="button" class="ci-chip" data-val="illness">Illness recovery</button>
                <button type="button" class="ci-chip" data-val="school">Changed school</button>
                <button type="button" class="ci-chip" data-val="travel">Travelled / relocated</button>
                <button type="button" class="ci-chip" data-val="birthday">Birthday this quarter</button>
            </div>
        </div>

        {{-- Food Changes --}}
        <div class="ci-field-group">
            <label class="ci-label" for="ci-food">FOOD CHANGES (OPTIONAL)</label>
            <textarea class="ci-input ci-textarea" id="ci-food" rows="4"
                placeholder="e.g. He now eats ragi porridge! But started refusing palak dal this month…"></textarea>
        </div>

        {{-- What happens next info box --}}
        <div class="ci-info-box ci-anim-3">
            <div class="ci-info-head">
                <span class="ci-info-dot"></span>
                What happens next
            </div>
            <p class="ci-info-body">
                Scores recalculate · Meal plan refreshes for season · Product timing updates · Growth signals
                re-evaluate · New picky eater tips. About 10 seconds.
            </p>
        </div>

        {{-- Submit CTA --}}
        <div class="ci-submit-wrap">
            <button type="submit" class="ci-submit-btn" id="ci-submit">
                <span class="ci-btn-text">Regenerate Arjun's plan →</span>
                <span class="ci-btn-spinner" aria-hidden="true"></span>
            </button>
        </div>

    </form>

    {{-- ── Success State (hidden by default) ── --}}
    <div class="ci-success ci-anim-4" id="ci-success" style="display:none">
        <div class="ci-success-icon">✓</div>
        <div class="ci-success-title">Plan regenerated!</div>
        <p class="ci-success-body">Arjun's meal plan, health scores, and supplement schedule have been updated based on your answers.</p>
        <div class="ci-success-actions">
            <a href="{{ route('growth-signal') }}" class="ci-goto-btn">View Health Scores →</a>
            <a href="{{ route('meal-plan') }}" class="ci-goto-btn ci-goto-outline">See New Meal Plan →</a>
        </div>
    </div>

</div>{{-- /ci-page --}}

@push('scripts')
<script>
/* ── Sidebar helpers ── */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
}
function setActive(el) {
    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    if (window.innerWidth <= 900) closeSidebar();
}

/* ── Multi-select chips ── */
const chips = document.querySelectorAll('#ci-chips .ci-chip');
chips.forEach(chip => {
    chip.addEventListener('click', () => {
        const val = chip.dataset.val;

        if (val === 'nothing') {
            /* "Nothing major" deselects all others */
            chips.forEach(c => c.classList.remove('ci-chip-active'));
            chip.classList.add('ci-chip-active');
        } else {
            /* Remove "Nothing major" if something else selected */
            const nothingChip = document.querySelector('[data-val="nothing"]');
            nothingChip.classList.remove('ci-chip-active');
            chip.classList.toggle('ci-chip-active');

            /* If nothing left selected, re-activate "Nothing major" */
            const anyActive = [...chips].some(c => c.classList.contains('ci-chip-active'));
            if (!anyActive) nothingChip.classList.add('ci-chip-active');
        }
    });
});

/* ── Form submit ── */
function handleCheckinSubmit(e) {
    e.preventDefault();

    const btn    = document.getElementById('ci-submit');
    const form   = document.getElementById('checkInForm');
    const success= document.getElementById('ci-success');

    /* Loading state */
    btn.classList.add('ci-loading');
    btn.disabled = true;

    /* Simulate API call */
    setTimeout(() => {
        form.style.opacity  = '0';
        form.style.transform= 'translateY(-10px)';
        form.style.transition= 'opacity .35s ease, transform .35s ease';

        setTimeout(() => {
            form.style.display = 'none';
            success.style.display = 'block';
            /* re-trigger animation */
            success.classList.remove('ci-anim-4');
            void success.offsetWidth;
            success.classList.add('ci-anim-4');
        }, 360);
    }, 2200);
}
</script>
@endpush

@endsection
