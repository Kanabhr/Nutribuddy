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

// ---------------------------------------------------------------------------
// C7-FIX-C: Exclusive chip logic lives here — this is the authoritative
// definition of qzSelectChip.  Any inline-script wrapper in the blade would
// wrap undefined and be overwritten on load; placing the logic here avoids
// that entirely.
//
// Exclusive behaviour (Q26 "None of the above" / "Not sure"):
//   - clicking an exclusive chip  → deselect all non-exclusive chips
//   - clicking a non-exclusive chip → deselect all exclusive chips
//   - "No preference" region logic is preserved
// ---------------------------------------------------------------------------
function qzSelectChip(el, rowId, qid, isMulti) {
    const row = document.getElementById(rowId);
    const wasSelected = el.classList.contains('selected');

    // ── existing "No preference" logic (unchanged) ──────────────────────────
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

    // ── C7-FIX-C: exclusive chip handling ───────────────────────────────────
    if (isMulti) {
        const exclusiveAttr = row.getAttribute('data-qz-exclusive');
        if (exclusiveAttr) {
            const isExclusive = el.dataset.qzExclusiveChip === 'true';
            const nowSelected = el.classList.contains('selected');
            if (isExclusive && nowSelected) {
                // Exclusive chip just selected: clear all non-exclusive chips
                row.querySelectorAll('.qz-choice-chip').forEach(c => {
                    if (c !== el && c.dataset.qzExclusiveChip !== 'true') {
                        c.classList.remove('selected');
                    }
                    // Also clear other exclusive chips (only one exclusive at a time)
                    if (c !== el && c.dataset.qzExclusiveChip === 'true') {
                        c.classList.remove('selected');
                    }
                });
            } else if (!isExclusive && nowSelected) {
                // Non-exclusive chip just selected: clear all exclusive chips
                row.querySelectorAll('.qz-choice-chip[data-qz-exclusive-chip="true"]').forEach(c => {
                    c.classList.remove('selected');
                });
            }
        }
    }
    // ────────────────────────────────────────────────────────────────────────

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

// ---------------------------------------------------------------------------
// C7-FIX-A: Helper — return true if a form field should be ignored because
// it lives inside a hidden question block (.qz-question-block with
// style="display:none" or the qz-hidden-for-boy class with display none).
// ---------------------------------------------------------------------------
function qzFieldIsHidden(field) {
    const block = field.closest('.qz-question-block');
    if (!block) return false;
    // Check inline display:none (set by qzUpdatePeriodsVisibility)
    if (block.style.display === 'none') return true;
    // Check computed style as a fallback
    if (window.getComputedStyle(block).display === 'none') return true;
    return false;
}

// ---------------------------------------------------------------------------
// C7-FIX-A: Validate required fields in a panel, skipping hidden blocks.
// Returns true if valid, false if any visible required field is empty.
// Shows the .qz-error element for each failing field and scrolls to the first.
// ---------------------------------------------------------------------------
function qzValidatePanel(panel) {
    let valid = true;
    let firstInvalid = null;
    panel.querySelectorAll('.qz-form-field[required]').forEach(f => {
        // Skip fields inside a hidden question block
        if (qzFieldIsHidden(f)) return;
        if (!f.value.trim()) {
            valid = false;
            const e = document.getElementById('qzErr_' + f.dataset.qid);
            if (e) e.classList.add('show');
            if (!firstInvalid) firstInvalid = e || f;
        }
    });
    if (!valid && firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return valid;
}

function qzGo(from, to) {
    qzAutoApplyHeights();
    const panel = document.getElementById('qzPanel' + from);
    // C7-FIX-A: use qzValidatePanel which skips hidden blocks
    if (!qzValidatePanel(panel)) return;
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
        // C7-FIX-A: skip fields in hidden blocks
        if (qzFieldIsHidden(f)) return;
        // C7-FIX-B: skip the __file_pending__ placeholder value; files handled separately
        if (f.id && f.id.startsWith('hidFile_') && f.dataset.pendingFile) return;
        const key = 'q_' + f.dataset.qid;
        if (f.type === 'hidden' && f.value.includes(',')) {
            data[key] = f.value.split(',').map(value => value.trim()).filter(Boolean);
        } else {
            data[key] = f.value;
        }
    });
    return data;
}

// ---------------------------------------------------------------------------
// C7-FIX-B: Check whether the user has chosen a file for any Q31-style input.
// Returns the first {qid, file} found, or null.
// ---------------------------------------------------------------------------
function qzFindChosenFile() {
    var result = null;
    document.querySelectorAll('.qz-form-field[id^="hidFile_"]').forEach(function (hid) {
        if (!hid.dataset.pendingFile) return;
        if (qzFieldIsHidden(hid)) return;  // skip hidden blocks
        const qid = hid.id.replace('hidFile_', '');
        const input = document.getElementById('fileInput_' + qid);
        if (input && input.files && input.files[0]) {
            result = { qid: qid, file: input.files[0] };
        }
    });
    return result;
}

