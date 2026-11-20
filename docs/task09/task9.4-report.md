# Task9.4 — Table Detail & Order Entry

## Task Status
PARTIAL — read-only Table Detail implemented and verified. Active Order lookup and Create/Continue Order are BLOCKED by the integration/authorization contracts described below. Browser acceptance remains UNVERIFIED. READY FOR REVIEW of the read-only portion; entire task is not complete.

## Task ID
Task9.4 / Area & Table POS Management — TASK 04.

## Summary
Added an Admin-only GET table detail page, linked from floor-plan cards. Shows actual table code/name, Area, capacity and persisted status; refresh reloads data. Existing model scope limits it to nondeleted tables with nondeleted Areas, consistent with task9.3. Does not fetch or expose Order data, create Orders, change statuses or provide fake operational actions.

## Files Created
- app/Http/Controllers/Admin/TableDetailController.php — read-only detail lookup/error handling.
- resources/views/admin/tables/detail.blade.php — detail view with refresh/back navigation and explicit Order unavailable state.
- tests/Feature/TableDetailOrderEntryTest.php — six read-only integration/security/error tests.
- docs/task09/task9.4-report.md — this report.

## Files Modified
- routes/admin.php — one import and GET tables/{cafe_table}/detail route.
- resources/views/admin/tables/components/table-card.blade.php — real detail link; omitted when reused within detail page.
- resources/views/admin/tables/floor-plan.blade.php — update integration notice for available detail view.
- resources/css/admin/table-floor-plan.css — scoped two-column detail grid, stacked at <=768px.
- tests/Feature/CafeTableIntegrationTest.php — route matrix now expects the existing seven routes plus one new read-only route; all middleware assertions retained.
- tests/Feature/TableFloorPlanTest.php — assert real detail link instead of the previous pending-detail notice; read-only assertions retained.

## Files Deleted
None.

## Implementation Details
### Source Survey / Data Contract
Branch feature/area-table-pos-ui. Task9.1-9.3 work is present and uncommitted. Pre-existing edits include Area/CafeTable controllers/models, app.css, Area/table index and tests; untracked task documents, Area/floor styles/views/tests were also already present. Those changes were retained. This task modifies only the files listed above on top of that work. Source review included task9.4, previous reports/architecture, CafeTable and Order models, OrderService::create, OrderController, StoreOrderRequest, PosController, staff/Admin routes, role middleware and CafeTableManagementService reservation guards. Authority documents were read in preceding tasks within this session and retained as context.

CafeTable identity is id; fields table_code/table_name/area_id/capacity/status are actual migrated columns. CafeTable::area uses withTrashed for historical management; forFloorPlan explicitly excludes deleted parents. The detail uses that scope, reads the table and eagerly loads Area name/status. Inactive Areas remain observable, labeled as such; eligibility to create an Order is not inferred from the displayed status.

Order::cafeTable belongsTo via orders.table_id, withTrashed. CafeTable::orders hasMany via table_id. orders.status enum is pending/completed/cancelled; order_type is dine_in/takeaway. No schema changes.

### Existing / Partial / Missing Contracts
| Concern | Verified source | State for this task |
| --- | --- | --- |
| Table Detail | No existing suitable detail route; Admin floor-plan read scope exists | IMPLEMENTED as Admin GET |
| Pending dine-in candidate | OrderService::create checks table_id + order_type=dine_in + status=pending under locks | EXISTING create guard; canonical active resolver/multiple policy not approved |
| Active resolver | No authoritative none/one/multiple endpoint; task9.1 labels IR02 missing/proposed | MISSING/BLOCKED |
| Canonical create | POST staff.orders.store calls OrderService::create; returns redirect staff.orders.show with real Order ID | EXISTING for active Staff, blocked from Admin UI |
| Alternate create | POST staff.pos.checkout persists a session cart through a separate path | EXISTING but different checks; cannot treat as interchangeable create/open API |
| Continue | GET staff.orders.show exists, calls assertActiveStaff | EXISTING for Staff; blocked for Admin |
| Reservation walk-in rule | Table management guards future-effective pending/confirmed/arrived reservations; OrderService::create has no corresponding Reservation guard | MISSING approved Order-entry reservation policy |
| Duplicate safety | OrderService locks actor/Area/Table, checks pending dine-in and available, then writes occupied atomically; alternate POS writer differs | PARTIAL; no claim of general idempotency across writers |

