@extends('layouts.admin')
@php
    $title = 'Notification Templates';
    $subTitle = 'System / Customer notifications';
    $channelLabels = ['email' => 'Email', 'whatsapp' => 'WhatsApp'];
@endphp

@section('content')
    @include('admin.partials.messages')

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 radius-12 mb-24">
        <div class="card-body p-24">
            <h6 class="mb-8">How this works</h6>
            <ul class="text-sm text-secondary-light mb-12">
                <li><strong>Email</strong> templates are plain text. Use the values below in curly braces, for example <code>{order_number}</code>.</li>
                <li><strong>WhatsApp</strong> messages use a template approved in AiSensy. Enter its campaign name and the list of values it expects,
                    in the same order as the variables in that template. Leave it switched off until the campaign exists.</li>
                <li>The "Order placed" email is still the existing order confirmation email and is not edited here.</li>
                <li>Each message is sent once per order and event. Failures never block checkout or order updates; see the log below.</li>
            </ul>
            <div class="row g-2">
                @foreach ($placeholders as $key => $description)
                    <div class="col-md-6 text-sm"><code>{{ '{'.$key.'}' }}</code> <span class="text-secondary-light">{{ $description }}</span></div>
                @endforeach
            </div>
        </div>
    </div>

    @foreach ($events as $eventKey => $event)
        <div class="card border-0 radius-12 mb-24">
            <div class="card-header"><h6 class="mb-0">{{ $event['label'] }}</h6></div>
            <div class="card-body p-24">
                <div class="row g-4">
                    @foreach ($event['channels'] as $channel)
                        @php $template = $templates[$eventKey][$channel] ?? null; @endphp
                        <div class="col-lg-6">
                            <h6 class="text-sm mb-12">{{ $channelLabels[$channel] }}</h6>
                            @if (! $template)
                                <p class="text-sm text-secondary-light">Template row missing. Run the point 26 migration.</p>
                            @else
                                <form method="POST" action="{{ route('admin.system.notification-templates.update', $template) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-check form-switch mb-12">
                                        <input type="hidden" name="enabled" value="0">
                                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled-{{ $template->id }}" @checked($template->enabled)>
                                        <label class="form-check-label" for="enabled-{{ $template->id }}">Send this message</label>
                                    </div>

                                    @if ($channel === 'email')
                                        <div class="mb-12">
                                            <label class="form-label">Subject</label>
                                            <input type="text" name="subject" class="form-control" maxlength="200" value="{{ old('subject', $template->subject) }}" required>
                                        </div>
                                        <div class="mb-12">
                                            <label class="form-label">Message</label>
                                            <textarea name="body" rows="8" class="form-control" maxlength="5000" required>{{ old('body', $template->body) }}</textarea>
                                        </div>
                                    @else
                                        <div class="mb-12">
                                            <label class="form-label">AiSensy campaign name</label>
                                            <input type="text" name="whatsapp_campaign" class="form-control" maxlength="100" value="{{ old('whatsapp_campaign', $template->whatsapp_campaign) }}">
                                        </div>
                                        <div class="mb-12">
                                            <label class="form-label">Values, in order (comma separated)</label>
                                            <input type="text" name="whatsapp_params" class="form-control" maxlength="255" value="{{ old('whatsapp_params', implode(', ', (array) $template->whatsapp_params)) }}">
                                        </div>
                                    @endif

                                    <button type="submit" class="btn btn-primary-600 btn-sm">Save</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="card basic-data-table">
        <div class="card-header"><h6 class="mb-0">Recent sends (latest 40)</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr><th>When</th><th>Order</th><th>Event</th><th>Channel</th><th>Status</th><th>To</th><th>Note</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                                <td>
                                    @if ($log->order)
                                        <a href="{{ route('admin.ecommerce.orders.show', $log->order) }}">{{ $log->order->order_number }}</a>
                                    @endif
                                </td>
                                <td>{{ $events[$log->event]['label'] ?? $log->event }}</td>
                                <td>{{ $channelLabels[$log->channel] ?? $log->channel }}</td>
                                <td>
                                    @if ($log->status === 'sent')
                                        <span class="badge bg-success-100 text-success-600 px-2">Sent</span>
                                    @elseif ($log->status === 'failed')
                                        <span class="badge bg-danger-100 text-danger-600 px-2">Failed</span>
                                    @else
                                        <span class="badge bg-warning-100 text-warning-600 px-2">{{ ucfirst($log->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $log->recipient }}</td>
                                <td class="text-sm text-secondary-light">{{ $log->error }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-secondary-light">Nothing sent yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection