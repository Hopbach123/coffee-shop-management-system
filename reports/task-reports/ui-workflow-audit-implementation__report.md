# Rà soát và triển khai UI theo nghiệp vụ

## Task Status
PARTIAL: hoàn thành báo cáo thiết kế và nhóm màn đọc dữ liệu/điều hướng/pha chế; chưa hoàn thành mọi màn nghiệp vụ thiếu backend và chưa nghiệm thu browser.

## Task ID
CAFE-UI-WORKFLOW-001, 26/10/2026.

## Summary
Audit toàn bộ nhóm Admin/Staff/Khách theo routes/controllers/views. Báo cáo docs/BAO_CAO_TO_CHUC_UI_THEO_NGHIEP_VU.md chỉ rõ màn có thật, placeholder và thiếu; đề xuất vị trí và owner. Triển khai Staff inbox online/hẹn, request detail, queue pha có thao tác hiện hữu, payment history trong Order; Admin đọc công thức và ledger từ màn cha.

## Files Created
- docs/BAO_CAO_TO_CHUC_UI_THEO_NGHIEP_VU.md
- app/Http/Controllers/Staff/OnlineRequestController.php
- app/Http/Controllers/Admin/InventoryOverviewController.php
- resources/views/components/internal/workflow-status.blade.php
- resources/views/staff/online-requests/index.blade.php
- resources/views/staff/online-requests/show.blade.php
- resources/views/admin/ingredients/history.blade.php
- resources/views/admin/product-variants/recipe.blade.php
- tests/Feature/WorkflowScreenAvailabilityTest.php
- reports/task-reports/ui-workflow-audit-implementation__report.md

## Files Modified
- app/Http/Controllers/Staff/OrderController.php: load real Payment/creator, distinguish missing payment schema.
- app/Http/Controllers/Staff/WorkspaceController.php: queue only pending Orders, old-first, pagination and topping eager load.
- routes/admin.php, routes/staff.php: 4 GET screens within existing role groups.
- config/admin_menu.php: active child routes only, no new unrestricted menu registry entry or DB mutation.
- resources/views/layouts/internal.blade.php: Staff operational links.
- resources/views/admin/ingredients/index.blade.php, resources/views/admin/product-variants/index.blade.php: links from parent entities.
- resources/views/staff/brewing/index.blade.php: existing preparation action with CSRF; no direct stock write.
- resources/views/staff/orders/show.blade.php: transaction history/status and purchase type.
- tests/Feature/OnlinePickupSchemaMySqlTest.php: MySQL rendered workflow/real preparation scenario.
- plan.md: current task scope and criteria.

## Files Deleted
None.

## Implementation Details
Read frontend standard and existing layouts/tokens/views before UI; preserve current template, no new CSS/framework/dependencies. Business workflow writes remain existing endpoints. New screens are GET queries only, no fake accept/payment buttons. Request inbox reads online_order_requests, not legacy pickup_requests; no exposing token hashes. Filters validate enums, paginate15; unavailable schema distinct from empty data, detail 503. Guest redirected, Staff/Admin role group internal; ledger/recipe Admin only. Detail token never included in query result. Payment preference is not treated as paid.

Queue excludes cancelled/completed Orders, pending/preparing/ready items only, paginate30 old-first. Staff gets existing preparing/ready POST forms, Admin read-only due preexisting backend staff-only restrictions; queue does not serve/complete or mark payment. Order show reads payments and actual creator; no inferred successful charge. Admin ledger read-only and recipe read-only deliberately do not duplicate missing write workflows from module branches. No Model/Base changes, no manual DB edits or local migration/seed imports.

Worktree had schema/spec/seed/docs changes from earlier tasks, preserved as preexisting to this UI turn. MySQL test reuse includes earlier schema/seed tests; new screen test is the new contribution. Sidebar menu data in user DB not altered. Do not interpret read-only screens as full online checkout/accept/payment implementation.

