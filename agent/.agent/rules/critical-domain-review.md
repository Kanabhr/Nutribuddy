# Critical Domain Review & Refactoring Rules

Use this ruleset for changes to Products and checkout, Nutrition and diet charts, Assessments and questionnaires, Nutrition subscriptions, and customer/admin authentication and authorization. The goal is to find and fix real defects while preserving established route, view, API, database, payment, and session behavior.

## 1. Baseline and Scope

Before changing code:

1. Read `AGENTS.md` and the relevant focused rules, especially `controller-optimization-and-refactor.md`, `diet-chart-generation.md`, `database-eloquent.md`, and `architecture-services.md`.
2. Record the current branch and working tree. Do not overwrite unrelated user changes or add unrelated artifacts.
3. Map each flow end to end: routes and middleware, controller, request validation, services/models, migrations, views/JavaScript, payment callbacks/webhooks, and tests. Search for all callers before changing a route, response, service contract, model field, or view name.
4. Run the narrowest relevant regression tests before editing and record existing failures. Do not treat a pre-existing failure as caused by the refactor.
5. Work in small, domain-bounded changes. Preserve current behavior unless a verified defect requires a deliberate correction; add a regression test for every corrected defect.

Use the APIs supported by the installed Laravel 11 dependency. Check Laravel's official versioned documentation for unfamiliar framework behavior. A major framework upgrade, new authentication/token package, payment provider, or queue/cache infrastructure change is a separately scoped migration; do not bundle one into routine domain refactoring.

## 2. Contracts That Must Be Preserved

Unless a behavior change is explicitly approved and covered by tests, preserve:

- Named routes, HTTP methods, middleware, redirects, validation errors, flash messages, Blade view names, and required view data.
- Existing JSON status codes, keys, types, and error shapes, including questionnaire, cart, payment, and meal logging endpoints.
- Existing order, payment, assessment, diet-plan, and subscription records and their relationships.
- Admin/customer guard separation, role and permission semantics, ownership checks, and CSRF protection.
- Existing payment gateway signature verification and callback contracts. A browser redirect or client-supplied success value alone must never prove payment.

When a contract must change, update all callers and document the migration and compatibility behavior before removing the old contract.

## 3. Product, Cart, Discount, Coins, and Checkout

Review the complete customer journey: catalog/search and product detail, variants/options, stock and price display, cart add/update/remove, guest-to-authenticated cart behavior, coupons/discounts, coin earning/redemption, address/shipping/tax totals, order placement, payment, order history, cancellation, returns, and refunds.

- Treat client-submitted prices, discounts, tax, coin balances, stock, and totals as untrusted. Recompute payable totals from current server-side records and validated selections.
- Use one authoritative pricing path for cart summaries and order placement. Keep currency precision exact (integer minor units or the established decimal strategy); avoid floating-point monetary arithmetic.
- Validate product/variant ownership, active status, purchasable quantity, stock, coupon eligibility/usage limits, coin balance/caps, and discount stacking on the server at the point of use.
- Make order placement and coin/coupon/stock side effects atomic. Protect concurrent purchases/redemptions with database transactions and appropriate row locks or conditional updates. Avoid charging or decrementing twice on retries.
- Make checkout/payment operations idempotent where repeated browser submissions, gateway callbacks, or webhooks can occur. Verify signatures and match gateway IDs, user/order, amount, currency, and expected state before applying effects.
- Keep order totals and the applied price/discount/tax/coin breakdown as a durable snapshot so later catalog or coupon edits do not rewrite purchase history.
- Add regression tests for guest/auth flows as applicable, variants, boundaries, expired/used coupons, stacking rules, coin limits, stock races where feasible, duplicate submissions/callbacks, and cancellation/refund reversals.

Do not invent new coupon stacking, coin expiry, loyalty earning, guest-cart merge, or refund policy. First establish the current product policy from code/tests and preserve it; raise a product decision only where behavior is ambiguous and implementation depends on it.

## 4. Nutrition and Diet Chart Safety

