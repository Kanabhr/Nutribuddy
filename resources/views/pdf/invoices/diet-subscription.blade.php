<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription Invoice</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; padding: 36px; font-size: 12px; }
        .head { border-bottom: 2.5px solid #ec4899; padding-bottom: 18px; }
        .logo { max-height: 70px; max-width: 260px; height: auto; width: auto; display: block; margin-bottom: 10px; }
        h1 { color: #ec4899; float: right; margin: 0; font-size: 26px; font-weight: 800; }
        table { width: 100%; border-collapse: collapse; margin-top: 28px; }
        th, td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-weight: bold; color: #475569; }
        .total { text-align: right; font-size: 16px; font-weight: bold; margin-top: 24px; color: #7c3aed; }
    </style>
</head>
<body>
@php
    $logoBase64 = null;
    $customLogoPath = \App\Models\Setting::get('side_section_logo');
    if ($customLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($customLogoPath)) {
        $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($customLogoPath);
        if (file_exists($fullPath)) {
            $mime = mime_content_type($fullPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
        }
    }
    if (!$logoBase64) {
        $fallback = public_path('img/logo.png');
        if (file_exists($fallback)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($fallback));
        } elseif (file_exists(public_path('assets/images/logo.png'))) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('assets/images/logo.png')));
        }
    }
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'NutriBuddy');
    $companyAddress = \App\Models\Setting::get('address', '');
    $companyGst = \App\Models\Setting::get('gst_number', '');
@endphp
<div class="head">
    <h1>INVOICE</h1>
    @if($logoBase64)
        <img class="logo" src="{{ $logoBase64 }}" alt="{{ $companyName }}">
    @else
        <div style="font-size: 20px; font-weight: bold; color: #ec4899; margin-bottom: 6px;">{{ $companyName }}</div>
    @endif
    <p>
        <strong>{{ $companyName }}</strong><br>
        @if($companyAddress) {{ $companyAddress }}<br> @endif
        @if($companyGst) GSTIN: {{ $companyGst }} @endif
    </p>
</div>
<table>
    <tr>
        <th>Bill to</th>
        <th>Plan details</th>
    </tr>
    <tr>
        <td>
            <strong>{{ $subscription->user->name }}</strong><br>
            {{ $subscription->user->email }}<br>
            {{ $subscription->user->phone ?? '' }}
        </td>
        <td>
            <strong>{{ $subscription->subscriptionPlan?->name ?? $subscription->model }}</strong><br>
            Child: {{ $subscription->child?->name }}<br>
            Valid: {{ $subscription->starts_at?->format('d M Y') }} – {{ $subscription->expires_at?->format('d M Y') }}<br>
            Payment: {{ strtoupper($subscription->payment_status) }}
        </td>
    </tr>
</table>
<div class="total">Total paid: ₹{{ number_format((float) $subscription->amount_paid, 2) }}</div>
<p style="margin-top: 45px; color: #6b7280; font-size: 10px;">This is a computer-generated invoice and does not require a signature.</p>
</body>
</html>
