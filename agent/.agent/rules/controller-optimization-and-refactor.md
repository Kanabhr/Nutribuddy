# Controller Optimization & Systematic Refactoring Protocol

This document defines the strict, non-negotiable architectural standards and safety procedures for refactoring and optimizing all Laravel controllers in **Nutribuddy**.

---

## 1. Zero-Breakage Core Invariants

Refactoring controllers must **NEVER** alter user-facing behavior, API payloads, or database state. When refactoring any controller, the following three core business pillars must remain 100% functional:

### A. Pillar 1: Product Section & E-Commerce Flow
- **Storefront Catalog:** `route('product')` and `route('product.show', $slug)` must continue returning identical Blade views (`pages.all-products`, `pages.product-details`) with identical view variables (`$product`, `$products`, `$categoryCounts`, `$minPrice`, `$maxPrice`).
- **Variants & Pricing:** Variable products must preserve default variant selection, tax calculations, dynamic stock checks, and inventory status.
- **Cart & Checkout:** `route('user.cart.store')`, `route('checkout.index')`, and checkout summary calculations must not have their parameter keys or validation payloads modified.
- **Admin Management:** `route('admin.ecommerce.products.*')`, variant inventory updates, trash/restore operations, and the problem-solution hub must maintain all route names and parameters.

### B. Pillar 2: Pediatric Diet Chart Creation & Lead Flow
- **Questionnaire Submission:** `POST /api/nutrition/questionnaire/submit` (`route('api.nutrition.questionnaire.submit')`) must continue accepting multi-step questionnaire payloads, validating child health data, and linking/creating leads.
- **Lead Lifecycle:** Admin leads listing (`route('admin.nutrition.leads.index')`), single assignment (`route('admin.nutrition.leads.assign')`), and bulk assignment (`route('admin.nutrition.leads.bulk-assign')`) must remain intact.
- **Nutritionist Review Queue:** `route('admin.nutrition.lead-queue.index')` and `show` must maintain access controls, eligible food slot mappings, and approval mechanisms (`route('admin.nutrition.lead-queue.approve')`).
- **Parent Chart Display:** `route('child.diet-chart.show', $child)` and PDF generation (`route('child.diet-chart.download-pdf', $child)`) must preserve all data structures (`$sevenDayPlan`, `$nutrientGaps`, `$clinicalTips`).

### C. Pillar 3: Assessment & Quiz System
- **Public Questionnaire:** `route('frontend.nutrition.questionnaire')` must load the reactive questionnaire view without JavaScript or CSS disruptions.
- **Admin Question Management:** `route('admin.assessment.questions.*')` and `route('admin.nutrition.questions.*')` must preserve section reordering, active status toggling, and question CRUD routes.

---

## 2. Systematic Target Directory & Namespace Structure

Controllers must be grouped cleanly by domain rather than dumped into monolithic directories:

```
app/Http/Controllers/
├── Admin/
│   ├── Access/
│   │   ├── AccessControlController.php
│   │   ├── UserController.php
│   │   └── RoleController.php
│   ├── Content/
│   │   ├── BlogPostController.php
│   │   ├── BlogCategoryController.php
│   │   ├── ReviewController.php
│   │   └── VideoReviewController.php
│   ├── Dashboard/
│   │   ├── DashboardController.php          # Render overview & analytics views only
│   │   └── DashboardExportController.php    # Single-action download (invokable)
│   ├── Ecommerce/
│   │   ├── ProductController.php           # Standard CRUD only (< 150 lines)
│   │   ├── ProductVariantController.php
│   │   ├── ProductMediaController.php      # Image upload/reorder/delete
│   │   ├── ProductProblemSolutionController.php # Landing builder
│   │   ├── CategoryController.php
│   │   ├── AttributeController.php
│   │   ├── OrderController.php
│   │   ├── OrderReturnController.php
│   │   ├── CouponController.php
│   │   └── TaxRateController.php
│   ├── Nutrition/
│   │   ├── NutritionLeadController.php
│   │   ├── NutritionistQueueController.php # Lead review & plan dispatch
│   │   ├── SubscriptionPlanController.php  # Renamed from DietPlanController for clarity
│   │   ├── DietSubscriptionController.php
│   │   ├── FoodController.php
│   │   ├── QuestionnaireAdminController.php
│   │   └── DietRuleController.php
│   └── System/
│       ├── GeneralSettingController.php
│       ├── NotificationController.php
│       └── SideSectionController.php
│
├── Frontend/
│   ├── Account/
│   │   ├── UserProfileController.php
│   │   ├── UserAddressController.php
│   │   ├── UserOrderController.php
│   │   ├── UserReturnController.php
│   │   └── UserSupportTicketController.php
│   ├── Checkout/
│   │   ├── CartController.php
│   │   ├── CheckoutController.php
│   │   └── PaymentController.php
│   ├── Nutrition/
│   │   ├── QuestionnaireController.php     # Public questionnaire view
│   │   ├── DietChartController.php         # Parent diet chart & PDF
│   │   └── ChildProfileController.php
│   └── Storefront/
│       ├── HomeController.php
│       ├── ProductController.php
│       └── BlogController.php
│
└── Api/
    ├── Nutrition/
    │   ├── QuestionnaireApiController.php
    │   ├── DietChartApiController.php
    │   └── MealLoggingApiController.php    # toggle-item & check-ins
    └── Webhooks/
        ├── AiSensyWebhookController.php
        ├── CashfreePaymentController.php
        └── RazorpayPaymentController.php
```

