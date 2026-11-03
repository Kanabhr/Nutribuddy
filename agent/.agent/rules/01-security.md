# 01 – Security Rules

## Secrets
- Keys/secrets only from environment variables or a secret manager. Never hardcode, never commit.
- `.env*` must be in `.gitignore`; provide `.env.example` with placeholder names only.
- Razorpay `key_id` may reach the client; **`key_secret` and webhook secret never**. Cashfree `x-client-secret` never reaches the client; only `payment_session_id` does.
- Razorpay webhook secret ≠ API key secret. Cashfree webhook signing uses the client secret per its docs **(verify)**. Keep each in its own named env var.
- Scan the repo and git history for leaked keys (`rzp_live_`, `rzp_test_`, Cashfree client secrets). If a live secret was ever committed → **rotate immediately** and report as Critical.
- Never log secrets, full headers, full webhook bodies in production, card/UPI/VPA/phone/email in clear text. Mask (`****1234`).

## Amount & order integrity
- Compute price/amount/currency **on the server** from your own DB. Ignore client-sent amounts.
- Create the gateway order server-side; store `gateway_order_id` ↔ your `order_id` ↔ `user_id`.
- On verify/webhook, assert: gateway amount == expected amount, currency == INR, order belongs to expected user/cart. Mismatch → mark `flagged`, do not fulfil, alert.

## Signature verification (mandatory everywhere)
- Verify against the **raw, unparsed** request body (bytes/string exactly as received). Do not re-serialise JSON.
- Use **constant-time comparison** (`crypto.timingSafeEqual`, `hmac.compare_digest`, `hash_equals`).
- Fail closed: missing/invalid signature → `401/400`, no processing, no side effects, log a masked warning.
- Verify the client-callback signature too (Razorpay `order_id|payment_id`), and still confirm via the gateway API/webhook before fulfilment.

## Endpoint hardening
- HTTPS only (HSTS). Webhook URLs must be public HTTPS in live mode.
- Rate-limit order-create and verify endpoints per user/IP. Webhook endpoint: generous limit + body size cap (e.g. 256 KB).
- Authenticate order-create/verify with the user session; enforce ownership (no IDOR on `order_id`).
- CORS: allow only your origins. CSRF protection on cookie-authenticated POSTs (webhooks are exempt from CSRF but protected by signature).
- Validate inputs with a schema; reject unknown fields on money-related payloads.
- Optional defence in depth: IP allow-list gateway webhook IPs **(verify current list; never rely on it alone)**.
- Replay protection: reject webhooks with stale timestamps (Cashfree: use `x-webhook-timestamp`) and dedupe by event ID.

## Data & logging
- Never store raw card data (PCI). Store only gateway IDs, last4, status, amount.
- Webhook raw payload may be stored for audit in an access-controlled, encrypted table with retention policy; redact PII in app logs.
- Add alerting for: signature failures spike, amount mismatches, reconciliation finding paid-but-unfulfilled orders.

## Dependencies
- Use official SDKs or minimal HTTP clients; pin versions; run `npm audit` / `pip-audit` and fix High+.
