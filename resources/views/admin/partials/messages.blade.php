@if (session('success'))
    <div class="alert alert-success bg-success-100 text-success-700 border-success-200 mb-16">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger bg-danger-100 text-danger-700 border-danger-200 mb-16">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger bg-danger-100 text-danger-700 border-danger-200 mb-16">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
