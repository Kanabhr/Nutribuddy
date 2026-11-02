/* ── CONFIG ── */
// Get route and CSRF token from HTML meta tags
const APP_CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const APP_ROUTE_STORE = '/user-panel/child-profile';

/* ── VIEW SWITCHING ── */
function openEdit(){
  document.getElementById('vProfile').classList.remove('on');
  document.getElementById('vEdit').classList.add('on');
  goTo(1);
  window.scrollTo({top:0, behavior:'smooth'});
}

function backToProfile(){
  document.getElementById('vEdit').classList.remove('on');
  document.getElementById('vProfile').classList.add('on');
  window.scrollTo({top:0, behavior:'smooth'});
}

/* ── VALIDATION ── */
function v1(){
  let ok = true;
  const n = document.getElementById('oName').value.trim();
  if (!n) {
    markE('oName', 'eName');
    ok = false;
  } else {
    clrE(document.getElementById('oName'), 'eName');
  }

  const a = document.getElementById('oAge').value;
  if (!a) {
    markE('oAge', 'eAge');
    ok = false;
  } else {
    clrE(document.getElementById('oAge'), 'eAge');
  }

  const h = parseFloat(document.getElementById('oHeight').value);
  if (!h || h < 50 || h > 220) {
    markE('oHeight', 'eHeight');
    ok = false;
  } else {
    clrE(document.getElementById('oHeight'), 'eHeight');
  }

  const w = parseFloat(document.getElementById('oWeight').value);
  if (!w || w < 5 || w > 150) {
    markE('oWeight', 'eWeight');
    ok = false;
  } else {
    clrE(document.getElementById('oWeight'), 'eWeight');
  }

  const r = document.getElementById('oRegion').value;
  if (!r) {
    markE('oRegion', 'eRegion');
    ok = false;
  } else {
    clrE(document.getElementById('oRegion'), 'eRegion');
  }

  document.getElementById('e1').classList.toggle('on', !ok);
  if (!ok) {
    document.getElementById('e1').scrollIntoView({behavior:'smooth', block:'nearest'});
  }

  return ok;
}

function v2() {
  const ok = !!document.querySelector('#s2 .cc.sel');
  document.getElementById('e2').classList.toggle('on', !ok);
  return ok;
}

function v3() {
  const ok = !!document.querySelector('.dc.sel') && !!document.querySelector('.pc.sel');
  document.getElementById('e3').classList.toggle('on', !ok);
  return ok;
}

function v4() {
  const ok = !!document.querySelector('.lc.sel');
  document.getElementById('e4').classList.toggle('on', !ok);
  return ok;
}

function v5() {
  const ok = !!document.querySelector('#s5 .gc.sel');
  document.getElementById('e5').classList.toggle('on', !ok);
  return ok;
}

function markE(iId, eId) {
  document.getElementById(iId).classList.add('err');
  document.getElementById(eId).classList.add('on');
}

function clrE(el, eId) {
  if (el.classList) {
    el.classList.remove('err');
  }
  const e = document.getElementById(eId);
  if (e) {
    e.classList.remove('on');
  }
}

/* ── STEP NAV ── */
const vmap = {1:v1, 2:v2, 3:v3, 4:v4, 5:v5};

function goTo(n) {
  const cur = parseInt(document.querySelector('.os.on')?.id.slice(1));
  if (n > cur && vmap[cur] && !vmap[cur]()) {
    return;
  }

  const name = (document.getElementById('oName').value.trim()) || 'your child';
  ['nd3','nd4','nd5','nd5b','nd5c','nd6'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = name;
  });

  document.querySelectorAll('.cname').forEach(el => el.textContent = name);

  const genBtn = document.getElementById('genBtn');
  if (genBtn) {
    genBtn.innerHTML = `Generate ${name}'s Plan <span class="arr">→</span>`;
  }

  document.querySelectorAll('.os').forEach(s => s.classList.remove('on'));
  const next = document.getElementById('s' + n);
  if (next) {
    next.classList.add('on');
    window.scrollTo({top:0, behavior:'smooth'});
  }

  if (n === 6) {
    setTimeout(() => {
      cu('rda1', 87, '%', 1200);
      cu('rda2', 79, '%', 1400);
      cu('rda3', 72, '%', 1100);
    }, 350);
  }
}

