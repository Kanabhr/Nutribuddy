# AI development instructions

- Read the relevant code and existing `.agent/rules/` guidance before changing it. Search for existing behavior first and keep changes focused.
- Preserve project conventions: Laravel 11, PHP `^8.2`, Blade views, Form Requests, services for domain logic, and the existing auth guards and permissions.
- Add or update tests for behavior changes. Run the applicable checks from `.ai/TESTING_RULES.md` and report actual results.
- Never expose `.env` values or credentials. Keep secrets in environment configuration and use placeholders in examples.
- Follow `.ai/DATABASE_RULES.md` for every schema or data change. Never connect to or change production.
- GitHub Actions are for quality and security checks only. Production deployment, Hostinger access, and production database changes remain manual and owner-controlled.