Review questionnaire submission through profile normalization, assessment persistence, target calculation, food filtering, plan generation, nutritionist review/approval, parent display, regeneration, meal logging, and PDF/export.

- Preserve strict allergy, dietary restriction, disliked-food, age suitability, clinical triage, and professional-review gates in `diet-chart-generation.md`. Safety filters run before ranking or AI output is accepted.
- Treat submitted answers and generated/AI content as untrusted. Validate and normalize them, constrain generated food IDs to eligible records, and provide a safe failure path without silently dispatching an unsafe plan.
- Keep child ownership checks on every read/write route, including downloads, regeneration, check-ins, and meal-item updates. Never rely on a client-provided child ID alone.
- Persist assessment/plan transitions consistently. Keep pending clinical review pending until an authorized nutritionist approves; reject invalid or stale state transitions.
- Make generation and quota/entitlement consumption atomic and retry-safe. Preserve plan versioning, audit/debug provenance needed by support, and existing nutrition data contracts.
- Test allergy and diet exclusions, risk flags, missing/partial assessments, duplicate submission, failed generation rollback, ownership denial, review permissions/transitions, regeneration quotas, and rendered/PDF output contracts.

Do not change medical formulas, ICMR targets, safety thresholds, or clinical advice as a code cleanup. Changes to these require domain review and explicit expected-value tests.

## 5. Assessment and Questionnaire

Review public/client questionnaire rendering and submission, admin question/option/section management, scoring, history/results, comparison, and downstream diet-chart creation.

- Preserve question identifiers, answer formats, conditional/required behavior, ordering, scoring rules, and existing client/API response contracts.
- Validate question and option IDs against the active assessment and ensure submitted answers belong to the correct user/child/attempt. Reject malformed, duplicated, stale, or cross-assessment answers safely.
- Persist a submitted attempt and its answers/results atomically; define and test retry/duplicate submission behavior without creating duplicate attempts or plans.
- Restrict question/section changes to the correct admin permissions and preserve ordering/active-status behavior.
- Test browser and API submissions, validation failures, inactive/unknown questions, score/result consistency, authorization/ownership, admin CRUD/reordering, and the handoff to diet-plan generation.

## 6. Nutrition Subscription and Renewal Lifecycle

First document the behavior the current product and payment gateways actually implement. Distinguish a one-time prepaid entitlement from a recurring, automatically charged subscription; do not describe or implement automatic renewal unless the selected gateway, customer consent/mandate, scheduled charge flow, notifications, and cancellation rules support it.

- Model and test explicit lifecycle states and legal transitions for pending payment, active entitlement, expiry, cancellation, failure/refund, and renewal. Keep payment status separate from entitlement status where needed.
- Use persisted gateway identifiers and verified gateway events as payment authority. Verify signatures and ownership; handle duplicate and out-of-order webhooks idempotently. A return URL is not authoritative proof of payment.
- Make activation, entitlement/quota grants, diet-lead creation, and renewal linkage transactional and exactly-once under retries. Do not grant benefits for pending, failed, or mismatched payments.
- Define period start/end and timezone semantics; calculate renewal from the contractual period rather than request time where the existing plan terms require continuity. Preserve history with linked renewal records rather than overwriting prior purchases.
- Define cancellation timing (immediate or end-of-period), expiry/grace, failed-charge retries, refund effects, and renewal notifications only to the extent supported by existing business policy and gateway capabilities. Never charge automatically without clear user consent and a valid recurring authorization.
- Test free plans, pending/failed/paid payments, tampered/mismatched callbacks, duplicate/out-of-order events, expiry, renewal boundaries, cancellation, refund, ownership, entitlement counts, and concurrent activation.

Do not add a scheduler, recurring mandate, new status vocabulary, grace period, or subscription provider solely to match a generic industry pattern. Identify current policy and gaps first; scope any new commercial behavior separately.

## 7. Customer/Admin Login, Roles, Sessions, and Tokens

