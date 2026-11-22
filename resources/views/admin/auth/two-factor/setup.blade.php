@extends('layouts.admin')
@php
    $title    = '2FA Settings';
    $subTitle = 'Security / Two-Factor Authentication';
@endphp
@section('content')
    @include('admin.partials.messages')

    @if (session('error'))
        <div class="alert alert-danger radius-12 mb-24 px-16 py-10 text-sm">{{ session('error') }}</div>
    @endif

    @if (session('recovery_codes'))
        <div class="alert alert-warning radius-12 mb-24 px-16 py-10">
            <p class="fw-bold mb-8">Save these recovery codes now — they will not be shown again.</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach (session('recovery_codes') as $code)
                    <code class="bg-base border radius-6 px-10 py-4 text-sm">{{ $code }}</code>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card border-0 radius-12" style="max-width:580px;">
        <div class="card-header">
            <h5 class="card-title mb-0">Two-Factor Authentication (TOTP)</h5>
        </div>
        <div class="card-body p-24">

            @if ($enabled)
                {{-- 2FA is active --}}
                <div class="d-flex align-items-center gap-10 mb-24">
                    <span class="badge bg-success-100 text-success-600 px-12 py-6 radius-8">
                        <iconify-icon icon="solar:shield-check-bold" class="me-1"></iconify-icon> Enabled
                    </span>
                    <span class="text-secondary-light text-sm">
                        Active since {{ $admin->two_factor_confirmed_at->format('d M Y') }}
                    </span>
                </div>

                <form method="POST" action="{{ route('admin.2fa.disable') }}">
                    @csrf
                    <div class="mb-16">
                        <label class="form-label">Confirm your password to disable 2FA</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                            placeholder="Current password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-danger-600"
                        onclick="return confirm('Disable 2FA? This reduces account security.')">
                        Disable 2FA
                    </button>
                </form>

            @elseif ($pendingSecret)
                {{-- Step 2: scan QR and confirm --}}
                <p class="text-secondary-light mb-16 text-sm">
                    Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to activate.
                </p>

                <div class="mb-16 d-inline-block p-10 border radius-12 bg-white">
                    {!! $qrSvg !!}
                </div>

                <p class="text-xs text-secondary-light mb-24">
                    Can't scan? Enter this secret manually: <code class="text-dark">{{ $pendingSecret }}</code>
                </p>

                <form method="POST" action="{{ route('admin.2fa.confirm') }}">
                    @csrf
                    <div class="mb-16">
                        <label class="form-label">Verification code</label>
                        <input type="text" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6"
                            class="form-control @error('code') is-invalid @enderror"
                            placeholder="6-digit code" autocomplete="one-time-code" autofocus required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Confirm and Activate</button>
                </form>

            @else
                {{-- Step 1: start setup --}}
                <p class="text-secondary-light mb-24 text-sm">
                    Add an extra layer of security. Once enabled, you will need to enter a code from your authenticator app after each sign-in.
                </p>
                <form method="POST" action="{{ route('admin.2fa.enable') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary-600">
                        <iconify-icon icon="solar:shield-keyhole-outline" class="me-1"></iconify-icon>
                        Set up 2FA
                    </button>
                </form>
            @endif

        </div>
    </div>
@endsection
