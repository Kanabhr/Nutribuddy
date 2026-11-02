@extends('layouts.user-panel')
@section('title', 'Subscription — NutriBuddy Kids')
@section('panel-page-class', 'panel-subscription')
@section('panel-content')

    <div class="inner-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">Subscription ⭐</span>
        <div style="width:36px"></div>
    </div>


        <div class="page">

               


            <!-- CURRENT PLAN BANNER -->
            <div class="current-plan">
                <div class="cp-left">
                    <div class="cp-badge">⚡ Active Plan</div>
                    <div class="cp-name">Growth Plan</div>
                    <div class="cp-desc">Unlocking the best nutrition for Jaydafsdf's little ones </div>
                </div>
                <div class="cp-right">
                    <div class="cp-price">₹299</div>
                    <div class="cp-period">/month</div>
                    <div class="cp-renew">🔄 Renews on May 3, 2026</div>
                    <button class="cp-manage-btn" onclick="showToast('Opening billing portal...')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <rect x="1" y="4" width="22" height="16" rx="2" />
                            <line x1="1" y1="10" x2="23" y2="10" />
                        </svg>
                        Manage Billing
                    </button>
                </div>
            </div>

            <!-- BILLING TOGGLE -->
            <div class="billing-toggle">
                <span class="toggle-label active" id="lbl-monthly">Monthly</span>
                <button class="toggle-pill" id="toggleBtn" onclick="switchBilling()">
                    <div class="knob"></div>
                </button>
                <span class="toggle-label" id="lbl-yearly">Yearly</span>
                <span class="save-badge">Save 30%</span>
            </div>

            <!-- PLANS GRID -->
            <div class="plans-grid">

                <!-- Starter -->
                <div class="plan-card">
                    <div class="plan-icon" style="background:var(--skl)">🌱</div>
                    <div class="plan-name">Starter</div>
                    <div class="plan-tagline">Perfect for trying it out</div>
                    <div class="plan-price">
                        <span class="currency">₹</span>
                        <span class="amount" id="p1">0</span>
                        <span class="period">/mo</span>
                    </div>
                    <div class="plan-yearly-note" id="n1"></div>
                    <div class="plan-divider"></div>
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> 1 Child profile
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Basic meal plans
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Nutrition tracker
                        </div>
                        <div class="feature-item off">
                            <div class="fi-dot">—</div> Expert consultations
                        </div>
                        <div class="feature-item off">
                            <div class="fi-dot">—</div> Priority delivery
                        </div>
                        <div class="feature-item off">
                            <div class="fi-dot">—</div> Custom diet plans
                        </div>
                    </div>
                    <button class="plan-btn outline" onclick="showToast('Downgrading to Starter...')">Get Started</button>
                </div>

                <!-- Growth (Current) -->
                <div class="plan-card popular current-active">
                    <div class="current-tag">✓ Current</div>
                    <div class="plan-icon" style="background:var(--pkl)">🚀</div>
                    <div class="plan-name">Growth</div>
                    <div class="plan-tagline">Most popular for families</div>
                    <div class="plan-price">
                        <span class="currency">₹</span>
                        <span class="amount" id="p2">299</span>
                        <span class="period">/mo</span>
                    </div>
                    <div class="plan-yearly-note" id="n2"></div>
                    <div class="plan-divider"></div>
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> 3 Child profiles
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Advanced meal plans
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Nutrition tracker
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> 2 Expert
                            consultations/mo
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Priority delivery
                        </div>
                        <div class="feature-item off">
                            <div class="fi-dot">—</div> Custom diet plans
                        </div>
                    </div>
                    <button class="plan-btn ghost">✓ Active Plan</button>
                </div>

                <!-- Premium -->
                <div class="plan-card popular">
                    <div class="popular-tag">⭐ Best Value</div>
                    <div class="plan-icon" style="background:var(--pul)">👑</div>
                    <div class="plan-name">Premium</div>
                    <div class="plan-tagline">Everything for power families</div>
                    <div class="plan-price">
                        <span class="currency">₹</span>
                        <span class="amount" id="p3">599</span>
                        <span class="period">/mo</span>
                    </div>
                    <div class="plan-yearly-note" id="n3"></div>
                    <div class="plan-divider"></div>
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Unlimited Child
                            profiles
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Custom meal plans
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Advanced tracker +
                            insights
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Unlimited
                            consultations
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Priority delivery
                        </div>
                        <div class="feature-item">
                            <div class="fi-dot" style="background:var(--mnl);color:#059669">✓</div> Custom diet plans
                        </div>
                    </div>
                    <button class="plan-btn solid" onclick="showToast('Upgrading to Premium! 🎉')">Upgrade Now</button>
                </div>

            </div>

            <!-- BENEFITS ROW -->
            <div class="benefits-row">
                <div class="benefit-card">
                    <div class="benefit-icon">🚚</div>
                    <div class="benefit-title">Free Delivery</div>
                    <div class="benefit-desc">On all orders above ₹499</div>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">👩‍⚕️</div>
                    <div class="benefit-title">Expert Support</div>
                    <div class="benefit-desc">Nutritionist consultations included</div>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">🔄</div>
                    <div class="benefit-title">Easy Cancel</div>
                    <div class="benefit-desc">No lock-in, cancel anytime</div>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">🛡️</div>
                    <div class="benefit-title">Secure Billing</div>
                    <div class="benefit-desc">100% safe & encrypted payments</div>
                </div>
            </div>

            <!-- BILLING HISTORY -->
            <div class="history-section">
                <div class="history-head">
                    <div class="h-icon">🧾</div>
                    <h3>Billing History</h3>
                </div>
                <div style="overflow-x:auto">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Plan</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-weight:700">#INV-0004</td>
                                <td>Growth Plan</td>
                                <td style="color:var(--muted)">Apr 3, 2026</td>
                                <td style="font-weight:700">₹299</td>
                                <td><span class="status-chip s-paid">✓ Paid</span></td>
                                <td><button class="inv-btn"
                                        onclick="showToast('Downloading invoice...')">Download</button></td>
                            </tr>
                            <tr>
                                <td style="font-weight:700">#INV-0003</td>
                                <td>Growth Plan</td>
                                <td style="color:var(--muted)">Mar 3, 2026</td>
                                <td style="font-weight:700">₹299</td>
                                <td><span class="status-chip s-paid">✓ Paid</span></td>
                                <td><button class="inv-btn"
                                        onclick="showToast('Downloading invoice...')">Download</button></td>
                            </tr>
                            <tr>
                                <td style="font-weight:700">#INV-0002</td>
                                <td>Starter Plan</td>
                                <td style="color:var(--muted)">Feb 3, 2026</td>
                                <td style="font-weight:700">₹0</td>
                                <td><span class="status-chip s-paid">✓ Free</span></td>
                                <td><button class="inv-btn"
                                        onclick="showToast('Downloading invoice...')">Download</button></td>
                            </tr>
                            <tr>
                                <td style="font-weight:700">#INV-0001</td>
                                <td>Starter Plan</td>
                                <td style="color:var(--muted)">Jan 3, 2026</td>
                                <td style="font-weight:700">₹0</td>
                                <td><span class="status-chip s-paid">✓ Free</span></td>
                                <td><button class="inv-btn"
                                        onclick="showToast('Downloading invoice...')">Download</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CANCEL ZONE -->
            <div class="cancel-zone">
                <div class="cancel-head">
                    <div class="c-icon">⚠️</div>
                    <h3>Cancel Subscription</h3>
                </div>
                <div class="cancel-body">
                    <div class="cancel-info">
                        <p>Cancelling will downgrade your account to the Starter (Free) plan at the end of your current
                            billing cycle on <strong>May 3, 2026</strong>. You won't be charged again and will retain access
                            until then.</p>
                    </div>
                    <button class="cancel-btn" onclick="showToast('Cancellation request sent.')">Cancel Plan</button>
                </div>
            </div>

        </div><!-- /page -->
    </div><!-- /main -->

    @push('scripts')
        <script>
            /* Sidebar */
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
            }

            /* Billing toggle */
            let yearly = false;
            const prices = {
                monthly: [0, 299, 599],
                yearly: [0, 209, 419]
            };
            const ids = ['p1', 'p2', 'p3'];
            const notes = ['n1', 'n2', 'n3'];
            const yearlyTotal = [0, 2508, 5028];

            function switchBilling() {
                yearly = !yearly;
                const btn = document.getElementById('toggleBtn');
                btn.classList.toggle('yearly', yearly);
                document.getElementById('lbl-monthly').classList.toggle('active', !yearly);
                document.getElementById('lbl-yearly').classList.toggle('active', yearly);
                ids.forEach((id, i) => {
                    document.getElementById(id).textContent = yearly ? prices.yearly[i] : prices.monthly[i];
                    document.getElementById(notes[i]).textContent = yearly && prices.yearly[i] > 0 ?
                        `₹${yearlyTotal[i]}/year — save ₹${(prices.monthly[i]-prices.yearly[i])*12}` : '';
                });
            }

            /* Toast */
            function showToast(msg) {
                const t = document.getElementById('toast');
                document.getElementById('toastMsg').textContent = msg;
                t.classList.add('show');
                setTimeout(() => t.classList.remove('show'), 3000);
            }
        </script>
    @endpush
@endsection