### Authorization Conflict / Dependency Handling
requirements.md says Admin has approved Staff operating rights. Current routes/staff.php nests role:staff inside the broader web.php group, while OrderController, StoreOrderRequest and OrderService also enforce active Staff only. This is the known Admin→Staff403 issue recorded by prior tasks. Task9.4 hardRules7/9/23 prohibit modifying Order or Auth policy and opening permissions. The affected integration is stopped rather than silently broadening authorization or exposing Order summaries through a new Admin endpoint.

Task9.4 taskUnit03.E explicitly allows completing Detail read-only when appropriate POS contracts are unavailable and requires marking Entry BLOCKED. That fallback is implemented. No new permission, Order proxy, operational POST, fake button or redirect to a forbidden Staff route was added.

### Active Order Lookup
BLOCKED. The pending+dine_in check is evidence of an existing create guard, not approval for a new Admin active-order reader. No latest()/first() heuristic, no arbitrary Order selection. The page says Order information is unavailable; it does not falsely claim the table has no active Order. None/one/multiple/completed/cancelled handling is deferred until an authorized resolver and owner-approved multiple-order policy exist. Order identifiers from query strings are not read or used.

### Create Order / Continue Order
BLOCKED. Existing create contract: POST staff/orders, order_type=dine_in, table_id, optional customer_id/note; StoreOrderRequest validates and authorizes; OrderService writes server-owned values; successful response redirects to staff/orders/{order}. Existing continue contract: GET staff/orders/{order}, active Staff authorization. These were inspected but not called from this Admin detail page. No write route is introduced, so CSRF/double-submit UI and write concurrency tests are not applicable to the completed read-only slice; they remain blocked acceptance for the full Entry task.

Owner handoff: Đạt must confirm canonical create/resolver path, accepted active-order definition, zero/one/multiple policy, duplicate/idempotency semantics across all writers, stale checks and real Order response contract. Đạt/Toàn must confirm walk-in/reservation eligibility. Hợp/engineer must resolve the existing approved Admin-vs-Staff authorization discrepancy in a separately authorized scope. No message was sent to others.

### Table Eligibility / Concurrency
All displayed statuses are read-only. Available does not imply entry eligibility. Occupied does not select an arbitrary Order. Maintenance is explicitly labeled and has no create action. Reserved/waiting_payment are not persisted Table statuses and are not inferred. Inactive Area details remain readable, not operationally enabled. No Reservation is altered/read by this detail; operational conflict decisions remain blocked.

Every detail/refresh GET re-reads current table state. Two viewers do not reserve/occupy the table. There are no Order writes to race in the implemented slice. Backend duplicate safety and stale Create/Continue checks must be verified after contracts are supplied; no concurrency guarantee is claimed from disabled buttons or read-only tests.

### Table Detail / UI / Security
GET /admin/tables/{cafe_table}/detail, name admin.tables.show, TableDetailController::show, numeric route ID, inherited auth:web + role:admin. Guest redirects, Staff403, missing/malformed/deleted table or deleted Area404. Original GET /admin/tables/{id} method behavior is unchanged.

Floor cards retain noninteractive article semantics with an explicit keyboard-focusable Xem chi tiết link. The detail reuses the existing table-card partial, suppressing its self-link. Back-to-floor URL is generated with the actual Area ID and view=floor. Refresh points to the exact table ID. Arbitrary return_url/order_id inputs are ignored; no open redirect or cross-table Order lookup. All text remains Blade-escaped.

On query error, the page displays unavailable/refresh without fake table/Order values; log includes a static message and SQLSTATE only. Loading is N/A for synchronous page navigation. Detail is a page, not a modal, so no focus-trap/close-dialog code is needed. Responsive grid stacks at768px, uses existing theme tokens and inherits48px controls/focus styles. Actual1440/768/375px browser, keyboard/touch/contrast/overflow checks remain unverified.

### Performance
Two Area/table reads for a normal detail (table with parent-existence condition, eager Area). No N+1, Order history/items/Payment/reservation loads, polling or cache. Model scope and existing components reused, no intermediate integration service or dependency added.

