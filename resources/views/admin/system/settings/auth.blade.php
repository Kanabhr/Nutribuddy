@extends('layouts.admin')
@php
    $title = 'Auth Settings';
    $subTitle = 'Control login methods available to customers';
@endphp

@section('content')
<style>
    .auth-card {
        background: #fff;
        border-radius: 16px;
        border: 1.5px solid #f1f5f9;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        padding: 28px 30px;
        margin-bottom: 20px;
        transition: 0.25s ease;
    }
    .auth-card:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,0.08);
        border-color: #e2e8f0;
    }
    .auth-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 0;
    }
    .auth-card.has-body .auth-card-header {
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px dashed #e2e8f0;
    }
    .auth-icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .auth-icon-phone { background: #eff6ff; color: #3b82f6; }
    .auth-icon-google { background: #fef2f2; color: #ef4444; }

    /* Toggle Switch */
    .nb-toggle {
        position: relative;
        display: inline-block;
        width: 56px;
        height: 28px;
    }
    .nb-toggle input { display: none; }
    .nb-toggle-slider {
        position: absolute;
        inset: 0;
        background: #e2e8f0;
        border-radius: 100px;
        cursor: pointer;
        transition: 0.3s;
    }
    .nb-toggle-slider:before {
        content: '';
        position: absolute;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #fff;
        top: 3px;
        left: 3px;
        transition: 0.3s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .nb-toggle input:checked + .nb-toggle-slider { background: #22c55e; }
    .nb-toggle input:checked + .nb-toggle-slider:before { transform: translateX(28px); }

    .auth-method-info h6 {
        font-weight: 800;
        font-size: 1rem;
        color: #1e293b;
        margin: 0 0 4px;
    }
    .auth-method-info p {
        font-size: 0.85rem;
        color: #64748b;
        margin: 0;
    }

    .redirect-uri-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 16px;
        font-family: monospace;
        font-size: 0.85rem;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        word-break: break-all;
    }
    .copy-btn {
        background: #e2e8f0;
        border: none;
        border-radius: 8px;
        padding: 5px 12px;
        font-size: 0.78rem;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
        transition: 0.2s;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .copy-btn:hover { background: #cbd5e1; color: #1e293b; }

    .status-badge {
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .badge-active   { background: #dcfce7; color: #16a34a; }
    .badge-inactive { background: #fef3c7; color: #d97706; }

    .warning-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 0.875rem;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>

<div class="row g-4">
    <div class="col-12">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" style="border-radius: 12px;" role="alert">
                <iconify-icon icon="solar:check-circle-bold" style="font-size: 1.2rem;"></iconify-icon>
                {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @php
            $bothDisabled = $settings['phone_login_enabled'] !== '1' && $settings['google_login_enabled'] !== '1';
        @endphp

        @if($bothDisabled)
            <div class="warning-box mb-4">
                <iconify-icon icon="solar:danger-triangle-bold" style="font-size: 1.3rem; color: #d97706; flex-shrink: 0;"></iconify-icon>
                <span><strong>Warning:</strong> Both login methods are disabled. Customers will not be able to log in to the storefront.</span>
            </div>
        @endif

        <form action="{{ route('admin.ecommerce.settings.auth.update') }}" method="POST">
            @csrf

            {{-- ── Phone / OTP Login ─────────────────────────────────── --}}
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="d-flex align-items-center gap-16">
                        <div class="auth-icon-wrap auth-icon-phone">
                            <iconify-icon icon="solar:phone-bold"></iconify-icon>
                        </div>
                        <div class="auth-method-info">
                            <h6>Phone OTP Login</h6>
                            <p>Customers login via their mobile number with a one-time password (OTP).</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-16">
                        <span class="status-badge {{ $settings['phone_login_enabled'] === '1' ? 'badge-active' : 'badge-inactive' }}" id="phoneStatusBadge">
                            {{ $settings['phone_login_enabled'] === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                        <label class="nb-toggle">
                            <input type="checkbox" name="phone_login_enabled" value="1" id="phoneToggle"
                                {{ $settings['phone_login_enabled'] === '1' ? 'checked' : '' }}
                                onchange="updateBadge('phoneStatusBadge', this.checked)">
                            <span class="nb-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ── Google Login ──────────────────────────────────────── --}}
            <div class="auth-card has-body" id="googleCard">
                <div class="auth-card-header">
                    <div class="d-flex align-items-center gap-16">
                        <div class="auth-icon-wrap auth-icon-google">
                            <iconify-icon icon="flat-color-icons:google"></iconify-icon>
                        </div>
                        <div class="auth-method-info">
                            <h6>Google Login</h6>
                            <p>Allow customers to sign in using their Google account via OAuth2.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-16">
                        <span class="status-badge {{ $settings['google_login_enabled'] === '1' ? 'badge-active' : 'badge-inactive' }}" id="googleStatusBadge">
                            {{ $settings['google_login_enabled'] === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                        <label class="nb-toggle">
                            <input type="checkbox" name="google_login_enabled" value="1" id="googleToggle"
                                {{ $settings['google_login_enabled'] === '1' ? 'checked' : '' }}
                                onchange="updateBadge('googleStatusBadge', this.checked); toggleGoogleFields(this.checked)">
                            <span class="nb-toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div id="googleFields" style="{{ $settings['google_login_enabled'] !== '1' ? 'display:none;' : '' }}">
                    {{-- Redirect URI hint --}}
                    <div class="mb-24">
                        <label class="form-label fw-bold text-sm">Authorized Redirect URI <span class="text-secondary-light fw-normal">(add this in Google Cloud Console)</span></label>
                        <div class="redirect-uri-box">
                            <span id="redirectUriText">{{ url('/auth/google/callback') }}</span>
                            <button type="button" class="copy-btn" onclick="copyRedirectUri()">Copy</button>
                        </div>
                        <p class="text-xs text-secondary-light mt-8">
                            Go to <a href="https://console.cloud.google.com" target="_blank" class="text-primary-600">Google Cloud Console</a>
                            → Credentials → Your OAuth Client → Add this URI to "Authorized redirect URIs".
                        </p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Google Client ID</label>
                            <div class="icon-field">
                                <span class="icon"><iconify-icon icon="solar:key-outline"></iconify-icon></span>
                                <input type="text" name="google_client_id"
                                    value="{{ old('google_client_id', $settings['google_client_id']) }}"
                                    class="form-control"
                                    placeholder="xxxxxxxxx.apps.googleusercontent.com">
                            </div>
                            @error('google_client_id')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Google Client Secret</label>
                            <div class="icon-field">
                                <span class="icon"><iconify-icon icon="solar:lock-password-outline"></iconify-icon></span>
                                <input type="password" name="google_client_secret" id="googleSecretInput"
                                    value="{{ old('google_client_secret', $settings['google_client_secret']) }}"
                                    class="form-control"
                                    placeholder="GOCSPX-xxxxxxxxxxxxxxxxxx"
                                    autocomplete="new-password">
                            </div>
                            <div class="mt-6">
                                <label style="font-size: 0.8rem; color: #64748b; cursor: pointer; user-select: none;">
                                    <input type="checkbox" onchange="document.getElementById('googleSecretInput').type = this.checked ? 'text' : 'password'" style="margin-right: 4px;">
                                    Show secret
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-3">
                <button type="submit" class="btn btn-primary-600 px-32">
                    <iconify-icon icon="solar:diskette-bold" class="me-6"></iconify-icon>
                    Save Auth Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateBadge(badgeId, isEnabled) {
        const badge = document.getElementById(badgeId);
        badge.textContent = isEnabled ? 'Enabled' : 'Disabled';
        badge.className = 'status-badge ' + (isEnabled ? 'badge-active' : 'badge-inactive');
    }

    function toggleGoogleFields(show) {
        const fields = document.getElementById('googleFields');
        fields.style.display = show ? 'block' : 'none';
    }

    function copyRedirectUri() {
        const text = document.getElementById('redirectUriText').textContent;
        navigator.clipboard.writeText(text).then(() => {
            const btn = event.currentTarget;
            btn.textContent = 'Copied!';
            btn.style.background = '#dcfce7';
            btn.style.color = '#16a34a';
            setTimeout(() => {
                btn.textContent = 'Copy';
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        });
    }
</script>
@endsection
