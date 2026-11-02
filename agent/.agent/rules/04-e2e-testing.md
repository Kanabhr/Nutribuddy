# 04 – E2E Testing Rules

## Principles
- Tests are deterministic and repeatable; the suite must pass **3 consecutive runs**.
- Sandbox only. Credentials from env (`.env.test`, git-ignored). Fail fast if keys look live.
- Each test creates its own order and cleans up; no shared mutable state.
- Use a **webhook simulator** (signed with the test secret) for deterministic tests, and a smaller number of **true sandbox** tests via tunnel for realism.

## Mandatory test matrix

### A. Unit
| Area | Cases |
|---|---|
| Signature (each gateway) | valid · tampered body · wrong secret · missing header · empty body · whitespace-altered body · (Cashfree) stale/missing timestamp |
| Amounts | ₹0.01, ₹499.00, ₹1,00,000.50, rounding, paise↔rupee conversion per gateway, reject negative/zero/float drift |
| State machine | all legal transitions, all illegal ones ignored, concurrent `markPaid` |

### B. Integration (API + DB)
- Create order → DB row + gateway order id stored, amount from server
- Verify endpoint: valid sig → still confirmed against gateway/webhook; invalid sig → 400
- Webhook: new · duplicate (×5 concurrent) · out-of-order (failed after success) · unknown event · malformed JSON · oversized body · invalid signature
- Fulfilment exactly once under duplicate + concurrent delivery
- DB outage mid-webhook → `5xx`, later retry succeeds, no double fulfilment
- Reconciliation recovers an order whose webhook never arrived

### C. E2E sandbox (per gateway)
1. Successful card payment
2. Successful UPI payment (Razorpay `success@razorpay`; Cashfree sandbox UPI test VPA **(verify in docs)**)
3. Failed payment (Razorpay `failure@razorpay`; failed test card per gateway docs)
4. User drops/abandons checkout
5. Retry after failure on the same order → succeeds
6. Pending → success (delayed)
7. Order expiry
8. Full refund, partial refund, refund > remaining (must be rejected), refund webhook
9. Payment attempted with tampered amount from client (server must ignore)
10. User B tries to verify User A's order (must be 403/404)

### D. Security/negative
Forged webhook · replayed valid webhook · replay with old timestamp · missing secret env at boot · live key in test env → boot failure · secrets absent from logs (grep log output in test).

### E. Performance/resilience
- 100 concurrent webhook deliveries: p95 ack < 500 ms, no duplicates processed
- Worker killed mid-job → job retried, side effects once
- Gateway API timeout during verify → graceful error + reconciliation picks up

## Test data (verify current values in docs)
- Razorpay: test card `4111 1111 1111 1111`, any future expiry, any CVV, OTP/3DS via test page; UPI `success@razorpay` / `failure@razorpay`.
- Cashfree: sandbox test cards/UPI/netbanking from the Cashfree "test data" docs; use the `sandbox.cashfree.com` base URL.

## Reporting
Output: `passed/failed/skipped`, duration, coverage % for payment modules (target ≥ 85% lines, 100% of signature + state-machine code), and proof of 3 clean runs.
