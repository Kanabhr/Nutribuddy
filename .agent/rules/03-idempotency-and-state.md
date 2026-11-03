# 03 – Idempotency & Payment State Machine

## Idempotency
- Order creation: use your own idempotency key (e.g. cart/checkout ID) so double-clicks don't create two gateway orders. Unique constraint on it.
- Webhook handling: unique `(provider, event_key)`.
- Fulfilment: guarded by a conditional update, e.g.
  `UPDATE orders SET status='paid', fulfilled_at=now() WHERE id=? AND status IN ('created','pending')` → proceed only if `rowsAffected == 1`.
- Refunds: pass an idempotency/reference ID (Cashfree `refund_id` is caller-supplied; for Razorpay use `receipt`/notes and check existing refunds first).
- Wrap status change + outbox record in **one DB transaction**; send emails/notifications from the outbox worker.

## State machine (order level)
```
created ──► pending ──► paid ──► partially_refunded ──► refunded
   │           │
   │           ├──► failed (attempt failed; order may retry → pending)
   └──► expired / cancelled
```
Rules:
- `paid`, `refunded` are terminal-ish: never move back to `pending/failed`.
- `failed` is per **attempt**; the order stays retryable until expiry.
- Illegal transition → ignore + log at warn (not an exception).
- Every transition writes a row to `payment_events` / audit log (who/what/when/source=client|webhook|reconcile).

## Required DB shape (adapt names)
- `orders(id, user_id, amount_minor, currency, status, gateway, gateway_order_id, idempotency_key UNIQUE, fulfilled_at, created_at, expires_at)`
- `payment_attempts(id, order_id, gateway_payment_id UNIQUE, method, status, amount_minor, raw_status, created_at)`
- `webhook_events(id, provider, event_key, type, payload_json, signature_ok, status, attempts, received_at, processed_at, UNIQUE(provider,event_key))`
- `refunds(id, order_id, gateway_refund_id UNIQUE, amount_minor, status, created_at)`

## Concurrency
- Webhook and client-verify may race. Both must call the same `markPaid(orderId, source)` function, safe to call concurrently and repeatedly.
