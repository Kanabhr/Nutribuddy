@extends('layouts.user-panel')
@section('title', 'Growth Adaptation Signals — NutriBuddy Kids')
@section('panel-page-class', 'panel-growth-signal')

@section('panel-content')

{{-- ══ MOBILE TOPBAR ══ --}}
<div class="inner-topbar" id="innerTopbar">
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
    </button>
    <span class="it-title">Growth Signals</span>
    <div style="width:36px"></div>
</div>

{{-- ══ PAGE ══ --}}
<div class="gs-page">

    @if($plan)
    <div class="gs-how-card gs-anim-1" style="margin-bottom:20px">
        <div class="gs-how-header"><span class="gs-how-title">{{ $child?->name }}’s current plan</span></div>
        <p class="gs-how-body">{{ $plan->ai_summary ?: 'Your personalised diet plan is active and being monitored for growth changes.' }}</p>
        <a class="gs-update-btn" href="{{ route('child-profile') }}">View diet chart</a>
    </div>
    @endif

    {{-- ── Page Header ── --}}
    <div class="gs-header gs-anim-1">
        <div class="gs-header-left">
            <h1 class="gs-title">Growth Adaptation Signals</h1>
            <p class="gs-subtitle">
                <span class="gs-signal-dot"></span>
                8 signals tracked — plan changes automatically when any signal is detected
            </p>
        </div>
        <div class="gs-header-right">
            <button class="gs-checkin-btn" onclick="handleGsCheckin()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Quarterly Check-in
            </button>
        </div>
    </div>

    {{-- ── Signal Cards Grid ── --}}
    <div class="gs-grid gs-anim-2">

        {{-- Card 1: Weight below target --}}
        <div class="gs-card gs-anim-card" style="--delay:.05s">
            <div class="gs-card-icon" style="background:var(--yel);">⚖️</div>
            <div class="gs-card-body">
                <div class="gs-card-title">Weight below target</div>
                <div class="gs-card-desc">Brihana plan — gond ke ladoo, dal makhani, ashwagandha milk, extra ghee. 8–12 weeks.</div>
                <div class="gs-card-tag" style="background:var(--yel);color:#9a6700;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    8–12 weeks
                </div>
            </div>
        </div>

        {{-- Card 2: Height growth slow --}}
        <div class="gs-card gs-anim-card" style="--delay:.10s">
            <div class="gs-card-icon" style="background:var(--skl);">📏</div>
            <div class="gs-card-body">
                <div class="gs-card-title">Height growth slow</div>
                <div class="gs-card-desc">Bone builder — calcium 3× daily, ragi, moringa, Vit D timing fix, ashwagandha. 12–24 weeks.</div>
                <div class="gs-card-tag" style="background:var(--skl);color:#0077a8;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    12–24 weeks
                </div>
            </div>
        </div>

        {{-- Card 3: Frequent illness --}}
        <div class="gs-card gs-anim-card" style="--delay:.15s">
            <div class="gs-card-icon" style="background:var(--orl);">🤧</div>
            <div class="gs-card-body">
                <div class="gs-card-title">Frequent illness <span class="gs-badge">3+/quarter</span></div>
                <div class="gs-card-desc">Immunity shield — amla daily, giloy+tulsi kadha, turmeric milk 2×/day. 4–6 weeks.</div>
                <div class="gs-card-tag" style="background:var(--orl);color:#cc4a00;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    4–6 weeks
                </div>
            </div>
        </div>

        {{-- Card 4: Focus / memory issues --}}
        <div class="gs-card gs-anim-card" style="--delay:.20s">
            <div class="gs-card-icon" style="background:var(--pkl);">🧠</div>
            <div class="gs-card-body">
                <div class="gs-card-title">Focus / memory issues</div>
                <div class="gs-card-desc">Brain boost — walnuts daily, flaxseed smoothie, brahmi milk before study. 6–8 weeks.</div>
                <div class="gs-card-tag" style="background:var(--pkl);color:var(--pkd);">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    6–8 weeks
                </div>
            </div>
        </div>

        {{-- Card 5: Constipation --}}
        <div class="gs-card gs-anim-card" style="--delay:.25s">
            <div class="gs-card-icon" style="background:var(--mnl);">🌿</div>
            <div class="gs-card-body">
                <div class="gs-card-title">Constipation</div>
                <div class="gs-card-desc">Gut plan — papaya daily, fibre 3×, probiotics every meal, triphala at bedtime. 1–2 weeks.</div>
                <div class="gs-card-tag" style="background:var(--mnl);color:#00896a;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    1–2 weeks
                </div>
            </div>
        </div>

        {{-- Card 6: Seasonal illness spike --}}
        <div class="gs-card gs-anim-card" style="--delay:.30s">
            <div class="gs-card-icon" style="background:var(--pul);">🌧️</div>
            <div class="gs-card-body">
                <div class="gs-card-title">Seasonal illness spike</div>
                <div class="gs-card-desc">Seasonal overlay — hot cooked foods, ginger-tulsi kadha, no raw salads. Ongoing.</div>
                <div class="gs-card-tag" style="background:var(--pul);color:var(--pud);">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="22 12 12 12 12 22"/></svg>
                    Ongoing
                </div>
            </div>
        </div>

        {{-- Card 7: Child joins sport --}}
        <div class="gs-card gs-anim-card" style="--delay:.35s">
            <div class="gs-card-icon" style="background:#e0f7ff;">⚽</div>
            <div class="gs-card-body">
                <div class="gs-card-title">Child joins sport</div>
                <div class="gs-card-desc">Sports plan — pre: banana+sattu · during: coconut water · post: milk+banana within 45 min.</div>
                <div class="gs-card-tag" style="background:#e0f7ff;color:#0077a8;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Active plan
                </div>
            </div>
        </div>

        {{-- Card 8: Birthday milestone --}}
        <div class="gs-card gs-anim-card" style="--delay:.40s">
            <div class="gs-card-icon" style="background:var(--yel);">🎂</div>
            <div class="gs-card-body">
                <div class="gs-card-title">Birthday milestone</div>
                <div class="gs-card-desc">Full phase transition — RDA recalculates, new foods, new Ayurvedic herbs, plan regenerates.</div>
                <div class="gs-card-tag" style="background:var(--yel);color:#9a6700;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Auto-trigger
                </div>
            </div>
        </div>

    </div>{{-- /gs-grid --}}

    {{-- ── How Detection Works ── --}}
    <div class="gs-how-card gs-anim-3">
        <div class="gs-how-header">
            <span class="gs-how-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </span>
            <span class="gs-how-title">How detection works</span>
        </div>
        <p class="gs-how-body">
            Each quarterly check-in, parent inputs weight, height, illness count, focus, and activity. Backend compares against age benchmarks and triggers relevant plan changes. Life events can be self-reported any time.
        </p>
    </div>

    {{-- ── Signal Status Summary ── --}}
    <div class="gs-status-bar gs-anim-4">
        <div class="gs-status-item">
            <div class="gs-status-num gs-num-active">3</div>
            <div class="gs-status-label">Active Signals</div>
        </div>
        <div class="gs-status-divider"></div>
        <div class="gs-status-item">
            <div class="gs-status-num gs-num-watch">2</div>
            <div class="gs-status-label">Watching</div>
        </div>
        <div class="gs-status-divider"></div>
        <div class="gs-status-item">
            <div class="gs-status-num gs-num-resolved">3</div>
            <div class="gs-status-label">Resolved</div>
        </div>
        <div class="gs-status-divider"></div>
        <div class="gs-status-item">
            <div class="gs-status-num gs-num-total">8</div>
            <div class="gs-status-label">Total Tracked</div>
        </div>
        <div class="gs-status-cta">
            <button class="gs-update-btn" onclick="handleGsCheckin()">
                Update Signals
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
    </div>

</div>{{-- /gs-page --}}

@push('scripts')
<script>
/* ── Sidebar toggle ── */
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

/* ── Quarterly check-in CTA ── */
function handleGsCheckin() {
    const btns = document.querySelectorAll('.gs-checkin-btn, .gs-update-btn');
    btns.forEach(btn => {
        btn.textContent = '✓ Check-in initiated…';
        btn.style.opacity = '.7';
        btn.disabled = true;
    });
    setTimeout(() => {
        btns.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
        document.querySelector('.gs-checkin-btn').innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                <polyline points="9 11 12 14 22 4"></polyline>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            Quarterly Check-in`;
        document.querySelector('.gs-update-btn').innerHTML = `Update Signals
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>`;
    }, 2800);
}

/* ── Stagger animation observer ── */
const gsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animationPlayState = 'running';
            gsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.gs-anim-card').forEach(el => gsObserver.observe(el));
</script>
@endpush

@endsection