---

## 3. Thin Controller Protocol (The "150-Line Rule")

Controllers must serve solely as **HTTP traffic directors**:
1. **Validate Request:** Authorize and validate using dedicated Form Request classes (`App\Http\Requests\<Domain>\<Action>Request`).
2. **Execute Business Logic:** Delegate computation, transactions, generation, and formatting to the Service Layer (`App\Services\...`).
3. **Return Response:** Return a Blade view with compact view models or a JSON response (`JsonResource`).

### Prohibited in Controllers:
- ❌ **No inline `$request->validate([...])`** with more than 3 basic fields.
- ❌ **No direct file system or raw binary manipulation** (e.g. creating zip/xlsx files directly with `ZipArchive`).
- ❌ **No complex Eloquent aggregations or raw DB queries** spanning dozens of lines.
- ❌ **No cross-domain calculations** (e.g., RDA nutrient gap formulas, cohort retention logic).

---

## 4. Form Request Extraction Standard

For every controller modifying state (`store`, `update`, `destroy`, `bulk*`), a dedicated Form Request must exist:
- **Location:** `app/Http/Requests/<Domain>/<Action><Entity>Request.php` (e.g., `App\Http\Requests\Admin\Ecommerce\StoreProductRequest`).
- **Authorization:** Enforce permission checks within `authorize()` method where applicable using `$this->user('admin')->can(...)`.
- **Validation Rules:** Encapsulate nested array validations, unique rules with soft-delete ignorance, and custom error messages in `rules()` and `messages()`.

---

## 5. Dead Code & Template Residue Cleanup Checklist

Before or during controller re-organization, purge unused template leftovers to eliminate clutter. The following controllers were confirmed as non-functional theme templates:
1. `Admin/CryptocurrencyController.php`
2. `Admin/UsersController.php` (superseded by `Admin/UserController.php` & `AccessControlController.php`)
3. `Admin/RoleandaccessController.php` (superseded by Spatie `RoleController.php` & `AccessControlController.php`)
4. `Frontend/ComponentpageController.php`
5. `Frontend/TableController.php`
6. `Frontend/FormsController.php`
7. `Frontend/ChartController.php`
8. `Frontend/AiapplicationController.php`
9. `Frontend/Nutrition/DietChartController.php` (hardcoded mock data, unused)

*Rule: Always verify route references across `routes/*.php` before deleting any controller.*

---

## 6. Step-by-Step Refactoring Process (Regression-Proof)

Any refactoring of an existing controller must follow this 5-step cycle:

```
[1. Run Regression Tests]
          │
          ▼
[2. Extract Form Request] ──► Verify validation & error response
          │
          ▼
[3. Extract Business Logic to Service] ──► Write unit test for Service
          │
          ▼
[4. Slim Down Controller] ──► Delegate to Service, retain view/redirect
          │
          ▼
[5. Run Full Test Suite & Clear Route Cache] ──► php artisan test && php artisan route:clear
```

1. **Step 1: Baseline Test** - Run `php artisan test --filter=<Domain>RegressionTest` to guarantee existing functionality passes.
2. **Step 2: Form Request** - Extract inline `$request->validate()` into `App\Http\Requests\<Domain>\...`.
3. **Step 3: Service Layer** - Move transactional logic, queries, and file operations to `App\Services\<Domain>\...`.
4. **Step 4: Controller Update** - Inject the Service into the controller action and return the original Blade view or redirect with identical flash messages (`success`, `error`).
5. **Step 5: Verification** - Run the regression test suite. Clear route and view cache:
   ```bash
   php artisan route:clear
   php artisan view:clear
   php artisan test
   ```
