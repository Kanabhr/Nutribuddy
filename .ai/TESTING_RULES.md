# Testing and local quality checks

Install PHP dependencies with `composer install` and frontend dependencies with `npm ci`.

Before a focused change, check `git status` and run relevant existing tests to establish a baseline. Afterward run the applicable checks and report failures separately from skipped checks:

```sh
composer validate
find app bootstrap config database routes tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
vendor/bin/pint --test
php artisan test
npm run build
npm audit --audit-level=moderate
git diff --check
```

The repository's PHPUnit configuration uses in-memory SQLite by default. CI additionally runs migrations and the test suite against a disposable MySQL 8 service. Never point local checks or CI at production. Composer security auditing and outdated-package reporting run in GitHub Actions.
