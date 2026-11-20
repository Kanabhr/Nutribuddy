# Testing and local quality checks

Install PHP dependencies with `composer install` and frontend dependencies with `npm ci`.

## Required regression workflow

For every code change:

1. Check `git status` and inspect the affected implementation, callers, routes, permissions, database operations, views, and dependent workflows before editing.
2. Run relevant existing tests before editing and record the baseline, including existing failures. Identify the tests and business rules that cover the affected behavior.
3. Add or update tests for the new or changed behavior. Run those tests, directly affected regression tests, dependent module or integration tests, and the complete available suite.
4. Verify affected routes, permissions, database behavior, calculations, reports, and frontend or Livewire interactions where applicable.
5. Compare the results to baseline. Investigate and fix regressions without changing or removing existing tests just to make the suite pass.
6. Review the final diff and report passes, failures, baseline failures, skipped checks, unverified behavior, and remaining risks. Do not claim unrelated functionality is unaffected without evidence.

If coverage is missing, add relevant tests where practical. If a check cannot be run, say why and identify the behavior that remains unverified.

## Local quality checks

After focused regression tests, run the full available checks and report failures separately from skipped checks:

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
