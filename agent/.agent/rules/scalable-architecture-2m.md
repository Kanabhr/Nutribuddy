# Nutribuddy 2M+ User High-Scale Architecture & Modernization Blueprint

This document specifies the technical roadmap, architectural patterns, directory restructuring, performance optimizations, database indexing, and caching strategy required to scale Nutribuddy to **2+ million active users with sub-100ms response times and zero downtime**.

---

## 1. Executive Summary & Core Bottlenecks Identified

### Current Bottlenecks in the Codebase:
1. **Uncached Heavy Database Queries:**
   - Dynamic assessment queries (`FoodMaster::query()->get()->filter(...)`) fetch entire food tables into PHP memory on every plan generation or preview.
   - Global view composers querying `Setting::get(...)` directly from SQL without Redis caching.
2. **Synchronous Plan Generation:**
   - Heavy combinatorial scoring and external LLM/OpenAI calls are invoked synchronously during HTTP requests in `DietChartGenerator.php`. Under concurrent traffic (>50 req/sec), PHP-FPM workers exhaust immediately.
3. **Database Concurrency & Session Storage:**
   - Session and cache drivers currently default to `database` / `file` / `sqlite`, which will lock tables under massive concurrent loads.
   - Missing composite indexes on high-throughput lookup tables (`assessment_answers`, `child_assessments`, `diet_plans`, `diet_plan_meals`, `orders`).
4. **Scattered Domain Logic:**
   - Controller classes handle multi-step domain flows (e.g. `NutritionistLeadController` doing meal sorting, grouping, and food filtering directly).
5. **Route Duplication & Missing Middleware Rate Limits:**
   - Routes like `/nutrition/toggle-item` and `/child/{child}/diet-chart` are declared multiple times across `routes/web.php` and `routes/nutrition.php`.

---

## 2. Target High-Scale Architecture (2M+ Users)

```
                            [Cloudflare / AWS CloudFront CDN]
                           (SSL, DDoS Shield, Edge Static Cache)
                                             │
                                             ▼
                             [HAProxy / AWS ALB Load Balancer]
                                             │
                    ┌────────────────────────┴────────────────────────┐
                    ▼                                                 ▼
        [App Server Cluster 1..N]                         [Queue Workers (Laravel Horizon)]
        - PHP 8.3 OPcache + JIT                           - Redis Cluster Backed
        - Octane / FrankenPHP                             - Diet Plan Generation
        - Stateless API & Blade Views                     - PDF Rendering, Emails, WhatsApp
                    │                                                 │
                    ├────────────────────────┬────────────────────────┤
                    ▼                        ▼                        ▼
          [Redis Cluster 7.x]         [MySQL 8.0 Primary]      [MySQL Read Replicas 1..N]
          - Sessions & Caches         - Writes / Transactions   - Read traffic (Admin, Feeds)
          - Rate Limiting             - Row-level Locking       - Replica lag < 100ms
          - Meal Slot Caching
```

---

## 3. Recommended Modular Directory & File Structure

To support clean domain boundaries, maintainability, and clean dependency injection:

```
app/
├── Domain/                              # Pure Domain Entities & Business Rules
│   ├── Nutrition/
│   │   ├── Contracts/                   # Interfaces (FoodFilterInterface, TargetCalculatorInterface)
│   │   ├── DTOs/                        # Data Transfer Objects (ChildProfileDTO, TargetNutrientsDTO)
│   │   ├── Engines/                     # Calculation engines (RuleBasedEngine, TargetEngine)
│   │   ├── Events/                      # Domain events (DietPlanGenerated, AssessmentCompleted)
│   │   ├── Pipelines/                   # Multi-stage pipelines (SafetyFilterPipeline, VarietyPipeline)
│   │   └── ValueObjects/                # Portion, MacroNutrient, AgeRange
│   ├── Ecommerce/
│   │   ├── DTOs/
│   │   └── Services/
│   └── User/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                       # Back-office controllers (Thin)
│   │   │   └── Nutrition/
│   │   ├── Api/                         # Versioned JSON APIs
│   │   │   └── V1/
│   │   │       ├── Auth/
│   │   │       ├── Nutrition/
│   │   │       └── Store/
│   │   └── Web/                         # Public client Blade view controllers
│   ├── Middleware/
│   │   ├── EnforceJson.php
│   │   ├── Idempotency.php              # Prevents duplicate checkout/order submissions
│   │   └── SecurityHeaders.php
│   └── Requests/                        # Dedicated Form Requests with strict validation
│       ├── Admin/
│       └── Api/V1/
├── Jobs/                                # Async Background Jobs (Horizon)
│   ├── Nutrition/
│   │   ├── GenerateDietPlanJob.php      # Async generation for zero HTTP request timeouts
│   │   ├── ExportDietPlanPdfJob.php
│   │   └── SendQuarterlyReminderJob.php
│   └── Orders/
├── Models/
│   ├── Nutrition/                       # Eloquent models with typed scopes and relations
│   ├── Ecommerce/
│   └── User/
├── Observers/
├── Providers/
│   ├── AppServiceProvider.php           # Global boot, macros
│   ├── AuthServiceProvider.php          # Policies & Spatie Gates
│   ├── CacheServiceProvider.php         # Warmers and cache tags
│   ├── HorizonServiceProvider.php       # Queue monitoring security
│   ├── NutritionDomainServiceProvider.php # Domain singleton & interface bindings
│   └── RouteServiceProvider.php         # Clean route isolation
├── Repositories/                        # Cached read queries
│   └── Nutrition/
│       ├── CachedFoodRepository.php     # In-memory Redis cached food dataset
│       └── CachedTargetRepository.php
└── Services/                            # Application Orchestration Layer
    ├── Nutrition/
    │   ├── DietPlanOrchestrator.php
    │   └── LeadAssignmentService.php
    └── Payment/
```

