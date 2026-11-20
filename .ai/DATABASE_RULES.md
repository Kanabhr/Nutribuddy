# Database safety rules

- Production database access, migrations, queries, and data changes are owner-controlled and never part of AI or CI work.
- CI may run migrations only against its disposable MySQL service. Local migrations must use an explicitly local test database.
- Inspect related migrations, models, foreign keys, indexes, constraints, and existing records before proposing a schema change.
- Use Laravel migrations with safe rollback logic where practical. Consider populated databases before adding required columns or changing types.
- For every schema change, document the reason, affected tables and columns, migration, existing-data impact, rollback limits, and manual production deployment steps.
- Never run destructive SQL, `migrate:fresh`, `db:wipe`, or table truncation against production. Do not change production credentials.
