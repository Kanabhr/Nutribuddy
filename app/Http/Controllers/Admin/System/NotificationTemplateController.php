<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Support\NotificationEvents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.system.notification-templates.index', [
            'events' => NotificationEvents::all(),
            'templates' => NotificationTemplate::all()->groupBy('event')->map->keyBy('channel'),
            'placeholders' => NotificationEvents::placeholders(),
            'logs' => NotificationLog::with('order:id,order_number')->latest('id')->limit(40)->get(),
        ]);
    }

    public function update(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $enabled = $request->boolean('enabled');

        if ($template->channel === 'email') {
            $data = $request->validate([
                'subject' => ['required', 'string', 'max:200'],
                'body' => ['required', 'string', 'max:5000'],
            ]);
            $template->fill($data);
        } else {
            $data = $request->validate([
                'whatsapp_campaign' => ['nullable', 'string', 'max:100'],
                'whatsapp_params' => ['nullable', 'string', 'max:255'],
            ]);

            $campaign = trim((string) ($data['whatsapp_campaign'] ?? ''));
            $params = array_values(array_filter(array_map('trim', explode(',', (string) ($data['whatsapp_params'] ?? '')))));

            $unknown = array_diff($params, array_keys(NotificationEvents::placeholders()));
            if ($unknown) {
                return back()->withErrors(['whatsapp_params' => 'Unknown value(s): '.implode(', ', $unknown).'. Use the names listed on this page.'])->withInput();
            }
            if ($enabled && $campaign === '') {
                return back()->withErrors(['whatsapp_campaign' => 'Enter the AiSensy campaign name before switching WhatsApp on.'])->withInput();
            }

            $template->whatsapp_campaign = $campaign !== '' ? $campaign : null;
            $template->whatsapp_params = $params;
        }

        $template->enabled = $enabled;
        $template->updated_by = $request->user('admin')?->id;
        $template->save();

        return back()->with('success', 'Template saved.');
    }
}