- Keep browser authentication session-based with Laravel's supported guards, CSRF middleware, secure cookies, and rate limits appropriate to login, OTP, reset, and verification endpoints. API bearer tokens are a separate client contract and must not be introduced just because the application has JSON endpoints.
- On successful authentication, regenerate the session ID. On logout, remove the correct guard's authentication state and rotate/invalidate session and CSRF state according to the app's multi-guard behavior. Since customer and admin currently share a session store, verify that logging out of one guard does not accidentally log out or retain stale privileges for the other; write tests for the intended behavior.
- Enforce active-account checks and admin/customer guard boundaries on every login path, including OTP and Google OAuth. Validate OAuth state and redirect targets; never permit open redirects or cross-guard login.
- OTPs and reset/verification tokens must be time-limited, single-use, rate-limited, protected from enumeration, and delivered through the configured production channel. Development-only test codes must not be exposed or accepted in production.
- Store only password hashes; use Laravel's password broker and throttling conventions where password reset is supported. Do not build custom token/session cryptography.
- Enforce authorization server-side with policies, gates, or Spatie permissions on every protected action; hiding UI controls is not authorization. Preserve guard-specific permission checks and test role changes/revocation against existing sessions.
- Keep API credentials hashed, scoped, revocable, and expiring if a future API-token flow is approved; use a maintained Laravel-supported mechanism rather than homegrown tokens. Do not expose session IDs, OTPs, secrets, or tokens in logs/responses.
- Test login success/failure, inactive users, session fixation, guard isolation, logout/CSRF behavior, throttling, OTP expiry/reuse, OAuth errors and redirect validation, role/permission denial, and protected route coverage.

## 8. Laravel Structure and Performance

- Keep controllers focused on HTTP orchestration; put reusable domain rules and transactional workflows in existing domain services. Use Form Requests for non-trivial validation and policies/gates for authorization.
- Prefer Eloquent relations, eager loading, pagination, database constraints, and measured indexes. Avoid speculative repositories, abstraction layers, caching, Redis requirements, or architecture rewrites without a demonstrated need.
- Use database transactions for multi-record invariants. Dispatch queued work after commit when it depends on committed rows; jobs must be retry-safe and observable.
- Keep external payment, SMS, OAuth, and AI calls behind existing service boundaries, with bounded timeouts and safe failure handling. Never log sensitive payloads.
- Add migrations compatibly: nullable/additive first where needed, backfill safely, then enforce constraints in a later deploy. Include reversible `down()` behavior where safe; do not rewrite production history.
- Follow the installed Laravel 11 APIs and project conventions. Do not copy Laravel 12/13 examples into this Laravel 11 app without checking versioned documentation and compatibility.

## 9. Required Verification Before Completing a Domain Change

1. Run relevant focused feature/regression tests before and after the change; add tests for each fixed issue.
2. Run the complete `php artisan test` suite after the final domain changes.
3. Run `php artisan route:list`/route integrity tests when routes or middleware change; verify route caching if routes are intended to be cacheable.
4. Run `php artisan view:cache` and clear compiled views when Blade/view contracts change.
5. Run `vendor/bin/pint --test` (or format only touched files when requested by project workflow) and `git diff --check`.
6. Report passing/failing tests, pre-existing failures, framework deprecations, unresolved product-policy decisions, and remaining risks. Do not claim load or security coverage from ordinary feature tests; add targeted concurrency/security tests or state the limit.

## 10. Official Framework References

- [Laravel 11 Authentication](https://laravel.com/docs/11.x/authentication)
- [Laravel 11 Sessions](https://laravel.com/docs/11.x/session)
- [Laravel 11 CSRF Protection](https://laravel.com/docs/11.x/csrf)
- [Laravel 11 Rate Limiting](https://laravel.com/docs/11.x/rate-limiting)
- [Laravel 11 Database Transactions](https://laravel.com/docs/11.x/database#database-transactions)
- [Laravel 11 Queues and after-commit behavior](https://laravel.com/docs/11.x/queues#jobs-and-database-transactions)