## Tests / Checks Executed
PHP8.3.33 via Laragon; Node22/npm existing installation. Feature fixtures enforce sqlite/:memory: before migrations. Live MySQL probe was read-only, used an in-memory rendering identity and empty error bag, no user/Order/table writes.

| ID | Scenario / Expected | Actual |
| --- | --- | --- |
| TD-01 | Floor link opens exact table, related Area/capacity/status; safe back/refresh links | PASS |
| TD-02 | No writes, no Order/Payment/Reservation queries, unavailable rather than false no-order; ignore injected order_id/return_url | PASS |
| TD-03 | Missing/malformed/deleted table and deleted Area404 | PASS |
| TD-04 | Guest redirect, Staff403, only GET/HEAD accepted; existing Admin middleware | PASS |
| TD-05 | Escaped text and refresh sees maintenance/inactive changes | PASS |
| TD-06 | Query failure safe unavailable state and safe log | PASS |
| REG-01 | artisan test --compact --filter='TableDetailOrderEntry\|TableFloorPlan\|AreaManagement\|AreaUpdate\|AreaIntegrity\|CafeTable' | 51 PASS,894 assertions |
| DB-01 | Independent local MySQL table+Area reconciliation and actual detail Blade render | PASS for5 live tables; no writes or Order queries |
| ROUTE-01 | artisan route:list --path=admin/tables | PASS,8 routes including new read-only detail |
| PHP-01 | php -l TableDetailController.php | PASS |
| FORMAT-01 | Pint on new PHP files | Applied local formatting; unrelated route formatter edits were reverted |
| BUILD-01 | npm.cmd run build | PASS |
| DIFF-01 | git diff --check and changed-source review | PASS; existing CRLF normalization notice only |
| ENTRY-01 | Active resolver / Create / Continue / duplicate concurrency / reservation conflict | BLOCKED; not simulated with fake backend |
| UI-01 | Browser1440/768/375px, keyboard/touch/overflow | NOT RUN / UNVERIFIED |

## Test Results
All51 targeted tests passed. Tests ensure the new route expands the route matrix from7 to8 while preserving existing middleware assertions; previous route assertion and pending-detail notice were updated for the intentional feature addition, not removed. No write-based fixture probes were run on MySQL. Full repository suite and Reservation/POS suites were not run; those sources are unchanged.

Build retains existing optional fontaine and unresolved /assets/video/maison-coffee-poster.jpg warnings. No dependency was installed. Known RoleAuthorizationTest Admin→Staff403 failure was observed in task9.3, not rerun in this task's targeted selection.

