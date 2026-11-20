# NutriBuddy

Laravel application for NutriBuddy's storefront, administration, orders, and nutrition services.

## Requirements

- PHP 8.3 with the extensions required by Laravel and Composer dependencies
- Composer 2
- Node.js 22 and npm
- MySQL for development or a disposable local test database

The project currently declares PHP `^8.2` and Laravel 11. CI verifies the application on PHP 8.3.

## Local setup

```sh
composer install
copy .env.example .env
php artisan key:generate
npm ci
```

Set local database and service credentials in `.env`; never copy production credentials into a development environment. Create a local database, then run `php artisan migrate`. Seed only when needed for local development and after reviewing the seeders.

Start the app and frontend asset watcher in separate terminals:

```sh
php artisan serve
npm run dev
```

## Quality checks

```sh
composer validate
vendor/bin/pint --test
php artisan test
npm run build
npm audit --audit-level=moderate
git diff --check
```

See `.ai/TESTING_RULES.md` for the full checklist and `.ai/DATABASE_RULES.md` for database safeguards. GitHub Actions runs quality checks, disposable MySQL migration/tests, frontend build, and Composer/npm dependency audits. It does not deploy.

## Deployment

Production deployment to Hostinger and production database migrations are performed manually by the project owner. A passing CI run does not deploy or authorize a production change.
