# Go-Live Checklist (Razorpay + Cashfree)

## Credentials & config
- [ ] Live keys only in production secret manager; none in repo/CI logs
- [ ] `PAYMENT_ENV=live` boots only with live keys; sandbox keys rejected (and vice versa)
- [ ] Razorpay live webhook created with its **own** secret; Cashfree production webhook URL configured
- [ ] Cashfree `x-api-version` pinned and matches webhook version
- [ ] Callback / return URLs are HTTPS production URLs

## Security
- [ ] Signature verification passes on live test webhook; forged ones rejected
- [ ] Amounts computed server-side; mismatch alerts on
- [ ] Rate limits, CORS, HSTS, body size caps enabled
- [ ] Log scan: no secrets/PII
- [ ] Key rotation runbook written

## Webhooks
- [ ] Only needed events subscribed
- [ ] Ack latency p95 < 500 ms in production-like load
- [ ] Dedupe constraint present; DLQ + alerts configured
- [ ] Reconciliation cron running and tested with a dropped webhook
- [ ] Retention/cleanup job scheduled

## Functional (live, minimal real amount ₹1–₹10)
- [ ] One real success payment per gateway, fulfilled exactly once
- [ ] One real refund per gateway, webhook received
- [ ] Failed + abandoned payment behave correctly

## Operations
- [ ] Dashboards: success rate, failures by reason, webhook failures, DLQ depth
- [ ] Alerts: success-rate drop, signature-failure spike, paid-but-unfulfilled orders
- [ ] Support runbook: how to find a payment, re-run a webhook, issue a refund
- [ ] Settlement reconciliation process agreed with finance
