function initHomeDietQuestionnaire() {
    const form = document.getElementById('homeDietQuestionnaire');
    if (!form) return;

    // Questionnaire answers are submitted only from the authenticated checkout
    // action below. Never let Enter or another submit trigger post to a URL.
    form.addEventListener('submit', (event) => event.preventDefault());

    const questionStep = document.getElementById('homeDietQuestionStep');
    const plansStep = document.getElementById('homeDietPlansStep');
    const message = document.getElementById('homeDietMessage');
    const checkoutMessage = document.getElementById('homeDietCheckoutMessage');
    let childId = null;
    const heightStates = new Map();

    function heightLabel(cm, mode) {
        if (mode === 'cm') return `${cm} cm`;
        const totalInches = Math.round(cm / 2.54);
        return `${Math.floor(totalInches / 12)}'${totalInches % 12}"`;
    }

    function writeHeightAnswer(control, cm) {
        const state = heightStates.get(control.dataset.homeHeightControl);
        state.cm = cm;
        const totalInches = Math.round(cm / 2.54);
        const feet = Math.floor(totalInches / 12);
        const inches = totalInches % 12;
        control.querySelector('[data-home-height-answer]').value = state.mode === 'cm'
            ? `${cm} cm`
            : `${feet}ft ${inches}in (${cm}cm)`;
        updateHeightSuggestion();
    }

    function renderHeightOptions(control) {
        const state = heightStates.get(control.dataset.homeHeightControl);
        const select = control.querySelector('[data-home-height-select]');
        select.replaceChildren(new Option(state.mode === 'cm' ? 'Select height (cm)' : 'Select height (ft / in)', ''));
        for (let cm = 91; cm <= 213; cm++) {
            select.add(new Option(heightLabel(cm, state.mode), String(cm), false, state.cm === cm));
        }
        select.add(new Option('Enter manually…', '__custom__'));
    }

    function updateHeightSuggestion() {
        const weight = Number(form.querySelector('[data-home-height-weight]')?.value);
        form.querySelectorAll('[data-home-height-suggestion]').forEach((suggestion) => {
            if (!Number.isFinite(weight) || weight <= 0) {
                suggestion.hidden = true;
                return;
            }
            const min = Math.max(91, Math.round(Math.sqrt(weight / 24) * 100) - 3);
            const max = Math.min(213, Math.round(Math.sqrt(weight / 13.5) * 100) + 3);
            suggestion.textContent = `Suggested height range: ${min}–${max} cm`;
            suggestion.hidden = false;
        });
    }

    form.querySelectorAll('[data-home-height-control]').forEach((control) => {
        try {
        const state = { mode: 'ft', cm: null };
        heightStates.set(control.dataset.homeHeightControl, state);
        renderHeightOptions(control);

        control.querySelector('[data-home-height-unit]').addEventListener('change', (event) => {
            state.mode = event.target.value;
            renderHeightOptions(control);
            control.querySelector('[data-home-height-custom]').hidden = true;
        });
        control.querySelector('[data-home-height-select]').addEventListener('change', (event) => {
            const custom = event.target.value === '__custom__';
            const customWrap = control.querySelector('[data-home-height-custom]');
            customWrap.hidden = !custom;
            customWrap.classList.remove('has-error');
            if (custom) {
                control.querySelector('[data-home-height-custom-cm]').hidden = state.mode !== 'cm';
                control.querySelector('[data-home-height-custom-ftin]').hidden = state.mode === 'cm';
                return;
            }
            if (event.target.value) writeHeightAnswer(control, Number(event.target.value));
        });
        control.querySelector('[data-home-height-apply]').addEventListener('click', () => {
            let cm;
            if (state.mode === 'cm') {
                const value = Number(control.querySelector('[data-home-height-cm]').value);
                if (value >= 30 && value <= 250) cm = Math.round(value);
            } else {
                const feetInput = control.querySelector('[data-home-height-ft]');
                const inchesInput = control.querySelector('[data-home-height-in]');
                const feet = feetInput.value === '' ? 0 : Number(feetInput.value);
                const inches = inchesInput.value === '' ? 0 : Number(inchesInput.value);
                if ((feetInput.value !== '' || inchesInput.value !== '') && feet >= 0 && feet <= 8 && inches >= 0 && inches <= 11) {
                    const cmValue = Math.round((feet * 12 + inches) * 2.54);
                    if (cmValue >= 86 && cmValue <= 218) cm = cmValue;
                }
            }
            if (cm == null) {
                control.querySelector('[data-home-height-custom]').classList.add('has-error');
                return;
            }
            control.querySelector('[data-home-height-custom]').classList.remove('has-error');
            writeHeightAnswer(control, cm);
            control.querySelector('[data-home-height-custom]').hidden = true;
            renderHeightOptions(control);
        });
        } catch (error) {
            console.error('Unable to initialize the optional height picker.', error);
        }
    });
    form.querySelector('[data-home-height-weight]')?.addEventListener('input', updateHeightSuggestion);

    function showStep(step) {
        const showPlans = step === 'plans';
        questionStep.hidden = showPlans;
        plansStep.hidden = !showPlans;
        document.querySelectorAll('[data-home-diet-step]').forEach((item) => {
            const active = Number(item.dataset.homeDietStep) === (showPlans ? 2 : 1);
            item.classList.toggle('active', active);
            item.classList.toggle('done', showPlans && Number(item.dataset.homeDietStep) === 1);
        });
        if (showPlans) {
            document.getElementById('homeDietBack')?.focus();
        } else {
            questionStep.querySelector('input:not(:disabled), textarea:not(:disabled)')?.focus({ preventScroll: true });
        }
        (showPlans ? plansStep : questionStep).scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function updateConditionalQuestions() {
        const diet = form.querySelector('input[name="q_6"]:checked')?.value?.toLowerCase();
        form.querySelectorAll('[data-hide-for-vegan]').forEach((field) => {
            const hidden = diet === 'vegan';
            field.hidden = hidden;
            field.querySelectorAll('input, textarea, select').forEach((input) => {
                input.disabled = hidden;
                if (hidden) input.required = false;
            });
        });
    }

    form.addEventListener('change', (event) => {
        if (event.target.matches('input[name="q_6"]')) updateConditionalQuestions();
        const group = event.target.closest('[data-answer-group]');
        if (group && group.dataset.multiple === 'true' && event.target.checked) {
            const none = [...group.querySelectorAll('input[type="checkbox"]')].find((input) => input.value.toLowerCase() === 'none');
            if (event.target.value.toLowerCase() === 'none') {
                group.querySelectorAll('input[type="checkbox"]').forEach((input) => { if (input !== event.target) input.checked = false; });
            } else if (none) {
                none.checked = false;
            }
        }
        const field = event.target.closest('.home-diet-question');
        if (field) {
            field.classList.remove('has-error');
            const error = field.querySelector('.home-diet-field-error');
            if (error) error.textContent = '';
        }
    });

    document.getElementById('homeDietShowPlans')?.addEventListener('click', () => {
        updateConditionalQuestions();
        let firstInvalid = null;
        form.querySelectorAll('.home-diet-question').forEach((field) => {
            if (field.hidden) return;
            const group = field.querySelector('[data-answer-group]');
            const valid = group
                ? group.dataset.required !== 'true' || group.querySelector('input:checked') !== null
                : [...field.querySelectorAll('input[required], textarea[required]')].every((input) => input.value.trim() !== '');
            field.classList.toggle('has-error', !valid);
            const error = field.querySelector('.home-diet-field-error');
            if (error) error.textContent = valid ? '' : 'Please answer this question to continue.';
            if (!valid && !firstInvalid) firstInvalid = field;
        });

        message.hidden = true;
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.querySelector('input:not(:disabled), textarea:not(:disabled)')?.focus({ preventScroll: true });
            return;
        }
        showStep('plans');
    });

    document.getElementById('homeDietBack')?.addEventListener('click', () => {
        checkoutMessage.hidden = true;
        checkoutMessage.textContent = '';
        showStep('questions');
    });

    let selectedCycle = 'monthly';
    const cycleLabels = { monthly: 'Per month', quarterly: 'Every 3 months', annually: 'Per year' };
    document.querySelectorAll('[data-home-billing-cycle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            selectedCycle = toggle.dataset.homeBillingCycle;
            document.querySelectorAll('[data-home-billing-cycle]').forEach((item) => {
                const selected = item === toggle;
                item.classList.toggle('is-selected', selected);
                item.setAttribute('aria-pressed', String(selected));
            });

            document.querySelectorAll('.home-diet-plan-card').forEach((card) => {
                const rawPrice = card.dataset[`${selectedCycle}Price`];
                const price = rawPrice === '' ? null : Number(rawPrice);
                const priceElement = card.querySelector('[data-plan-price]');
                const periodElement = card.querySelector('[data-plan-period]');
                const buyButton = card.querySelector('.home-diet-buy');
                const available = price !== null && Number.isFinite(price);

                if (priceElement) {
                    priceElement.textContent = available
                        ? `₹${new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 }).format(price)}`
                        : 'Not available';
                }
                if (periodElement) periodElement.textContent = cycleLabels[selectedCycle];
                if (buyButton) buyButton.disabled = !available;
            });
        });
    });

    document.querySelectorAll('.home-diet-buy').forEach((button) => {
        button.addEventListener('click', () => {
            const planSlug = button.dataset.plan;
            const cycle = selectedCycle;

            const beginPurchase = () => {
                window.nutribuddyConfig.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || window.nutribuddyConfig.csrfToken;
                saveAnswersAndCheckout(planSlug, cycle, button);
            };
            if (window.homeDietIsLoggedIn) {
                beginPurchase();
            } else if (typeof window.openLoginModal === 'function') {
                window.openLoginModal(() => {
                    window.homeDietIsLoggedIn = true;
                    beginPurchase();
                });
            } else {
                checkoutMessage.textContent = 'Please log in to save your answers and continue to checkout.';
                checkoutMessage.hidden = false;
            }
        });
    });

    // Mark enhancement only after every interactive handler is attached. The
    // template's no-JS fallback can then safely distinguish an initialized form.
    form.dataset.enhanced = 'true';

    async function saveAnswersAndCheckout(planSlug, cycle, button) {
        checkoutMessage.hidden = true;
        button.disabled = true;
        const originalText = button.textContent;
        button.textContent = childId ? 'Opening checkout…' : 'Saving answers…';

        try {
            if (!childId) {
                const answers = {};
                for (const [name, value] of new FormData(form)) {
                    const key = name.match(/^q_(\d+)\[\]$/)?.[1];
                    if (key) {
                        (answers[`q_${key}`] ??= []).push(value);
                    } else if (/^q_\d+$/.test(name)) {
                        answers[name] = value;
                    }
                }
                const response = await fetch('/api/nutrition/questionnaire/submit', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.nutribuddyConfig.csrfToken,
                    },
                    body: JSON.stringify({ ...answers, plan_slug: 'basic', cycle }),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success || !result.child_id) {
                    const validation = result.errors ? Object.values(result.errors).flat()[0] : null;
                    throw new Error(validation || result.message || 'We could not save the answers. Please try again.');
                }
                childId = result.child_id;
            }

            if (typeof window.startDietCheckout !== 'function') {
                throw new Error('Checkout is temporarily unavailable. Please try again later.');
            }
            button.textContent = originalText;
            button.disabled = false;
            await window.startDietCheckout(planSlug, button, childId, cycle);
        } catch (error) {
            checkoutMessage.textContent = error.message || 'Unable to continue. Please try again.';
            checkoutMessage.hidden = false;
            button.textContent = originalText;
            button.disabled = false;
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHomeDietQuestionnaire, { once: true });
} else {
    initHomeDietQuestionnaire();
}
