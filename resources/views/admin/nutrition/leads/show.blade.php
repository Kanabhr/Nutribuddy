@extends('layouts.admin')

@php($title = 'Diet Chart Review')
@php($subTitle = 'Review the child profile, diet plan, and clinical flags')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/nutrition-leads.css') }}">
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    <div class="lead-review-toolbar">
        <a class="btn btn-outline-secondary" href="{{ route('admin.nutrition.lead-queue.index') }}">
            <i class="ri-arrow-left-line me-1" aria-hidden="true"></i> Back to diet chart generation
        </a>
    </div>

    <div class="row g-4 align-items-start lead-review-layout">
        <aside class="col-xl-4">
            <section class="lead-profile-card" aria-labelledby="lead-customer-heading">
                <div class="lead-profile-head">
                    <div class="lead-avatar" aria-hidden="true">{{ strtoupper(substr($lead->child?->name ?? 'C', 0, 1)) }}</div>
                    <div class="min-w-0">
                        <span>Customer</span>
                        <h5 id="lead-customer-heading">{{ $lead->customer?->name ?? 'Customer unavailable' }}</h5>
                        <small class="lead-customer-email">{{ $lead->customer?->email ?? 'No email available' }}</small>
                    </div>
                </div>

                <dl class="lead-metrics">
                    <div><dt>Child</dt><dd>{{ $lead->child?->name ?? 'Profile pending' }}</dd></div>
                    <div><dt>Subscription</dt><dd>{{ $lead->subscription?->subscriptionPlan?->name ?? $lead->subscription?->model ?? 'Unavailable' }}</dd></div>
                    <div><dt>Amount paid</dt><dd>₹{{ number_format((float) ($lead->subscription?->amount_paid ?? 0), 2) }}</dd></div>
                    <div><dt>Lead status</dt><dd>{{ ucfirst(str_replace('_', ' ', $lead->status)) }}</dd></div>
                </dl>

                @if ($assessment?->professional_review_required || $clinicalFlags->isNotEmpty())
                    <div class="clinical-review-alert" role="alert">
                        <strong><i class="ri-heart-pulse-line me-1" aria-hidden="true"></i> Clinical review required</strong>
                        <p>This profile has been flagged for professional review. Check the notes before approving.</p>
                        @if ($clinicalFlags->isNotEmpty())
                            <ul>
                                @foreach ($clinicalFlags as $flag)
                                    <li><strong>{{ $flag->flag_name }}</strong>@if ($flag->reason) — {{ $flag->reason }}@endif</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <section class="lead-diet-context" aria-labelledby="lead-diet-context-heading">
                    <h5 id="lead-diet-context-heading">Dietary requirements</h5>
                    <dl class="lead-context-list">
                        <div><dt>Diet type</dt><dd>{{ $dietType }}</dd></div>
                        <div><dt>Allergies</dt><dd>{{ collect($allergies)->filter()->implode(', ') ?: 'None reported' }}</dd></div>
                        <div><dt>Meal pattern</dt><dd>{{ $mealPattern }}</dd></div>
                        <div><dt>Cooking medium</dt><dd>{{ $cookingMedium }}</dd></div>
                    </dl>
                </section>

                @if ($assessment)
                    <details class="answer-drawer">
                        <summary>Questionnaire answers <span>{{ $assessment->answers->count() }}</span></summary>
                        @if ($assessment->answers->isNotEmpty())
                            <dl class="lead-answer-list">
                                @foreach ($assessment->answers->sortBy(fn ($answer) => $answer->question?->sort_order ?? PHP_INT_MAX) as $answer)
                                    @php($answerValue = $answer->answer_value)
                                    <div>
                                        <dt>{{ $answer->question?->question ?? 'Question unavailable' }}</dt>
                                        <dd>{{ is_array($answerValue) ? collect($answerValue)->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))->implode(', ') : ($answerValue ?? 'Not answered') }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-secondary mb-0 mt-3">No questionnaire answers were saved.</p>
                        @endif
                    </details>

                    <details class="answer-drawer">
                        <summary>Generation checks <span><i class="ri-information-line" aria-hidden="true"></i></span></summary>
                        <ul class="generation-check-list">
                            <li>Diet and allergy exclusions are applied before meal selection.</li>
                            <li>Foods are checked against each meal slot.</li>
                            <li>Nutrient gaps, variety, region, and child acceptance guide ranking.</li>
                        </ul>
                    </details>
                @endif
            </section>
        </aside>

        <section class="col-xl-8" aria-labelledby="diet-chart-heading">
            <div class="chart-review-card">
                <div class="chart-review-head">
                    <div>
                        <span class="lead-eyebrow">Nutritionist workspace</span>
                        <h4 id="diet-chart-heading">Generated diet chart</h4>
                    </div>
                    @if ($dietPlan)
                        <span class="review-pill {{ $dietPlan->review_status === 'approved' ? 'is-approved' : '' }}">
                            {{ ucfirst(str_replace('_', ' ', $dietPlan->review_status)) }}
                        </span>
                    @endif
                </div>

                @if ($dietPlan?->review_status === 'approved')
                    <div class="alert alert-info mt-3 mb-0">Editing meals or recommendations returns this chart to review and temporarily removes the approved version from the family view.</div>
                @endif

                @if (! $dietPlan)
                    <div class="chart-empty">
                        <div class="chart-empty-icon" aria-hidden="true"><i class="ri-restaurant-line"></i></div>
                        @if ($assessment)
                            <h5>No diet chart yet</h5>
                            <p>The questionnaire is available. Generate a rule-based chart for review.</p>
                            <form method="POST" action="{{ route('admin.nutrition.lead-queue.generate', $lead) }}">
                                @csrf
                                <button class="btn btn-primary px-4" type="submit">Generate diet chart</button>
                            </form>
                        @else
                            <h5>Questionnaire not available</h5>
                            <p>A completed child assessment is required before a diet chart can be generated.</p>
                        @endif
                    </div>
                @else
                    @php($recommendedProductIds = $dietPlan->plan_data['supplement_product_ids'] ?? [])
                    <details class="recommended-products">
                        <summary class="recommended-products-summary">
                            <span class="recommended-products-icon" aria-hidden="true"><i class="ri-capsule-line"></i></span>
                            <span class="recommended-products-title">
                                <strong id="recommended-products-heading">Recommended supplements</strong>
                                <small>Manage products shown in the family’s supplement section</small>
                            </span>
                            <span class="recommended-products-count">{{ count($recommendedProductIds) }} selected</span>
                            <i class="ri-arrow-down-s-line recommended-products-chevron" aria-hidden="true"></i>
                        </summary>
                        <div class="recommended-products-content">
                            <form method="POST" action="{{ route('admin.nutrition.lead-queue.products', [$lead, $dietPlan]) }}">
                                @csrf
                                @method('PATCH')
                                <label for="recommendedProducts">Choose products</label>
                                <select class="form-select" id="recommendedProducts" name="supplement_product_ids[]" multiple aria-describedby="recommended-products-help">
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected(in_array($product->id, $recommendedProductIds))>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small id="recommended-products-help">Use Ctrl (Windows) or Command (Mac) to select multiple products.</small>
                                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="ri-save-line me-1" aria-hidden="true"></i>Save recommendations</button>
                            </form>
                        </div>
                    </details>

                    <form method="POST" action="{{ route('admin.nutrition.lead-queue.update', [$lead, $dietPlan]) }}">
                        @csrf
                        @method('PATCH')
                        @if ($hasUnavailableMeals)
                            <div class="alert alert-warning mt-4 mb-0">Some meal slots have no eligible food choices. Update the food catalog or diet rules before saving or approving this chart.</div>
                        @endif
                        <div class="day-plan-list">
                            @forelse ($mealsByDay as $day => $meals)
                                <details class="day-plan" @if ($loop->first) open @endif>
                                    <summary>
                                        <span class="day-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="day-plan-title"><strong>{{ $day }}</strong><small>{{ $meals->count() }} eating occasions</small></span>
                                        <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
                                    </summary>
                                    <div class="day-meals">
                                        @foreach ($meals as $meal)
                                            @php($eligibleFoods = $foodsByMeal->get($meal->id, collect()))
                                            <div class="meal-editor">
                                                <div class="meal-slot">
                                                    <span class="meal-slot-icon" aria-hidden="true"><i class="ri-restaurant-2-line"></i></span>
                                                    <div><strong>{{ $meal->meal_slot }}</strong><small>{{ $meal->timing ?? 'Flexible timing' }}</small></div>
                                                </div>
                                                <label class="meal-select">
                                                    <span>Suggested dish</span>
                                                    <select class="form-select" name="foods[{{ $meal->id }}]" @disabled($eligibleFoods->isEmpty())>
                                                        @foreach ($eligibleFoods as $food)
                                                            <option value="{{ $food->id }}" @selected((int) $meal->items->first()?->food_id === (int) $food->id)>{{ $food->dish_name }}</option>
                                                        @endforeach
                                                        @if ($eligibleFoods->isEmpty())
                                                            <option value="">No eligible foods for this meal slot</option>
                                                        @endif
                                                    </select>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @empty
                                <div class="alert alert-warning">This chart has no meal entries yet.</div>
                            @endforelse
                        </div>
                        @if ($mealsByDay->isNotEmpty())
                            <button class="btn btn-outline-primary mt-4" type="submit" @disabled($hasUnavailableMeals)><i class="ri-save-line me-1" aria-hidden="true"></i> Save chart changes</button>
                        @endif
                    </form>

                    <form method="POST" action="{{ route('admin.nutrition.lead-queue.approve', [$lead, $dietPlan]) }}" class="approval-panel">
                        @csrf
                        @method('PATCH')
                        <div class="approval-heading">
                            <span class="approval-icon"><i class="ri-shield-check-line" aria-hidden="true"></i></span>
                            <div>
                                <span class="lead-eyebrow">Final review</span>
                                <h6>{{ $dietPlan->review_status === 'approved' ? 'Chart published' : 'Ready to publish this chart?' }}</h6>
                                <p>{{ $dietPlan->review_status === 'approved' ? 'This approved chart is visible in the family’s panel.' : 'Check the meal plan and clinical notes before publishing it to the family.' }}</p>
                            </div>
                        </div>
                        <div class="approval-actions">
                            <label for="reviewNotes">Note for the family <span>Optional</span></label>
                            <textarea class="form-control" id="reviewNotes" name="review_notes" rows="4" maxlength="2000" placeholder="Share a helpful note about the plan or how to follow it." @readonly($dietPlan->review_status === 'approved')>{{ old('review_notes', $dietPlan->review_notes) }}</textarea>
                            <p class="approval-note-hint"><i class="ri-information-line me-1" aria-hidden="true"></i> The family can read this note in the approved diet chart.</p>
                            <button class="btn btn-success" type="submit" @disabled($dietPlan->review_status === 'approved' || $mealsByDay->isEmpty() || $hasUnavailableMeals)>
                                <i class="{{ $dietPlan->review_status === 'approved' ? 'ri-check-double-line' : 'ri-send-plane-line' }} me-1" aria-hidden="true"></i>
                                {{ $dietPlan->review_status === 'approved' ? 'Already published' : 'Approve and publish chart' }}
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
