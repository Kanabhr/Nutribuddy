function getQZSteps() {
    return document.querySelectorAll('.qz-step-panel').length;
}

function qzSelectCard(el, gridId, qid, isMulti) {
    const grid = document.getElementById(gridId);
    if (!isMulti) grid.querySelectorAll('.qz-age-card,.qz-gender-card').forEach(c => c.classList.remove('selected'));
    el.classList.toggle('selected');
    const vals = [...grid.querySelectorAll('.qz-age-card.selected,.qz-gender-card.selected')].map(c => c.dataset.val);
    const hid = document.getElementById('hidAge_' + qid) || document.getElementById('hidGender_' + qid);
    if (hid) { hid.value = vals.join(','); hideErr(qid); }
}

function qzSelectChip(el, rowId, qid, isMulti) {
    const row = document.getElementById(rowId);
    const wasSelected = el.classList.contains('selected');
    if (!isMulti) {
        row.querySelectorAll('.qz-choice-chip').forEach(c => c.classList.remove('selected'));
    } else if (el.dataset.val.trim().toLowerCase() === 'no preference') {
        row.querySelectorAll('.qz-choice-chip').forEach(c => c.classList.remove('selected'));
    } else {
        row.querySelectorAll('.qz-choice-chip[data-val="No preference"]').forEach(c => c.classList.remove('selected'));
    }
    if (isMulti && el.dataset.val.trim().toLowerCase() === 'no preference' && wasSelected) {
        el.classList.remove('selected');
    } else {
        el.classList.toggle('selected');
    }
    const vals = [...row.querySelectorAll('.qz-choice-chip.selected')].map(c => c.dataset.val);
    const hid = document.getElementById('hidChip_' + qid);
    if (hid) { hid.value = vals.join(','); hideErr(qid); }
}

function hideErr(qid) {
    const e = document.getElementById('qzErr_' + qid);
    if (e) e.classList.remove('show');
}

function qzUpdateStepper(to) {
    const QZ_STEPS = getQZSteps();
    for (let i = 1; i <= QZ_STEPS; i++) {
        const sp = document.getElementById('qzSp' + i);
        if (sp) { sp.classList.remove('active', 'done'); if (i < to) sp.classList.add('done'); else if (i === to) sp.classList.add('active'); }
        const ln = document.getElementById('qzLine' + i);
        if (ln) ln.style.width = i < to ? '100%' : '0%';
    }
}

// ---------------------------------------------------------------------------
// Height auto-apply
// ---------------------------------------------------------------------------
// The height widget stores its value in a hidden input (#h_hid_<qid>) that is
// only populated when the user picks a dropdown option OR clicks the "Apply"
// button after typing a custom value.  If the user typed a custom height but
// forgot to click Apply, the hidden field stayed empty and the height answer
// was never posted — the root cause of the "height not saved" bug.
//
// qzAutoApplyHeights() runs before step-navigation and submission validation.
// For every height question whose custom-entry row is active but whose hidden
// field is still empty, it calls qzApplyCustomHeight() so the typed value is
// captured.  It is idempotent: dropdown selections and already-applied values
// are left untouched.
function qzAutoApplyHeights() {
    if (typeof qzHeightState === 'undefined') return;
    Object.keys(qzHeightState).forEach(function (qid) {
        var state = qzHeightState[qid];
        if (!state || !state.customActive) return;
        var hidEl = document.getElementById('h_hid_' + qid);
        if (hidEl && hidEl.value && hidEl.value.trim() !== '') return;
        if (typeof qzApplyCustomHeight === 'function') qzApplyCustomHeight(qid);
    });
}

