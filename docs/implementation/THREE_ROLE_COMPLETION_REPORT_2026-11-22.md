# Three-role completion report — 22/11/2026

## Task Status

PARTIAL — P0 public-cart ambiguity is corrected; source/SQLite workflows, an isolated MySQL QA migration + seed verifier, and static-asset HTTP checks have evidence. Interactive browser acceptance remains unverified.

## Task ID

P0-00A / C-R10 — one public customer journey.

## Summary

The current approved requirement is the web-delivery contract in `requirements.md` §§14–15: a guest builds a cart, submits a delivery request, Staff accepts it into an Order, prepares it, dispatches it, confirms receipt, and records COD when applicable. The public product detail had exposed this delivery cart alongside the older pickup cart. That made one product page start two independent session carts.

The product page now exposes only the canonical delivery-cart form:

`menu detail -> /cart -> /cart/checkout -> /customer-orders -> Staff customer-order inbox`

The pickup/scheduled implementation remains in place at its existing dedicated routes and models (`/pickup/cart`, `OnlineOrderRequest`, `PickupRequest`). It is intentionally not deleted: it protects historical data and existing Staff flows until a separately approved data/route migration maps it into a single request contract.

## Populated-copy MySQL upgrade rehearsal (22/11/2026)

`coffee_shop` was copied into the isolated schema `coffee_shop_upgrade_qa_cdx1122`; the source schema was never migrated, seeded, or changed. Before migration, 13 core table counts matched between source and copy. The five then-pending migrations completed on the copy, and every compared count still matched: `cafe_tables`, users, categories/products/variants/toppings, ingredients, orders/items/payments/promotions, reservations, and online requests.

The seed was corrected so existing V1 fixtures stay intact, missing image paths are backfilled only when `NULL`, and missing V2 fixtures are added exactly once from stable V1 keys. On the upgraded copy, `verify_seed_data.sql` returned zero invalid rows for all 13 checks. A second seed import reported an existing dataset and created no duplicate V2 fixtures. This is QA evidence only; a backup/restore procedure and engineer approval are still required before upgrading any non-QA database.

## Files Created

- `docs/implementation/THREE_ROLE_COMPLETION_REPORT_2026-11-22.md`

## Files Modified

- `resources/views/public/menu/show.blade.php`
- `tests/Feature/CustomerPickupFlowTest.php`

## Files Deleted

None.

## Implementation Details

- Removed the legacy pickup form include from the public product-detail page.
- Retained the canonical `cart.items.store` form and all existing server-side catalog/price/topping validation.
- Retained legacy pickup routes, controller, session contract and tests; there is no schema/data deletion.
- Updated the pickup regression test to assert that the product page does not render a legacy `POST /cart` form while confirming the dedicated pickup flow still works through its own route.

## Verified Role Loops

| Role | Source workflow with test evidence | Status |
| --- | --- | --- |
| Customer | Menu -> canonical cart -> delivery checkout -> request tracking; legacy pickup is preserved separately | Source/SQLite verified |
| Staff | Accept request -> Order -> prepare/ready/served; delivery dispatch/receipt and COD handling | Source/SQLite verified |
| Admin | Catalog/recipe/inventory, promotion/payment reporting and dashboard reads | Source/SQLite verified |

The following suites specifically passed on 22/11/2026 using PHP 8.3.30 and SQLite `:memory:`:

- `PublicCustomerCartTest`, `CustomerPickupFlowTest`, `CustomerOrderWorkflowTest`, `OnlineOrderWorkflowTest`, `PromotionManagementTest`: **55 passed / 744 assertions**.
- `CustomerOrderWorkflowTest`, `OnlineOrderWorkflowTest`, `PosOrderWorkflowTest`, `PaymentWorkflowTest`, `InventoryServiceTest`, `InventoryImportServiceTest`, `PromotionServiceTest`, `DashboardIntegrationTest`: **111 passed / 1,081 assertions**.

The full Laravel suite was then collected through a JUnit artifact to avoid terminal-output truncation: **602 tests, 8,003 assertions, 0 failures, 0 errors, 20 skipped** in 41.93 seconds. `npm.cmd run build` completed successfully on the current worktree.

