## Task Status
SUCCESS for documentation/survey scope. **Full Area/Table POS Architecture: NOT READY for end-to-end implementation** pending owner contracts and policy decisions. No POS functionality implemented in TASK01.

## Task ID
task9.1 — Area & Table POS Architecture & Integration Planning.

## Summary
Created docs/area-table-pos-architecture.md with evidence-based module inventory, physical/model relationship map, route inventory, persisted/derived state distinction, workflows, sequence diagram, proposed contracts, ownership and task02–09 handoff. Existing operations are not conflated with missing Payment/Receipt or proposed API behavior.

## Files Created
- docs/area-table-pos-architecture.md — full architecture and integration plan.
- docs/task09/task9.1-report.md — this report.

## Files Modified
None.

## Files Deleted
None.

## Implementation Details
Actual branch feature/area-table-pos-ui; initial status only untracked docs/task09. Laravel 13.25.0, Blade/Vite/MySQL, MVC/selective services and generated Base/custom models. Existing layout/navigation reused in plan; no UI source changes. Read source for Area/Table controllers/services/models, Staff OrderController/OrderService/PosController/WorkspaceController, Order FormRequests, Reservation services, Promotion hooks, migrations and relevant views/tests.

Key findings:
- Two create paths: empty Order through OrderService vs session-cart save through PosController::checkout. The latter does not pay, creates pending Order and has different Area/reservation/active-order checks.
- Staff Order create/items/toppings exist with server snapshots/recalculation and transaction locks. No active-order-by-table resolver or stable JSON read DTO found.
- Payment model/schema exists, but Payment service/controller/result route and printable receipt/template not found. PromotionService hooks are not Payment orchestration.
- Actual Table statuses: available/occupied/maintenance only; no reserved or waiting_payment stored state.
- Reservation create/approve/cancel are local/testing-only; overlap [start,end), pending/confirmed/arrived block schedule. No arrived→Order linking endpoint found; OrderService creates reservation_id NULL.
- Manual Admin table release has its own pending-order/future-effective-reservation checks; no evidence that Payment success automatically frees table or completes Order.
- Admin is blocked from Staff flow by nested role middleware AND active-Staff checks at Controller/Request/Service. This conflicts with intended requirements; Hợp/Đạt owner action needed, no auth changes made.

Integration plan covers read Area/Table snapshots, no/one/multiple active-order outcomes, canonical create path/idempotency, existing items/toppings, missing checkout/result/receipt and state refresh. Proposed fields/routes/error codes are explicitly marked PROPOSED; currency VND is presentation policy, not a database column. No legal e-invoice implementation claimed.

## Tests / Checks Executed
- git branch/status/diff/source inventory, composer/framework version.
- artisan --version: Laravel 13.25.0.
- artisan route:list --path=staff: 19 real routes; route source checked for Admin and Staff middleware/actions. Subsequent shell search with no matching architecture file produced exit1; route:list itself succeeded.
- Read-only current MySQL information_schema.COLUMNS/TABLE_CONSTRAINTS for areas/cafe_tables/orders/order_items/order_item_toppings/products/product_variants/payments/reservations. Tables present; status enums/column lists and constraint counts verified. No customer/order row data or secrets read; no writes.
- artisan test --compact --filter='StaffOrderManagementTest|StaffOrderItemManagementTest|CafeTableStatusTest|RoleAuthorizationTest'.
- git diff --check and final changed-file scope review.

## Test Results
44 tests: **43 PASS, 1 FAIL**, 397 assertions. Failure: RoleAuthorizationTest::test_admin_can_use_both_interfaces at /staff, expected200/actual403. Source explains failure; pre-existing and reported, no test or Auth edits. Test DB SQLite :memory: configured in phpunit.xml; existing guards inspected. These tests do not establish MySQL simultaneous create/payment or full POS acceptance.

Survey status: VERIFIED source routes/models/schema shapes; PARTIAL Order/POS integration; MISSING Payment/Receipt/resolver; BLOCKED Admin POS and dependent checkout/release; PROPOSED new read DTO/owner contracts. Full FK/data enforcement, all historical statuses, performance and live mutation flows NOT CHECKED. No production/live payment attempted. Build/browser N/A because docs-only, no UI changed.

## Acceptance Criteria
PASS for survey/design evidence in architecture doc: 1–18 (completion not implied), 19–30 (risks/proposals/open decisions), 31–40 (scope/report), 42–43 (explicit missing dependencies/no implementation claims).

41 PASS for a complete survey and task-specific handoff; downstream readiness is conditional and marked NOT READY for integrated POS. No declaration that existing APIs are accepted/complete or that all tests pass. Owner must approve proposed contracts before implementation-dependent tasks.

## Risks
Double-submit Order/items; divergent creation paths and inconsistent state; payment attempts lack unique paid-per-order guarantee; lost payment response retry; stale table states; missed Reservation walk-in conflict; missing release/cleanup rule. Proposed fixes are contracts for owners, not implemented features. UI must not infer payment from print or use float/client totals as authoritative. Existing OrderController SQL reporting should be reviewed for bindings/PII by owner before new integration.

## Blockers
- Hợp+Đạt: Admin Staff capability across middleware/Requests/Controller/Service.
- Đạt: canonical create and active-order resolver/idempotency; checkout/result/receipt APIs and lifecycle/one-paid safeguards.
- Toàn+Đạt: reservation arrival/order link, walk-in conflict, authoritative release/maintenance/cleaning rules.
- Ninh+Hợp+Đạt: promotion quota and stock transition transaction boundary.

No request for permission needed to deliver this docs-only task; no external teammate messages sent.

## Assumptions
None promoted to business rules. Proposed active-order candidate is pending+dine_in for owner confirmation; no split/merge/transfer inferred. Use existing frontend tokens/shell; future modern POS wording does not approve replacing visual standards.

## Out-of-Scope Findings
PosController contains a separate multi-model transaction in Controller and float-based conversions unlike selective-service/money pattern. Some historical relation queries use withTrashed, so live eligibility requires explicit checks. No refactor performed. Missing APIs are task handoff items, not silently implemented.

## Unverified Items
Real concurrent MySQL operations, payment/receipt behavior (not implemented), area/table floor UI/browser, printer/e-invoice capabilities, full application suite and production deployment.

## Engineer Review Required
Review architecture/contracts and resolve named owner blockers. Independent Area UI task02 can be scoped next while dependent POS/checkout remains blocked. No schema/source/UI or business data changes, no commit/push/merge.

## Recommended Next Task
Owner contract review, then task9.2 Area UI with explicit criteria; do not implement automatically. Tasks9.4–9.7 depend on unresolved Order/Payment/Reservation contracts above.
