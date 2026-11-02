<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\UpdateSideSectionRequest;
use App\Models\Setting;
use App\Services\Admin\SideSectionSettingsService;

class SideSectionController extends Controller
{
    public function index()
    {
        $settings = [
            'side_section_logo' => Setting::get('side_section_logo'),
            'side_section_contact_number' => Setting::get('side_section_contact_number'),
            'side_section_email' => Setting::get('side_section_email'),
            'side_section_address' => Setting::get('side_section_address'),
            'side_section_description' => Setting::get('side_section_description'),
            'side_section_social_links' => Setting::get('side_section_social_links', '[]')
        ];

        return view('admin.system.side-section.index', compact('settings'));
    }

    public function update(UpdateSideSectionRequest $request, SideSectionSettingsService $settingsService)
    {
        $settingsService->update($request->validated(), $request->file('logo'));

        return back()->with('success', 'Side section settings updated successfully.');
    }
}
