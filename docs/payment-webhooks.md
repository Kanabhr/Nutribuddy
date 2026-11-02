# Payment webhook setup

The application exposes signed webhook handlers for Cashfree and Razorpay. Configure the URLs below in each provider's dashboard after deploying the application to a public HTTPS domain. A localhost URL such as `127.0.0.1` cannot receive provider callbacks.

| Provider | Webhook URL | Events to enable | Signing secret |
| --- | --- | --- | --- |
| Cashfree | `https://<your-domain>/payment/cashfree/webhook` | Payment success, failure, and user dropped events | The Cashfree secret key configured as `CASHFREE_SECRET_KEY` |
| Razorpay | `https://<your-domain>/payment/razorpay/webhook` | `payment.captured` and `order.paid` | The webhook secret configured as `RAZORPAY_WEBHOOK_SECRET` |

## Cashfree

Set `CASHFREE_APP_ID`, `CASHFREE_SECRET_KEY`, and `CASHFREE_ENV` in the deployment environment. Use the matching sandbox or production credentials and dashboard. The handler verifies Cashfree's `x-webhook-signature` against the raw request body and timestamp, then checks the order with Cashfree before syncing payment state.

## Razorpay

Set `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, and `RAZORPAY_WEBHOOK_SECRET`. In the Razorpay dashboard, add the production or test callback URL as appropriate, enable the listed events, and enter the same webhook secret as `RAZORPAY_WEBHOOK_SECRET`. The webhook signature is checked against the unmodified request body before payment details are validated.

Keep all API and webhook secrets in the server environment. Do not commit real credentials or send them through chat. The provider's sandbox/test mode and live mode have separate dashboard configurations, so configure and verify each mode independently before enabling live payments.
