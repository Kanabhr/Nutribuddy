@php view()->share('isUserPanel', true); @endphp
@extends('layouts.main')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/user.css') }}?v={{ filemtime(public_path('assets/css/user.css')) }}">
    @if(request()->routeIs('user.assessment.*'))
    <link rel="stylesheet" href="{{ asset('assets/css/assessment.css') }}?v={{ filemtime(public_path('assets/css/assessment.css')) }}">
    @endif
    <style>
        @include('partials.user-panel-sidebar-styles')
    </style>
@endpush

@section('content')
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <div class="ud-layout">
        <aside class="ud-sidebar" id="sidebar">
            @include('partials.user-panel-sidebar')
        </aside>

        <main class="ud-main">
            <button type="button" class="ud-mobile-nav-trigger" data-user-panel-nav-trigger
                    aria-controls="sidebar" aria-expanded="false" aria-label="Open account navigation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span>Account menu</span>
            </button>
            <div class="ud-panel-content @yield('panel-page-class')">
                @include('partials.user-panel-page-hero')
                @yield('panel-content')
            </div>
        </main>
    @push('scripts')
        @include('partials.user-panel-scripts')
    @endpush
@endsection
