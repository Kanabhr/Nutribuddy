# 06 – Cashfree Rules (verify against current docs)

## Setup
- Base URLs: sandbox `https://sandbox.cashfree.com/pg`, production `https://api.cashfree.com/pg`.
- Headers on every API call: `x-client-id`, `x-client-secret`, `x-api-version` (**pin a specific version in config**; don't float), `Content-Type: application/json`. Add `x-idempotency-key` / `x-request-id` where supported.

## Flow
1. Server: `POST /orders` with `order_id` (unique, yours), `order_amount` (**rupees, decimal**), `order_currency:'INR'`, `customer_details` (valid customer_id, phone), `order_meta.return_url` and `order_meta.notify_url`.
2. Server returns only `payment_session_id` to the client SDK/JS.
3. Return URL is **UX only**. On return, server calls `GET /orders/{order_id}` (and `/payments`) to confirm `order_status == PAID`.
4. Webhook finalises state.

## Webhook
- Headers: `x-webhook-signature`, `x-webhook-timestamp`.
- Signature = `Base64( HMAC_SHA256( timestamp + raw_body, client_secret ) )`. Compare constant-time. Reject if timestamp too old (e.g. > 5 min) to stop replays.
- Verify using the **raw body**.
- Events to handle: `PAYMENT_SUCCESS_WEBHOOK`, `PAYMENT_FAILED_WEBHOOK`, `PAYMENT_USER_DROPPED_WEBHOOK`, `REFUND_STATUS_WEBHOOK`.
- Webhook payload version should match your pinned API version; set webhook version in dashboard accordingly.
- Production webhook URL must be HTTPS and publicly reachable; use dashboard "test webhook" plus the simulator.

## Integrity checks
- `order_amount` == expected; `order_currency` == INR; `order_id` known to you.
- `payment_status == SUCCESS` before fulfilment; handle `PENDING` and `USER_DROPPED` without marking failed permanently.
- Set `order_expiry_time` explicitly and make expiry a handled terminal state.

## Refunds
- `POST /orders/{order_id}/refunds` with your unique `refund_id`, `refund_amount` (rupees) ≤ remaining refundable.
- Track via `REFUND_STATUS_WEBHOOK` and `GET /orders/{order_id}/refunds/{refund_id}`; statuses can be `PENDING` → `SUCCESS`/`CANCELLED`/`ONHOLD` **(verify)**.

## Common mistakes to flag
- Sending paise as `order_amount` (100× overcharge)
- Not pinning `x-api-version`
- Missing `notify_url` or using HTTP in production
- Trusting `return_url` query params as proof of payment
- Reusing `order_id` across retries without handling "order already exists"
- Skipping timestamp in signature computation