## Tests / Checks Executed
- ONLINE_SCHEMA_MYSQL_TEST=1 php artisan test --filter=OnlinePickupSchemaMySqlTest: 3 tests / 126 assertions PASS.
- WorkflowScreenAvailabilityTest: 1 test / 12 assertions PASS (SQLite :memory:, no online schema).
- npm run build PASS, optional fontaine/runtime poster warnings unchanged.
- Pint PHP new/controllers/test changes PASS; git diff --check PASS; routes registered and middleware source reviewed.
- Full suite với ONLINE_SCHEMA_MYSQL_TEST=1: 380 tests, 376 passed, 2 failed, 2 skipped, 5459 assertions. Hai failures vẫn là RoleAuthorizationTest::test_admin_can_use_both_interfaces và UserManagementIntegrationTest::test_edit_role_and_email_are_used_by_authentication_and_authorization, /staff trả 403 thay vì 200. Không có test failure mới từ UI.
- Computer Use initialize/list apps succeeded; app selection approval timed out. No browser interaction/screenshot test performed, no visual PASS claimed.

## Test Results
MySQL tests use fresh random disposable database, migration+seed fixture, show/filter/guest/Staff/Admin routes, linked Order/Payment, no tracking token leak, queue ignores closed/cancelled orders, actual preparation command deducts stock. Database test is removed by teardown; coffee_shop not modified. Missing-schema screen says unavailable instead of false empty, malformed filters return validation errors. Build/render not a substitute for responsive review.

## Acceptance Criteria
| Criterion | Result |
| --- | --- |
| Audit missing UI and specify where/owner/workflow | PASS — report matrix and flow |
| Real request inbox/detail, schema unavailable/empty, internal access | PASS — MySQL and SQLite tests |
| Queue uses existing stock-validating action and excludes finished Orders | PASS — MySQL command/render test |
| Real payment history under Order, no pretend charge | PASS — MySQL paid fixture render |
| Recipe/ledger reachable from parent Admin pages | PASS — route/render tests |
| Preserve theme/no new framework | PASS — existing components/classes, build |
| Public cart/checkout/token tracking + accept/payment/movement writes | INCOMPLETE — backend dependencies listed in design report |
| Browser 375/768/1440, keyboard/no overflow | UNVERIFIED — Computer Use approval timeout |
| Full regression green | FAIL — known Admin Staff 403 baseline unless final output differs |

## Risks
Online migrations still Pending on local coffee_shop so new inbox will show unavailable there; real V2 UI tested on disposable MySQL DB. Missing command Services mean read-only views do not complete business workflow. Queue Admin writes remain disabled until permission fix. Timestamp displayed explicitly UTC to avoid reinterpreting old data; local timezone UX remains follow-up. Large workflows need module ownership and task sizing, not fake interactions.

## Blockers
Browser capture approval timed out. Public online submit/tracking and atomic accept/payment/movement commands absent, preventing full end-to-end UI activation. These are implementation gaps, not successful features.

## Assumptions
User scope is UI audit/organization and implementation using existing functional backend; not authorization to fabricate successful accepts/payments or duplicate modules. Three ways to buy, pickup only; no delivery/gateway/slots/holds.

## Out-of-Scope Findings
Admin role denied by existing POS/Order/Inventory backend despite requirements; two baseline tests fail. Legacy pickup_requests PATCH only changes status, does not create Order; new UI does not link to it. Missing Recipe CRUD branch not silently copied. No modification to those writes in UI task.

## Unverified Items
Browser visual/keyboard, deployed CI, schema migration onto coffee_shop, authenticated public token workflow, gateway (out of scope), full app completion.

## Engineer Review Required
Review report, four read screens, menu paths and queue behavior. Test with V2 dev schema before enabling online module; local pending schema intentionally shows unavailable. Review incomplete backend tasks with owners before next implementation. No stage/commit/push.

## Recommended Next Task
Fix Admin/Staff permission consistency or implement online submit/accept Services, then connect public checkout/tracking and writes according to report. Do not implement automatically.
