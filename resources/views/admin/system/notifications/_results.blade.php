<div class="card border-0 radius-12">
    <div class="card-header bg-base border-bottom py-16 px-24 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h5 class="card-title mb-1">Notifications</h5>
            <span class="text-sm text-secondary-light">Showing {{ $notifications->firstItem() ?? 0 }}–{{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }}</span>
        </div>
        @if ($hasUnreadNotifications)
            <button type="button" id="mark-all-read" class="btn btn-sm btn-primary-600 d-inline-flex align-items-center gap-1"
                    data-url="{{ route('admin.ecommerce.notifications.read-all') }}">
                <iconify-icon icon="lucide:check-check" class="text-xl"></iconify-icon>
                Mark All as Read
            </button>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table bordered-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">Notification</th>
                        <th scope="col">Received</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $typeKey = collect($notificationTypes)->search(fn ($type) => $type['class'] === $notification->type);
                            $typeLabel = $notificationTypes[$typeKey]['label'] ?? 'Other';
                            $isUnread = is_null($notification->read_at);
                        @endphp
                        <tr id="notification-{{ $notification->id }}" class="{{ $isUnread ? 'notification-unread' : '' }}">
                            <td><span class="badge bg-info-100 text-info-600">{{ $typeLabel }}</span></td>
                            <td class="notification-copy">
                                <div class="fw-semibold {{ $isUnread ? 'text-primary-600' : 'text-dark' }} notification-title">
                                    {{ $data['title'] ?? 'System Notification' }}
                                </div>
                                <div class="text-sm text-secondary-light notification-message">
                                    {{ $data['message'] ?? 'You have a new activity.' }}
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $notification->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $isUnread ? 'bg-warning-100 text-warning-main' : 'bg-success-100 text-success-600' }} notification-status">
                                    {{ $isUnread ? 'Unread' : 'Read' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                                    @if ($isUnread)
                                        <button type="button" class="btn btn-sm btn-outline-primary mark-as-read"
                                                data-id="{{ $notification->id }}"
                                                data-url="{{ route('admin.ecommerce.notifications.read', $notification->id) }}">
                                            Mark read
                                        </button>
                                    @endif
                                    @if (! empty($data['action_url']))
                                        <a href="{{ $data['action_url'] }}" class="btn btn-sm btn-primary-600">View</a>
                                    @endif
                                    <form action="{{ route('admin.ecommerce.notifications.destroy', $notification->id) }}" method="POST"
                                          class="notification-delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary-light">No notifications match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($notifications->hasPages())
        <div class="card-footer bg-base border-top py-16 px-24 d-flex justify-content-end">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
