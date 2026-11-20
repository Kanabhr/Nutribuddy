# AI development instructions

- Read the relevant code and existing `.agent/rules/` guidance before changing it. Search for existing behavior first and keep changes focused.
- Preserve project conventions: Laravel 11, PHP `^8.2`, Blade views, Form Requests, services for domain logic, and the existing auth guards and permissions. Keep existing business logic and public behavior unchanged unless the user explicitly asks for a change.
- For every code change, follow `.ai/TESTING_RULES.md`: record baseline results first, test the new behavior and affected workflows, and run the full regression suite. Never remove or weaken existing tests to hide failures.
- Do not claim existing functionality is unaffected without test evidence. Distinguish failures already present at baseline from failures introduced by the change, and report anything not verified.
- Never expose `.env` values or credentials. Keep secrets in environment configuration and use placeholders in examples.
- Follow `.ai/DATABASE_RULES.md` for every schema or data change. Never connect to or change production.
- GitHub Actions are for quality and security checks only. Production deployment, Hostinger access, and production database changes remain manual and owner-controlled.