## Acceptance Criteria
| Criterion | Result | Evidence |
| --- | --- | --- |
| 1 | PASS | Previous task source/reports inspected |
| 2 | PASS | Table/Order models and migration verified |
| 3 | PASS | table_id relationships inspected |
| 4 | PASS | New read-only detail page |
| 5 | PASS | DB-01 real MySQL rendering/reconciliation |
| 6 | PASS | TD-01 correct code |
| 7 | PASS | TD-01 correct Area |
| 8 | PASS | TD-01 actual capacity |
| 9 | PASS | Persisted mapping,TD-05 refresh |
| 10 | PASS | TD-02 no write/Order query |
| 11 | PASS | TD-02 unchanged snapshot |
| 12 | BLOCKED | No approved authorized active resolver |
| 13 | PASS (read-only) | No Order access/navigation implemented |
| 14 | BLOCKED | Explicit unavailable state; no false no-order claim |
| 15 | BLOCKED | Multiple-active policy/resolver pending |
| 16 | BLOCKED | Resolver not integrated |
| 17 | BLOCKED | Admin permission/eligibility/canonical create contract |
| 18 | BLOCKED | Existing backend inspected,not invoked |
| 19 | PASS | No direct Order insert |
| 20 | BLOCKED | No create request |
| 21 | BLOCKED | Existing Staff route unavailable to Admin |
| 22 | PASS (read-only) | No Continue action or Order write |
| 23 | PASS | Order Items untouched |
| 24 | PASS | Payment untouched |
| 25 | PASS | Table state unchanged |
| 26 | BLOCKED | Entry eligibility policy incomplete |
| 27 | BLOCKED | Reservation walk-in conflict contract missing |
| 28 | PASS (read-only) | Maintenance labeled,no operation offered; Entry rules blocked |
| 29 | PASS | auth:web inherited |
| 30 | PASS | role:admin inherited,TD-04 |
| 31 | PASS (tested slice) | Numeric ID,scope,permission,ignored Order ID; no Order exposure |
| 32 | N/A read-only / BLOCKED Entry | No write endpoint added |
| 33 | PASS | GET does not create |
| 34 | BLOCKED | No create submit UI |
| 35 | BLOCKED | Cross-writer idempotency contract unresolved |
| 36 | PARTIAL | Detail refresh verified; Entry stale/conflict blocked |
| 37 | PARTIAL | Safe query error; loading N/A sync; Entry error states blocked |
| 38 | UNVERIFIED | Desktop CSS implemented,no browser |
| 39 | UNVERIFIED | Tablet CSS implemented,no browser |
| 40 | UNVERIFIED | Mobile CSS implemented,no browser |
| 41 | PASS (markup) | Real links,labels,focus inheritance; actual keyboard acceptance pending |
| 42 | PASS | TD-02 fixed2 Area/table queries |
| 43 | PASS | Order/Payment logic unchanged |
| 44 | PASS | Auth logic unchanged |
| 45 | PASS | Reservation unchanged |
| 46 | PASS | No migration |
| 47 | PASS | No schema edit |
| 48 | PASS |51 targeted regressions pass |
| 49 | PASS | No commit/push/merge |
| 50 | PASS | sqlite/:memory: guarded; MySQL read-only |
| 51 | PASS | Exact observed evidence reported |
| 52 | PASS | Entry explicitly BLOCKED |
| 53 | PASS | This report |
| 54 | BLOCKED | Full Create/Continue acceptance incomplete |
| 55 | PASS | Read-only fallback,no fake Order |
| 56 | PASS | No Order-at-Table/Payment/receipt completion claim |

## Risks
Entire task remains incomplete until integration contracts/authorization are resolved. Browser acceptance outstanding. Detail status is a point-in-time snapshot, not an eligibility or reservation guarantee.

## Blockers
1. Current Admin UI actor cannot access existing Staff-only Order routes/controller/request/service; task forbids changing permissions.
2. No approved canonical authorized active-order resolver and multiple-order policy.
3. Canonical create path, reservation eligibility and duplicate/idempotency semantics need owner confirmation across OrderService and PosController writers.

## Assumptions
Implement the task's explicitly permitted read-only fallback. Match floor-plan visibility for deleted/inactive Areas. Do not expose Order summaries until authorization and lookup contract are resolved.

## Out-of-Scope Findings
Approved Admin operating rights conflict with current Staff-only Order implementation. Prior RoleAuthorizationTest failure documents this. Existing build warnings. These findings were not repaired in this task.

## Unverified Items
Create/Continue/active resolver/duplicate/stale operational behavior are BLOCKED, not PASS. Browser responsive/keyboard/touch/contrast checks and full repository test suite NOT RUN. MySQL render probe validates data/rendering, not an authenticated browser session.

## Engineer Review Required
Review read-only source and route. Open /admin/tables?view=floor as Admin, then Xem chi tiết. Review task9.1 IR02/IR03, resolve the authorization discrepancy in its proper scope, and provide approved active resolver/canonical create/reservation/idempotency contracts before enabling Order actions. No permission broadening or Order implementation was attempted here.

## Recommended Next Task
Resolve the task9.4 Entry blockers with the relevant owners before task9.5. Do not implement automatically.

### Next Task Handoff
Selected table identifier is the real cafe_tables.id. Detail route: GET /admin/tables/{id}/detail, admin.tables.show, Admin-only. No selected state or Order ID is persisted. Future authorized resolver must return none/one/multiple plus allowed action/reason; use actual linked Order ID from backend, never latest() heuristic or client-injected order_id. Existing Staff create/show contracts are documented above, not currently a usable Admin entry path. Product selection remains task9.5 and must reuse authorized backend item operations after Entry is resolved.
