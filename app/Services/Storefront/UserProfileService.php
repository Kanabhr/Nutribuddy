<?php

namespace App\Services\Storefront;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Updates profile fields and safely replaces an optional avatar. */
class UserProfileService
{
    /** @param array<string, mixed> $data */
    public function update(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        $attributes = [
            'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'phone' => $data['phone'] ?? null,
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'bio' => $data['bio'] ?? null,
        ];
        $newAvatar = $avatar?->store('avatars', 'public');
        $previousAvatar = $user->avatar;
        if ($newAvatar) {
            $attributes['avatar'] = $newAvatar;
        }

        try {
            DB::transaction(function () use ($user, $attributes): void {
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $lockedUser->update($attributes);
            });
        } catch (Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
            throw $exception;
        }

        $user->refresh();
        if ($newAvatar && $previousAvatar) {
            Storage::disk('public')->delete($previousAvatar);
        }

        return $user;
    }
}
