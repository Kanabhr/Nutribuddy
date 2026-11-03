# Environment, Shell & Tool Standards

## 1. Operating Environment
- Host operating system is **Windows** with **PowerShell**.
- Always use Windows-safe path conventions (`\` or forward slashes `/`).
- Never run interactive commands that wait for user stdin without flags (use `--no-interaction` or predefined arguments).

## 2. Artisan & Cache Management
- Clear appropriate caches when updating routes, config, or blade views:
  - `php artisan route:clear`
  - `php artisan config:clear`
  - `php artisan view:clear`
- Run code formatting using Laravel Pint when requested: `vendor/bin/pint`.

## 3. Code Integrity & Comments
- Never strip out existing domain logic, formulas, or medical/diet comments without explicit instructions.
- Add descriptive PHPDoc blocks to new classes and helper methods.
