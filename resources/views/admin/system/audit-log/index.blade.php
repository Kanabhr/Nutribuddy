@extends('layouts.admin')
@php
    $title    = 'Audit Log';
    $subTitle = 'System / Audit Log';
@endphp
@section('content')
    @include('admin.partials.messages')

    {{-- Filters --}}
    <div class="card border-0 radius-12 mb-24">
        <div class="card-body p-24">
            <form method="GET" action="{{ route('admin.system.audit-log.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Actor (Admin)</label>
                    <select name="actor_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" {{ request('actor_id') == $actor->id ? 'selected' : '' }}>
                                {{ $actor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Entity</label>
                    <select name="entity" class="form-select">
                        <option value="">All</option>
                        @foreach ($entities as $entity)
                            <option value="{{ $entity }}" {{ request('entity') === $entity ? 'selected' : '' }}>
                                {{ $entity }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.system.audit-log.index') }}" class="btn btn-outline-secondary ms-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card basic-data-table">
        <div class="card-header">
            <h5 class="card-title mb-0">Audit Log
                <span class="badge bg-secondary-100 text-secondary-600 ms-2">{{ $logs->total() }} entries</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Changed Fields</th>
                            <th>IP</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-secondary-light text-xs">{{ $log->id }}</td>
                                <td>
                                    @if ($log->actor)
                                        <span class="fw-semibold">{{ $log->actor->name }}</span>
                                        <div class="text-xs text-secondary-light">{{ $log->actor_type }}</div>
                                    @elseif ($log->actor_id)
                                        <span class="text-secondary-light">#{{ $log->actor_id }} ({{ $log->actor_type }})</span>
                                    @else
                                        <span class="badge bg-secondary-100 text-secondary-600">{{ $log->actor_type }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($log->action) {
                                            'created' => 'bg-success-100 text-success-600',
                                            'deleted' => 'bg-danger-100 text-danger-600',
                                            default   => 'bg-warning-100 text-warning-600',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-2">{{ $log->action }}</span>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $log->auditable_type }}</span>
                                    @if ($log->auditable_id)
                                        <div class="text-xs text-secondary-light">#{{ $log->auditable_id }}</div>
                                    @endif
                                </td>
                                <td style="max-width:380px;">
                                    @if ($log->old_values || $log->new_values)
                                        <details>
                                            <summary class="text-xs text-primary-600 cursor-pointer">
                                                {{ count(array_keys((array)$log->new_values ?: $log->old_values)) }} field(s)
                                            </summary>
                                            <div class="mt-1">
                                                @foreach (array_keys(array_merge((array)$log->old_values, (array)$log->new_values)) as $field)
                                                    <div class="text-xs mb-1">
                                                        <span class="fw-semibold text-dark">{{ $field }}:</span>
                                                        @if (isset($log->old_values[$field]))
                                                            <span class="text-danger-600 text-decoration-line-through">{{ Str::limit((string) $log->old_values[$field], 60) }}</span>
                                                            →
                                                        @endif
                                                        <span class="text-success-600">{{ Str::limit((string) ($log->new_values[$field] ?? '—'), 60) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-secondary-light text-xs">—</span>
                                    @endif
                                </td>
                                <td class="text-xs text-secondary-light">{{ $log->ip_address ?? '—' }}</td>
                                <td class="text-xs text-secondary-light text-nowrap">
                                    {{ $log->created_at->format('d M Y, H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-secondary-light py-32">No audit entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer py-16 px-24">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
