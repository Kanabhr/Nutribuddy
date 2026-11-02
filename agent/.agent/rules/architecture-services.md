# Architecture & Service Layer Standards

## 1. MVC & Dedicated Services
- **Thin Controllers:** Never put complex nutrition formulas, lead triage, target computations, or export logic directly into Controllers.
- **Dedicated Service Layer:** Keep domain calculations in `app/Services/Nutrition/` (e.g., `TargetEngine`, `NutritionCalculationService`).
- **Form Requests:** Validate request inputs using dedicated Form Requests or `$request->validate(...)` before touching services.

## 2. Dependency Injection & Service Contracts
- Inject services into controllers via constructor or method injection.
- Ensure all public service methods have strict type hints and clear PHPDoc blocks specifying parameter and return types.
