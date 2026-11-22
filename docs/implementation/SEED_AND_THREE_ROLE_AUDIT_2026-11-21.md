# Seed assets and three-role workflow audit

## Task Status

PARTIAL — seed-image implementation, Laravel regression, isolated MySQL migration/seed/concurrency checks, and frontend build are complete. Interactive browser acceptance remains unverified because the browser-automation host is unavailable.

## Task ID

SEED-ASSET-ROLE-AUDIT-001 / 2026-11-21

## Summary

This audit uses the current `dev` source, routes, Controllers, Blade views, migrations, and existing implementation reports. It does not treat older reports as current runtime proof.

The project has UI/source coverage for the three product roles. Source and automated MySQL checks now cover the core customer → staff → admin lifecycle, but the three roles do **not** yet form one browser-accepted closed loop. The remaining product-design gap is that two separate public ordering contracts coexist:

- `CustomerCartController` + `CustomerOrderController` uses `CustomerCartService` and `CustomerOrderRequest` for a delivery-style request.
- `CartController` + `OnlineRequestSubmissionService` uses the legacy `customer_cart` session and `OnlineOrderRequest` for online/scheduled pickup.

The routes share `/cart` across different verbs but use different session data and views. This is evidence of two unfinished customer checkout paths, not evidence of a single coherent customer journey.

## Files Created

- `database/migrations/2026_11_21_000001_add_image_to_promotions_table.php`
- `public/images/promotions/demo-coffee-morning.png`
- `public/images/promotions/demo-matcha-offer.png`
- `public/images/promotions/demo-espresso-offer.png`
- This audit report.

## Files Modified

- `app/Models/Promotion.php`
- `app/Http/Controllers/Admin/PromotionController.php`
- `app/Http/Requests/Admin/StorePromotionRequest.php`
- `resources/views/admin/promotions/_form.blade.php`
- `resources/views/admin/promotions/create.blade.php`
- `resources/views/admin/promotions/edit.blade.php`
- `resources/views/admin/promotions/index.blade.php`
- `resources/css/admin/promotions.css`
- `database/seed_data.sql`
- `database/verify_seed_data.sql`
- `database-design.md`
- `docs/Rp-seeddata-runMySQL.md`
- `tests/Feature/PromotionManagementTest.php`

## Files Deleted

None.

## Implementation Details

### Populated-fixture upgrade support

- Seed V1 remains protected: reruns do not replace baseline records, credentials, financial history, or a non-null image chosen locally.
- Missing `NULL` image paths on stable V1 demo products/promotions are safely backfilled.
- V2 pickup fixtures now use their own idempotency marker. They are added exactly once to an existing complete V1 fixture database and are skipped on the next run.
- The behavior was verified on a disposable copy of the current local `coffee_shop` data after all five pending migrations: all 13 verifier checks returned zero invalid rows.

### Seed media

- Added local, versioned images for the four existing seed products and three seed categories.
- Added three original promotion-banner images and maps all six DEMO-V1 promotions to a stable `images/promotions/...` path.
- Added meaningful descriptions to the six promotion fixtures without altering codes, amounts, validity windows, usage state, or seed idempotency.
- Added `seed_image_references` to the SQL verifier. It validates database path references; file-system availability must be checked by the web server/browser.

### Promotion image support

- Added a nullable `promotions.image` column in an additive migration; it is a display-only field.
- Added guarded JPG/PNG/WebP upload validation (5 MB maximum).
- New uploads are written before persistence, deleted if persistence fails, and replace a previous managed upload only after a successful database save.
- Static seed images under `images/promotions/` are never deleted by the upload replacement logic.
- Admin promotion cards render an optional image with a contrast overlay; promotion eligibility and monetary logic remain unchanged.

## Three-role source/UI coverage

Percentages below are **route/view coverage estimates**, not business acceptance or test pass rates.

| Role | Source/UI observed | Estimated screen coverage | Workflow assessment |
| --- | --- | ---: | --- |
| Admin | Dashboard, fixed navigation, users, customers, catalog, variants, toppings, promotions, ingredients, stock import/adjustment/history, recipes, areas/tables, reservations, finance | 90% | Management screens are broadly present. Inventory and promotion UI are present; full visual/browser acceptance remains unverified. |
| Staff | POS, order list/detail, payment screen, online/scheduled request queue, customer-order request queue, brewing queue, tables, reservation/pickup workspaces | 85% | Counter and request-handling screens exist. Stock/payment/order transition correctness still requires runtime integration proof. |
| Customer | Home, menu/detail/search, cart, checkout, request confirmation/tracking, reservation | 75% | Public screens exist, but the two independent cart/request implementations prevent calling the ordering journey unified. |

