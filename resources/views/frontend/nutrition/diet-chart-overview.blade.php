@extends('layouts.main')
@section('title', 'NutriBuddy — 7-Day Personalized Diet Chart')

@section('content')
<style>
    .diet-chart-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 40px 20px;
        border-bottom-left-radius: 40px;
        border-bottom-right-radius: 40px;
        margin-bottom: -40px;
        position: relative;
        z-index: 1;
        text-align: center;
    }
    .chart-container {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        position: relative;
        z-index: 2;
        margin-bottom: 50px;
    }
    .day-tab-btn {
        border-radius: 50px !important;
        margin: 0 5px 10px 5px;
        font-weight: 600;
        color: #666;
        background-color: #f3f4f6;
        border: none !important;
        padding: 10px 20px !important;
        transition: all 0.3s ease;
    }
    .day-tab-btn.active {
        background-color: #10b981 !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    .meal-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .meal-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .meal-time-badge {
        background: #ede9fe;
        color: #7c3aed;
        padding: 8px 16px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        width: 130px;
        text-align: center;
        margin-right: 20px;
        flex-shrink: 0;
    }
    .meal-details {
        flex-grow: 1;
    }
    .meal-name {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .meal-macros {
        display: flex;
        gap: 15px;
        font-size: 0.85rem;
        color: #6b7280;
    }
    .macro-badge {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .macro-icon {
        color: #10b981;
    }
    .btn-back-profile {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: white;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 20px;
        transition: opacity 0.2s;
    }
    .btn-back-profile:hover {
        color: white;
        opacity: 0.8;
    }
    
    @media (max-width: 768px) {
        .meal-card {
            flex-direction: column;
            align-items: flex-start;
        }
        .meal-time-badge {
            margin-bottom: 12px;
        }
    }
</style>

<div class="diet-chart-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('child.profile', ['child' => $childId]) }}" class="btn-back-profile">
                ← Back to Profile
            </a>
        </div>
        <h2 class="text-white mb-2 fw-bold">7-Day Personalized Diet Chart</h2>
        <p class="text-white-50 mb-0">Specially curated nutrition plan to meet your child's goals.</p>
    </div>
</div>

<div class="container pb-5">
    <div class="chart-container">
        
        <!-- Day Tabs -->
        <ul class="nav nav-pills justify-content-center mb-4" id="dietDaysTab" role="tablist">
            @foreach($days as $index => $day)
                <li class="nav-item" role="presentation">
                    <button class="nav-link day-tab-btn {{ $index === 0 ? 'active' : '' }}" 
                            id="tab-{{ $day }}" 
                            data-bs-toggle="pill" 
                            data-bs-target="#pane-{{ $day }}" 
                            type="button" role="tab">
                        {{ $day }}
                    </button>
                </li>
            @endforeach
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="dietDaysTabContent">
            @foreach($days as $index => $day)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                     id="pane-{{ $day }}" role="tabpanel" tabindex="0">
                    
                    <h4 class="mb-4 text-center fw-bold" style="color: #374151;">{{ $day }}'s Menu</h4>
                    
                    @foreach($dietPlan[$day] as $time => $meal)
                        <div class="meal-card">
                            <div class="meal-time-badge">
                                @if(str_contains($time, 'Breakfast')) 🍳 
                                @elseif(str_contains($time, 'Lunch')) 🥗 
                                @elseif(str_contains($time, 'Dinner')) 🍲 
                                @else 🍎 @endif
                                {{ $time }}
                            </div>
                            <div class="meal-details">
                                <div class="meal-name">{{ $meal['name'] }}</div>
                                <div class="meal-macros">
                                    <div class="macro-badge">
                                        <span class="macro-icon">🔥</span>
                                        <strong>{{ $meal['calories'] }}</strong> kcal
                                    </div>
                                    <div class="macro-badge">
                                        <span class="macro-icon">💪</span>
                                        <strong>{{ $meal['protein'] }}g</strong> Protein
                                    </div>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <button class="btn btn-outline-success btn-sm rounded-pill px-3">Mark Done</button>
                            </div>
                        </div>
                    @endforeach

                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection
