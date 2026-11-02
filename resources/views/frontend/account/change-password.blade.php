@extends('layouts.user-panel')
@section('title', 'Change Password — NutriBuddy Kids')
@section('panel-page-class', 'panel-change-password')

@section('panel-content')
    <div class="inner-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">Change Password 🔒</span>
        <div style="width:36px"></div>
    </div>
    <div class="main">


        <div class="page">

            <!-- <div class="page-header fade-in d1">
                        <div class="page-header-left">
                            <h1>Change Password </h1>
                            <p>Manage your profile details and account settings</p>
                        </div>
                    </div> -->

            <div class="welcome-banner d1">
                <div class="welcome-text" style="position:relative;z-index:1">
                    <h2>Change Password </h2>
                    <p>Manage your profile details and account settings</p>
                </div>
                <div class="welcome-right">
                    <div class="banner-stat">
                        <div class="bs-num">100%</div>
                        <div class="bs-lbl">Secure</div>
                    </div>
                    <div class="banner-stat">
                        <div class="bs-num">1</div>
                        <div class="bs-lbl">Password</div>
                    </div>
                    <div class="banner-emoji">🔒</div>
                </div>
            </div>

            <div class="cp-card">
                <div class="cp-card-title">
                    <div class="title-icon">🔒</div>
                    Update Your Password
                </div>

                <!-- Current Password -->
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <div class="input-wrap">
                        <input type="password" class="form-input" id="currentPwd" placeholder="Enter current password">
                        <button class="toggle-eye" onclick="toggleVis('currentPwd',this)" type="button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- New + Confirm -->
                <div class="form-row">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">New Password</label>
                        <div class="input-wrap">
                            <input type="password" class="form-input" id="newPwd" placeholder="Enter new password"
                                oninput="checkStrength(this.value)">
                            <button class="toggle-eye" onclick="toggleVis('newPwd',this)" type="button">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        <!-- strength -->
                        <div class="strength-wrap">
                            <div class="strength-bar">
                                <div class="strength-seg" id="s1"></div>
                                <div class="strength-seg" id="s2"></div>
                                <div class="strength-seg" id="s3"></div>
                                <div class="strength-seg" id="s4"></div>
                            </div>
                            <div class="strength-label" id="strengthLabel">Enter a password</div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-wrap">
                            <input type="password" class="form-input" id="confirmPwd" placeholder="Confirm new password"
                                oninput="checkMatch()">
                            <button class="toggle-eye" onclick="toggleVis('confirmPwd',this)" type="button">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- hints -->
                <div class="hints" style="margin-top:14px">
                    <div class="hint" id="h-len">
                        <div class="dot">✓</div> At least 8 characters
                    </div>
                    <div class="hint" id="h-up">
                        <div class="dot">✓</div> One uppercase letter
                    </div>
                    <div class="hint" id="h-num">
                        <div class="dot">✓</div> One number
                    </div>
                    <div class="hint" id="h-match">
                        <div class="dot">✓</div> Passwords match
                    </div>
                </div>

                <div class="divider"></div>

                <div class="form-actions">
                    <button class="btn-cancel">Cancel</button>
                    <button class="btn-save" onclick="handleSave()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Update Password
                    </button>
                </div>
            </div>

        </div>

        <!-- Toast -->
        <div class="toast" id="toast">
            <div class="t-icon">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </div>
            Password updated successfully!
        </div>

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

                /* Eye toggle */
                function toggleVis(id, btn) {
                    const inp = document.getElementById(id);
                    const show = inp.type === 'password';
                    inp.type = show ? 'text' : 'password';
                    btn.innerHTML = show ?
                        `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>` :
                        `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
                }

                /* Strength */
                const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
                const labels = ['Weak', 'Fair', 'Good', 'Strong'];

                function checkStrength(v) {
                    let s = 0;
                    if (v.length >= 8) s++;
                    if (/[A-Z]/.test(v)) s++;
                    if (/[0-9]/.test(v)) s++;
                    if (/[^A-Za-z0-9]/.test(v)) s++;
                    for (let i = 1; i <= 4; i++) {
                        const seg = document.getElementById('s' + i);
                        seg.style.background = i <= s ? colors[s - 1] : 'var(--border)';
                    }
                    document.getElementById('strengthLabel').textContent = v ? labels[Math.min(s, 4) - 1] || 'Too short' :
                        'Enter a password';
                    document.getElementById('strengthLabel').style.color = v ? colors[Math.min(s, 4) - 1] : 'var(--muted)';
                    updateHints(v);
                }

                function updateHints(v) {
                    toggle('h-len', v.length >= 8);
                    toggle('h-up', /[A-Z]/.test(v));
                    toggle('h-num', /[0-9]/.test(v));
                    checkMatch();
                }

                function checkMatch() {
                    const n = document.getElementById('newPwd').value;
                    const c = document.getElementById('confirmPwd').value;
                    toggle('h-match', c && n === c);
                }

                function toggle(id, ok) {
                    document.getElementById(id).classList.toggle('ok', ok);
                }

                /* Save */
                function handleSave() {
                    const cur = document.getElementById('currentPwd').value;
                    const n = document.getElementById('newPwd').value;
                    const c = document.getElementById('confirmPwd').value;
                    if (!cur || !n || !c) {
                        alert('Please fill all fields.');
                        return;
                    }
                    if (n !== c) {
                        alert('Passwords do not match.');
                        return;
                    }
                    if (n.length < 8) {
                        alert('Password must be at least 8 characters.');
                        return;
                    }
                    const t = document.getElementById('toast');
                    t.classList.add('show');
                    setTimeout(() => t.classList.remove('show'), 3000);
                    document.getElementById('currentPwd').value = '';
                    document.getElementById('newPwd').value = '';
                    document.getElementById('confirmPwd').value = '';
                    checkStrength('');
                    ['h-len', 'h-up', 'h-num', 'h-match'].forEach(id => document.getElementById(id).classList.remove('ok'));
                }
            </script>
        @endpush
@endsection