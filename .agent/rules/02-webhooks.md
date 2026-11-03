# 02 – Webhook Rules (correctness + optimisation)

## Receive pipeline (in this exact order)
1. Capture **raw body** (e.g. Express `express.raw({type:'application/json'})` on the webhook route *before* any JSON parser).
2. Verify signature (+ timestamp for Cashfree). Reject on failure.
3. Parse JSON.
4. Extract a stable **event key**: Razorpay `X-Razorpay-Event-Id` header; Cashfree: derive from `type` + order/payment/refund identifiers + event time **(verify)**.
5. **Insert-or-ignore** into `webhook_events` (unique on `provider + event_key`). If it already exists → return `200` immediately (duplicate).
6. Enqueue a job with the event row ID.
7. Return `200` within ~1 second. Do **not** do DB-heavy work, emails, or third-party calls inline.

## Worker pipeline
- Load event row → fetch current payment/order state from your DB → apply via state machine (see rule 03) → trigger side effects once → mark event `processed`.
- On transient failure: retry with exponential backoff + jitter (e.g. 5 attempts), then dead-letter and alert.
- If no queue exists, process after responding (fire-and-forget with persistence of the event row first). Never lose an event that was acknowledged.

## Response codes
| Situation | Respond |
|---|---|
| Valid, new | `200` |
| Valid, duplicate | `200` |
| Valid, unknown/unsubscribed event type | `200` (log & ignore) |
| Invalid signature / stale timestamp | `401` (no retry value) |
| Malformed JSON after valid signature | `200` + alert (retrying won't help) |
| Our DB/queue down | `5xx` (so the gateway retries) |

Gateways retry on non-2xx; wrongly returning `5xx` for permanent errors causes retry storms and may get the webhook auto-disabled **(verify)**.

## Subscribe minimally
Only enable events you handle. Suggested:
- Razorpay: `payment.captured`, `payment.failed`, `order.paid`, `refund.processed`, `refund.failed` (add `payment.authorized` only if you capture manually; subscription events only if used).
- Cashfree: `PAYMENT_SUCCESS_WEBHOOK`, `PAYMENT_FAILED_WEBHOOK`, `PAYMENT_USER_DROPPED_WEBHOOK`, `REFUND_STATUS_WEBHOOK` (others only if used).

## Ordering & duplicates
- Delivery is **at-least-once and unordered.** `failed` can arrive after `captured` for retried attempts; refund events can precede your DB commit.
- Never regress state (e.g. `paid` → `failed`). Use monotonic transitions and compare event timestamps where available.
- A single order may have multiple payment attempts. Track per-attempt records; the order is `paid` when **any** attempt is captured/success.

## Reconciliation (safety net, required)
- Cron every 5–10 min: find orders `pending` older than N minutes (and `paid` without fulfilment). Fetch status from the gateway API, and feed the result through the **same** state machine used by webhooks.
- Nightly: compare gateway settlement/payment list vs DB for the last 48h; report mismatches.

## Optimisation checklist
- [ ] Single endpoint per gateway (not per event)
- [ ] Raw-body capture only on the webhook route
- [ ] Signature check is O(1), before DB access
- [ ] Dedupe via DB unique index (not read-then-write)
- [ ] Heavy work async; ack latency p95 < 500 ms
- [ ] Indexes on `webhook_events(provider,event_key)`, `payments(gateway_order_id)`, `payments(gateway_payment_id)`
- [ ] Event retention/cleanup job (e.g. 30–90 days)
- [ ] Metrics + alerts (rejected signatures, DLQ depth, ack latency, reconciliation fixes)
- [ ] Webhook secret rotation procedure documented (accept old+new during window)

## Local/dev testing
- Use a tunnel (ngrok / cloudflared) to receive sandbox webhooks, plus a local **signed-payload simulator** for deterministic tests.
- Never disable signature verification in dev; instead generate valid signatures with the test secret.