/* ── ONBOARDING HELPERS ── */
const pDesc = {
  '1': '<strong>Phase 1 — Early childhood (4–6 yrs)</strong><br>Calcium bone foundation · Iron for brain · Probiotics for gut immunity · Soft textures, mild flavours',
  '2': '<strong>Phase 2 — Middle childhood (7–9 yrs)</strong><br>Omega-3 for brain growth · Zinc for immunity · Complex carbs for energy · Variety building',
  '3': '<strong>Phase 3 — Late childhood (10–12 yrs)</strong><br>Iron for girls · Protein for growth spurts · Calcium peak window · Sports nutrition',
  '4': '<strong>Phase 4 — Early teen (13–15 yrs)</strong><br>Hormonal support · B-vitamins for energy · Protein for muscle · Iron replacement',
  '5': '<strong>Phase 5 — Late teen (16–17 yrs)</strong><br>Bone density final window · Stress & focus support · Heart health foundations'
};

function selG(b) {
  document.querySelectorAll('.gbtn').forEach(x => x.classList.remove('sel'));
  b.classList.add('sel');
}

function selP(b) {
  document.querySelectorAll('.pp').forEach(x => x.classList.remove('sel'));
  b.classList.add('sel');
  const pNoteEl = document.getElementById('pNote');
  if (pNoteEl) {
    pNoteEl.innerHTML = pDesc[b.dataset.p] || '';
  }
}

function autoPhase() {
  const a = parseInt(document.getElementById('oAge').value);
  const p = a <= 6 ? '1' : a <= 9 ? '2' : a <= 12 ? '3' : a <= 15 ? '4' : '5';
  const pill = document.querySelector(`.pp[data-p="${p}"]`);
  if (pill) {
    selP(pill);
  }
}

function selO(el, sel, cls) {
  document.querySelectorAll(sel).forEach(c => c.classList.remove(cls));
  el.classList.add(cls);
}

function togCC(el) {
  el.classList.toggle('sel');
}

function togG(el) {
  el.classList.toggle('sel');
}

function togP(el) {
  el.classList.toggle('sel');
}

function pickPlan(b, plan) {
  b.textContent = '✓ Selected!';
  b.style.background = 'var(--g)';
  b.style.color = '#fff';
  b.style.borderColor = 'var(--g)';
  setTimeout(() => {
    b.textContent = plan === 'Champion' ? 'Start 7-day trial →' : 'Choose ' + plan;
    b.style.background = '';
    b.style.color = '';
    b.style.borderColor = '';
  }, 2000);
}

/* ── SAVE & RETURN ── */
function saveReturn() {
  const name = (document.getElementById('oName').value.trim()) || 'Arjun';
  const age = document.getElementById('oAge').value || '5';
  const h = document.getElementById('oHeight').value || '106';
  const w = document.getElementById('oWeight').value || '18';
  const region = document.getElementById('oRegion').value || 'West India (Gujarat)';
  const g = document.querySelector('.gbtn.sel')?.dataset.g || 'boy';
  const gl = {boy:'Boy', girl:'Girl', other:'Other'}[g];
  const phase = document.querySelector('.pp.sel')?.dataset.p || '1';
  const pm = {
    '1':'Phase 1 — 4–6 yrs',
    '2':'Phase 2 — 7–9 yrs',
    '3':'Phase 3 — 10–12 yrs',
    '4':'Phase 4 — 13–15 yrs',
    '5':'Phase 5 — 16–17 yrs'
  };
  const diet = document.querySelector('.dc.sel .dn2')?.textContent || 'Pure vegetarian';
  const picky = document.querySelector('.pc.sel .pn')?.textContent || 'Slightly picky';
  const act = document.querySelector('.lc.sel .lcn')?.textContent || 'Moderately active';
  const alg = [...document.querySelectorAll('#s3 .pt .ptl.sel')].map(p => p.textContent);
  const allergies = alg.length ? alg.join(', ') : 'None';

  const btn = document.querySelector('.btn-sp') || document.querySelector('.btn-co.or');
  btn.innerHTML = '⏳ Generating Diet Plan (may take 20s)...';
  btn.disabled = true;

  fetch(APP_ROUTE_STORE, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": APP_CSRF_TOKEN
    },
    body: JSON.stringify({
      name: name,
      age: age,
      sex: g,
      height: h,
      weight: w,
      region: region,
      diet: diet,
      picky: picky,
      allergies: allergies,
      activity: act
    })
  }).then(res => res.json()).then(data => {
    showToast('✓ ' + name + "'s plan generated successfully!");
    window.location.reload();
  }).catch(e => {
    console.error(e);
    btn.innerHTML = '✓ Save & View Updated Profile →';
    btn.disabled = false;
    showToast('⚠️ Error generating plan');
  });
}

