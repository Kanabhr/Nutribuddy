<?php

namespace App\Http\Controllers\Admin;

/**
 * Prevent resource actions that conflict with their domain lifecycle.
 * Purchases, user-submitted content, and order-derived records are created
 * through their dedicated flows, not from admin CRUD routes.
 */
trait ResourceWritesDisabled
{
    public function store(...$parameters): never
    {
        abort(405);
    }

    public function update(...$parameters): never
    {
        abort(405);
    }

    public function destroy(...$parameters): never
    {
        abort(405);
    }
}
