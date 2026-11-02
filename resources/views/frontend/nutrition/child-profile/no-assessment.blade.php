@extends('layouts.user-panel')

@section('content')
    <section class="container py-5">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 720px;">
            <div class="card-body p-4 p-md-5 text-center">
                <h1 class="h3 mb-3">Assessment needed for {{ $child->name }}</h1>
                <p class="text-secondary mb-4">{{ $message }}</p>
                <a class="btn btn-primary" href="{{ route('frontend.nutrition.questionnaire') }}">Start assessment</a>
            </div>
        </div>
    </section>
@endsection