/* ── PROFILE HELPERS ── */
function switchTab(btn, id) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('on'));
  document.querySelectorAll('.tp').forEach(p => p.classList.remove('on'));
  btn.classList.add('on');
  const p = document.getElementById(id);
  p.classList.add('on');
  p.style.animation = 'none';
  p.offsetHeight;
  p.style.animation = 'fu .36s ease both';
}

function switchDietDay(dayId, btn) {
  document.querySelectorAll('.diet-day-pane').forEach(p => p.style.display = 'none');
  document.getElementById(dayId).style.display = 'block';

  document.querySelectorAll('.diet-day-btn').forEach(b => {
    b.style.background = 'var(--wh)';
    b.style.color = 'var(--mu)';
    b.style.borderColor = 'var(--br)';
    b.classList.remove('active');
  });

  btn.style.background = 'var(--pk)';
  btn.style.color = '#fff';
  btn.style.borderColor = 'var(--pk)';
  btn.classList.add('active');
}

function doCheckin() {
  const b = document.getElementById('ciBtn');
  b.innerHTML = '⏳ Initiating check-in…';
  b.style.background = 'var(--pu)';
  b.disabled = true;
  setTimeout(() => {
    b.innerHTML = '✓ Check-in Complete! Scores updated';
    b.style.background = 'var(--g)';
    setTimeout(() => {
      b.innerHTML = 'Quarterly Check-in <span class="arr">→</span>';
      b.style.background = '';
      b.disabled = false;
    }, 2800);
  }, 1400);
}

/* ── ONBOARDING HELPERS ── */
const pDesc = {
  '1': '<strong>Phase 1 — Early childhood (4–6 yrs)</strong><br>Calcium bone foundation · Iron for brain · Probiotics for gut immunity · Soft textures, mild flavours',
  '2': '<strong>Phase 2 — Middle childhood (7–9 yrs)</strong><br>Omega-3 for brain growth · Zinc for immunity · Complex carbs for energy · Variety building',
  '3': '<strong>Phase 3 — Late childhood (10–12 yrs)</strong><br>Iron for girls · Protein for growth spurts · Calcium peak window · Sports nutrition',
  '4': '<strong>Phase 4 — Early teen (13–15 yrs)</strong><br>Hormonal support · B-vitamins for energy · Protein for muscle · Iron replacement',
  '5': '<strong>Phase 5 — Late teen (16–17 yrs)</strong><br>Bone density final window · Stress & focus support · Heart health foundations'
};

function selG(b) {
  document.querySelectorAll('.gbtn').forEach(x => x.classList.remove('sel'));
  b.classList.add('sel');
}

function selP(b) {
  document.querySelectorAll('.pp').forEach(x => x.classList.remove('sel'));
  b.classList.add('sel');
  const pNoteEl = document.getElementById('pNote');
  if (pNoteEl) {
    pNoteEl.innerHTML = pDesc[b.dataset.p] || '';
  }
}

function autoPhase() {
  const a = parseInt(document.getElementById('oAge').value);
  const p = a <= 6 ? '1' : a <= 9 ? '2' : a <= 12 ? '3' : a <= 15 ? '4' : '5';
  const pill = document.querySelector(`.pp[data-p="${p}"]`);
  if (pill) {
    selP(pill);
  }
}

function selO(el, sel, cls) {
  document.querySelectorAll(sel).forEach(c => c.classList.remove(cls));
  el.classList.add(cls);
}

function togCC(el) {
  el.classList.toggle('sel');
}

function togG(el) {
  el.classList.toggle('sel');
}

function togP(el) {
  el.classList.toggle('sel');
}

function pickPlan(b, plan) {
  b.textContent = '✓ Selected!';
  b.style.background = 'var(--g)';
  b.style.color = '#fff';
  b.style.borderColor = 'var(--g)';
  setTimeout(() => {
    b.textContent = plan === 'Champion' ? 'Start 7-day trial →' : 'Choose ' + plan;
    b.style.background = '';
    b.style.color = '';
    b.style.borderColor = '';
  }, 2000);
}

/* ── UTILS ── */
function cu(id, target, suffix, dur) {
  const el = document.getElementById(id);
  if (!el) return;

  let c = 0;
  const step = target / (dur / 16);
  const t = () => {
    c = Math.min(c + step, target);
    el.textContent = Math.round(c) + suffix;
    if (c < target) {
      requestAnimationFrame(t);
    }
  };
  requestAnimationFrame(t);
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('on');
  setTimeout(() => t.classList.remove('on'), 3400);
}

/* ── INIT ── */
window.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => cu('scoreVal', 75, '', 1300), 300);
});
