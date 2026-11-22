<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">

<x-head />

<body>
<section class="auth bg-base d-flex flex-wrap">
    <div class="auth-left d-lg-block d-none">
        <div class="d-flex align-items-center flex-column h-100 justify-content-center">
            <img src="{{ asset('assets/images/auth/online_shopping.jpg') }}" alt="">
        </div>
    </div>
    <div class="auth-right py-32 px-24 d-flex flex-column justify-content-center">
        <div class="max-w-464-px mx-auto w-100">
            <h4 class="mb-8">Two-Factor Authentication</h4>
            <p class="mb-32 text-secondary-light text-lg">
                Enter the 6-digit code from your authenticator app, or use a recovery code.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger radius-12 mb-16 px-16 py-8 text-sm">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.2fa.verify') }}" method="POST">
                @csrf
                <div class="icon-field mb-24">
                    <span class="icon top-50 translate-middle-y">
                        <iconify-icon icon="solar:shield-keyhole-outline"></iconify-icon>
                    </span>
                    <input type="text" name="code" inputmode="numeric"
                        class="form-control h-56-px bg-neutral-50 radius-12"
                        placeholder="6-digit code or recovery code"
                        autocomplete="one-time-code" autofocus required>
                </div>

                <button type="submit" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12">
                    Verify
                </button>
            </form>

            <div class="mt-24 text-center">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger-600">
                        Sign out and start over
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<x-script />
</body>
</html>