// ---------------------------------------------------------------------------
// C7-FIX-B: Build a FormData payload for multipart submission (file present).
// No Content-Type header — browser sets it with the boundary.
// ---------------------------------------------------------------------------
function qzBuildFormData(data, fileInfo) {
    const fd = new FormData();
    for (const [key, val] of Object.entries(data)) {
        if (Array.isArray(val)) {
            val.forEach(v => fd.append(key + '[]', v));
        } else {
            fd.append(key, val ?? '');
        }
    }
    fd.append('q_' + fileInfo.qid, fileInfo.file);
    return fd;
}

// ---------------------------------------------------------------------------
// Toast Notification Helper (Top-Right Widget)
// ---------------------------------------------------------------------------
function qzNotify(msg, type = 'warning', title = '') {
    if (typeof window.nbToast === 'function') {
        window.nbToast(msg, type, title);
        return;
    }
    if (typeof window.toastr !== 'undefined' && typeof window.toastr[type] === 'function') {
        window.toastr[type](msg, title);
        return;
    }
    if (typeof window.showToast === 'function') {
        window.showToast(msg);
        return;
    }
    // Fallback: create floating toast at top-right
    let wrap = document.getElementById('nb-toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'nb-toast-wrap';
        wrap.style.cssText = 'position:fixed;top:22px;right:22px;z-index:99999;display:flex;flex-direction:column;gap:12px;pointer-events:none;';
        document.body.appendChild(wrap);
    }
    const t = document.createElement('div');
    t.style.cssText = 'pointer-events:all;padding:14px 18px;border-radius:12px;background:#fff;border-left:5px solid ' + (type === 'warning' ? '#f59e0b' : '#ef4444') + ';box-shadow:0 8px 30px rgba(0,0,0,0.15);font-family:sans-serif;font-size:14px;color:#1f2937;max-width:380px;';
    t.innerHTML = '<strong>' + (title || (type === 'warning' ? 'Notice' : 'Alert')) + '</strong><div style="margin-top:4px;">' + msg + '</div>';
    wrap.appendChild(t);
    setTimeout(() => { t.remove(); }, 4000);
}

// ---------------------------------------------------------------------------
// C7-FIX-B: Show server 422 validation errors to the user inline or as alert.
// ---------------------------------------------------------------------------
function qzShowServerErrors(errors) {
    const errorMessages = [];
    for (const [key, messages] of Object.entries(errors || {})) {
        const msg = Array.isArray(messages) ? messages[0] : messages;
        if (!msg) continue;
        // Try to show inline next to the field
        const match = key.match(/^q_(\d+)(?:\.\*)?$/);
        if (match) {
            const errEl = document.getElementById('qzErr_' + match[1]);
            if (errEl) {
                errEl.textContent = msg;
                errEl.classList.add('show');
                continue;
            }
        }
        errorMessages.push(msg);
    }
    if (errorMessages.length > 0) {
        qzNotify('Please fix the following:\n' + errorMessages.join('\n'), 'warning', 'Form Incomplete');
    }
}

