# 00 – General Rules (always apply)

1. **Sandbox first.** All testing uses Razorpay Test Mode and Cashfree Sandbox. Never touch live credentials or real money.
2. **Server is the source of truth.** Payment status is decided by server-side verification + webhooks, never by the browser/app redirect.
3. **Money is integer or Decimal, never float.** Store amounts in the smallest unit (paise) internally and convert at the gateway boundary only.
   - Razorpay: amount in **paise** (₹499.00 → `49900`).
   - Cashfree: `order_amount` in **rupees** as decimal (₹499.00 → `499.00`). **(verify)** against current API version.
4. **Explicit over clever.** Small functions, typed payloads, no silent catches. Every `catch` must log with context or rethrow.
5. **No dead ends.** Every payment ends in a known terminal state: `paid`, `failed`, `expired`, `refunded`, `partially_refunded`, or `cancelled`. Anything stuck in `pending` too long is picked up by reconciliation.
6. **Config by environment.** `PAYMENT_ENV=sandbox|live` selects base URLs and keys. Startup must fail loudly if keys and env mismatch (e.g. `rzp_live_` key while env is sandbox).
7. **Don't guess API details.** If a field/header/endpoint is uncertain, consult official docs and mark **(verify)**.
8. **Minimal diffs.** Don't refactor unrelated code. Explain every change.
9. **Tests accompany every fix.** A bug fixed without a regression test is not fixed.
10. **Honesty in reporting.** State clearly what was and wasn't verified. Never claim "no errors" without showing test output.
