@extends('layouts.admin')
@php
    $title = 'Notifications';
    $subTitle = 'Administrative / Notifications';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card border-0 radius-12 mb-24">
        <div class="card-body p-24">
            <form id="notification-filter-form" method="GET" action="{{ route('admin.ecommerce.notifications.index') }}"
                  class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label for="notification-search" class="form-label">Search notifications</label>
                    <input id="notification-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           class="form-control" maxlength="100" placeholder="Search title or message">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="notification-type" class="form-label">Type</label>
                    <select id="notification-type" name="type" class="form-select">
                        <option value="">All types</option>
                        @foreach ($notificationTypes as $key => $type)
                            <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="notification-status" class="form-label">Status</label>
                    <select id="notification-status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="unread" @selected(($filters['status'] ?? '') === 'unread')>Unread</option>
                        <option value="read" @selected(($filters['status'] ?? '') === 'read')>Read</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="notification-from" class="form-label">From</label>
                    <input id="notification-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="notification-to" class="form-label">To</label>
                    <input id="notification-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-600">Filter</button>
                    <a href="{{ route('admin.ecommerce.notifications.index') }}" id="notification-clear-filters"
                       class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div id="notification-feedback" class="alert alert-danger d-none" role="status" aria-live="polite"></div>
    <div id="notification-results" aria-live="polite">
        @include('admin.system.notifications._results')
    </div>

    <style>
        .notification-copy { min-width: 260px; max-width: 620px; white-space: normal; overflow-wrap: anywhere; }
        .notification-copy > div + div { margin-top: 4px; }
        .notification-unread { background: var(--bs-primary-bg-subtle, #f4f7ff); }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('notification-filter-form');
            const results = document.getElementById('notification-results');
            const feedback = document.getElementById('notification-feedback');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let activeRequest;

            const showError = (message) => {
                feedback.textContent = message;
                feedback.classList.remove('d-none');
            };
            const clearError = () => {
                feedback.textContent = '';
                feedback.classList.add('d-none');
            };
            const syncFilters = (url) => {
                const params = new URL(url).searchParams;
                for (const field of form.elements) {
                    if (field.name) field.value = params.get(field.name) ?? '';
                }
            };
            const loadResults = async (url, pushState = true) => {
                activeRequest?.abort();
                const controller = new AbortController();
                activeRequest = controller;
                results.setAttribute('aria-busy', 'true');
                results.classList.add('opacity-50');
                clearError();

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        signal: controller.signal
                    });
                    if (!response.ok) {
                        const error = await response.json().catch(() => ({}));
                        const message = error.message || Object.values(error.errors || {}).flat()[0] || 'Could not load notifications.';
                        throw new Error(message);
                    }

                    const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const content = parsed.body.firstElementChild;
                    if (!content) throw new Error('The notification response was empty.');
                    results.replaceChildren(content);
                    if (pushState) history.pushState({}, '', url);
                } catch (error) {
                    if (error.name !== 'AbortError') showError(error.message || 'Could not load notifications.');
                } finally {
                    if (activeRequest === controller) {
                        results.removeAttribute('aria-busy');
                        results.classList.remove('opacity-50');
                    }
                }
            };
            const filteredUrl = () => {
                const url = new URL(form.action);
                for (const [key, value] of new FormData(form)) {
                    if (String(value).trim()) url.searchParams.set(key, value);
                }
                return url;
            };
            const sendAction = async (url, method = 'POST') => {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Notification update failed.');
                return data;
            };

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                loadResults(filteredUrl());
            });

            document.addEventListener('click', function (event) {
                const clearLink = event.target.closest('#notification-clear-filters');
                if (clearLink) {
                    event.preventDefault();
                    form.reset();
                    loadResults(clearLink.href);
                    return;
                }

                const pageLink = event.target.closest('#notification-results .pagination a[href]');
                if (pageLink) {
                    event.preventDefault();
                    loadResults(pageLink.href);
                    return;
                }

                const markRead = event.target.closest('#notification-results .mark-as-read');
                if (markRead) {
                    sendAction(markRead.dataset.url).then(() => loadResults(window.location.href, false)).catch(error => showError(error.message));
                    return;
                }

                const markAll = event.target.closest('#mark-all-read');
                if (markAll) {
                    sendAction(markAll.dataset.url).then(() => loadResults(window.location.href, false)).catch(error => showError(error.message));
                }
            });

            document.addEventListener('submit', function (event) {
                const deleteForm = event.target.closest('#notification-results .notification-delete-form');
                if (!deleteForm) return;
                event.preventDefault();
                if (!window.confirm('Delete this notification?')) return;

                const button = deleteForm.querySelector('button[type="submit"]');
                button.disabled = true;
                sendAction(deleteForm.action, 'DELETE').then(data => {
                    const url = new URL(window.location.href);
                    const page = Number(url.searchParams.get('page') || 1);
                    if (page > 1 && data.remaining <= (page - 1) * 10) url.searchParams.set('page', String(page - 1));
                    history.replaceState({}, '', url);
                    return loadResults(url, false);
                }).catch(error => {
                    button.disabled = false;
                    showError(error.message);
                });
            });

            window.addEventListener('popstate', function () {
                syncFilters(window.location.href);
                loadResults(window.location.href, false);
            });
        });
    </script>
@endsection
