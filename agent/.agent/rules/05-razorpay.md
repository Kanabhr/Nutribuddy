# 05 – Razorpay Rules (verify against current docs)

## Flow
1. Server: `orders.create({ amount (paise), currency:'INR', receipt, notes })` → store `order.id`.
2. Client: Checkout with `key_id` + `order_id`. Never pass `key_secret`.
3. Client success handler returns `razorpay_payment_id`, `razorpay_order_id`, `razorpay_signature`.
4. Server verify: `HMAC_SHA256(order_id + "|" + payment_id, key_secret)` == `razorpay_signature` (constant-time).
5. Then confirm status (fetch payment: `captured`) **or** wait for webhook. Fulfil only on confirmed capture.
6. Webhook is the source of truth for late captures, failures, and refunds.

## Webhook
- Header: `X-Razorpay-Signature` = `HMAC_SHA256(raw_body, WEBHOOK_SECRET)` (hex). Secret is set when creating the webhook in the dashboard, **not** the API key secret.
- Dedupe with header `X-Razorpay-Event-Id`.
- Respond `2xx` quickly; Razorpay retries failed deliveries with backoff for up to ~24h and may disable a persistently failing webhook.
- Useful events: `payment.captured`, `payment.failed`, `order.paid`, `refund.processed`, `refund.failed`.

## Settings
- Prefer **auto-capture** (dashboard / `payment_capture` per current API). If using manual capture, handle `payment.authorized` and capture before the auto-refund window; capture amount must equal authorised amount.
- Amount integrity: compare `payment.amount` (paise) with expected.
- Use `notes` to store your `order_id` for cross-reference, but don't trust notes for authorisation.

## Common mistakes to flag
- Using key secret as webhook secret
- Parsing JSON before verifying signature (breaks HMAC)
- Treating `payment.authorized` as paid when manual capture is off/on inconsistently
- Float arithmetic on amounts
- Not handling multiple payment attempts per order
- Test key used with live webhook (or vice versa)
