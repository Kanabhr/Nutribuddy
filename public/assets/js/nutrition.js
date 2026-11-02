// Nutrition Module Local State & Logic

// ========================================
// Global State
// ========================================
let totalCals = 2100;
let consumedCals = 0;
let currentProtein = 0, currentCarbs = 0, currentFats = 0;

// Targets (blade se overwrite honge)
let proteinTarget = 152;
let carbsTarget = 236;
let fatsTarget = 61;

// ========================================
// Helper Functions (hoisted)
// ========================================

function updateCalories(addedCals) {
    const consumedEl = document.getElementById('calsConsumed');
    const remainingEl = document.getElementById('calsRemaining');
    const ringProgress = document.querySelector('.cal-ring-progress');
    const ringContainer = document.querySelector('.cal-ring-container');

    if (!consumedEl || !remainingEl || !ringProgress) return;

    // 🔥 Target from blade data attribute
    const bladeTarget = parseInt(ringContainer?.getAttribute('data-target-cals')) || 2100;
    if (bladeTarget > 0) {
        totalCals = bladeTarget;
    }

    consumedCals += addedCals;
    if (consumedCals < 0) consumedCals = 0;
    // ✅ FIX #3: Do NOT clamp consumedCals to totalCals — allow overshoot
    // Ring caps visually via Math.min on percent below.

    const remaining = Math.max(0, totalCals - consumedCals);

    consumedEl.innerText = Math.round(consumedCals);
    remainingEl.innerText = Math.round(remaining);

    const dailyCaloriesConsumed = document.getElementById('dailyCaloriesConsumed');
    const dailyCaloriesProgress = document.getElementById('dailyCaloriesProgress');
    const dailyCaloriesStatus = document.getElementById('dailyCaloriesStatus');
    const dailyProgressBar = dailyCaloriesProgress?.closest('[role="progressbar"]');
    const dailyPercent = Math.min((consumedCals / totalCals) * 100, 100);

    if (dailyCaloriesConsumed) dailyCaloriesConsumed.innerText = Math.round(consumedCals);
    if (dailyCaloriesProgress) dailyCaloriesProgress.style.width = dailyPercent + '%';
    if (dailyProgressBar) dailyProgressBar.setAttribute('aria-valuenow', String(Math.round(consumedCals)));
    if (dailyCaloriesStatus) {
        dailyCaloriesStatus.innerText = consumedCals > totalCals
            ? Math.round(consumedCals - totalCals) + ' kcal over goal'
            : Math.round(remaining) + ' kcal remaining';
    }

    // Ring math
    const circumference = 251.3;
    const percent = Math.min(consumedCals / totalCals, 1);
    const dashoffset = circumference - (percent * circumference);

    ringProgress.style.strokeDasharray = circumference;
    ringProgress.style.strokeDashoffset = dashoffset;

    // 🔥 Dynamic color based on percentage
    let ringColor = '#e5e7eb'; // grey (0%)
    if (percent >= 0.8) {
        ringColor = '#22c55e'; // green (80%+)
    } else if (percent >= 0.5) {
        ringColor = '#facc15'; // yellow (50-80%)
    } else if (percent > 0) {
        ringColor = '#f59e0b'; // orange (1-50%)
    }

    ringProgress.style.stroke = ringColor;

    // Update new Energy Card in Insights tab
    const energyCardVal = document.getElementById('calsConsumedEnergyCard');
    const energyBarFill = document.getElementById('energyBarFill');
    const energyBadge = document.getElementById('energyBadge');

    if (energyCardVal) energyCardVal.innerText = Math.round(consumedCals);
    if (energyBarFill) energyBarFill.style.width = (percent * 100) + '%';

    if (energyBadge) {
        if ((percent * 100) < 80) {
            energyBadge.innerText = 'Needs a boost';
            energyBadge.style.background = '#fef3c7';
            energyBadge.style.color = '#b45309';
        } else {
            energyBadge.innerText = 'On track ✓';
            energyBadge.style.background = '#ecfccb';
            energyBadge.style.color = '#4d7c0f';
        }
    }
}

