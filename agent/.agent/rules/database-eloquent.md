# Database Safety & Eloquent Rules

## 1. Null Safety with Dates & Relationships
- Always guard against `null` timestamps or optional relations before calling Carbon methods or relation properties.
  - Correct: `$lead->created_at?->diffForHumans()` or `optional($lead->created_at)->format('Y-m-d')`
  - Incorrect: `$lead->created_at->diffForHumans()` when `created_at` can be null.

## 2. Preventing N+1 Queries
- Always eager-load relations in index and listing views:
  - Example: `Lead::with(['nutritionist', 'dietPlan', 'assignedUser'])->paginate(20)`
- Never iterate over Eloquent collections while querying related models inside loops.

## 3. Database Migrations Safety
- Never run destructive column modifications in production without fallback checks.
- When creating migrations, always implement down() methods where reversible.
