<section class="diet-section" id="diet-chart">
    <div class="diet-header">
        <span class="sec-eye">Free for Every Parent</span>
        <h2 class="sec-title">Get Your Child's <span class="acc">Personalized</span><br>Diet Chart</h2>
        <p class="sec-sub">Tell us about your child, then compare available plans and choose the one that fits your family.</p>
    </div>

    <div class="stepper-wrap">
        <div class="stepper-progress" aria-label="Diet chart steps">
            <div class="sp-step active" data-home-diet-step="1"><div class="sp-ball">1</div><div class="sp-label">Child details</div></div>
            <div class="sp-line"><div class="sp-line-fill"></div></div>
            <div class="sp-step" data-home-diet-step="2"><div class="sp-ball">2</div><div class="sp-label">Choose a plan</div></div>
        </div>

        <div class="diet-card home-diet-card">
            <div id="homeDietQuestionStep">
                <div class="step-head">
                    <h3>Tell us about your child</h3>
                    <p>Answer the Basic nutrition questions so the plan can reflect your child's needs.</p>
                </div>

                @if ($basicDietQuestions->isNotEmpty())
                    <form id="homeDietQuestionnaire" method="post" onsubmit="event.preventDefault(); return false;" novalidate>
                        @csrf
                        @foreach ($basicDietQuestions->groupBy('section') as $section => $questions)
                            <fieldset class="home-diet-question-section">
                                <legend>{{ $section ?: 'About your child' }}</legend>
                                <div class="home-diet-question-grid">
                                    @foreach ($questions as $question)
                                        @php
                                            $type = strtolower((string) $question->answer_type);
                                            $isMulti = str_contains($type, 'multi');
                                            $isChoice = str_contains($type, 'choice') || $isMulti;
                                            $isHeight = str_contains(strtolower((string) $question->question), 'height');
                                            $isWeight = str_contains(strtolower((string) $question->question), 'weight');
                                            $isMeasurement = $isHeight || $isWeight;
                                            $isOptional = $question->is_optional || $isMeasurement;
                                            $options = preg_split('/\s+\/\s+/', (string) $question->options_scale, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                            $options = array_values(array_filter($options, fn ($option) => trim($option) !== '—'));
                                            $inputType = str_contains($type, 'date') ? 'date' : (str_contains($type, 'number') ? 'number' : 'text');
                                            $conditionalVegan = str_contains(strtolower((string) $question->only_ask_if), 'vegan');
                                        @endphp
                                        <div class="home-diet-question {{ $isMulti ? 'home-diet-question-wide' : '' }}" data-home-diet-question="{{ $question->id }}" @if ($conditionalVegan) data-hide-for-vegan @endif>
                                            <label class="home-diet-question-label" @if ($isChoice) id="question-label-{{ $question->id }}" @endif>
                                                {{ $question->question }}
                                                @if ($isOptional)<span class="home-diet-optional">Optional</span>@endif
                                            </label>

                                            @if ($isHeight)
                                                <div class="home-diet-measurement-help">Choose a height or enter it manually.</div>
                                                {{-- The data-home-height-control wrapper MUST contain the select row,
                                                    the custom-entry block, the hidden answer input and the suggestion
                                                    paragraph. home-diet-questionnaire.js scopes every element lookup to
                                                    this control via control.querySelector(...), so all of these elements
                                                    must be descendants of it. --}}
                                                <div data-home-height-control="{{ $question->id }}">
                                                    <div class="home-diet-height-row">
                                                        <select class="home-diet-cycle" aria-label="Height" data-home-height-select>
                                                            <option value="">Select height</option>
                                                        </select>
                                                        <select class="home-diet-cycle" aria-label="Height unit" data-home-height-unit>
                                                            <option value="ft">ft / in</option>
                                                            <option value="cm">cm</option>
                                                        </select>
                                                    </div>
                                                    <div class="home-diet-height-custom" data-home-height-custom hidden>
                                                        <div data-home-height-custom-cm>
                                                            <input type="number" min="30" max="250" step="1" placeholder="e.g. 173" aria-label="Height in centimeters" data-home-height-cm>
                                                        </div>
                                                        <div class="home-diet-height-ftin" data-home-height-custom-ftin hidden>
                                                            <label>Feet<input type="number" min="0" max="8" step="1" placeholder="5" data-home-height-ft></label>
                                                            <label>Inches<input type="number" min="0" max="11" step="1" placeholder="7" data-home-height-in></label>
                                                        </div>
                                                        <button type="button" class="home-diet-height-apply" data-home-height-apply>Apply</button>
                                                    </div>
                                                    <input type="hidden" name="q_{{ $question->id }}" data-home-height-answer>
                                                    <p class="home-diet-height-suggestion" data-home-height-suggestion hidden></p>
                                                </div>
                                            @elseif ($isWeight)
                                                <div class="home-diet-measurement-help">Enter weight in kilograms.</div>
                                                <div class="home-diet-weight-wrap">
                                                    <input type="number" name="q_{{ $question->id }}" step="any" min="0" placeholder="e.g. 22" data-home-height-weight>
                                                    <span>kg</span>
                                                </div>
                                            @elseif ($isChoice && count($options))
                                                <div class="home-diet-options" role="group" @if ($isMulti) aria-labelledby="question-label-{{ $question->id }}" @else aria-labelledby="question-label-{{ $question->id }}" @endif data-answer-group="{{ $question->id }}" data-multiple="{{ $isMulti ? 'true' : 'false' }}" data-required="{{ $question->is_optional ? 'false' : 'true' }}">
                                                    @foreach ($options as $option)
                                                        <label class="home-diet-option">
                                                            <input type="{{ $isMulti ? 'checkbox' : 'radio' }}" name="q_{{ $question->id }}{{ $isMulti ? '[]' : '' }}" value="{{ trim($option) }}" @if (! $question->is_optional && ! $isMulti) required @endif>
                                                            <span>{{ $question->question === 'How often falls ill' ? str_replace(' yr', ' year', trim($option)) : trim($option) }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @elseif (str_contains($type, 'yes/no'))
                                                <textarea name="q_{{ $question->id }}" rows="3" placeholder="{{ $question->options_scale ?: 'Add details if applicable' }}" @if (! $isOptional) required @endif></textarea>
                                            @else
                                                <input type="{{ $inputType }}" name="q_{{ $question->id }}" placeholder="{{ $inputType === 'number' ? $question->options_scale : '' }}" @if ($inputType === 'number') step="any" min="0" @endif @if (! $isOptional) required @endif>
                                            @endif
                                            <small class="home-diet-field-error" aria-live="polite"></small>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach

                        <div class="home-diet-message" id="homeDietMessage" role="alert" hidden></div>
                        <div class="step-nav home-diet-nav">
                            <span>Only the questions marked optional may be left blank.</span>
                            <button type="button" id="homeDietShowPlans" class="btn-next" onclick="if (!document.getElementById('homeDietQuestionnaire').dataset.enhanced) { document.getElementById('homeDietQuestionStep').hidden = true; document.getElementById('homeDietPlansStep').hidden = false; }">See available plans <span aria-hidden="true">→</span></button>
                        </div>
                    </form>
                @else
                    <p class="home-diet-empty">The Basic questionnaire is temporarily unavailable. Please try again later.</p>
                @endif
            </div>

            <div id="homeDietPlansStep" hidden>
                <div class="step-head">
                    <h3>Choose the plan that works for you</h3>
                    <p>Your answers are ready. Review current plan pricing and select a billing period to continue.</p>
                </div>

                @if ($dietPlans->isNotEmpty())
                    <div class="home-diet-billing-toggle" role="group" aria-label="Billing period">
                        <button type="button" class="is-selected" data-home-billing-cycle="monthly" aria-pressed="true">Monthly</button>
                        <button type="button" data-home-billing-cycle="quarterly" aria-pressed="false">Quarterly</button>
                        <button type="button" data-home-billing-cycle="annually" aria-pressed="false">Annually</button>
                    </div>
                    <div class="home-diet-plan-grid">
                        @foreach ($dietPlans as $plan)
                            @php
                                $features = is_array($plan->features) ? $plan->features : [];
                                $tierIcons = ['🥉', '🥈', '🥇', '🏆'];
                                $isPopular = strtolower((string) $plan->slug) === 'plus';
                            @endphp
                            <article class="home-diet-plan-card {{ $isPopular ? 'is-popular' : '' }}"
                                data-monthly-price="{{ $plan->monthly_price ?? '' }}"
                                data-quarterly-price="{{ $plan->quarterly_price ?? '' }}"
                                data-annually-price="{{ $plan->annual_price ?? '' }}">
                                @if ($isPopular)
                                    <span class="home-diet-popular-badge">★ Popular</span>
                                @endif
                                <div class="home-diet-plan-icon" aria-hidden="true">{{ $tierIcons[$loop->index % count($tierIcons)] }}</div>
                                <h4>{{ $plan->name }}</h4>
                                <div class="home-diet-plan-price" data-plan-price>
                                    @if ($plan->monthly_price !== null)
                                        ₹{{ number_format((float) $plan->monthly_price) }}
                                    @else
                                        Not available
                                    @endif
                                </div>
                                <p class="home-diet-plan-period" data-plan-period>
                                    Per month
                                </p>
                                @if (count($features))
                                    <ul>
                                        @foreach ($features as $feature)
                                            <li>{{ is_array($feature) ? ($feature['name'] ?? $feature['title'] ?? '') : $feature }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <button type="button" class="btn-next home-diet-buy" data-plan="{{ $plan->slug }}" @disabled($plan->monthly_price === null)>Continue to checkout</button>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="home-diet-empty">Plans are temporarily unavailable. Please try again later.</p>
                @endif

                <div class="home-diet-message" id="homeDietCheckoutMessage" role="alert" hidden></div>
                <div class="step-nav home-diet-nav home-diet-plan-footer">
                    <button type="button" class="btn-back" id="homeDietBack" onclick="if (!document.getElementById('homeDietQuestionnaire').dataset.enhanced) { document.getElementById('homeDietPlansStep').hidden = true; document.getElementById('homeDietQuestionStep').hidden = false; document.getElementById('homeDietQuestionStep').scrollIntoView({ behavior: 'smooth', block: 'start' }); }">← Edit answers</button>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        window.nutribuddyConfig = {
            paymentCreateRoute: @json(route('diet.payment.create')),
            paymentVerifyRoute: @json(route('diet.payment.verify')),
            mealPlanRoute: @json(route('meal-plan')),
            csrfToken: @json(csrf_token()),
            cashfreeEnv: @json(\App\Services\Payments\CashfreePaymentService::resolveEnvironment()),
            defaultPaymentGateway: 'cashfree'
        };
        window.homeDietIsLoggedIn = @json(auth()->check());
    </script>
    <script src="{{ asset('assets/js/pages/diet-chart-payment.js') }}?v={{ filemtime(public_path('assets/js/pages/diet-chart-payment.js')) }}"></script>
    <script src="{{ asset('js/home-diet-questionnaire.js') }}?v={{ filemtime(public_path('js/home-diet-questionnaire.js')) }}&flow=4"></script>
@endpush