`npm.cmd run build` also passed. Vite reports two absolute public-asset references as unresolved at build-analysis time. The files were subsequently verified through a PHP static server rooted at `public`: `/assets/video/maison-coffee-poster.jpg` and `/images/auth-bg-golden.jpg` both return `200 image/jpeg`.

On 22/11/2026, the team-created isolated schema `coffee_shop_qa_20261122` was migrated from empty state on local MySQL 8.4.3. All migrations, including the promotion-image migration and the pending order/payment migrations, completed successfully. `database/seed_data.sql` imported DEMO-V1 and DEMO-V2 fixtures; `database/verify_seed_data.sql` returned `invalid_count = 0` for all 13 checks: image references, fixture presence, request-to-order links, snapshots/totals, payment states, prepaid scenario, order/item totals, stock-ledger chain/current stock/recipe consumption, and promotion usage.

Additional opt-in MySQL evidence: `OnlinePickupSchemaMySqlTest` passed **3 tests / 147 assertions** on self-created-and-dropped `online_schema_test_*` databases. It verifies fresh migration, seed rerun, historic-data upgrade, schema constraints, guarded rollback, Staff screens and preparation ledger. `InventoryImportConcurrencyMySqlTest` passed **3 tests / 84 assertions** on self-created-and-dropped `inventory_import_test_*` databases. `FinanceMySqlTest` ran on a separately created, empty `coffee_finance_qa_cdx1122a` schema; its enabled test passed **1 test / 60 assertions**, while 9 tests were skipped by the suite's own opt-in conditions. The temporary Finance schema was verified empty after teardown and then removed. This does not prove that a populated legacy `coffee_shop` database can be upgraded safely; that database was not changed.

`DashboardRevenueMySqlTest` passed **1 test / 6 assertions** against a separately created, empty `coffee_dashboard_test_cdx1122a` schema. During this work its empty-schema guard was corrected to query only the configured QA schema; the prior `Schema::getTableListing()` call incorrectly listed tables from every database on the MySQL server. The test confirms DECIMAL aggregation and detects duplicate paid payments before reporting revenue. The temporary Dashboard schema was verified empty after teardown and then removed.

`ReservationConcurrencyTest` passed **1 test / 75 assertions** against a separately created, empty `coffee_reservation_test_cdx1122a` schema. Its empty-schema guard received the same schema-scoping correction as the Dashboard test. The suite covers competing create/approve/cancel operations after waiting on a MySQL table lock. The temporary Reservation schema was verified empty after teardown and then removed.

The standalone `tests/manual/table-order-mysql.php` probe passed after its isolated migration list was completed with the `payments` dependency required by `OrderService`. It creates and removes only a random `coffee_task95_probe_*` schema, then verifies real migrations, Admin-owned catalog/table data, POS item persistence, a `24.70` server-calculated total, and rejection when the table is in maintenance.

To cover the suites that require a second MySQL instance, an ephemeral localhost-only MySQL 8.4.3 instance was created on port 3308 under `storage/framework/testing/`. `SalesFinanceMySqlTest` passed **9 tests / 219 assertions** on self-created-and-dropped `sales_finance_test_*` databases. It covers receipt replay, cashier/bank-reference races, cancellation/serve/complete/promotion races, last-stock acceptance, historical upgrade constraints, counter/online/scheduled completion, stale transaction snapshots, and dine-in table/reservation races. `CustomerPosMySqlConcurrencyTest` passed **2 tests / 35 assertions** on a separately migrated `coffee_workflow_qa_cdx1122a` schema; it verifies concurrent POS checkout and concurrent guest submit/Staff accept/bank confirmation each preserve one business result per operation. That workflow schema was inspected (2 orders, 2 payments, 2 inventory transactions for its two intentional scenarios) and removed afterward.

## Acceptance Criteria