function qzGo(from, to) {
    qzAutoApplyHeights();
    const panel = document.getElementById('qzPanel' + from);
    let valid = true;
    panel.querySelectorAll('.qz-form-field[required]').forEach(f => {
        if (!f.value.trim()) {
            valid = false;
            const e = document.getElementById('qzErr_' + f.dataset.qid);
            if (e) e.classList.add('show');
        }
    });
    if (!valid) return;
    document.getElementById('qzPanel' + from).classList.remove('active');
    document.getElementById('qzPanel' + to).classList.add('active');
    qzUpdateStepper(to);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function qzCollect() {
    qzAutoApplyHeights();
    const form = document.getElementById('questionnaireForm');
    const data = {
        plan_slug: form.querySelector('input[name="plan_slug"]').value,
        cycle: form.querySelector('input[name="cycle"]').value,
        _token: form.querySelector('input[name="_token"]').value
    };
    const childId = form.querySelector('input[name="child_id"]');
    if (childId) data.child_id = childId.value;
    document.querySelectorAll('.qz-form-field').forEach(f => {
        if (!f.dataset.qid) return;
        const key = 'q_' + f.dataset.qid;
        if (f.type === 'hidden' && f.value.includes(',')) {
            data[key] = f.value.split(',').map(value => value.trim()).filter(Boolean);
        } else {
            data[key] = f.value;
        }
    });
    return data;
}

async function qzSubmit(btn) {
    qzAutoApplyHeights();
    const QZ_STEPS = getQZSteps();
    const panel = document.getElementById('qzPanel' + QZ_STEPS);
    let valid = true;
    panel.querySelectorAll('.qz-form-field[required]').forEach(f => {
        if (!f.value.trim()) {
            valid = false;
            const e = document.getElementById('qzErr_' + f.dataset.qid);
            if (e) e.classList.add('show');
        }
    });
    if (!valid) return;

    const data = qzCollect();

    if (window.isUserLoggedIn === false) {
        if (typeof openLoginModal === 'function') {
            document.getElementById('loginModalTitle').textContent = 'Account Required';
            document.getElementById('loginModalSub').textContent = 'Please log in to save your child\'s profile and complete the purchase.';
            openLoginModal(function (data) {
                window.isUserLoggedIn = true;
                // Re-trigger the submit now that user is logged in
                qzSubmit(btn);
            });
        } else {
            alert("Please log in or create an account to complete the purchase.");
            window.location.href = window.loginUrlBase + '?plan=' + encodeURIComponent(data.plan_slug) + '&cycle=' + encodeURIComponent(data.cycle);
        }
        return;
    }
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
        const res = await fetch('/api/nutrition/questionnaire/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data._token },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            // When the child already holds an active paid subscription for this plan,
            // skip payment entirely and go straight to the meal plan page.
            if (window.qzSkipPayment) {
                const mealPlanBase = window.qzMealPlanBase || '/user/pages/nutrition/child';
                window.location.href = mealPlanBase + '/' + encodeURIComponent(result.child_id);
                return;
            }
            qzInitiatePayment(data.plan_slug, data.cycle, result.child_id, btn);
        } else {
            alert(result.message || 'Error submitting questionnaire');
            btn.disabled = false; btn.textContent = 'Submit & Pay';
        }
    } catch (e) {
        console.error(e); alert('An error occurred.');
        btn.disabled = false; btn.textContent = 'Submit & Pay';
    }
}

async function qzInitiatePayment(planSlug, cycle, childId, btn) {
    btn.textContent = 'Initializing Payment...';
    try {
        const cr = await fetch('/diet-chart/payment/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
            body: JSON.stringify({ plan_slug: planSlug, gateway: 'cashfree', cycle: cycle, child_id: childId })
        });
        const cd = await cr.json();
        if (!cr.ok || !cd.success) {
            alert(cd.message || 'Failed to initialize payment.');
            btn.textContent = 'Submit & Pay'; btn.disabled = false; return;
        }
        if (cd.gateway === 'free') { window.location.href = '/user/pages/nutrition/child/' + encodeURIComponent(childId); return; }

        if (cd.gateway === 'cashfree') {
            btn.textContent = 'Redirecting...';
            const loadCF = () => {
                const cfMode = (window.nutribuddyConfig && window.nutribuddyConfig.cashfreeEnv === 'production') ? 'production' : 'sandbox';
                const cashfree = Cashfree({ mode: cfMode });
                cashfree.checkout({ paymentSessionId: cd.payment_session_id, redirectTarget: '_self' });
            };

            if (typeof Cashfree === 'undefined') {
                const s = document.createElement('script');
                s.src = 'https://sdk.cashfree.com/js/v3/cashfree.js';
                s.onload = loadCF;
                document.body.appendChild(s);
            } else {
                loadCF();
            }
        }
    } catch (e) {
        console.error(e); alert('An error occurred during payment init.');
        btn.textContent = 'Submit & Pay'; btn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.isUserLoggedIn === true) {
        const pending = localStorage.getItem('pendingQzData');
        if (pending) {
            localStorage.removeItem('pendingQzData');
            const data = JSON.parse(pending);
            const btn = document.getElementById('qzSubmitBtn');
            if (btn) {
                // Jump to last step so user sees loading state
                const QZ_STEPS = getQZSteps();
                for (let i = 1; i <= QZ_STEPS; i++) {
                    const p = document.getElementById('qzPanel' + i);
                    if (p) p.classList.remove('active');
                }
                const lastPanel = document.getElementById('qzPanel' + QZ_STEPS);
                if (lastPanel) lastPanel.classList.add('active');
                qzUpdateStepper(QZ_STEPS);

                // Trigger submission with restored data
                qzSubmitPending(btn, data);
            }
        }
    }
});

async function qzSubmitPending(btn, data) {
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
        const res = await fetch('/api/nutrition/questionnaire/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data._token },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            qzInitiatePayment(data.plan_slug, data.cycle, result.child_id, btn);
        } else {
            alert(result.message || 'Error submitting questionnaire');
            btn.disabled = false; btn.textContent = 'Submit & Pay';
        }
    } catch (e) {
        console.error(e); alert('An error occurred.');
        btn.disabled = false; btn.textContent = 'Submit & Pay';
    }
}