---

## 4. Layer-by-Layer Fixes & Implementation Plan

### A. Routing & Clean Separation
- **Eliminate Duplications:**
  - Remove redundant `/nutrition/toggle-item` and duplicate questionnaires in `web.php` and `nutrition.php`.
- **Implement API Versioning:**
  - Route all mobile/SPA traffic through `routes/api/v1/...` with `auth:sanctum` or JWT.
- **Route Caching Requirement:**
  - All routes must use controller-action tuples `[Controller::class, 'method']` (no inline closures) to enable `php artisan route:cache`.

### B. Authentication & Session Scalability
- **Switch Sessions to Redis:**
  ```env
  SESSION_DRIVER=redis
  SESSION_CONNECTION=session
  CACHE_STORE=redis
  QUEUE_CONNECTION=redis
  ```
- **Redis Connection Pooling:**
  - Separate Redis databases: DB 0 for Application Cache, DB 1 for Sessions, DB 2 for Queues.
- **Stateless Admin & Customer Guards:**
  - Retain session-based guards for web, implement Sanctum tokens for API clients.

### C. Controllers & Service Providers
- **De-bloat Controllers:**
  - Extract meal group/day chunking logic out of `NutritionistLeadController` into a dedicated `DietPlanPresenter` or `DietPlanResource`.
- **Decouple Providers:**
  - Bind interfaces to implementations in `NutritionDomainServiceProvider` (e.g. `DietPlanEngineInterface` bound to `RuleBasedDietPlanEngine`).
- **Global Settings Cache Warmer:**
  - In `AppServiceProvider`, replace uncached `Setting::get(...)` calls with a tagged remember cache:
    ```php
    Cache::rememberForever('settings.global', fn() => Setting::pluck('val', 'key')->all());
    ```

### D. Diet Chart Engine & Async Pipeline (Critical for 2M Users)
- **Eliminate In-Memory Filtering Bottleneck:**
  - Current: `FoodMaster::query()->get()->filter(...)` pulls the entire table into PHP memory on every plan generation.
  - Fix: Cache the active, allergen-mapped food registry in Redis with tags:
    ```php
    Cache::tags(['foods'])->remember('foods.all_eligible', 86400, fn() => FoodMaster::with('nutrients')->where('is_active', true)->get());
    ```
- **Asynchronous Execution with Polling / WebSockets:**
  1. User submits questionnaire -> Returns HTTP 202 (Accepted) with `job_id` or `assessment_id`.
  2. `GenerateDietPlanJob` pushes to dedicated high-priority Redis queue.
  3. Client listens via Laravel Reverb / Pusher or polls `/api/v1/nutrition/plans/{id}/status`.
  4. Response time drops from 4–8 seconds down to **120ms**.

### E. Database Performance, Sharding & Indexing Rules

Apply the following composite indexes via migration:

```sql
-- Speed up lead queries and nutritionist assignment queues
CREATE INDEX idx_leads_assigned_status ON nutrition_leads (assigned_to, status, created_at);

-- Optimize child assessment retrieval
CREATE INDEX idx_assessments_child_latest ON child_assessments (child_id, created_at DESC);

-- Optimize diet plan lookup by assessment and status
CREATE INDEX idx_diet_plans_assessment_version ON diet_plans (child_assessment_id, version DESC, review_status);

-- Optimize meal item slot lookups
CREATE INDEX idx_diet_plan_meals_plan_slot ON diet_plan_meals (diet_plan_id, meal_slot, sort_order);

-- Food query filtering index
CREATE INDEX idx_food_filters ON food_masters (is_active, is_veg, is_vegan, is_jain_ok, age_min, age_max);
```

### F. High-Traffic Caching Strategy

| Component | Strategy | Storage | TTL | Invalidation Trigger |
| :--- | :--- | :--- | :--- | :--- |
| **Site Settings & SEO** | `Cache::rememberForever` | Redis | Forever | `SettingObserver` on update |
| **ICMR Targets** | In-Memory / Array Cache | Redis | 30 days | Admin target update |
| **Food Master Catalog** | Tagged Cache (`foods`) | Redis | 24 hours | `FoodMasterObserver` on save |
| **User Diet Plan View** | `Cache::tags(['plan_'.$id])` | Redis | 1 hour | Nutritionist review / edit |
| **Product Listings** | Paginated Keys | Redis | 15 mins | Inventory / Order sync |

---

## 5. Step-by-Step Migration & Rollout Checklist

1. [ ] **Phase 1: Environment & Drivers**
   - Switch session and cache driver to Redis.
   - Configure Read/Write database connection strings in `config/database.php`.
2. [ ] **Phase 2: Database Indexes**
   - Create migration for composite indexes on `nutrition_leads`, `diet_plans`, and `food_masters`.
3. [ ] **Phase 3: Route & Code Clean-Up**
   - Deduplicate conflicting routes between `web.php` and `nutrition.php`.
   - Ensure all route declarations use controller tuples to allow route caching.
4. [ ] **Phase 4: Async Queue Setup**
   - Move AI / LLM and complex diet chart generation into background jobs (`GenerateDietPlanJob`).
   - Setup Laravel Horizon for queue supervision.
5. [ ] **Phase 5: Domain Refactoring**
   - Move business engines to `app/Domain/Nutrition/`.
   - Create `CachedFoodRepository` to eliminate table scans.
6. [ ] **Phase 6: Stress Testing & Verification**
   - Run k6 / Locust load tests simulating 10,000 concurrent virtual users.
   - Profile memory and slow query logs with Laravel Pulse / Telescope.
