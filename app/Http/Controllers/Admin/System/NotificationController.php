<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['order', 'return', 'contact', 'support'])],
            'status' => ['nullable', Rule::in(['unread', 'read'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $notificationTypes = [
            'order' => ['label' => 'Orders', 'class' => \App\Notifications\NewOrderNotification::class],
            'return' => ['label' => 'Returns', 'class' => \App\Notifications\NewReturnNotification::class],
            'contact' => ['label' => 'Contact leads', 'class' => \App\Notifications\NewContactLeadNotification::class],
            'support' => ['label' => 'Support tickets', 'class' => \App\Notifications\NewSupportTicketNotification::class],
            'nutrition' => ['label' => 'Diet chart & subscriptions', 'class' => \App\Notifications\DietLifecycleNotification::class],
        ];

        $query = $request->user('admin')->notifications()->latest();

        if (! empty($filters['type'])) {
            $query->where('type', $notificationTypes[$filters['type']]['class']);
        }

        if (! empty($filters['status'])) {
            $filters['status'] === 'unread'
                ? $query->whereNull('read_at')
                : $query->whereNotNull('read_at');
        }

        if (! empty($filters['search'])) {
            $search = '%' . addcslashes($filters['search'], '\\%_') . '%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('data', 'like', $search)
                    ->orWhere('type', 'like', $search);
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $notifications = $query->paginate(10)->withQueryString();
        $hasUnreadNotifications = $request->user('admin')->unreadNotifications()->exists();

        $viewData = compact(
            'notifications',
            'notificationTypes',
            'filters',
            'hasUnreadNotifications',
        );

        return $request->ajax()
            ? view('admin.system.notifications._results', $viewData)
            : view('admin.system.notifications.index', $viewData);
    }

    public function markAsRead($id)
    {
        $notification = request()->user('admin')->notifications()->findOrFail($id);
        $notification->markAsRead();
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Notification marked as read.']);
        }
        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        request()->user('admin')->unreadNotifications()->update(['read_at' => now()]);
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
        }
        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy($id)
    {
        $admin = request()->user('admin');
        $notification = $admin->notifications()->findOrFail($id);
        $notification->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'remaining' => $admin->notifications()->count(),
            ]);
        }

        return back()->with('success', 'Notification deleted.');
    }
}