async function qzSubmit(btn) {
    qzAutoApplyHeights();
    const QZ_STEPS = getQZSteps();
    const panel = document.getElementById('qzPanel' + QZ_STEPS);
    // C7-FIX-A: use qzValidatePanel which skips hidden blocks and shows errors
    if (!qzValidatePanel(panel)) return;

    const data = qzCollect();

    if (window.isUserLoggedIn === false) {
        // C7-FIX-B: File cannot survive localStorage. Save text answers only
        // and show a note that the file must be re-attached after login.
        const fileInfo = qzFindChosenFile();
        if (fileInfo) {
            qzNotify('Note: your file attachment (blood report) cannot be saved during login. Please attach it again after logging in.', 'info', 'Notice');
        }
        if (typeof openLoginModal === 'function') {
            document.getElementById('loginModalTitle').textContent = 'Account Required';
            document.getElementById('loginModalSub').textContent = 'Please log in to save your child\'s profile and complete the purchase.';
            openLoginModal(function (data) {
                window.isUserLoggedIn = true;
                qzSubmit(btn);
            });
        } else {
            qzNotify("Please log in or create an account to complete the purchase.", 'warning', 'Account Required');
            window.location.href = window.loginUrlBase + '?plan=' + encodeURIComponent(data.plan_slug) + '&cycle=' + encodeURIComponent(data.cycle);
        }
        return;
    }
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
        // C7-FIX-B: Use multipart FormData when a file is chosen, JSON otherwise.
        const fileInfo = qzFindChosenFile();
        let res;
        if (fileInfo) {
            const fd = qzBuildFormData(data, fileInfo);
            res = await fetch('/api/nutrition/questionnaire/submit', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': data._token },
                // No Content-Type: let browser set multipart boundary
                body: fd
            });
        } else {
            res = await fetch('/api/nutrition/questionnaire/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data._token },
                body: JSON.stringify(data)
            });
        }
        const result = await res.json();
        if (!res.ok || !result.success) {
            if (result.errors) {
                qzShowServerErrors(result.errors);
            } else {
                qzNotify(result.message || 'Error submitting questionnaire', 'error', 'Submission Error');
            }
            btn.disabled = false; btn.textContent = 'Submit & Pay';
            return;
        }
        // Success path
        if (window.qzSkipPayment) {
            const mealPlanBase = window.qzMealPlanBase || '/user/pages/nutrition/child';
            window.location.href = mealPlanBase + '/' + encodeURIComponent(result.child_id);
            return;
        }
        qzInitiatePayment(data.plan_slug, data.cycle, result.child_id, btn);
    } catch (e) {
        console.error(e); qzNotify('An unexpected error occurred.', 'error');
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
            const isAlreadyActive = cd.message && (cd.message.toLowerCase().includes('already has an active') || cd.message.toLowerCase().includes('active subscription'));
            qzNotify(
                cd.message || 'Failed to initialize payment.',
                isAlreadyActive ? 'warning' : 'error',
                isAlreadyActive ? 'Plan Already Active' : 'Payment Error'
            );
            btn.textContent = 'Submit & Pay'; btn.disabled = false; return;
        }
        if (cd.gateway === 'free') { window.location.href = '/user/pages/nutrition/child/' + encodeURIComponent(childId); return; }

        if (cd.gateway === 'cashfree') {
            btn.textContent = 'Redirecting...';
            const loadCF = () => {
                try {
                    const cfMode = (window.nutribuddyConfig && window.nutribuddyConfig.cashfreeEnv === 'production') ? 'production' : (cd.mode || 'sandbox');
                    const cashfree = Cashfree({ mode: cfMode });
                    const sessionId = cd.subscription_session_id || cd.subs_session_id || cd.subsSessionId || cd.payment_session_id;

                    if (cd.subscription_session_id || cd.subs_session_id || cd.subsSessionId) {
                        cashfree.subscriptionsCheckout({
                            subsSessionId: sessionId,
                            subscriptionSessionId: sessionId,
                            redirectTarget: '_self'
                        }).then(function (result) {
                            if (result && result.error) {
                                console.error('Cashfree subscription error:', result.error);
                                qzNotify(result.error.message || 'Payment initiation failed.', 'error', 'Payment Error');
                                btn.textContent = 'Submit & Pay';
                                btn.disabled = false;
                            }
                        }).catch(function (err) {
                            console.error('Subscription checkout error:', err);
                            qzNotify('Checkout error: ' + (err.message || err), 'error', 'Checkout Error');
                            btn.textContent = 'Submit & Pay';
                            btn.disabled = false;
                        });
                    } else if (cd.payment_session_id) {
                        cashfree.checkout({ paymentSessionId: cd.payment_session_id, redirectTarget: '_self' });
                    } else {
                        throw new Error('No valid payment session ID received from server.');
                    }
                } catch (err) {
                    console.error('loadCF error:', err);
                    qzNotify('Unable to open payment gateway: ' + (err.message || err), 'error', 'Gateway Error');
                    btn.textContent = 'Submit & Pay';
                    btn.disabled = false;
                }
            };

            if (typeof Cashfree === 'undefined') {
                const s = document.createElement('script');
                s.src = 'https://sdk.cashfree.com/js/v3/cashfree.js';
                s.onload = loadCF;
                s.onerror = function () {
                    qzNotify('Failed to load Cashfree Payment SDK. Please check your internet connection.', 'error', 'Network Error');
                    btn.textContent = 'Submit & Pay';
                    btn.disabled = false;
                };
                document.body.appendChild(s);
            } else {
                loadCF();
            }
        }
    } catch (e) {
        console.error(e); qzNotify('An error occurred during payment init.', 'error');
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
    // C7-FIX-B: qzSubmitPending is the post-login localStorage restore path.
    // Files cannot be stored in localStorage so there is never a file here.
    // Always use JSON (no file to send).
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
        const res = await fetch('/api/nutrition/questionnaire/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data._token },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (!res.ok || !result.success) {
            if (result.errors) {
                qzShowServerErrors(result.errors);
            } else {
                qzNotify(result.message || 'Error submitting questionnaire', 'error', 'Submission Error');
            }
            btn.disabled = false; btn.textContent = 'Submit & Pay';
            return;
        }
        qzInitiatePayment(data.plan_slug, data.cycle, result.child_id, btn);
    } catch (e) {
        console.error(e); qzNotify('An unexpected error occurred.', 'error');
        btn.disabled = false; btn.textContent = 'Submit & Pay';
    }
}