| Criterion | Status | Evidence |
| --- | --- | --- |
| A new guest does not see two independent cart forms from one product page | PASS (source/SQLite) | Product detail renders only `cart.items.store`; regression test passes. |
| Existing delivery cart and checkout retain server-side price/catalog validation | PASS (source/SQLite) | `PublicCustomerCartTest`, `CustomerOrderWorkflowTest`. |
| Existing pickup data/flow is not removed | PASS (source/SQLite) | `CustomerPickupFlowTest`, `OnlineOrderWorkflowTest`. |
| Customer -> Staff -> Order/payment/inventory lifecycle is regression-covered | PASS (source/SQLite) | 111-test domain suite. |
| Fresh MySQL QA migration and seed integrity | PASS (fresh QA only) | MySQL 8.4.3 schema `coffee_shop_qa_20261122`; all migrations applied; 13 verifier checks returned zero invalid rows. |
| Online schema and inventory concurrency on MySQL | PASS (isolated test schemas) | `OnlinePickupSchemaMySqlTest` 3/147; `InventoryImportConcurrencyMySqlTest` 3/84. |
| Finance MySQL suite | PARTIAL | 1 enabled test / 60 assertions passed; 9 suite-controlled skips remain. |
| Dashboard revenue on MySQL | PASS (isolated test schema) | `DashboardRevenueMySqlTest` 1/6; exact DECIMAL totals and duplicate-paid guard verified. |
| Reservation concurrency on MySQL | PASS (isolated test schema) | `ReservationConcurrencyTest` 1/75; conflicting table operations are rechecked under lock. |
| POS/table persistence on MySQL | PASS (isolated probe schema) | `table-order-mysql.php`; real migration dependencies, server total and maintenance rejection verified. |
| Sales/Finance concurrency on separate MySQL instance | PASS (localhost QA port 3308) | `SalesFinanceMySqlTest` 9/219; payment, stock, lifecycle and table/reservation races. |
| Customer/POS concurrency on separate MySQL instance | PASS (localhost QA port 3308) | `CustomerPosMySqlConcurrencyTest` 2/35; one result per POS/submit/accept/payment operation. |
| Upgrade of populated legacy MySQL database / locking behavior | UNVERIFIED | Existing `coffee_shop` was deliberately not migrated or altered. |
| Seed/static image paths can be served over HTTP | PASS (HTTP) | Six checked public assets, including all three promotion banners, returned HTTP 200 with image MIME types. |
| Browser/mobile acceptance of DB-backed pages | UNVERIFIED | Browser automation host is unavailable; HTTP route checks do not replace visual or interactive acceptance. |

## Risks

- Two request models still exist internally. Hiding the duplicate entry point prevents new customers from splitting their cart, but it is not a physical schema merge.
- The fresh QA schema proves migration + seed compatibility only. It does not prove upgrade compatibility for a populated legacy schema.
- Browser automation is unavailable in the current environment; HTTP checks do not prove responsive layout or interactive behavior.
- The MySQL concurrency evidence uses only disposable schemas; it does not authorize running concurrent workflows against the populated live database.

## Blockers

None for the source-level P0 correction or fresh-QA migration/seed check. Browser acceptance is blocked by the unavailable browser-automation host.

## Assumptions

- `requirements.md` §§14–15 is the current authority over the earlier pickup-only planning text because it contains later explicit approvals for web delivery/COD.
- Legacy pickup remains a compatibility feature until a separately scoped mapping/migration task is approved.

## Out-of-Scope Findings

- Public menu/index Blade contains substantial inline visual styling and unrelated AI-widget content. It was not refactored because this task addresses checkout ambiguity, not a public-site redesign.
- Vite's static-analysis warnings for the two absolute image URLs are not HTTP failures; the assets are present and served.

## Unverified Items

- Upgrade of a populated legacy MySQL database.
- Desktop/tablet/mobile browser flow, including uploaded/seeded images.
- Real staff/admin login permissions in a browser.

## Engineer Review Required

- Confirm whether pickup/scheduled ordering remains a visible public offering. If yes, approve a later contract/schema consolidation rather than re-exposing two forms on the product page.
- Provide a backup-confirmed populated QA copy before rehearsing legacy-database upgrade behavior.

## Recommended Next Task

Run the documented browser E2E matrix on a QA database: canonical delivery request -> Staff accept -> prepare -> dispatch/deliver -> paid/completed, then verify Admin inventory movements, finance report and dashboard values. Do not implement automatically.
