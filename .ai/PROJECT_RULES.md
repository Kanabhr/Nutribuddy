# Project architecture and coding standards

## Stack

- Laravel 11 (`laravel/framework` 11.51.0 in the inspected lockfile), PHP constraint `^8.2`; CI runs PHP 8.3.
- MySQL is the production database. PHPUnit currently defaults to in-memory SQLite; CI uses a disposable MySQL 8 service to check migration compatibility.
- Server-rendered Blade frontend with Vite assets. The inspected Composer manifests do not include Livewire.
- Authentication includes separate admin and customer flows, with Spatie Laravel Permission for roles and permissions.

## Structure and conventions

- Routes are split by domain under `routes/` and included from `routes/web.php`.
- Controllers are grouped under `app/Http/Controllers`; request validation belongs in the existing Form Request pattern under `app/Http/Requests`.
- Reuse `app/Services`, models, middleware, and policies where suitable. Keep business rules out of views and avoid duplicate logic.
- Blade templates are under `resources/views`; frontend entry points are configured in `vite.config.js`.
- Keep payment and webhook behavior server-verified and use the existing rules in `.agent/rules/` for sensitive flows.

Read the relevant files under `.agent/rules/` before work on payments, data integrity, security, database access, or frontend assets.
