@extends('layouts.user-panel')
@section('title', 'Meal Plan')
@section('panel-page-class', 'panel-meal-plan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nutrition.css') }}?v={{ time() }}">
@endpush

@section('panel-content')
@php
    $mealChild = $mealChild ?? null;
    $mealPlan  = $mealPlan ?? null;

    $metaParts = [];
    if ($mealChild) {
        $metaParts[] = $mealChild['name'];
        if (!empty($mealChild['age']))  { $metaParts[] = $mealChild['age']; }
        if (!empty($mealChild['diet'])) { $metaParts[] = $mealChild['diet']; }
    }

    $totalDays = $mealPlan['total_days'] ?? 0;
    $titleText = $totalDays > 7 ? 'Meal Plan' : ($totalDays > 0 ? '7-Day Meal Plan' : 'Meal Plan');
@endphp

<div class="page">

  <!-- Header: real child, no hard-coded values -->
  <div class="page-header">
    <h1 class="page-title">{{ $titleText }}</h1>
    @if (!empty($metaParts))
    <p class="page-meta">
      @foreach ($metaParts as $i => $part)
        @if ($i > 0)<span class="sep">&middot;</span>@endif
        {{ $part }}
      @endforeach
      @if ($mealPlan)
        <span class="sep">&middot;</span>
        {{ $totalDays }} days from {{ \Carbon\Carbon::parse($mealPlan['start_date'])->format('j M Y') }}
      @endif
    </p>
    @endif
  </div>

  <!-- Questionnaire pending banner (Rule 5) -->
  @if (!empty($questionnairePending))
  <div class="season-alert">
    <div class="alert-icon">&#x1F4CB;</div>
    <div>
      <div class="alert-title">Complete your questionnaire to unlock your plan</div>
      <div class="alert-body">
        Your payment was successful! Answer a few more questions so we can personalise your diet chart.
        <a href="{{ $questionnaireUrl ?? '#' }}" class="fw-bold ms-2">Continue &rarr;</a>
      </div>
    </div>
  </div>
  @elseif (!$mealPlan)
  <div class="season-alert">
    <div class="alert-icon">{!! !empty($reviewPending) ? '&#x23F3;' : '&#x1F957;' !!}</div>
    <div>
      @if (!empty($reviewPending))
        <div class="alert-title">Your chart is being reviewed</div>
        <div class="alert-body">Our nutritionist is checking your child's diet chart. It will appear here as soon as it is approved.</div>
      @else
        <div class="alert-title">No diet plan yet</div>
        <div class="alert-body">No diet plan has been generated yet. Please complete the assessment to view your personalised plan.</div>
      @endif
    </div>
  </div>
  @endif

  @if ($mealPlan)
  <!-- Week tabs: only for plans longer than 7 days -->
  @if ($mealPlan['is_monthly'])
  <div class="nutri-week-tabs" id="weekTabBar"></div>
  @endif

  <!-- Day tabs for the active week -->
  <div class="tabs-scroll">
    <div class="tabs-track" id="tabsTrack"></div>
  </div>

  <!-- Meal card -->
  <div class="meal-card-wrap">
    <div class="meal-card" id="mealCard"></div>
  </div>
  @endif

</div>

@if ($mealPlan)
@push('scripts')
<script>
(function () {
  var plan = @json($mealPlan, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
  var days = plan.days || [];
  var active = plan.active_day_index || 0;

  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function dayByIndex(i) {
    for (var k = 0; k < days.length; k++) { if (days[k].index === i) return days[k]; }
    return null;
  }

  function weekOf(i) { var d = dayByIndex(i); return d ? d.week : 1; }

  function renderWeekTabs() {
    var bar = document.getElementById('weekTabBar');
    if (!bar) return;
    var weeks = [];
    days.forEach(function (d) { if (weeks.indexOf(d.week) === -1) weeks.push(d.week); });
    var cur = weekOf(active);
    bar.innerHTML = weeks.map(function (w) {
      return '<button type="button" class="nutri-week-tab' + (w === cur ? ' active' : '') +
             '" data-week="' + w + '">Week ' + w + '</button>';
    }).join('');
  }

  function renderDayTabs() {
    var cur = weekOf(active);
    document.getElementById('tabsTrack').innerHTML = days
      .filter(function (d) { return d.week === cur; })
      .map(function (d) {
        return '<button type="button" class="day-tab' + (d.index === active ? ' active' : '') +
               '" data-index="' + d.index + '">' +
               '<span class="tab-day">' + esc(d.short) + '</span>' +
               '<span class="tab-label">' + esc(d.date) + (d.is_today ? ' &middot; Today' : '') + '</span>' +
               '</button>';
      }).join('');
  }

  function renderCard() {
    var d = dayByIndex(active);
    var card = document.getElementById('mealCard');
    if (!d) { card.innerHTML = ''; return; }

    var rows = d.meals.map(function (m) {
      var items = m.items.map(function (it) {
        var extra = [];
        if (it.portion) extra.push(esc(it.portion));
        extra.push(esc(it.kcal) + ' kcal');
        return '<div class="meal-name" style="margin-bottom:4px;">' + esc(it.name) +
               (it.regional ? ' <span style="font-weight:600;color:var(--muted);">(' + esc(it.regional) + ')</span>' : '') +
               ' <span style="font-weight:600;color:var(--muted);font-size:.84rem;">&middot; ' + extra.join(' &middot; ') + '</span></div>';
      }).join('');

      return '<div class="meal-row">' +
        '<div class="meal-time"><span class="time-txt">' + esc(m.slot) + '</span></div>' +
        '<div class="meal-emoji">' + esc(m.emoji) + '</div>' +
        '<div class="meal-content">' + items +
        (m.focus ? '<div class="meal-desc">' + esc(m.focus) + '</div>' : '') +
        '<div class="nutrient-tags"><span class="n-tag protein">' + esc(m.kcal) + ' kcal this meal</span></div>' +
        '</div></div>';
    }).join('');

    if (!rows) rows = '<div class="p-4 text-center">No meals are planned for this day.</div>';

    card.innerHTML =
      '<div class="meal-card-header">' +
        '<span class="card-day-title">' + esc(d.weekday) + ', ' + esc(d.date) + '</span>' +
        (d.kcal ? '<span class="focus-badge" style="color:var(--pk);background:var(--pkl);">about ' + esc(d.kcal) + ' kcal</span>' : '') +
      '</div>' + rows;

    card.classList.remove('animating');
    void card.offsetWidth;
    card.classList.add('animating');
  }

  function renderAll() { renderWeekTabs(); renderDayTabs(); renderCard(); }

  document.getElementById('tabsTrack').addEventListener('click', function (e) {
    var tab = e.target.closest('.day-tab');
    if (!tab) return;
    active = parseInt(tab.dataset.index, 10);
    renderDayTabs();
    renderCard();
  });

  var weekBar = document.getElementById('weekTabBar');
  if (weekBar) {
    weekBar.addEventListener('click', function (e) {
      var tab = e.target.closest('.nutri-week-tab');
      if (!tab) return;
      var w = parseInt(tab.dataset.week, 10);
      var first = days.filter(function (d) { return d.week === w; })[0];
      if (first) { active = first.index; renderAll(); }
    });
  }

  renderAll();
})();
</script>
@endpush
@endif
@endsection