The earlier 83% estimate remains only a route/view estimate. Automated evidence now includes fresh MySQL migration/seed verification, payment/inventory/reservation concurrency checks and the full Laravel suite. Browser acceptance for the complete role journey remains unverified.

## Evidence reviewed

- Role boundaries: `routes/web.php`, `routes/admin.php`, `routes/staff.php`.
- Admin UI inventory: `resources/views/admin/**`; fixed navigation evidence: `docs/implementation/ADMIN_FIXED_NAVIGATION_REPORT.md`.
- Staff UI inventory: `resources/views/staff/**`.
- Customer UI inventory: `resources/views/public/**`.
- Lifecycle services found: `OrderService`, `PaymentService`, `InventoryService`, `PosCheckoutService`, `CustomerOrderService`, `OnlineRequestSubmissionService`.
- Source conflict found: `routes/web.php` maps the public customer request flow and the legacy pickup flow to separate controllers/session contracts.

## Tests / Checks Executed

- `git diff --check` — passed (no whitespace error output).
- `npm.cmd run build` — passed.
- Static review of changed source/diff and route/controller/view trace.
- Full Laravel suite (JUnit): 602 tests, 8,003 assertions, 0 failures, 0 errors, 20 skips.
- Isolated MySQL QA: fresh migration, `seed_data.sql`, and `verify_seed_data.sql`; all 13 SQL checks returned `invalid_count = 0`.
- MySQL lifecycle/concurrency checks: online schema 3/147, inventory import 3/84, Sales/Finance 9/219, Customer/POS 2/35, reservation 1/75, dashboard revenue 1/6, Finance 1 enabled/60.
- HTTP asset checks: product and promotion seed assets returned 200 with the expected image MIME types.
- `npm.cmd run build` — passed; Vite retains non-fatal analysis warnings for two absolute public asset URLs that were separately HTTP-verified.

## Test Results

- PASS: Vite production build completed.
- PASS: generated static promotion assets are in `public/images/promotions/`.
- PASS: source guard prevents deletion of static `images/promotions/...` files through promotion upload replacement.
- PASS: migration works on fresh isolated MySQL QA schemas; the populated live `coffee_shop` schema was intentionally not migrated.
- PASS: promotion-focused and full Laravel test coverage executed.
- PASS: seed import plus every `verify_seed_data.sql` count is zero on QA.
- UNVERIFIED: browser display, responsive layout, and image delivery.

## Acceptance Criteria

- PASS: Seed products/categories and all promotion fixtures have local image references.
- PASS: Admin can submit an optional promotion banner through the established upload pattern.
- PASS: The display-image field does not change promotion calculation/history fields.
- PASS: A source-based three-role UI/workflow audit was recorded.
- PASS: Images are available through HTTP after fresh QA seed import.
- FAIL: A single canonical customer order journey is not demonstrated; two public cart/request workflows remain.

## Risks

- The SQL seed checks image path values only; MySQL cannot test that static web assets exist.
- Applying the migration is required before importing the updated SQL; this sequence passed on fresh QA but was not applied to the populated live schema.
- A real upgrade still requires a backup-confirmed target and engineer approval; the populated-data result was obtained only on a disposable QA copy.

## Blockers

None for automated QA. Browser-based interactive acceptance is blocked by the unavailable browser-automation host.

## Assumptions

- `promotions.image` is approved as display-only master data because the user explicitly requested promotion images; it intentionally is not copied into `order_promotions` historical snapshots.
- The three banner assets are visual fixtures for development/demo, not production marketing assets.

## Out-of-Scope Findings

- Duplicate public customer cart/checkout contracts need a separate product decision and consolidation task. This task does not delete, redirect, or merge either flow.
- Browser visual QA for the broad Admin/Staff redesign remains pending in the existing fixed-navigation report.

## Unverified Items

- Exact account `admin.local@example.test` was not used or changed; it belongs to an environment that was not authenticated/verified in this task.
- Upgrade of a populated legacy MySQL database.
- Browser desktop/tablet/mobile interaction for each role, including authenticated Admin/Staff workflows.

## Engineer Review Required

1. Confirm the customer product scope: one unified web checkout, or intentional coexistence of delivery and pickup contracts.
2. Review the QA evidence in `THREE_ROLE_COMPLETION_REPORT_2026-11-22.md` and authorize a separate backup-first upgrade rehearsal if the populated legacy database must be upgraded.
3. Open Admin → Khuyến mãi to review banner composition and upload/replace behavior in a browser.
4. Provide/recover browser automation or perform the documented desktop/tablet/mobile manual acceptance matrix.

## Recommended Next Task

Decide and implement one canonical public ordering contract before claiming a complete Customer → Staff → Admin business loop. Do not automatically delete either existing public workflow.
