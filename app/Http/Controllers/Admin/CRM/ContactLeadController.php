<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CRM\UpdateContactLeadRequest;
use App\Models\ContactLead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactLeadController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceWritesDisabled;
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    public function index(): View
    {
        return view('admin.crm.contact-leads.index', [
            'leads' => ContactLead::with('assignee')->latest()->get(),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateContactLeadRequest $request, ContactLead $contactLead): RedirectResponse
    {
        $contactLead->update($request->validated());

        return back()->with('success', 'Lead updated successfully.');
    }

    public function destroy(ContactLead $contactLead): RedirectResponse
    {
        $contactLead->delete();

        return back()->with('success', 'Lead deleted successfully.');
    }
}
