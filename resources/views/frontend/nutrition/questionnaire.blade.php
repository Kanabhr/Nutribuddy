@extends('layouts.main')
@section('title', 'NutriBuddy — Personalize Your Plan')

@section('content')
<link rel="stylesheet" href="{{ asset('css/questionnaire.css') }}?v={{ filemtime(public_path('css/questionnaire.css')) }}">

<style>
    /* ============================================================
       Height & Weight — Apply button FIXED
       ============================================================ */

    .qz-h-block {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 22px;
    }

    .qz-h-helper {
        font-size: .82rem;
        color: #94a3b8;
        font-weight: 500;
        margin-top: -6px;
        line-height: 1.4;
    }

    .qz-h-row {
        display: flex;
        gap: 10px;
        width: 100%;
        align-items: stretch;
    }

    .qz-h-field {
        display: block;
        width: 100%;
        padding: 14px 44px 14px 18px;
        font-size: .95rem;
        font-weight: 600;
        color: #1e293b;
        background-color: #fff;
        border: 1.5px solid #e6e1f0;
        border-radius: 14px;
        cursor: pointer;
        font-family: inherit;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        appearance: none;
        -webkit-appearance: none;
        box-sizing: border-box;
        line-height: 1.5;
        min-height: 54px;
    }
    .qz-h-field.qz-h-select {
        flex: 1 1 auto;
        min-width: 0;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237c3aed' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 18px center;
    }
    .qz-h-field:hover { border-color: #c9bce8; }
    .qz-h-field:focus {
        border-color: #845ec2;
        box-shadow: 0 0 0 4px rgba(132, 94, 194, .12);
    }

    .qz-h-unit-select {
        flex: 0 0 115px;
        padding: 14px 36px 14px 16px;
        font-size: .85rem;
        font-weight: 700;
        color: #7c3aed;
        background-color: #faf8ff;
        border: 1.5px solid #e6e1f0;
        border-radius: 14px;
        cursor: pointer;
        font-family: inherit;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%237c3aed' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        transition: border-color .2s, box-shadow .2s, background-color .15s;
        box-sizing: border-box;
        min-height: 54px;
    }
    .qz-h-unit-select:hover {
        background-color: #f3ebff;
        border-color: #c9bce8;
    }
    .qz-h-unit-select:focus {
        border-color: #845ec2;
        box-shadow: 0 0 0 4px rgba(132, 94, 194, .12);
    }

    /* Custom row */
    .qz-h-custom-row {
        display: none;
        gap: 10px;
        width: 100%;
        align-items: flex-end;
    }
    .qz-h-custom-row.show { display: flex; }

    .qz-h-custom-single {
        flex: 1 1 auto;
        min-width: 0;
        padding: 14px 18px;
        font-size: .95rem;
        font-weight: 600;
        color: #1e293b;
        background-color: #fff;
        border: 1.5px solid #e6e1f0;
        border-radius: 14px;
        font-family: inherit;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
        min-height: 54px;
    }
    .qz-h-custom-single:focus {
        border-color: #845ec2;
        box-shadow: 0 0 0 4px rgba(132, 94, 194, .12);
    }
    .qz-h-custom-single::placeholder { color: #b8b1c7; font-weight: 500; }

    .qz-h-ftin-wrap {
        flex: 1 1 auto;
        display: flex;
        gap: 10px;
        min-width: 0;
    }

    /* Column: label above input — NO overlap */
    .qz-h-sub {
        flex: 1 1 0;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .qz-h-sub-label {
        font-size: .68rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding-left: 4px;
        line-height: 1;
    }
    .qz-h-sub-input {
        width: 100%;
        padding: 14px 16px;
        font-size: .95rem;
        font-weight: 600;
        color: #1e293b;
        background-color: #fff;
        border: 1.5px solid #e6e1f0;
        border-radius: 14px;
        font-family: inherit;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
        min-height: 54px;
        text-align: center;
    }
    .qz-h-sub-input:focus {
        border-color: #845ec2;
        box-shadow: 0 0 0 4px rgba(132, 94, 194, .12);
    }
    .qz-h-sub-input::placeholder { color: #b8b1c7; font-weight: 500; }

    /* Native arrows */
    .qz-h-custom-single::-webkit-outer-spin-button,
    .qz-h-custom-single::-webkit-inner-spin-button,
    .qz-h-sub-input::-webkit-outer-spin-button,
    .qz-h-sub-input::-webkit-inner-spin-button {
        opacity: .55;
        height: 30px;
        cursor: pointer;
    }
    .qz-h-custom-single:hover::-webkit-outer-spin-button,
    .qz-h-custom-single:hover::-webkit-inner-spin-button,
    .qz-h-sub-input:hover::-webkit-outer-spin-button,
    .qz-h-sub-input:hover::-webkit-inner-spin-button {
        opacity: 1;
    }

    .qz-h-custom-apply {
        flex: 0 0 auto;
        padding: 0 22px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #845ec2 0%, #d65db1 100%);
        color: #fff;
        font-size: .85rem;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(132, 94, 194, .28);
        transition: transform .15s, box-shadow .15s;
        white-space: nowrap;
        min-height: 54px;
        align-self: flex-end;
    }
    .qz-h-custom-apply:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(132, 94, 194, .35);
    }

    /* Applied state */
    .qz-h-custom-apply.applied {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        box-shadow: 0 4px 14px rgba(34, 197, 94, .30);
    }

    /* Warning */
    .qz-h-warn {
        display: none;
        align-items: flex-start;
        gap: 8px;
        font-size: .78rem;
        font-weight: 600;
        color: #b45309;
        background: #fef3c7;
        border-radius: 12px;
        padding: 11px 16px;
        line-height: 1.45;
        width: 100%;
        box-sizing: border-box;
    }
    .qz-h-warn.show { display: flex; }
    .qz-h-warn::before {
        content: '⚠';
        font-size: .95rem;
        line-height: 1.3;
        flex-shrink: 0;
    }

    /* Success state */
    .qz-h-warn.success {
        color: #166534;
        background: #dcfce7;
    }
    .qz-h-warn.success::before { content: '✓'; }

    /* Weight */
    .qz-w-wrap {
        display: flex;
        align-items: stretch;
        width: 100%;
        border: 1.5px solid #e6e1f0;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
        transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
        min-height: 54px;
    }
    .qz-w-wrap:focus-within {
        border-color: #845ec2;
        box-shadow: 0 0 0 4px rgba(132, 94, 194, .12);
    }
    .qz-w-input {
        flex: 1 1 auto;
        min-width: 0;
        border: none;
        outline: none;
        background: transparent;
        padding: 14px 18px;
        font-size: .95rem;
        font-weight: 600;
        color: #1e293b;
        font-family: inherit;
        box-sizing: border-box;
        line-height: 1.5;
    }
    .qz-w-input::placeholder { color: #b8b1c7; font-weight: 500; }
    .qz-w-input::-webkit-outer-spin-button,
    .qz-w-input::-webkit-inner-spin-button {
        opacity: .55;
        height: 30px;
        cursor: pointer;
    }
    .qz-w-input:hover::-webkit-outer-spin-button,
    .qz-w-input:hover::-webkit-inner-spin-button {
        opacity: 1;
    }
    .qz-w-suffix {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        padding: 0 20px;
        font-size: .85rem;
        font-weight: 700;
        color: #a094b8;
        background: #faf8ff;
        border-left: 1.5px solid #f0ecf9;
        letter-spacing: .3px;
        user-select: none;
        pointer-events: none;
    }

    .qz-opt-tag {
        display: inline-block;
        font-size: .68rem;
        font-weight: 700;
        color: #94a3b8;
        background: #f1f5f9;
        padding: 3px 10px;
        border-radius: 999px;
        letter-spacing: .4px;
        text-transform: uppercase;
        line-height: 1.2;
        vertical-align: middle;
        margin-left: 4px;
    }

    .qz-question-block .qz-field-label {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 0;
        line-height: 1.4;
    }

    @media (max-width: 560px) {
        .qz-h-unit-select {
            flex: 0 0 95px;
            padding: 14px 30px 14px 12px;
            font-size: .8rem;
        }
        .qz-h-custom-apply {
            padding: 0 16px;
            font-size: .78rem;
        }
        .qz-h-ftin-wrap { gap: 8px; }
    }
</style>

<div class="qz-wrap">

    <div class="qz-hero">
        <div class="qz-hero-badge">{{ $plan->name }} Plan</div>
        <h1>Build a <span>personalized</span> diet chart</h1>
        <p>Answer a few questions about your child and get a nutrition plan shaped around their age, goals, and daily routine.</p>
        <div class="qz-mini-stats">
            <div class="qz-mini-stat"><strong>{{ count($questions) }}</strong><span>guided steps</span></div>
            <div class="qz-mini-stat"><strong>2</strong><span>free preview days</span></div>
            <div class="qz-mini-stat"><strong>7</strong><span>day full plan</span></div>
        </div>
    </div>

    <div class="qz-shell">

        <aside class="qz-side-panel">
            <h3>What you get</h3>
            <p>Designed for busy parents, with clean steps and output that is easy to follow at home.</p>
            <div>
                <div class="qz-check-item"><span class="qz-tick">✓</span><span>Meal ideas based on diet preference and goals</span></div>
                <div class="qz-check-item"><span class="qz-tick">✓</span><span>Daily nutrition targets for calories, protein and micronutrients</span></div>
                <div class="qz-check-item"><span class="qz-tick">✓</span><span>Parent tips and a matching NutriBuddy product suggestion</span></div>
            </div>
            <div class="qz-side-note">Tip: add height and weight if you know them. The plan still works without them, but the summary becomes more accurate.</div>
        </aside>

        <div class="qz-main">

            <div class="qz-stepper">
                @php $stepCount = count($questions); $si = 1; @endphp
                @foreach($questions as $sectionName => $sectionQuestions)
                    <div class="qz-sp-step {{ $si === 1 ? 'active' : '' }}" id="qzSp{{ $si }}">
                        <div class="qz-sp-ball">{{ $si }}</div>
                        <div class="qz-sp-label">{{ Str::words($sectionName, 2, '') }}</div>
                    </div>
                    @if($si < $stepCount)
                        <div class="qz-sp-line"><div class="qz-sp-line-fill" id="qzLine{{ $si }}"></div></div>
                    @endif
                    @php $si++; @endphp
                @endforeach
            </div>

            <form id="questionnaireForm" style="display:none;">
                @csrf
                <input type="hidden" name="plan_slug" value="{{ $plan->slug }}">
                <input type="hidden" name="cycle" value="{{ $cycle }}">
                @if($child)<input type="hidden" name="child_id" value="{{ $child->id }}">@endif
            </form>

            @php
                $step = 1;
                $stepEmojis = ['👶','🎯','🥗','💡','📊','🌟','🧩','📋'];
                $ageEmojis  = ['👶','🧒','🧑','🧑','🧑'];
                $ageLabels  = ['Toddler','Pre-School','Primary','Middle','Teen'];
                $genderEmojis = ['👦','👧','🧒'];
            @endphp

            @foreach($questions as $sectionName => $sectionQuestions)
            <div class="qz-step-panel {{ $step === 1 ? 'active' : '' }}" id="qzPanel{{ $step }}">

                <div class="qz-step-head">
                    <span class="qz-step-emoji">{{ $stepEmojis[($step-1) % count($stepEmojis)] }}</span>
                    <div class="qz-section-badge">Section {{ $step }} of {{ $stepCount }}</div>
                    <h3>{{ $sectionName }}</h3>
                    <p>Please fill in the details below to personalize your child's plan.</p>
                </div>

                @foreach($sectionQuestions as $q)
                @php
                    $ql = strtolower($q->question);
                    $isHeight  = str_contains($ql, 'height');
                    $isWeight  = str_contains($ql, 'weight');
                    $isName    = str_contains($ql, 'name');
                    $isAgeQ    = str_contains($ql, 'age') && $q->answer_type === 'Choice';
                    $isGenderQ = str_contains($ql, 'gender') || str_contains($ql, 'sex');
                    $isMulti   = $q->answer_type === 'Multi'
                        || (str_contains($ql, 'region') && str_contains($ql, 'cuisine'));
                @endphp

                <div class="qz-question-block" data-qid="{{ $q->id }}">

                    @if($q->answer_type === 'Text' || $q->answer_type === 'Date' || $q->answer_type === 'Number')

                        {{-- HEIGHT --}}
                        @if($isHeight)
                            <div class="qz-h-block" id="h_block_{{ $q->id }}">
                                <label class="qz-field-label">
                                    📏 {{ $q->question }}
                                    <span class="qz-opt-tag">Optional</span>
                                </label>

                                <div class="qz-h-helper">Enter your current height</div>

                                <div class="qz-h-row">
                                    <select class="qz-h-field qz-h-select"
                                            id="h_select_{{ $q->id }}"
                                            onchange="qzHeightChanged('{{ $q->id }}', this.value)">
                                    </select>

                                    <select class="qz-h-unit-select"
                                            id="h_unit_select_{{ $q->id }}"
                                            onchange="qzSetHeightMode('{{ $q->id }}', this.value)">
                                        <option value="ft">ft / in</option>
                                        <option value="cm">cm</option>
                                    </select>
                                </div>

                                <div class="qz-h-custom-row" id="h_custom_row_{{ $q->id }}">
                                    {{-- CM --}}
                                    <input type="number"
                                           min="30" max="250" step="1"
                                           class="qz-h-custom-single"
                                           id="h_custom_cm_{{ $q->id }}"
                                           placeholder="e.g. 173"
                                           style="display:none;">

                                    {{-- FT/IN --}}
                                    <div class="qz-h-ftin-wrap" id="h_custom_ftin_{{ $q->id }}" style="display:none;">
                                        <div class="qz-h-sub">
                                            <span class="qz-h-sub-label">Feet</span>
                                            <input type="number"
                                                   min="0" max="8" step="1"
                                                   class="qz-h-sub-input"
                                                   id="h_custom_ft_{{ $q->id }}"
                                                   placeholder="5">
                                        </div>
                                        <div class="qz-h-sub">
                                            <span class="qz-h-sub-label">Inches</span>
                                            <input type="number"
                                                   min="0" max="11" step="1"
                                                   class="qz-h-sub-input"
                                                   id="h_custom_in_{{ $q->id }}"
                                                   placeholder="7">
                                        </div>
                                    </div>

                                    <button type="button"
                                            class="qz-h-custom-apply"
                                            id="h_apply_{{ $q->id }}"
                                            onclick="qzApplyCustomHeight('{{ $q->id }}')">Apply</button>
                                </div>

                                <div class="qz-h-warn" id="h_warn_{{ $q->id }}"></div>

                                <input type="hidden" class="qz-form-field" data-qid="{{ $q->id }}" id="h_hid_{{ $q->id }}">
                            </div>

                        {{-- WEIGHT --}}
                        @elseif($isWeight)
                            <div class="qz-h-block" id="w_block_{{ $q->id }}">
                                <label class="qz-field-label">
                                    ⚖️ {{ $q->question }}
                                    <span class="qz-opt-tag">Optional</span>
                                </label>
                                <div class="qz-h-helper">Enter weight in kilograms</div>
                                <div class="qz-w-wrap">
                                    <input type="number" step="any" min="0"
                                           class="qz-w-input qz-form-field qz-weight-input"
                                           data-qid="{{ $q->id }}"
                                           id="w_val_{{ $q->id }}"
                                           placeholder="e.g. 22">
                                    <span class="qz-w-suffix">kg</span>
                                </div>
                            </div>

                        @else
                            <label class="qz-field-label">
                                @if($isName)👦 @endif{{ $q->question }} <span style="color:#e84040">*</span>
                            </label>
                            <input type="{{ $q->answer_type === 'Date' ? 'date' : 'text' }}"
                                   class="qz-text-input qz-form-field"
                                   data-qid="{{ $q->id }}"
                                   placeholder="{{ $isName ? 'e.g. Aarav Sharma' : '' }}"
                                   required>
                        @endif

                    @elseif($q->answer_type === 'Choice' || $q->answer_type === 'Multi')
                        @php $opts = array_map('trim', explode('/', $q->options_scale)); @endphp

                        @if($isAgeQ)
                            <div class="qz-sub-heading">Age Group</div>
                            <div class="qz-age-grid" id="ageGrid_{{ $q->id }}">
                                @foreach($opts as $idx => $opt)
                                <div class="qz-age-card" data-val="{{ $opt }}"
                                     onclick="qzSelectCard(this,'ageGrid_{{ $q->id }}','{{ $q->id }}',false)">
                                    <span class="qz-age-emoji">{{ $ageEmojis[$idx] ?? '🧒' }}</span>
                                    <span class="qz-age-range">{{ $opt }}</span>
                                    <span class="qz-age-label">{{ $ageLabels[$idx] ?? '' }}</span>
                                </div>
                                @endforeach
                            </div>
                            <input type="hidden" class="qz-form-field" data-qid="{{ $q->id }}" id="hidAge_{{ $q->id }}" required>

                        @elseif($isGenderQ)
                            <div class="qz-sub-heading">Gender</div>
                            <div class="qz-gender-row" id="genderGrid_{{ $q->id }}">
                                @foreach($opts as $idx => $opt)
                                <div class="qz-gender-card" data-val="{{ $opt }}"
                                     onclick="qzSelectCard(this,'genderGrid_{{ $q->id }}','{{ $q->id }}',false)">
                                    <span class="qz-gender-emoji">{{ $genderEmojis[$idx] ?? '🧒' }}</span>
                                    <div class="qz-gender-name">{{ $opt }}</div>
                                </div>
                                @endforeach
                            </div>
                            <input type="hidden" class="qz-form-field" data-qid="{{ $q->id }}" id="hidGender_{{ $q->id }}" required>

                        @else
                            <label class="qz-field-label">
                                {{ $q->question }} <span style="color:#e84040">*</span>
                                @if($isMulti)<span style="font-size:.7rem;color:#aaa;font-weight:500;margin-left:4px">(Select all that apply)</span>@endif
                            </label>
                            <div class="qz-choice-row" id="choiceRow_{{ $q->id }}">
                                @foreach($opts as $opt)
                                <div class="qz-choice-chip"
                                     data-val="{{ $opt }}"
                                     onclick="qzSelectChip(this,'choiceRow_{{ $q->id }}','{{ $q->id }}',{{ $isMulti ? 'true' : 'false' }})">
                                    {{ $q->question === 'How often falls ill' ? str_replace(' yr', ' year', $opt) : $opt }}
                                </div>
                                @endforeach
                            </div>
                            <input type="hidden" class="qz-form-field" data-qid="{{ $q->id }}" id="hidChip_{{ $q->id }}" required>
                        @endif

                    @else
                        @php
                            $qLowerStr = strtolower($q->question);
                            $isH2 = str_contains($qLowerStr, 'height');
                            $isW2 = str_contains($qLowerStr, 'weight');
                        @endphp

                        @if($isH2)
                            <div class="qz-h-block" id="h_block_{{ $q->id }}">
                                <label class="qz-field-label">
                                    📏 {{ $q->question }} <span style="color:#e84040">*</span>
                                </label>
                                <div class="qz-h-helper">Enter your current height</div>

                                <div class="qz-h-row">
                                    <select class="qz-h-field qz-h-select"
                                            id="h_select_{{ $q->id }}"
                                            onchange="qzHeightChanged('{{ $q->id }}', this.value)">
                                    </select>
                                    <select class="qz-h-unit-select"
                                            id="h_unit_select_{{ $q->id }}"
                                            onchange="qzSetHeightMode('{{ $q->id }}', this.value)">
                                        <option value="ft">ft / in</option>
                                        <option value="cm">cm</option>
                                    </select>
                                </div>

                                <div class="qz-h-custom-row" id="h_custom_row_{{ $q->id }}">
                                    <input type="number" min="30" max="250" step="1"
                                           class="qz-h-custom-single"
                                           id="h_custom_cm_{{ $q->id }}"
                                           placeholder="e.g. 173"
                                           style="display:none;">

                                    <div class="qz-h-ftin-wrap" id="h_custom_ftin_{{ $q->id }}" style="display:none;">
                                        <div class="qz-h-sub">
                                            <span class="qz-h-sub-label">Feet</span>
                                            <input type="number" min="0" max="8" step="1"
                                                   class="qz-h-sub-input"
                                                   id="h_custom_ft_{{ $q->id }}"
                                                   placeholder="5">
                                        </div>
                                        <div class="qz-h-sub">
                                            <span class="qz-h-sub-label">Inches</span>
                                            <input type="number" min="0" max="11" step="1"
                                                   class="qz-h-sub-input"
                                                   id="h_custom_in_{{ $q->id }}"
                                                   placeholder="7">
                                        </div>
                                    </div>

                                    <button type="button"
                                            class="qz-h-custom-apply"
                                            id="h_apply_{{ $q->id }}"
                                            onclick="qzApplyCustomHeight('{{ $q->id }}')">Apply</button>
                                </div>

                                <div class="qz-h-warn" id="h_warn_{{ $q->id }}"></div>
                                <input type="hidden" class="qz-form-field" data-qid="{{ $q->id }}" id="h_hid_{{ $q->id }}" required>
                            </div>

                        @elseif($isW2)
                            <div class="qz-h-block" id="w_block_{{ $q->id }}">
                                <label class="qz-field-label">
                                    ⚖️ {{ $q->question }} <span style="color:#e84040">*</span>
                                </label>
                                <div class="qz-h-helper">Enter weight in kilograms</div>

                                <div class="qz-w-wrap">
                                    <input type="number" step="any" min="0"
                                           class="qz-w-input qz-form-field qz-weight-input"
                                           data-qid="{{ $q->id }}"
                                           id="w_val_{{ $q->id }}"
                                           placeholder="e.g. 22"
                                           required>
                                    <span class="qz-w-suffix">kg</span>
                                </div>
                            </div>

                        @else
                            <label class="qz-field-label">
                                {{ $q->question }} <span style="color:#e84040">*</span>
                            </label>
                            <input type="text" class="qz-text-input qz-form-field"
                                   data-qid="{{ $q->id }}" required>
                        @endif
                    @endif

                    @if(!$isHeight && !$isWeight)
                        <div class="qz-error" id="qzErr_{{ $q->id }}">&#9888;&#xFE0F; This field is required.</div>
                    @endif
                </div>
                @endforeach

                <div class="qz-step-nav">
                    @if($step > 1)
                        <button class="qz-btn-back" type="button" onclick="qzGo({{ $step }}, {{ $step - 1 }})">&#8592; Back</button>
                    @else
                        <div></div>
                    @endif

                    @if($step < $stepCount)
                        <button class="qz-btn-next" type="button" onclick="qzGo({{ $step }}, {{ $step + 1 }})">Continue &#8594;</button>
                    @else
                        <button class="qz-btn-submit" type="button" id="qzSubmitBtn" onclick="qzSubmit(this)">
                            &#10024; Submit &amp; Pay
                        </button>
                    @endif
                </div>
            </div>
            @php $step++; @endphp
            @endforeach

        </div>
    </div>
</div>

<script>
    window.isUserLoggedIn = @json(auth()->check());
    window.loginUrlBase = "{{ route('api.nutrition.questionnaire.checkout.auth') }}";

    /* ============================================================
       Height state + logic — APPLY FIXED
       ============================================================ */

    const HEIGHT_MIN_CM = 91;
    const HEIGHT_MAX_CM = 213;

    const qzHeightState = {};

    // ---------- helper: pretty label from cm ----------
    function qzFormatHeightCm(cm, mode) {
        if (mode === 'cm') return cm + ' cm';
        const totalIn = Math.round(cm / 2.54);
        const ft = Math.floor(totalIn / 12);
        const inch = totalIn % 12;
        return ft + "'" + inch + '"';
    }

    // ---------- build dropdown options ----------
    function qzBuildHeightOptions(mode) {
        const out = [];
        if (mode === 'cm') {
            for (let cm = HEIGHT_MIN_CM; cm <= HEIGHT_MAX_CM; cm++) {
                out.push({ label: cm + ' cm', cm: cm });
            }
        } else {
            const minInches = Math.ceil(HEIGHT_MIN_CM / 2.54);
            const maxInches = Math.floor(HEIGHT_MAX_CM / 2.54);
            for (let totalIn = minInches; totalIn <= maxInches; totalIn++) {
                const ft = Math.floor(totalIn / 12);
                const inch = totalIn % 12;
                out.push({ label: ft + "'" + inch + '"', cm: Math.round(totalIn * 2.54) });
            }
        }
        return out;
    }

    function qzRenderHeightDropdown(qid) {
        const sel = document.getElementById('h_select_' + qid);
        const state = qzHeightState[qid];
        if (!sel || !state) return;

        const opts = qzBuildHeightOptions(state.mode);
        sel.innerHTML = '';

        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = state.mode === 'cm' ? 'Select height (cm)' : 'Select height (ft / in)';
        ph.selected = (state.valueCm == null && !state.customActive);
        sel.appendChild(ph);

        opts.forEach(function (opt) {
            const o = document.createElement('option');
            o.value = opt.cm;
            o.textContent = opt.label;
            if (state.valueCm === opt.cm && !state.customActive) o.selected = true;
            sel.appendChild(o);
        });

        const customOpt = document.createElement('option');
        customOpt.value = '__custom__';
        customOpt.textContent = '✎  Enter manually…';
        if (state.customActive) customOpt.selected = true;
        sel.appendChild(customOpt);
    }

    // ---------- toggle visible fields ----------
    function qzToggleCustomFields(qid, mode) {
        const cmInput = document.getElementById('h_custom_cm_' + qid);
        const ftWrap  = document.getElementById('h_custom_ftin_' + qid);
        if (mode === 'cm') {
            if (cmInput) cmInput.style.display = 'block';
            if (ftWrap)  ftWrap.style.display  = 'none';
        } else {
            if (cmInput) cmInput.style.display = 'none';
            if (ftWrap)  ftWrap.style.display  = 'flex';
        }
    }

    // ---------- dropdown change ----------
    function qzHeightChanged(qid, val) {
        const state = qzHeightState[qid];
        if (!state) return;

        const customRow = document.getElementById('h_custom_row_' + qid);
        const hidEl = document.getElementById('h_hid_' + qid);
        const applyBtn = document.getElementById('h_apply_' + qid);
        const warnEl = document.getElementById('h_warn_' + qid);

        if (val === '__custom__') {
            state.customActive = true;
            state.valueCm = null;
            if (customRow) customRow.classList.add('show');
            qzToggleCustomFields(qid, state.mode);
            if (hidEl) hidEl.value = '';
            if (warnEl) {
                warnEl.classList.remove('show', 'success');
                warnEl.textContent = '';
            }
            if (applyBtn) {
                applyBtn.classList.remove('applied');
                applyBtn.textContent = 'Apply';
            }

            setTimeout(function () {
                const first = state.mode === 'cm'
                    ? document.getElementById('h_custom_cm_' + qid)
                    : document.getElementById('h_custom_ft_' + qid);
                if (first) first.focus();
            }, 30);
            return;
        }

        state.customActive = false;
        if (customRow) customRow.classList.remove('show');
        if (applyBtn) {
            applyBtn.classList.remove('applied');
            applyBtn.textContent = 'Apply';
        }

        const cm = parseInt(val, 10);
        if (isNaN(cm) || val === '') {
            state.valueCm = null;
            if (hidEl) hidEl.value = '';
            return;
        }

        state.valueCm = cm;
        if (hidEl) hidEl.value = qzFormatHeightCm(cm, state.mode) === (cm + ' cm')
            ? cm + ' cm'
            : qzFormatHeightCm(cm, 'ft').replace("'", 'ft ').replace('"', 'in') + ' (' + cm + 'cm)';

        // Simpler: store as ft+in (cm) for ft mode, cm only for cm mode
        if (state.mode === 'cm') {
            hidEl.value = cm + ' cm';
        } else {
            const totalIn = Math.round(cm / 2.54);
            const ft = Math.floor(totalIn / 12);
            const inch = totalIn % 12;
            hidEl.value = ft + 'ft ' + inch + 'in (' + cm + 'cm)';
        }
    }

    // ---------- read manual fields ----------
    function qzReadCustomHeight(qid, mode) {
        if (mode === 'cm') {
            const el = document.getElementById('h_custom_cm_' + qid);
            if (!el) return null;
            const raw = (el.value || '').trim();
            if (raw === '') return null;
            const n = parseFloat(raw);
            if (isNaN(n) || n < 30 || n > 250) return null;
            return Math.round(n);
        } else {
            const ftEl = document.getElementById('h_custom_ft_' + qid);
            const inEl = document.getElementById('h_custom_in_' + qid);
            if (!ftEl || !inEl) return null;

            const ftRaw = (ftEl.value || '').trim();
            const inRaw = (inEl.value || '').trim();
            if (ftRaw === '' && inRaw === '') return null;

            const ft = ftRaw !== '' ? parseInt(ftRaw, 10) : 0;
            const inch = inRaw !== '' ? parseInt(inRaw, 10) : 0;

            if (isNaN(ft) || ft < 0 || ft > 8) return null;
            if (isNaN(inch) || inch < 0 || inch > 11) return null;

            const totalIn = ft * 12 + inch;
            const cm = Math.round(totalIn * 2.54);
            if (cm < HEIGHT_MIN_CM - 5 || cm > HEIGHT_MAX_CM + 5) return null;
            return cm;
        }
    }

    // ---------- APPLY ----------
    function qzApplyCustomHeight(qid) {
        const state = qzHeightState[qid];
        if (!state) return;

        const hidEl = document.getElementById('h_hid_' + qid);
        const warnEl = document.getElementById('h_warn_' + qid);
        const applyBtn = document.getElementById('h_apply_' + qid);

        const cm = qzReadCustomHeight(qid, state.mode);

        if (cm == null) {
            state.valueCm = null;
            if (hidEl) hidEl.value = '';
            if (warnEl) {
                warnEl.classList.remove('success');
                warnEl.textContent = state.mode === 'cm'
                    ? 'Please enter a valid height like 173 cm.'
                    : 'Please enter valid feet (0–8) and inches (0–11).';
                warnEl.classList.add('show');
            }
            if (applyBtn) {
                applyBtn.classList.remove('applied');
                applyBtn.textContent = 'Apply';
            }
            return;
        }

        // SUCCESS
        state.valueCm = cm;
        if (state.mode === 'cm') {
            if (hidEl) hidEl.value = cm + ' cm';
        } else {
            const totalIn = Math.round(cm / 2.54);
            const ft = Math.floor(totalIn / 12);
            const inch = totalIn % 12;
            if (hidEl) hidEl.value = ft + 'ft ' + inch + 'in (' + cm + 'cm)';
        }

        // Visual feedback
        if (warnEl) {
            warnEl.classList.remove('show');
            warnEl.classList.add('success');
            warnEl.textContent = 'Height applied: ' + qzFormatHeightCm(cm, state.mode)
                + (state.mode === 'ft' ? ' (' + cm + ' cm)' : '');
            warnEl.classList.add('show');
            setTimeout(function () {
                warnEl.classList.remove('show', 'success');
                warnEl.textContent = '';
            }, 2400);
        }
        if (applyBtn) {
            applyBtn.classList.add('applied');
            applyBtn.textContent = 'Applied ✓';
            setTimeout(function () {
                applyBtn.classList.remove('applied');
                applyBtn.textContent = 'Apply';
            }, 2400);
        }
    }

    // ---------- live update while typing (optional) ----------
    function qzUpdateHeightSuggestion(qid) {
        // Intentionally left as a no-op hook for future live preview.
        // Actual value is applied only when user clicks Apply.
    }

    // ---------- unit switch ----------
    function qzSetHeightMode(qid, mode) {
        const state = qzHeightState[qid];
        if (!state) return;

        state.mode = mode;
        qzRenderHeightDropdown(qid);

        if (state.customActive) {
            qzToggleCustomFields(qid, mode);
        }

        const hidEl = document.getElementById('h_hid_' + qid);
        if (state.valueCm != null) {
            if (mode === 'cm') {
                if (hidEl) hidEl.value = state.valueCm + ' cm';
            } else {
                const totalIn = Math.round(state.valueCm / 2.54);
                const ft = Math.floor(totalIn / 12);
                const inch = totalIn % 12;
                if (hidEl) hidEl.value = ft + 'ft ' + inch + 'in (' + state.valueCm + 'cm)';
            }
        }
    }

    // ---------- init ----------
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[id^="h_hid_"]').forEach(function (hidEl) {
            const qid = hidEl.id.replace('h_hid_', '');
            qzHeightState[qid] = { mode: 'ft', valueCm: null, customActive: false };
            qzRenderHeightDropdown(qid);
        });

        // Enter key in custom inputs = click Apply
        document.querySelectorAll('.qz-h-custom-row input').forEach(function (inp) {
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const row = inp.closest('.qz-h-custom-row');
                    if (!row) return;
                    const qid = row.id.replace('h_custom_row_', '');
                    qzApplyCustomHeight(qid);
                }
            });
        });
    });
</script>
<script src="{{ asset('js/questionnaire.js') }}"></script>
@endsection
