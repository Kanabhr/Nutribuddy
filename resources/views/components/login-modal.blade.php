@php
    $phoneLoginEnabled  = \App\Models\Setting::get('phone_login_enabled', '1') === '1';
    $googleLoginEnabled = \App\Models\Setting::get('google_login_enabled', '0') === '1';
@endphp
<div id="nbLoginModal" class="nb-modal-overlay">
    <div class="nb-modal-content">
        <button type="button" class="nb-modal-close" onclick="closeLoginModal()">&times;</button>
        
        <div class="nb-modal-header">
            <img src="{{ asset('img/logo.png') }}" alt="NutriBuddy" class="nb-modal-logo">
            <h2 id="loginModalTitle">Welcome Back! </h2>
            <p id="loginModalSub">Login or sign up to continue your healthy journey.</p>
        </div>

        <div class="nb-modal-body">
            {{-- Step 1: Phone --}}
            @if($phoneLoginEnabled)
            <div id="loginStepPhone" class="login-step active">
                <div class="input-group">
                    <label for="loginPhone">Phone Number</label>
                    <div class="phone-input-wrapper">
                        <span class="prefix">+91</span>
                        <input type="tel" id="loginPhone" placeholder="Enter 10-digit number" maxlength="10">
                    </div>
                </div>
                <button type="button" class="nb-btn-primary w-100 mt-4" onclick="handleSendOtp()">
                    <span class="btn-text">Send OTP</span>
                    <span class="btn-loader d-none"></span>
                </button>
            </div>

            {{-- Step 2: OTP --}}
            <div id="loginStepOtp" class="login-step">
                <div class="input-group">
                    <label>Verify OTP</label>
                    <p class="text-sm text-muted mb-3">Sent to <span id="displayPhone"></span></p>
                    <div class="otp-inputs">
                        <input type="text" maxlength="1" class="otp-field" data-index="0">
                        <input type="text" maxlength="1" class="otp-field" data-index="1">
                        <input type="text" maxlength="1" class="otp-field" data-index="2">
                        <input type="text" maxlength="1" class="otp-field" data-index="3">
                        <input type="text" maxlength="1" class="otp-field" data-index="4">
                        <input type="text" maxlength="1" class="otp-field" data-index="5">
                    </div>
                </div>
                <button type="button" class="nb-btn-primary w-100 mt-4" onclick="handleVerifyOtp()">
                    <span class="btn-text">Verify & Login</span>
                    <span class="btn-loader d-none"></span>
                </button>
                <div class="mt-4 text-center">
                    <p class="text-sm text-muted">Didn't receive code? <button type="button" id="resendBtn" class="nb-link-btn" disabled onclick="handleSendOtp()">Resend in <span id="timer">30</span>s</button></p>
                </div>
            </div>
            @endif

            {{-- Google Login --}}
            @if($googleLoginEnabled)
                @if($phoneLoginEnabled)
                    <div style="display: flex; align-items: center; gap: 12px; margin: 20px 0 4px;">
                        <hr style="flex: 1; border-color: #e2e8f0; margin: 0;">
                        <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">OR</span>
                        <hr style="flex: 1; border-color: #e2e8f0; margin: 0;">
                    </div>
                @endif
                <a id="loginGoogleLink" href="{{ route('frontend.google.redirect') }}" data-default-href="{{ route('frontend.google.redirect') }}"
                   style="display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; padding: 14px 20px; border: 2px solid #e2e8f0; border-radius: 16px; background: #fff; color: #374151; font-weight: 700; font-size: 0.95rem; text-decoration: none; transition: 0.2s; margin-top: 12px;"
                   onmouseover="this.style.borderColor='#4285F4'; this.style.background='#f8f9ff';"
                   onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#fff';">
                    <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Continue with Google
                </a>
            @endif

            @if(!$phoneLoginEnabled && !$googleLoginEnabled)
                <div style="text-align: center; padding: 20px; color: #94a3b8;">
                    <iconify-icon icon="solar:lock-bold" style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></iconify-icon>
                    <p style="margin: 0; font-size: 0.9rem;">Login is currently unavailable. Please contact support.</p>
                </div>
            @endif
        </div>

        <div id="loginError" class="nb-alert nb-alert-danger d-none"></div>
    </div>
</div>

