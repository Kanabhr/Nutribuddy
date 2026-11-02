<?php

namespace App\Http\Controllers\Frontend\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreContactLeadRequest;
use App\Models\ContactLead;

class ContactController extends Controller
{
    public function index()
    {
        return view('frontend.storefront.pages.contact');
    }

    public function store(StoreContactLeadRequest $request)
    {
        $validated = $request->validated();

        $lead = ContactLead::create([
            'name'    => trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? '')),
            'email'   => $validated['email'],
            'phone'   => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status'  => 'new',
        ]);

        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewContactLeadNotification($lead));
        }

        return back()->with('contact_success', 'Your message has been sent! We\'ll get back to you within 24 hours. 🎉');
    }
}
