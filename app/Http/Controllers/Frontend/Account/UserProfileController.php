<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\Account\UpdateProfileRequest;
use App\Services\Storefront\UserProfileService;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, UserProfileService $profiles): JsonResponse
    {
        $user = $profiles->update($request->user(), $request->validated(), $request->file('avatar'));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
        ]);
    }
}