<style>
    .nb-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .nb-modal-overlay.show {
        display: flex;
        opacity: 1;
    }

    .nb-modal-content {
        background: #fff;
        width: 90%;
        max-width: 420px;
        border-radius: 32px;
        padding: 40px;
        position: relative;
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }

    .nb-modal-overlay.show .nb-modal-content {
        transform: scale(1);
    }

    .nb-modal-close {
        position: absolute;
        top: 20px;
        right: 24px;
        background: #f8f9fa;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        font-size: 24px;
        color: #999;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .nb-modal-close:hover {
        background: #eee;
        color: #333;
    }

    .nb-modal-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .nb-modal-logo {
        height: 100px;
    
    }

    .nb-modal-header h2 {
        font-family: 'Fredoka One', cursive;
        color: #333;
        font-size: 1.8rem;
        margin-bottom: 8px;
    }

    .nb-modal-header p {
        color: #777;
        font-size: 0.95rem;
    }

    .input-group label {
        display: block;
        font-weight: 700;
        color: #444;
        margin-bottom: 10px;
        font-size: 0.9rem;
    }

    .phone-input-wrapper {
        display: flex;
        align-items: center;
        border: 2px solid #eee;
        border-radius: 16px;
        padding: 4px 16px;
        transition: border-color 0.3s;
    }

    .phone-input-wrapper:focus-within {
        border-color: var(--pk);
    }

    .phone-input-wrapper .prefix {
        font-weight: 700;
        color: #999;
        margin-right: 12px;
        border-right: 1px solid #eee;
        padding-right: 12px;
    }

    .phone-input-wrapper input {
        border: none;
        outline: none;
        width: 100%;
        padding: 12px 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        letter-spacing: 1px;
    }

    .nb-btn-primary {
        background: linear-gradient(135deg, var(--pk) 0%, #ff4d8d 100%);
        color: #fff;
        border: none;
        border-radius: 16px;
        padding: 16px;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 10px 20px -5px rgba(255, 107, 156, 0.4);
    }

    .nb-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 25px -5px rgba(255, 107, 156, 0.5);
    }

    .nb-btn-primary:active {
        transform: translateY(0);
    }

    .login-step {
        display: none;
    }

    .login-step.active {
        display: block;
        animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .otp-inputs {
        display: flex;
        gap: 8px;
        justify-content: space-between;
    }

    .otp-field {
        flex: 1;
        min-width: 0;
        max-width: 50px;
        height: 55px;
        border: 2px solid #eee;
        border-radius: 12px;
        text-align: center;
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--pk);
        transition: all 0.2s;
        padding: 0;
    }

    @media (max-width: 480px) {
        .otp-field {
            max-width: 40px;
            height: 48px;
            font-size: 1.2rem;
        }
        .nb-modal-content {
            padding: 30px 20px;
            width: 95%;
        }
    }

    .otp-field:focus {
        border-color: var(--pk);
        background: rgba(255, 107, 156, 0.05);
        outline: none;
        box-shadow: 0 0 0 4px rgba(255, 107, 156, 0.1);
    }

    .nb-link-btn {
        background: none;
        border: none;
        color: var(--pk);
        font-weight: 700;
        cursor: pointer;
        padding: 0;
        text-decoration: underline;
    }

    .nb-link-btn:disabled {
        color: #ccc;
        cursor: default;
        text-decoration: none;
    }

    .nb-alert {
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 0.85rem;
        margin-top: 20px;
        text-align: center;
    }

    .nb-alert-danger {
        background: #fff5f5;
        color: #e53e3e;
        border: 1px solid #fed7d7;
    }

    .d-none { display: none !important; }
    .w-100 { width: 100%; }
    .mt-4 { margin-top: 1.5rem; }
    .mb-3 { margin-bottom: 1rem; }
    .text-center { text-align: center; }
    .text-sm { font-size: 0.85rem; }
    .text-muted { color: #888; }
</style>

<script>
    let loginTimer;
    window.__loginSuccessCallback = null;
    
    function openLoginModal(onSuccess = null, options = {}) {
        window.__loginSuccessCallback = onSuccess;
        const googleLink = document.getElementById('loginGoogleLink');
        if (googleLink) {
            googleLink.href = googleLink.dataset.defaultHref;
            if (options.googleRedirect) {
                const googleUrl = new URL(googleLink.href, window.location.origin);
                googleUrl.searchParams.set('redirect', options.googleRedirect);
                googleLink.href = googleUrl.toString();
            }
        }
        document.getElementById('nbLoginModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeLoginModal() {
        document.getElementById('nbLoginModal').classList.remove('show');
        document.body.style.overflow = '';
        resetLoginModal();
    }

    function resetLoginModal() {
        document.getElementById('loginStepPhone').classList.add('active');
        document.getElementById('loginStepOtp').classList.remove('active');
        document.getElementById('loginPhone').value = '';
        document.querySelectorAll('.otp-field').forEach(f => f.value = '');
        document.getElementById('loginError').classList.add('d-none');
        clearInterval(loginTimer);
    }

    function updatePageCsrf(token) {
        if (!token) return;
        document.querySelectorAll('input[name="_token"]').forEach(input => input.value = token);
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
    }

    function currentPageCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async function refreshPageCsrf() {
        const response = await fetch("{{ route('csrf.token') }}", {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const data = await response.json().catch(() => ({}));
        updatePageCsrf(data.csrf_token || '');
        return data.csrf_token || currentPageCsrf();
    }

    async function postLoginJson(url, payload) {
        const makeRequest = () => fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': currentPageCsrf(),
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });

        let response = await makeRequest();
        if (response.status === 419) {
            await refreshPageCsrf();
            response = await makeRequest();
        }

        return response;
    }

    async function handleSendOtp() {
        const phone = document.getElementById('loginPhone').value;
        const btn = event.currentTarget;
        const errorEl = document.getElementById('loginError');

        if (!/^\d{10}$/.test(phone)) {
            showLoginError('Please enter a valid 10-digit phone number.');
            return;
        }

        errorEl.classList.add('d-none');
        btn.disabled = true;
        btn.querySelector('.btn-text').textContent = 'Sending...';

        try {
            const response = await postLoginJson("{{ route('frontend.sendOtp') }}", { phone });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                document.getElementById('loginStepPhone').classList.remove('active');
                document.getElementById('loginStepOtp').classList.add('active');
                document.getElementById('displayPhone').textContent = '+91 ' + phone;
                startTimer();
            } else {
                showLoginError(data.message || 'Failed to send OTP.');
            }
        } catch (err) {
            showLoginError('An error occurred. Please try again.');
        } finally {
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = 'Send OTP';
        }
    }

    async function handleVerifyOtp() {
        const phone = document.getElementById('loginPhone').value;
        const otp = Array.from(document.querySelectorAll('.otp-field')).map(f => f.value).join('');
        const btn = event.currentTarget;

        if (otp.length !== 6) {
            showLoginError('Please enter the 6-digit code.');
            return;
        }

        btn.disabled = true;
        btn.querySelector('.btn-text').textContent = 'Verifying...';

        try {
            const response = await postLoginJson("{{ route('frontend.verifyOtp') }}", {
                phone,
                otp,
                redirect_to: window.location.href
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                btn.querySelector('.btn-text').textContent = 'Success!';
                updatePageCsrf(data.csrf_token);
                
                if (window.__loginSuccessCallback) {
                    window.__loginSuccessCallback(data);
                    closeLoginModal();
                } else {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
                    }
                }
            } else {
                showLoginError(data.message || 'Invalid OTP.');
                btn.disabled = false;
                btn.querySelector('.btn-text').textContent = 'Verify & Login';
            }
        } catch (err) {
            showLoginError('An error occurred. Please try again.');
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = 'Verify & Login';
        }
    }

    function showLoginError(msg) {
        const errorEl = document.getElementById('loginError');
        errorEl.textContent = msg;
        errorEl.classList.remove('d-none');
    }

    function startTimer() {
        let timeLeft = 30;
        const timerEl = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');
        
        resendBtn.disabled = true;
        clearInterval(loginTimer);
        
        loginTimer = setInterval(() => {
            timeLeft--;
            timerEl.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(loginTimer);
                resendBtn.disabled = false;
                timerEl.parentElement.innerHTML = 'Resend Now';
            }
        }, 1000);
    }

    // OTP Field handling
    const otpInputs = document.querySelectorAll('.otp-field');
    otpInputs.forEach((field, index) => {
        field.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value !== '') {
                if (index < otpInputs.length - 1) {
                    setTimeout(() => otpInputs[index + 1].focus(), 10);
                }
            }
        });

        field.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '') {
                if (index > 0) {
                    setTimeout(() => otpInputs[index - 1].focus(), 10);
                }
            }
        });

        field.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, otpInputs.length);
            for (let i = 0; i < pastedData.length; i++) {
                if (index + i < otpInputs.length) {
                    otpInputs[index + i].value = pastedData[i];
                    if (index + i < otpInputs.length - 1) {
                        otpInputs[index + i + 1].focus();
                    } else {
                        otpInputs[index + i].focus();
                    }
                }
            }
        });
    });

    // Phone input validation (allow only digits)
    const loginPhoneInput = document.getElementById('loginPhone');
    if (loginPhoneInput) {
        loginPhoneInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Close on click outside
    document.getElementById('nbLoginModal').addEventListener('click', function(e) {
        if (e.target === this) closeLoginModal();
    });
</script>
