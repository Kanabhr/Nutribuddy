<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * Route resource pages back to the listing for admin screens that use inline
 * forms and row actions instead of separate create, edit, or show pages.
 */
trait ResourceIndexFallbacks
{
    public function create(): RedirectResponse
    {
        return $this->redirectToResourceIndex();
    }

    public function show(...$parameters): RedirectResponse
    {
        return $this->redirectToResourceIndex();
    }

    public function edit(...$parameters): RedirectResponse
    {
        return $this->redirectToResourceIndex();
    }

    private function redirectToResourceIndex(): RedirectResponse
    {
        $name = request()->route()?->getName();
        $indexRoute = is_string($name)
            ? preg_replace('/\.(?:create|show|edit)$/', '.index', $name)
            : null;

        abort_unless($indexRoute && Route::has($indexRoute), 404);

        return redirect()->route($indexRoute);
    }
}
