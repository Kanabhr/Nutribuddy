<?php

namespace App\Services\Admin;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Saves the side-section settings and optional logo together. */
class SideSectionSettingsService
{
    /** @param array<string, mixed> $data */
    public function update(array $data, ?UploadedFile $logo = null): void
    {
        $logoPath = $logo?->store('settings', 'public');
        $previousLogo = Setting::get('side_section_logo');
        $socialLinks = [];
        if (isset($data['social_platform'], $data['social_url'])) {
            foreach ($data['social_platform'] as $key => $platform) {
                if (! empty($data['social_url'][$key])) {
                    $socialLinks[] = ['platform' => $platform, 'url' => $data['social_url'][$key]];
                }
            }
        }

        try {
            DB::transaction(function () use ($data, $logoPath, $socialLinks): void {
                if ($logoPath) {
                    Setting::set('side_section_logo', $logoPath, 'side_section');
                } elseif (Setting::where('key', 'side_section_logo')->doesntExist()) {
                    Setting::set('side_section_logo', null, 'side_section');
                }

                Setting::set('side_section_contact_number', $data['contact_number'], 'side_section');
                Setting::set('side_section_email', $data['email'], 'side_section');
                Setting::set('side_section_address', $data['address'] ?? null, 'side_section');
                Setting::set('side_section_description', $data['description'] ?? null, 'side_section');
                Setting::set('side_section_social_links', json_encode($socialLinks), 'side_section');
            });
        } catch (Throwable $exception) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            throw $exception;
        }

        if ($logoPath && $previousLogo) {
            Storage::disk('public')->delete($previousLogo);
        }
    }
}
