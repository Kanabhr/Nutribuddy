# .agent – Payment Gateway Testing Kit (Cashfree + Razorpay)

Drop this folder in your repo root. Point your AI coding agent (Claude Code, Cursor, Windsurf, etc.) at it.

```
.agent/
├── README.md
├── prompts/
│   └── payment-gateway-e2e-testing.md   # The master prompt – paste/reference this to start
├── rules/                                # Always-on rules the agent must obey
│   ├── 00-general.md
│   ├── 01-security.md
│   ├── 02-webhooks.md
│   ├── 03-idempotency-and-state.md
│   ├── 04-e2e-testing.md
│   ├── 05-razorpay.md
│   ├── 06-cashfree.md
│   └── 07-cod-otp-verification.md
└── checklists/
    └── go-live-checklist.md
```

## How to use
1. Tell the agent: "Read every file in `.agent/rules/` first, then execute `.agent/prompts/payment-gateway-e2e-testing.md`."
2. Fill the `PROJECT CONTEXT` block at the top of the prompt (stack, routes, env names).
3. Run against **sandbox / test mode only**. Use `checklists/go-live-checklist.md` before switching to live keys.

> Gateway APIs change. Rules marked **(verify)** should be checked against the current Razorpay / Cashfree docs before go-live.