function updateMacros() {
    const pFill = document.querySelector('.macro-fill.protein');
    const cFill = document.querySelector('.macro-fill.carbs');
    const fFill = document.querySelector('.macro-fill.fats');

    const pText = document.querySelectorAll('.macro-val')[0];
    const cText = document.querySelectorAll('.macro-val')[1];
    const fText = document.querySelectorAll('.macro-val')[2];

    if (pFill) pFill.style.width = Math.min((currentProtein / proteinTarget) * 100, 100) + '%';
    if (cFill) cFill.style.width = Math.min((currentCarbs / carbsTarget) * 100, 100) + '%';
    if (fFill) fFill.style.width = Math.min((currentFats / fatsTarget) * 100, 100) + '%';

    if (pText) pText.innerText = Math.round(currentProtein) + ' / ' + proteinTarget + 'g';
    if (cText) cText.innerText = Math.round(currentCarbs) + ' / ' + carbsTarget + 'g';
    if (fText) fText.innerText = Math.round(currentFats) + ' / ' + fatsTarget + 'g';

    const dailyProtein = document.getElementById('dailyProteinConsumed');
    const dailyCarbs = document.getElementById('dailyCarbsConsumed');
    const dailyFat = document.getElementById('dailyFatConsumed');
    if (dailyProtein) dailyProtein.innerText = currentProtein.toFixed(1);
    if (dailyCarbs) dailyCarbs.innerText = currentCarbs.toFixed(1);
    if (dailyFat) dailyFat.innerText = currentFats.toFixed(1);
}

function updateSwapBtnVisibility(mealSection) {
    if (!mealSection) return;
    const swapBtn = mealSection.querySelector('.meal-swap-btn');
    if (!swapBtn) return;
    const hasTakenItem = mealSection.querySelector('.meal-item.meal-taken') !== null;
    if (hasTakenItem) {
        swapBtn.style.display = 'none';
    } else {
        swapBtn.style.display = '';
    }
}

// ✅ NEW HELPER: Update per-meal consumed header (consumed / total)
function updateMealHeader(mealSection, deltaCals, deltaProtein) {
    if (!mealSection) return;
    const kEl = mealSection.querySelector('.meal-consumed-kcal');
    const pEl = mealSection.querySelector('.meal-consumed-protein');

    if (kEl) {
        const cur = parseFloat(kEl.textContent) || 0;
        kEl.textContent = Math.max(0, Math.round(cur + deltaCals));
    }
    if (pEl) {
        const cur = parseFloat(pEl.textContent) || 0;
        pEl.textContent = Math.max(0, cur + deltaProtein).toFixed(1);
    }
}

// ========================================
// Main Logic
// ========================================

