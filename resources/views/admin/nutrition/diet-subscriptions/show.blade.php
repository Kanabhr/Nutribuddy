@extends('layouts.admin')

@php
    $title = 'Diet Subscription Details';
    $subTitle = 'Diet Services Management / Subscriptions';
    $child = $subscription->child;
    $customer = $subscription->user;
    $planName = $subscription->subscriptionPlan?->name ?: ($subscription->model ?: 'Unknown plan');
    $statusClasses = [
        'active' => 'success',
        'pending' => 'warning',
        'expired' => 'danger',
        'cancelled' => 'secondary',
        'suspended' => 'secondary',
    ];
    $paymentClasses = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'];
@endphp

@section('content')
<style>
    .nb-subscription-detail { color: #34423a; }
    .nb-subscription-detail .nb-detail-header { align-items: center; display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; margin-bottom: 20px; }
    .nb-subscription-detail .nb-detail-header p { color: #718076; margin: 5px 0 0; }
    .nb-subscription-detail .nb-summary-card, .nb-subscription-detail .nb-detail-card { border: 1px solid #e8eee8; border-radius: 14px; box-shadow: 0 8px 24px rgba(18, 26, 21, .035); height: 100%; }
    .nb-subscription-detail .nb-summary-card { min-height: 112px; }
    .nb-subscription-detail .nb-summary-label { color: #718076; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .nb-subscription-detail .nb-summary-value { color: #173d31; font-size: 20px; font-weight: 800; margin-top: 10px; }
    .nb-subscription-detail .nb-detail-card .card-body { padding: 22px; }
    .nb-subscription-detail .nb-card-title { color: #173d31; font-size: 16px !important; font-weight: 800; margin: 0 0 18px; }
    .nb-subscription-detail .nb-customer-avatar { align-items: center; background: #e8f4ed; border-radius: 12px; color: #236245; display: inline-flex; flex: 0 0 48px; font-size: 19px; font-weight: 800; height: 48px; justify-content: center; }
    .nb-subscription-detail .nb-customer-name { color: #203a30; font-size: 17px; font-weight: 800; overflow-wrap: anywhere; }
    .nb-subscription-detail .nb-customer-meta { color: #718076; font-size: 13px; overflow-wrap: anywhere; }
    .nb-subscription-detail .nb-info-list { display: grid; gap: 14px; margin: 20px 0 0; }
    .nb-subscription-detail .nb-info-row { align-items: start; border-top: 1px solid #edf1ed; display: flex; gap: 14px; justify-content: space-between; padding-top: 12px; }
    .nb-subscription-detail .nb-info-row dt { color: #718076; font-size: 13px; font-weight: 600; }
    .nb-subscription-detail .nb-info-row dd { color: #34423a; font-size: 13px; font-weight: 700; margin: 0; max-width: 62%; overflow-wrap: anywhere; text-align: right; }
    .nb-subscription-detail .nb-child-stat { background: #f7f9f7; border: 1px solid #e8eee8; border-radius: 11px; height: 100%; padding: 16px; }
    .nb-subscription-detail .nb-child-stat span { color: #718076; display: block; font-size: 12px; font-weight: 700; margin-bottom: 7px; }
    .nb-subscription-detail .nb-child-stat strong { color: #173d31; font-size: 18px; overflow-wrap: anywhere; }
    .nb-subscription-detail .nb-assessment { border: 1px solid #e5ebe5; border-radius: 12px; margin-top: 12px; overflow: hidden; }
    .nb-subscription-detail .nb-assessment summary { align-items: center; background: #fbfcfb; color: #263b31; cursor: pointer; display: flex; flex-wrap: wrap; font-weight: 700; gap: 8px 18px; justify-content: space-between; list-style: none; padding: 14px 16px; }
    .nb-subscription-detail .nb-assessment summary::-webkit-details-marker { display: none; }
    .nb-subscription-detail .nb-assessment-body { padding: 14px 16px; }
    .nb-subscription-detail .nb-answer-table th { background: #f7f9f7; color: #718076; font-size: 11px; letter-spacing: .04em; text-transform: uppercase; }
    .nb-subscription-detail .nb-answer-table td { overflow-wrap: anywhere; vertical-align: top; }
    @media (max-width: 575.98px) {
        .nb-subscription-detail .nb-detail-card .card-body { padding: 17px; }
        .nb-subscription-detail .nb-info-row { display: block; }
        .nb-subscription-detail .nb-info-row dd { margin-top: 4px; max-width: 100%; text-align: left; }
    }
</style>

<div class="nb-subscription-detail">
    <div class="nb-detail-header">
        <div>
            <p class="mb-0">Subscription #{{ $subscription->id }} · {{ $planName }}</p>
        </div>
        <a href="{{ route('admin.nutrition.diet-subscriptions.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <iconify-icon icon="solar:arrow-left-linear"></iconify-icon> Back to subscriptions
        </a>
    </div>

    <div class="row g-3 mb-20">
        <div class="col-6 col-xl-3">
            <div class="card nb-summary-card"><div class="card-body p-20">
                <div class="nb-summary-label">Subscription status</div>
                <div class="nb-summary-value"><span class="badge bg-{{ $statusClasses[strtolower((string) $subscription->status)] ?? 'secondary' }}-focus text-{{ $statusClasses[strtolower((string) $subscription->status)] ?? 'secondary' }}-main">{{ ucfirst((string) $subscription->status) }}</span></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card nb-summary-card"><div class="card-body p-20">
                <div class="nb-summary-label">Payment status</div>
                <div class="nb-summary-value"><span class="badge bg-{{ $paymentClasses[strtolower((string) $subscription->payment_status)] ?? 'secondary' }}-focus text-{{ $paymentClasses[strtolower((string) $subscription->payment_status)] ?? 'secondary' }}-main">{{ ucfirst((string) $subscription->payment_status) }}</span></div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card nb-summary-card"><div class="card-body p-20">
                <div class="nb-summary-label">Amount paid</div>
                <div class="nb-summary-value">₹{{ number_format((float) $subscription->amount_paid, 2) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card nb-summary-card"><div class="card-body p-20">
                <div class="nb-summary-label">Expires on</div>
                <div class="nb-summary-value">{{ $subscription->expires_at?->format('d M Y') ?? 'No expiry date' }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-20">
        <div class="col-xl-4 col-lg-5">
            <section class="card nb-detail-card">
                <div class="card-body">
                    <h2 class="nb-card-title">Customer</h2>
                    <div class="d-flex align-items-center gap-3">
                        <div class="nb-customer-avatar" aria-hidden="true">{{ strtoupper(substr($customer?->name ?: 'G', 0, 1)) }}</div>
                        <div class="min-w-0">
                            <div class="nb-customer-name">{{ $customer?->name ?: 'Guest customer' }}</div>
                            <div class="nb-customer-meta">{{ $customer?->email ?: 'No email available' }}</div>
                        </div>
                    </div>
                    <dl class="nb-info-list">
                        <div class="nb-info-row"><dt>Phone</dt><dd>{{ $customer?->phone ?: 'N/A' }}</dd></div>
                        <div class="nb-info-row"><dt>Customer since</dt><dd>{{ $customer?->created_at?->format('d M Y') ?? 'N/A' }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-xl-8 col-lg-7">
            <section class="card nb-detail-card">
                <div class="card-body">
                    <h2 class="nb-card-title">Child profile</h2>
                    @if ($child)
                        <div class="row g-3">
                            <div class="col-md-4"><div class="nb-child-stat"><span>Name</span><strong>{{ $child->name ?: 'N/A' }}</strong></div></div>
                            <div class="col-md-4"><div class="nb-child-stat"><span>Age / date of birth</span><strong>{{ $child->dob?->age ? $child->dob->age . ' years' : ($child->dob?->format('d M Y') ?? 'N/A') }}</strong></div></div>
                            <div class="col-md-4"><div class="nb-child-stat"><span>Sex</span><strong>{{ $child->sex ? ucfirst($child->sex) : 'N/A' }}</strong></div></div>
                        </div>
                    @else
                        <div class="alert alert-light border mb-0">No child profile is linked to this subscription.</div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <section class="card nb-detail-card mb-20">
        <div class="card-body">
            <h2 class="nb-card-title">Subscription &amp; payment</h2>
            <div class="row g-4">
                <div class="col-lg-6">
                    <dl class="nb-info-list mt-0">
                        <div class="nb-info-row"><dt>Plan</dt><dd>{{ $planName }}</dd></div>
                        <div class="nb-info-row"><dt>Billing model</dt><dd>{{ $subscription->model ?: 'N/A' }}</dd></div>
                        <div class="nb-info-row"><dt>Starts</dt><dd>{{ $subscription->starts_at?->format('d M Y') ?? 'N/A' }}</dd></div>
                        <div class="nb-info-row"><dt>Expires</dt><dd>{{ $subscription->expires_at?->format('d M Y') ?? 'N/A' }}</dd></div>
                        <div class="nb-info-row"><dt>Plans remaining</dt><dd>{{ $subscription->plans_remaining ?? 'N/A' }}</dd></div>
                        <div class="nb-info-row"><dt>Cancelled</dt><dd>{{ $subscription->cancelled_at?->format('d M Y') ?? 'N/A' }}</dd></div>
                    </dl>
                </div>
                <div class="col-lg-6">
                    <dl class="nb-info-list mt-0">
                        <div class="nb-info-row"><dt>Razorpay order</dt><dd>{{ $subscription->razorpay_order_id ?: 'N/A' }}</dd></div>
                        <div class="nb-info-row"><dt>Razorpay payment</dt><dd>{{ $subscription->razorpay_payment_id ?: 'N/A' }}</dd></div>
                        <div class="nb-info-row"><dt>Renewed from</dt><dd>{{ $subscription->renewed_from_id ? '#' . $subscription->renewed_from_id : 'Original subscription' }}</dd></div>
                        <div class="nb-info-row"><dt>Created</dt><dd>{{ $subscription->created_at?->format('d M Y, h:i A') ?? 'N/A' }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <section class="card nb-detail-card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="nb-card-title mb-1">Assessment history</h2>
                    <p class="text-secondary-light mb-0">Most recent assessments and their saved answers</p>
                </div>
                @if ($assessments instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                    <span class="text-sm text-secondary-light">{{ $assessments->total() }} assessments</span>
                @endif
            </div>

            @forelse ($assessments as $assessment)
                <details class="nb-assessment">
                    <summary>
                        <span>Assessment #{{ $assessment->id }} · {{ $assessment->model_level ?: 'Unspecified model' }}</span>
                        <span class="text-secondary-light text-sm">{{ $assessment->created_at?->format('d M Y') ?? 'Date unavailable' }}</span>
                    </summary>
                    <div class="nb-assessment-body">
                        @if ($assessment->answers->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm nb-answer-table mb-0">
                                    <thead><tr><th scope="col">Question</th><th scope="col">Answer</th></tr></thead>
                                    <tbody>
                                        @foreach ($assessment->answers as $answer)
                                            @php
                                                $answerValue = $answer->answer_value;
                                                $answerText = is_array($answerValue)
                                                    ? collect($answerValue)->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))->implode(', ')
                                                    : (is_scalar($answerValue) ? (string) $answerValue : json_encode($answerValue));
                                            @endphp
                                            <tr>
                                                <td>{{ $answer->question?->question ?? 'Question no longer available' }}</td>
                                                <td class="fw-semibold">{{ $answerText !== '' ? $answerText : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-secondary-light mb-0">No saved answers for this assessment.</p>
                        @endif
                    </div>
                </details>
            @empty
                <div class="alert alert-light border mt-3 mb-0">No assessments are linked to this child.</div>
            @endforelse

            @if ($assessments instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $assessments->hasPages())
                <div class="d-flex justify-content-end mt-3">{{ $assessments->links() }}</div>
            @endif
        </div>
    </section>
</div>
@endsection
