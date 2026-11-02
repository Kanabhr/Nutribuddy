@extends('layouts.user-panel')
@section('title', 'Meal Plan')


@section('panel-content')

<!-- ── PAGE ── -->
<div class="page">

  <!-- Header -->
  <div class="page-header">
    <h1 class="page-title">Meal Plan</h1>
    <p class="page-meta">
      Arjun
      <span class="sep">·</span>
      5 years
      <span class="sep">·</span>
      Pure veg
      <span class="sep">·</span>
      West India
      <span class="sep">·</span>
      🌤️ Greeshma summer plan
    </p>
  </div>

  <!-- Season Alert -->
  <div class="season-alert">
    <div class="alert-icon">🌤️</div>
    <div>
      <div class="alert-title">Greeshma season overlay active</div>
      <div class="alert-body">Cooling and light foods prioritised. Coconut water 2× daily. Early dinner by 7pm. No cold fridge water.</div>
    </div>
  </div>

  <!-- Day Tabs -->
  <div class="tabs-scroll">
    <div class="tabs-track" id="tabsTrack">
      <button class="day-tab" data-day="mon">
        <span class="tab-day">Mon</span><span class="tab-label">Immunity</span>
      </button>
      <button class="day-tab active" data-day="tue">
        <span class="tab-day">Tue</span><span class="tab-label">Growth</span>
      </button>
      <button class="day-tab" data-day="wed">
        <span class="tab-day">Wed</span><span class="tab-label">Brain</span>
      </button>
      <button class="day-tab" data-day="thu">
        <span class="tab-day">Thu</span><span class="tab-label">Immunity</span>
      </button>
      <button class="day-tab" data-day="fri">
        <span class="tab-day">Fri</span><span class="tab-label">Energy</span>
      </button>
      <button class="day-tab" data-day="sat">
        <span class="tab-day">Sat</span><span class="tab-label">Bone</span>
      </button>
      <button class="day-tab" data-day="sun">
        <span class="tab-day">Sun</span><span class="tab-label">Rest</span>
      </button>
    </div>
  </div>

  <!-- Meal Card -->
  <div class="meal-card" id="mealCard">
    <!-- injected by JS -->
  </div>

</div>


   @push('scripts')
<script>
/* ── DATA ── */
const days = @json($jsDays ?? []);

if (Object.keys(days).length === 0) {
    document.getElementById('mealCard').innerHTML = '<div class="p-4 text-center">{{ ($reviewPending ?? false) ? 'Your diet chart is ready and awaiting admin review.' : 'No diet plan has been generated yet. Please complete the assessment to view your personalized plan.' }}</div>';
}

const focusColors = {
  'IMMUNITY BOOST':  'var(--pu)',
  'IMMUNITY':        'var(--pu)',
  'GROWTH & BONES':  'var(--pk)',
  'BRAIN POWER':     'var(--pk)',
  'ENERGY DAY':      'var(--pk)',
  'BONE STRENGTH':   'var(--pu)',
  'REST & RESTORE':  'var(--pk)',
};

const focusBg = {
  'IMMUNITY BOOST':  'var(--mnl)',
  'IMMUNITY':        'var(--mnl)',
  'GROWTH & BONES':  'var(--pul)',
  'BRAIN POWER':     'var(--pkl)',
  'ENERGY DAY':      'var(--yel)',
  'BONE STRENGTH':   'var(--pul)',
  'REST & RESTORE':  'var(--mnl)',
};

function renderCard(dayKey) {
  if (Object.keys(days).length === 0) return;
  const d = days[dayKey];
  if (!d) return;
  
  const card = document.getElementById('mealCard');
  const fc = focusColors[d.focus] || 'var(--mn)';
  const fb = focusBg[d.focus]    || 'var(--mnl)';

  const rows = d.meals.map(m => {
    const tags = m.tags.map(t => {
      const [label, cls] = t.split(':');
      return `<span class="n-tag ${cls}">${label}</span>`;
    }).join('');

    return `
      <div class="meal-row">
        <div class="meal-time"><span class="time-txt">${m.time}</span></div>
        <div class="meal-emoji">${m.emoji}</div>
        <div class="meal-content">
          <div class="meal-name">${m.name}</div>
          <div class="meal-desc">${m.desc}</div>
          <div class="nutrient-tags">${tags}</div>
        </div>
      </div>`;
  }).join('');

  card.innerHTML = `
    <div class="meal-card-header">
      <span class="card-day-title">${d.label}</span>
      <span class="focus-badge" style="color:${fc};background:${fb};border-color:${fc};">${d.focus}</span>
    </div>
    ${rows}
  `;

  card.classList.remove('animating');
  void card.offsetWidth; // reflow
  card.classList.add('animating');
}

/* ── TABS ── */
document.getElementById('tabsTrack').addEventListener('click', e => {
  const tab = e.target.closest('.day-tab');
  if (!tab) return;
  document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
  tab.classList.add('active');
  renderCard(tab.dataset.day);
});

/* ── INIT ── */
const d = new Date();
const weekdays = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
const currentDayStr = weekdays[d.getDay()];

document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
const activeTab = document.querySelector('.day-tab[data-day="' + currentDayStr + '"]');
if (activeTab) {
    activeTab.classList.add('active');
    renderCard(currentDayStr);
} else {
    document.querySelector('.day-tab[data-day="mon"]').classList.add('active');
    renderCard('mon');
}
</script>
put this data in meal-plan.php in proper way like another page with header and footer

    @endpush
@endsection