document.addEventListener('DOMContentLoaded', () => {

    // ----------------------------------------
    // 0. Initialize targets from blade
    // ----------------------------------------
    const ringContainer = document.querySelector('.cal-ring-container');
    if (ringContainer) {
        const bladeTarget = parseInt(ringContainer.getAttribute('data-target-cals'));
        if (bladeTarget > 0) {
            totalCals = bladeTarget;
        }
    }

    // Initial check for swap button visibility across all meal sections
    document.querySelectorAll('.meal-section').forEach(section => {
        updateSwapBtnVisibility(section);
    });

    // Protein/Fat targets from macro-val text (parse karo)
    const macroVals = document.querySelectorAll('.macro-val');
    if (macroVals[0]) {
        const match = macroVals[0].innerText.match(/\/\s*(\d+)/);
        if (match) proteinTarget = parseInt(match[1]);
    }
    if (macroVals[1]) {
        const match = macroVals[1].innerText.match(/\/\s*(\d+)/);
        if (match) carbsTarget = parseInt(match[1]);
    }
    if (macroVals[2]) {
        const match = macroVals[2].innerText.match(/\/\s*(\d+)/);
        if (match) fatsTarget = parseInt(match[1]);
    }

    // ----------------------------------------
    // 1. Tab Switching
    // ----------------------------------------
    const tabs = document.querySelectorAll('.nutri-tab');
    const tabContents = document.querySelectorAll('.nutri-tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            tab.classList.add('active');
            const targetId = tab.getAttribute('data-target');
            document.getElementById(targetId)?.classList.add('active');
            document.querySelector('.nutrition-container')?.classList.toggle('nutri-view-active', targetId === 'tab-nutri');
        });
    });

    // ----------------------------------------
    // 2. Date Selector
    // ----------------------------------------
    const dateItems = document.querySelectorAll('.nutri-date-item');
    dateItems.forEach(item => {
        item.addEventListener('click', () => {
            dateItems.forEach(d => d.classList.remove('active'));
            item.classList.add('active');
        });
    });

    // ----------------------------------------
    // 3. Chart Filter
    // ----------------------------------------
    const chartBtns = document.querySelectorAll('.chart-filter-btn');
    chartBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            chartBtns.forEach(b => {
                b.classList.remove('active');
                b.style.background = '#fff';
                b.style.color = 'var(--muted)';
            });
            btn.classList.add('active');
            btn.style.background = 'var(--pkl)';
            btn.style.color = 'var(--pk)';

            const bars = document.querySelectorAll('#tab-calories > div.nutri-card:nth-child(2) > div:nth-child(3) > div');
            bars.forEach(bar => {
                const randomHeight = Math.floor(Math.random() * (95 - 20 + 1) + 20);
                bar.style.height = `${randomHeight}%`;
            });
        });
    });

    // ----------------------------------------
    // 4. Swap Modal
    // ----------------------------------------
    const swapBtns = document.querySelectorAll('.meal-swap-btn');
    const swapModal = document.getElementById('swapFoodModal');
    const closeSwapBtn = document.getElementById('closeSwapModal');
    const candidatesList = document.getElementById('swap-candidates-list');

    let currentSwapTarget = null;

    swapBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const mealSection = e.currentTarget.closest('.meal-section');
            if (mealSection && mealSection.querySelector('.meal-item.meal-taken')) {
                return;
            }
            currentSwapTarget = mealSection ? mealSection.querySelector('.food-list') : null;

            if (candidatesList) {
                candidatesList.innerHTML = '';
                try {
                    const alternatives = JSON.parse(e.currentTarget.getAttribute('data-alternatives') || '[]');
                    if (alternatives.length === 0) {
                        candidatesList.innerHTML = '<p style="text-align:center;color:var(--muted);padding:20px;">No exact alternatives found for this slot.</p>';
                    } else {
                        alternatives.forEach(alt => {
                            const div = document.createElement('div');
                            div.className = 'search-result-item';
                            div.innerHTML = `
                                <div>
                                    <div class="food-name">${alt.food_name}</div>
                                    <div class="food-details">Match Score: ${alt.score}% · P: ${alt.protein_g}g</div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span class="food-cal">${alt.energy_kcal} Kcal</span>
                                    <button class="search-result-add">Swap</button>
                                </div>
                            `;

                            const swapActionBtn = div.querySelector('.search-result-add');
                            swapActionBtn.addEventListener('click', () => {
                                if (currentSwapTarget) {
                                    const firstItem = currentSwapTarget.querySelector('.food-item');
                                    if (firstItem) {
                                        firstItem.querySelector('.food-name').innerText = alt.food_name;
                                        firstItem.querySelector('.food-details').innerText = `P: ${alt.protein_g}g`;
                                        firstItem.querySelector('.food-cal').innerText = `${alt.energy_kcal} Kcal`;

                                        firstItem.setAttribute('data-cals', alt.energy_kcal);
                                        firstItem.setAttribute('data-p', alt.protein_g);
                                    }
                                    closeSwapModalFn();
                                }
                            });
                            candidatesList.appendChild(div);
                        });
                    }
                } catch (err) {
                    console.error('Error parsing alternatives', err);
                }
            }

            if (swapModal) swapModal.classList.add('active');
        });
    });

    const closeSwapModalFn = () => {
        if (swapModal) swapModal.classList.remove('active');
        currentSwapTarget = null;
    };

    if (closeSwapBtn) closeSwapBtn.addEventListener('click', closeSwapModalFn);
    if (swapModal) {
        swapModal.addEventListener('click', (e) => {
            if (e.target === swapModal) closeSwapModalFn();
        });
    }

    // ----------------------------------------
    // 5. Meal Item Tick (DB persistence)
    // ----------------------------------------
    const mealItems = document.querySelectorAll('.meal-item');

    // ✅ FIX #2: Reset counters before init (safety — prevents drift)
    consumedCals = 0;
    currentProtein = 0;
    currentCarbs = 0;
    currentFats = 0;

    // Init: already-consumed items ka count set karo
    mealItems.forEach(item => {
        if (item.classList.contains('meal-taken')) {
            const cals = parseFloat(item.getAttribute('data-cals')) || 0;
            const p = parseFloat(item.getAttribute('data-p')) || 0;
            const c = parseFloat(item.getAttribute('data-c')) || 0;
            const f = parseFloat(item.getAttribute('data-f')) || 0;

            updateCalories(cals);
            currentProtein += p;
            currentCarbs += c;
            currentFats += f;
        }
    });
    updateMacros();

    // CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Click pe toggle + DB save
    mealItems.forEach(item => {
        item.addEventListener('click', async function (e) {
            const childId = this.getAttribute('data-child-id');
            const mealId = this.getAttribute('data-meal-id');
            const foodId = this.getAttribute('data-food-id');

            const isCurrentlyTaken = this.classList.contains('meal-taken');
            const cals = parseFloat(this.getAttribute('data-cals')) || 0;
            const p = parseFloat(this.getAttribute('data-p')) || 0;
            const c = parseFloat(this.getAttribute('data-c')) || 0;
            const f = parseFloat(this.getAttribute('data-f')) || 0;

            // Get meal section for header update
            const mealSection = this.closest('.meal-section');

            if (isCurrentlyTaken) {
                // Uncheck item
                this.classList.remove('meal-taken');
                this.setAttribute('data-consumed', '0');
                updateCalories(-cals);
                currentProtein = Math.max(0, currentProtein - p);
                currentCarbs = Math.max(0, currentCarbs - c);
                currentFats = Math.max(0, currentFats - f);

                // ✅ Update meal header (subtract)
                updateMealHeader(mealSection, -cals, -p);
            } else {
                // Check item
                this.classList.add('meal-taken');
                this.setAttribute('data-consumed', '1');
                updateCalories(cals);
                currentProtein += p;
                currentCarbs += c;
                currentFats += f;

                // ✅ Update meal header (add)
                updateMealHeader(mealSection, cals, p);
            }

            updateMacros();
            updateSwapBtnVisibility(mealSection);

            // DB save
            if (!childId || !foodId) {
                console.warn('Missing child_id or food_id — not saving to DB');
                return;
            }

            try {
                const response = await fetch('/nutrition/toggle-item', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        child_id: childId,
                        food_id: foodId,
                        meal_id: mealId,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    if (data.is_consumed) {
                        this.classList.add('meal-taken');
                        this.setAttribute('data-consumed', '1');
                    } else {
                        this.classList.remove('meal-taken');
                        this.setAttribute('data-consumed', '0');
                    }
                } else {
                    console.error('Save failed:', data.message);

                    // Revert UI
                    if (isCurrentlyTaken) {
                        this.classList.add('meal-taken');
                        this.setAttribute('data-consumed', '1');
                        updateCalories(cals);
                        currentProtein += p;
                        currentCarbs += c;
                        currentFats += f;
                        updateMealHeader(mealSection, cals, p);
                    } else {
                        this.classList.remove('meal-taken');
                        this.setAttribute('data-consumed', '0');
                        updateCalories(-cals);
                        currentProtein = Math.max(0, currentProtein - p);
                        currentCarbs = Math.max(0, currentCarbs - c);
                        currentFats = Math.max(0, currentFats - f);
                        updateMealHeader(mealSection, -cals, -p);
                    }
                    updateMacros();
                }
                updateSwapBtnVisibility(mealSection);
            } catch (err) {
                console.error('Network error:', err);
                // Revert UI
                if (isCurrentlyTaken) {
                    this.classList.add('meal-taken');
                    this.setAttribute('data-consumed', '1');
                    updateCalories(cals);
                    currentProtein += p;
                    currentCarbs += c;
                    currentFats += f;
                    updateMealHeader(mealSection, cals, p);
                } else {
                    this.classList.remove('meal-taken');
                    this.setAttribute('data-consumed', '0');
                    updateCalories(-cals);
                    currentProtein = Math.max(0, currentProtein - p);
                    currentCarbs = Math.max(0, currentCarbs - c);
                    currentFats = Math.max(0, currentFats - f);
                    updateMealHeader(mealSection, -cals, -p);
                }
                updateMacros();
                updateSwapBtnVisibility(mealSection);
            }
        });
    });

    const nutriDayButtons = document.querySelectorAll('.nv-day-button');
    const nutriDayPanels = document.querySelectorAll('.nv-day-panel');

    nutriDayButtons.forEach(button => {
        button.addEventListener('click', () => {
            nutriDayButtons.forEach(dayButton => {
                const isSelected = dayButton === button;
                dayButton.classList.toggle('active', isSelected);
                dayButton.setAttribute('aria-selected', String(isSelected));
            });
            nutriDayPanels.forEach(panel => {
                panel.hidden = panel.dataset.nvPanel !== button.dataset.nvDay;
            });
        });
    });

    const foodModal = document.getElementById('nvFoodModal');
    const foodModalDialog = foodModal?.querySelector('.nv-modal-dialog');
    const foodModalTitle = document.getElementById('nvFoodModalTitle');
    const foodModalPortion = document.getElementById('nvFoodModalPortion');
    const foodNutrients = document.getElementById('nvFoodNutrients');
    const closeFoodModalButton = document.getElementById('nvFoodModalClose');
    let lastFoodButton = null;

    const nutrientInfo = {
        energy_kcal: ['Energy', 'kcal'],
        protein_g: ['Protein', 'g'],
        carb_g: ['Carbohydrates', 'g'],
        fat_g: ['Total fat', 'g'],
        fibre_total_g: ['Dietary fiber', 'g'],
        free_sugar_g: ['Free sugars', 'g'],
        sfa_g: ['Saturated fat', 'g'],
        mufa_g: ['Monounsaturated fat', 'g'],
        pufa_g: ['Polyunsaturated fat', 'g'],
        calcium_mg: ['Calcium', 'mg'],
        iron_mg: ['Iron', 'mg'],
        zinc_mg: ['Zinc', 'mg'],
        magnesium_mg: ['Magnesium', 'mg'],
        vit_c_mg: ['Vitamin C', 'mg'],
        vit_d_ug: ['Vitamin D', 'µg'],
        b1_mg: ['Vitamin B1', 'mg'],
        b2_mg: ['Vitamin B2', 'mg'],
        b3_mg: ['Vitamin B3', 'mg'],
        b6_mg: ['Vitamin B6', 'mg'],
        folate_ug: ['Folate', 'µg'],
        b12_ug: ['Vitamin B12', 'µg']
    };

    const nutrientSections = [
        { title: 'Energy & macros', keys: ['energy_kcal', 'protein_g', 'carb_g', 'fat_g', 'fibre_total_g', 'free_sugar_g'] },
        { title: 'Fat profile', keys: ['sfa_g', 'mufa_g', 'pufa_g'] },
        { title: 'Minerals', keys: ['calcium_mg', 'iron_mg', 'zinc_mg', 'magnesium_mg'] },
        { title: 'Vitamins', keys: ['vit_c_mg', 'vit_d_ug', 'b1_mg', 'b2_mg', 'b3_mg', 'b6_mg', 'folate_ug', 'b12_ug'] }
    ];

    function appendNutrientGroup(title, keys, nutrients) {
        const entries = keys.filter(key => Object.prototype.hasOwnProperty.call(nutrients, key) && nutrients[key] !== null);
        if (!entries.length) return;

        const group = document.createElement('section');
        group.className = 'nv-nutrient-group';
        const heading = document.createElement('h3');
        heading.textContent = title;
        group.appendChild(heading);

        entries.forEach(key => {
            const row = document.createElement('div');
            row.className = 'nv-nutrient-row';
            const label = document.createElement('span');
            label.textContent = nutrientInfo[key]?.[0] || key.replaceAll('_', ' ');
            const value = document.createElement('strong');
            const number = Number(nutrients[key]);
            const formatted = Number.isFinite(number)
                ? number.toLocaleString(undefined, { maximumFractionDigits: 2 })
                : String(nutrients[key]);
            value.textContent = `${formatted} ${nutrientInfo[key]?.[1] || ''}`.trim();
            row.append(label, value);
            group.appendChild(row);
        });

        foodNutrients.appendChild(group);
    }

    function closeFoodModal() {
        if (!foodModal) return;
        foodModal.classList.remove('active');
        foodModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('nv-modal-open');
        lastFoodButton?.focus();
    }

    document.querySelectorAll('.nv-food-button').forEach(button => {
        button.addEventListener('click', () => {
            if (!foodModal || !foodNutrients) return;
            lastFoodButton = button;
            foodModalTitle.textContent = button.dataset.nvFoodName || 'Food nutrients';
            foodModalPortion.textContent = button.dataset.nvFoodPortion
                ? `Serving: ${button.dataset.nvFoodPortion}`
                : 'Nutrient values per serving';
            foodNutrients.replaceChildren();

            let nutrients = {};
            try {
                nutrients = JSON.parse(button.dataset.nvFoodNutrients || '{}');
            } catch (error) {
                console.error('Unable to read food nutrient data.', error);
            }

            const knownKeys = new Set(nutrientSections.flatMap(section => section.keys));
            nutrientSections.forEach(section => appendNutrientGroup(section.title, section.keys, nutrients));
            const otherKeys = Object.keys(nutrients).filter(key => !knownKeys.has(key));
            appendNutrientGroup('Other nutrients', otherKeys, nutrients);

            foodModal.classList.add('active');
            foodModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('nv-modal-open');
            foodModalDialog?.focus();
        });
    });

    closeFoodModalButton?.addEventListener('click', closeFoodModal);
    foodModal?.addEventListener('click', event => {
        if (event.target === foodModal) closeFoodModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && foodModal?.classList.contains('active')) closeFoodModal();
    });

    // ✅ FIX #1: REMOVED the "Init ring - by CR" block that was resetting the ring.
    // The init loop above already calls updateCalories() for consumed items,
    // which correctly drives the ring, macros, and energy card from the start.
});