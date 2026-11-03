# Frontend CSS and JavaScript Rules

Use these rules when changing browser-side code, stylesheets, or the views that load them. Keep existing page behavior and selectors working unless the change explicitly includes their callers and regression coverage.

## Asset layout in this repository

- Blade currently loads most production assets directly from `public/assets/css/` and `public/assets/js/`, with page-specific additions through `@push('styles')` and `@push('scripts')`.
- The shared frontend layout loads `frontendstyle.css`, `frontendresponsive.css`, `frontendscript.js`, and `pages/allfile.js`. Admin layouts load `style.css`, `admin.css`, `admin-nutribuddy.css`, and shared scripts through the head/script components.
- Domain assets also exist under `public/css/` and `public/js/`. Keep a page's existing asset location and load mechanism when making a narrow fix; do not move files as a cleanup-only change.
- Vite currently declares only `resources/css/app.css` and `resources/js/app.js` as inputs. `resources/css/app.css` is empty, and `resources/js/app.js` imports the Axios bootstrap entry. Do not assume a file under `public/assets/` or `public/css/` is built by Vite.
- `public/assets/sass/main.scss` imports the abstract, utility, component, and layout Sass trees, but the current Vite inputs do not compile it. Do not edit generated CSS and Sass together as if a build currently synchronizes them; first establish which file the page serves and preserve that source of truth.
- Treat files under `public/assets/css/lib/`, `public/assets/js/lib/`, and identifiable bundled third-party distributions as vendor assets. Do not patch or reformat them for application behavior; configure or wrap them from application-owned code.

## Before editing or moving an asset

1. Find every Blade include, `@push`/`@stack`, inline script, and script/style URL that loads or depends on it.
2. Search for its selectors, IDs, `data-*` attributes, global functions, and custom events in Blade, CSS, and JavaScript. These are page contracts even when they are not documented as APIs.
3. Preserve the existing HTML names, form field names, route URLs, payload shapes, CSRF behavior, and response handling unless the same change updates and tests every caller.
4. Remove an asset only after confirming there are no Blade, layout, inline, or dynamically constructed references. A missing Vite import does not prove an asset is unused.

## JavaScript

- Follow the existing page's runtime: much of the application uses vanilla JavaScript, while the admin theme also uses jQuery and Bootstrap. Do not introduce a second framework or add a dependency for a small interaction.
- Keep page code scoped to its page and guard optional DOM elements. Shared layout scripts may execute on pages that do not contain a particular widget.
- Prefer route URLs and configuration emitted by Blade (for example `window.nutribuddyConfig`, `window.AC_ROUTES`, or `data-*` attributes) over duplicating server routes in JavaScript. Keep CSRF headers on state-changing requests and handle non-2xx responses before reporting success.
- Keep browser validation as a usability aid only; server validation remains authoritative. Preserve the current questionnaire `q_<question id>` payload convention, including arrays for multi-select answers.
- Treat product selection, quantity, cart, checkout, payment, and subscription state as server-authoritative. The browser may start a flow and display verified results; it must not calculate or assert final prices, discounts, coin balances, payment success, or entitlements.
- Do not store passwords, OTPs, session identifiers, access tokens, payment secrets, or CSRF tokens in `localStorage` or `sessionStorage`. Persist only data required by an existing user flow, and review its lifetime and clearing behavior.
- Use `textContent`, form `.value`, and DOM creation for dynamic text. Do not interpolate user-controlled or server-provided values into `innerHTML`, `outerHTML`, or inline event handlers. If trusted rich HTML is required, define and test the sanitization boundary.
- Avoid adding new globals or inline handlers. Existing global functions and inline handlers are compatibility points; migrate them only with all Blade callers updated in the same change.
- Keep loading, disabled, cancellation, failure, and retry states reversible. Do not leave checkout or form buttons disabled after a failed request.
- Preserve keyboard access, focus behavior, and appropriate button semantics when changing tabs, modals, steppers, or custom choice controls.

## CSS and Sass

- Prefer a page- or component-scoped stylesheet and selectors. Avoid broad rules such as `*`, `body`, element-wide form rules, or generic class names in a domain stylesheet because layouts share assets.
- Preserve class and ID hooks used by JavaScript. A visually equivalent selector rename can still break a widget.
- Follow the local stylesheet's existing design tokens and breakpoints. The repository has multiple visual systems; do not copy variables between them without checking which stylesheet and font set the target page loads.
- Keep responsive behavior in the owning stylesheet and check mobile, tablet, and desktop. Product gallery, variant selection, quantity, and CTA styles have explicit small-screen overrides; changes to these must be checked with the actual product view.
- Avoid new `!important`, inline styles, duplicate declarations, or another global reset. When an override is needed to beat a legacy rule, scope it narrowly and document the dependency in a short comment.
- Respect `prefers-reduced-motion` for new nonessential motion and retain visible focus indicators and readable contrast.

## Verification

- For JavaScript changes, run `node --check` on each changed application-owned script and the relevant feature/regression tests for any server contract it calls.
- For Sass/CSS pipeline changes, verify the actual build command and the rendered asset URL. Do not claim Sass changes are deployed just because the Laravel Vite build succeeds; Sass is not currently a declared Vite input.
- For Blade-loaded assets, smoke-test the affected page and its primary interactive states at desktop and mobile sizes. Include success, validation failure, network failure, and retry where the script makes requests.
- After a move or deletion, verify that layouts and pages load the intended asset and that no old URL remains. Run the existing view and route regression checks when Blade loading changes.

## Findings from this inspection

- `public/js/child-profile.js` contains duplicate top-level declarations for `pDesc` and several helper functions. A duplicate `const` in one script scope prevents the browser from parsing the entire file. Treat this as a known defect for a separate, regression-checked repair; do not copy the duplication into new code.
- `public/js/child-profile.js` interpolates the entered child name into `innerHTML`. Do not use that pattern for new code; address the existing use in a focused security fix that preserves the current button markup and behavior.
- `public/assets/css/product.css` contains targeted `!important` overrides for the product gallery, variants, quantity, and mobile layout. These are existing cascade dependencies; inspect the full cascade before simplifying or moving them.
- `public/assets/js/pages/diet-chart-payment.js` starts checkout using Blade-provided configuration and posts CSRF-protected requests. Keep payment verification and entitlement decisions on the server.

This ruleset is based on the asset inventory and the application-owned frontend entrypoints/page assets traced during inspection. Third-party distributions are treated as vendor code, not application style references.
