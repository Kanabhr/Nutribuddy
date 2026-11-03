# Frontend & Blade View Standards

## 1. Directory Structure
- Keep admin views inside `resources/views/admin/` (e.g., `admin/nutrition/leads/`, `admin/diet-plans/`).
- Keep public client/customer views inside `resources/views/frontend/` (e.g., `frontend/nutrition/questionnaire.blade.php`).

## 2. Aesthetics & Styling
- Avoid raw, unstyled form controls or plain browser defaults.
- Maintain modern, clean typography, soft card borders, consistent badge colors, and smooth micro-interactions.
- Dedicated CSS stylesheets belong in `public/css/` (e.g., `public/css/questionnaire.css`).

## 3. Responsive Layouts
- All questionnaire forms, client intake wizards, and admin tables must be fully responsive across mobile, tablet, and desktop viewports.
