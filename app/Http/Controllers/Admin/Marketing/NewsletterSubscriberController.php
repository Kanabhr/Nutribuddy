<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Marketing\StoreNewsletterSubscriberRequest;
use App\Http\Requests\Admin\Marketing\UpdateNewsletterSubscriberRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class NewsletterSubscriberController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.marketing.newsletter.index', [
            'subscribers' => NewsletterSubscriber::latest()->get(),
        ]);
    }

    public function store(StoreNewsletterSubscriberRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['subscribed_at'] = now();
        $validated['unsubscribed_at'] = $validated['status'] === 'unsubscribed' ? now() : null;

        NewsletterSubscriber::create($validated);

        return back()->with('success', 'Subscriber added successfully.');
    }

    public function update(UpdateNewsletterSubscriberRequest $request, NewsletterSubscriber $newsletter): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['status'] === 'subscribed') {
            $validated['subscribed_at'] = $newsletter->subscribed_at ?? now();
            $validated['unsubscribed_at'] = null;
        } else {
            $validated['unsubscribed_at'] = now();
        }

        $newsletter->update($validated);

        return back()->with('success', 'Subscriber updated successfully.');
    }

    public function destroy(NewsletterSubscriber $newsletter): RedirectResponse
    {
        $newsletter->delete();

        return back()->with('success', 'Subscriber deleted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $status = $request->string('status')->toString();

        $fileName = 'newsletter-subscribers-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(function () use ($status) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Email', 'Name', 'Status', 'Source', 'Subscribed At', 'Unsubscribed At', 'Created At']);

            NewsletterSubscriber::query()
                ->when(in_array($status, ['subscribed', 'unsubscribed'], true), fn ($query) => $query->where('status', $status))
                ->orderBy('email')
                ->chunk(200, function ($subscribers) use ($handle) {
                    foreach ($subscribers as $subscriber) {
                        fputcsv($handle, [
                            $subscriber->email,
                            $subscriber->name,
                            $subscriber->status,
                            $subscriber->source,
                            optional($subscriber->subscribed_at)->format('Y-m-d H:i:s'),
                            optional($subscriber->unsubscribed_at)->format('Y-m-d H:i:s'),
                            optional($subscriber->created_at)->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, $headers);
    }
}
