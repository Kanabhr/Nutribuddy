# MASTER PROMPT: End-to-End Payment Gateway Test, Hardening & Webhook Optimisation

You are a senior payments engineer and security-minded QA lead. Your job is to **audit, test, fix and verify** my payment integration (Razorpay and Cashfree) so that it works end-to-end with **zero errors**, is **properly secured**, and uses **webhooks correctly and efficiently**.

Before doing anything, read ALL files in `.agent/rules/`. They are mandatory constraints. If a rule conflicts with my request, follow the rule and tell me.

---

## PROJECT CONTEXT (fill in; if blank, detect from the codebase and state your assumptions)

- Language / framework: `<e.g. Node + Express / Next.js / Django / Laravel>`
- Database: `<Postgres / MySQL / Mongo>`
- Queue / worker available: `<BullMQ / Celery / SQS / none>`
- Gateways in use: Razorpay, Cashfree  (which flows use which: `<e.g. Razorpay = web checkout, Cashfree = mobile / UPI>`)
- Payment flows: `<one-time / subscription / refunds / payouts / payment links>`
- Webhook URL(s): `<https://.../webhooks/razorpay>, <https://.../webhooks/cashfree>`
- Env files: `<.env.example path>`
- Currency: INR

---

## PHASE 0: Recon (no code changes)

1. Map every payment-related file: order creation, checkout/client code, verify endpoint, webhook handlers, refund code, DB models, env/config.
2. Produce a **flow diagram** (text/mermaid) for each gateway: create order → pay → client callback → server verify → webhook → fulfilment.
3. List all secrets/keys used and where they are loaded. Flag anything hardcoded, logged, or exposed to the client.
4. Output a **Findings Table**: `ID | Severity (Critical/High/Med/Low) | File:Line | Issue | Fix`.

Stop and show me the findings before Phase 1 only if you find a **Critical** issue (e.g. leaked live secret). Otherwise continue.

## PHASE 1: Security audit & fixes

Apply `rules/01-security.md`. Verify and fix:
- Server-side amount calculation (never trust client amount/price/currency).
- Signature verification on **both** the client-callback verify endpoint and every webhook, using the **raw body** and constant-time comparison.
- Secrets only from env/secret manager; `.env` git-ignored; no secrets in logs, errors, or frontend bundles.
- Test and live credentials strictly separated by environment.
- HTTPS-only, CORS locked down, rate limiting on payment + webhook endpoints, input validation, order-ownership (IDOR) checks.

## PHASE 2: Webhook correctness & optimisation

Apply `rules/02-webhooks.md` and `rules/03-idempotency-and-state.md`. Ensure each webhook handler:
1. Reads the raw body, verifies the signature (and timestamp where applicable) **before** parsing.
2. Responds `2xx` fast (target < 1s, hard limit well under the gateway timeout) and moves heavy work to a queue/worker.
3. Is **idempotent** (dedupe by gateway event ID / payment ID + event type, backed by a DB unique constraint).
4. Tolerates **out-of-order and duplicate** events using a state machine (no status regressions).
5. Subscribes only to events actually needed (no "select all").
6. Has a **reconciliation job** that polls the gateway for pending/unknown orders, so a missed webhook never loses a payment.
7. Logs structured, secret-free, correlation-ID'd entries and exposes metrics (received, verified, rejected, duplicate, processed, failed, latency).

## PHASE 3: Gateway-specific checks

- Razorpay → follow `rules/05-razorpay.md`
- Cashfree → follow `rules/06-cashfree.md`

## PHASE 4: Automated test suite (must be runnable with one command)

Build/extend tests per `rules/04-e2e-testing.md`:
- **Unit**: signature verification (valid, tampered body, wrong secret, missing header, replayed old timestamp), amount conversion (paise vs rupees), state machine transitions.
- **Integration**: webhook endpoint with signed fixtures; duplicate delivery; out-of-order delivery; malformed payload; unknown event type; DB failure mid-processing; slow downstream.
- **E2E (sandbox)**: real sandbox order → payment (success, failure, user-dropped/abandoned, pending→success) → webhook received via tunnel → DB state correct → fulfilment fired exactly once → refund (full + partial) → refund webhook.
- **Negative/security**: tampered amount, forged webhook, replayed webhook, other user's order ID, expired order, double-click pay, double submit.
- **Resilience**: kill the worker mid-job, drop the webhook entirely and confirm reconciliation recovers it.

Provide a script `npm run test:payments` (or the stack equivalent) and a webhook simulator that can generate **correctly signed** Razorpay and Cashfree payloads locally.

## PHASE 5: Run, fix, re-run

Run the whole suite. For every failure: root-cause → fix → re-run. Repeat until **all green with 0 flaky tests** (run the suite 3× consecutively to prove it).

## PHASE 6: Report

Deliver:
1. **Summary**: what was wrong, what you changed (files + why).
2. **Test results**: counts, coverage of payment modules, 3× run proof.
3. **Residual risks** and anything you could not verify (mark **(verify)** items; never claim live-mode verification you did not do).
4. **Go-live checklist** status using `checklists/go-live-checklist.md`.
5. **Env var list** (names only, never values).

---

## HARD CONSTRAINTS

- Sandbox/test mode only. **Never** use, request, print, or commit live keys.
- Never weaken a security check to make a test pass.
- Never mark a payment as paid based on the client callback alone; the server must verify with the gateway (signature and/or fetch-order/payment API) and webhooks are the source of truth.
- No `console.log`/print of full payloads containing PII, card, or UPI data.
- Don't invent gateway API fields. If unsure, say so, check the official docs, and mark **(verify)**.
- Make small, reviewable commits/diffs; explain each change.

## DEFINITION OF DONE

- [ ] Every flow in Phase 4 passes, 3 runs in a row, no flakiness
- [ ] No Critical/High findings open
- [ ] Forged, tampered, replayed and duplicate webhooks are all rejected/ignored safely
- [ ] A dropped webhook is recovered by reconciliation
- [ ] Fulfilment (order confirmation, stock, email, etc.) happens exactly once per successful payment
- [ ] Webhook responds fast; heavy work is async
- [ ] No secrets in repo, logs, or client bundle
- [ ] Report delivered
