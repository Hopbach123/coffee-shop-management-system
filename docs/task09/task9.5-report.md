# Task9.5 — Order-at-Table UI Integration

## Task Status
PARTIAL — approved Continue-only implementation, PHP/JavaScript tests, build and isolated MySQL write verification pass. Earlier probe incident has been audited and approved fixture cleanup completed. Browser acceptance remains outstanding; do not treat this as final visual acceptance.

## Task ID
Task9.5 / Area & Table POS Management — TASK05.

## Summary
Admin and Staff active can continue one pending dine_in Order linked to the selected Table. Menu includes real Catalog search/category/variants, memory-only draft, per-line quantity/note, sequential save through existing OrderService, saved-item snapshots and server totals. No Create Order button was added to the floor/detail workflow.

## Approved Scope Changes
User explicitly approved all three questions in this thread:
1. Expand route/controller/FormRequest/service guards so active Admin can use existing Order operations like active Staff.
2. Continue only pending+dine_in+matching table_id; exactly one candidate; conflict on multiple; reject maintenance/inactive/deleted Area; defer new Order creation from floor until Reservation policy is resolved.
3. Memory-only draft and leave warning; sequential save; retain failed lines; never auto-retry ambiguous results; no cross-tab/device idempotency guarantee.

These approvals override the original task restrictions only as necessary for the listed changes. InventoryService actor guard was aligned so Admin can use the existing Order item-preparation operation; no inventory calculation/transition was changed.

## Files Created
- app/Services/TableOrderContext.php — scoped lookup, eligibility, locking, delegation to OrderService, minimal saved snapshot.
- app/Http/Controllers/Staff/TableOrderController.php — menu/read snapshot and single-item HTTP integration.
- resources/views/staff/orders/table-menu.blade.php — menu, draft/saved sections and table context.
- resources/css/staff/table-menu.css — scoped responsive layout.
- resources/js/staff/table-menu.js — memory draft, sequential writes, explicit acknowledgements, error/retry controls.
- tests/Feature/TableOrderIntegrationTest.php — seven feature tests, isolated SQLite fixtures.
- tests/JavaScript/table-menu.test.mjs — three behavioral JS tests using Node and a minimal DOM test double; no dependency installed.
- tests/manual/table-order-mysql.php — guarded owned-schema MySQL probe; verification passed after isolation correction; see resolved incident below.

## Files Modified
- app/Http/Controllers/Admin/TableDetailController.php — bounded candidate lookup and active-actor requirement.
- resources/views/admin/tables/detail.blade.php — none/one/multiple/blocked entry states and real Continue link.
- routes/staff.php — Admin+Staff role group and two scoped menu endpoints.
- app/Http/Controllers/Staff/OrderController.php, PosController.php — actor guard accepts active Admin+Staff.
- app/Http/Requests/Order/{StoreOrderRequest,StoreOrderItemRequest,StoreOrderItemToppingRequest,UpdateOrderItemQuantityRequest,UpdateOrderItemStatusRequest,UpdateOrderItemToppingQuantityRequest}.php — same actor-only change; existing input rules retained.
- app/Services/OrderService.php, InventoryService.php — actor-only guard change; existing business persistence/pricing/stock logic retained.
- resources/views/staff/orders/show.blade.php — link to table menu for pending dine_in Order.
- resources/css/app.css, resources/js/app.js — module imports.
- tests/Feature/{StaffOrderManagementTest,StaffOrderItemManagementTest,StaffOrderCancellationTest,PosOrderWorkflowTest}.php — update previously conflicting Admin restrictions to approved access or inactive-Admin rejection; preserve Guest/inactive checks; add positive Admin inventory-preparation test.
- docs/task09/task9.5-report.md — replace prior blocked survey with current implementation/evidence.

## Files Deleted
None.

## Implementation Details
### Source Survey / Existing Work Safety
Read task9.5, previous task context/flow preparation and current Order/Catalog/Inventory/route/request/controller/view/tests. Worktree contained staged and unstaged task9.1–9.4 work plus unrelated task9.9 document edits; preserved them. Current diff contains those earlier changes, not all authored by task9.5. No commit, reset, push or branch switch. No package, schema, generated-model or vendor changes.

### Entry / Routes / Identity
Admin floor → detail → one pending dine_in candidate → staff.table-order.index. Staff can use the new link on existing Order detail. Candidate lookup is bounded to two records, sufficient to detect none/one/multiple without choosing latest arbitrarily.

| Method | Path / name | Behavior |
| --- | --- | --- |
| GET | staff/tables/{table}/orders/{order}/menu / staff.table-order.index | HTML menu; Accept:application/json returns server snapshot |
| POST | staff/tables/{table}/orders/{order}/items / staff.table-order.store | Single-line save, JSON201 with saved_item_id + snapshot |

Both use existing auth:web + role:admin,staff and active actor checks. IDs are numeric, Order must belong to Table and be dine_in. Menu lookup rejects closed Order, multiple candidates, maintenance, inactive/deleted Area or deleted Table. No arbitrary client order_id/price is trusted. No new create operation from this flow; existing Order creation routes remain available under their existing business rules and expanded actor policy.

### Server Save / Concurrency
TableOrderContext is selective integration orchestration, not a replacement persistence service. Save transaction locks current actor, Area, Table, related Order and candidate set; rechecks snapshot and delegates existing OrderService::addItem. Existing model/Service determines prices, snapshots and totals. Response snapshot is read within the outer transaction. Only variant_id/quantity/note validated by StoreOrderItemRequest are forwarded. No UI insert into orders/order_items, Payment mutation, table-state sync or stock decrement.

Existing general Order endpoints retain their existing business validation; the new Table-menu entry adds the approved table/Area/multiple-candidate eligibility checks. Quantity/update/delete/topping operations for saved lines remain at existing Order detail. Top-level account policy is consistently Admin+Staff active, including the Inventory preparation actor check.

Row locks protect eligibility/recalculation but do not identify repeated requests from two tabs/devices. No global exactly-once guarantee. No automatic client retry; this limitation was explicitly approved. No simultaneous-MySQL-client concurrency acceptance has been observed.

### Catalog / Draft / Pricing
ProductVariant query uses available variants and available nondeleted Products; eager Product/Category loading, search Product name or variant SKU, actual category_id filter, twelve variants/page, stable product_id/id ordering. Category visibility follows existing nondeleted Category list without inventing saleability rules. Product::imageUrl reused; no fake images/data.

Draft uses memory only, one page/Order context. Each added line remains separate, including variant/name/note. Quantity integer1..2147483647, note max500. Draft modifications do not POST. Navigation/filter/pagination/reload triggers browser beforeunload warning if unsaved lines remain; draft is not persisted across navigation. No localStorage/customer data storage.

Draft amount uses integer BigInt cents as an estimate; saved decimal snapshots/totals come from backend. Client sends no authoritative price. Saved and draft sections remain separate. Existing topping editing is available through saved Order detail; no unsupported draft-topping batch UI introduced.

### Save / Partial Failure / Ambiguous Results
JS disables repeated submission and editing while saving; sends one line at a time. A line is removed only after JSON201 with a saved_item_id and valid snapshot. Saved list/total refresh from that response. Known validation/403/404/409/419/422 errors stop the queue and keep unacknowledged lines. Already confirmed lines are not resent. The new transport provides an explicit acknowledgement without altering existing Order HTML endpoints.

Network loss, unexpected response, timeout or server5xx sets an uncertain state: draft remains, all save/edit/add actions lock, no automatic POST retry. Read-back button can refresh Saved Items without clearing the uncertain draft; user must inspect before leaving/recreating it. This intentionally favors avoiding a duplicate over transparent retry. Reload can discard draft only after warning; no automatic save on close.

Texts are created with textContent in JS and escaped in Blade; no inserted user HTML. CSRF header, session authentication and existing request whitelist retained. Backend errors log static labels/SQLSTATE without query bindings.

### UI / Performance
Responsive product/cart grid, stacked tablet/mobile,48px action controls, labeled quantity/notes/search/category, focus rules and aria-live feedback. No JS means the page points to existing native Order forms rather than claiming drafts work. Actual browser1440/768/375px, keyboard/touch/overflow/contrast remain UNVERIFIED. Node tests are not browser rendering evidence.

Eager Catalog/Category reads, paginated variants, bounded candidate lookup; Saved Items necessarily loads current Order lines, no full Order history/Payment data. No polling. Quantitative production-volume performance was not benchmarked. No dependency installed.

## Tests / Checks Executed
- PHP8.3.33: full `artisan test --compact`.
- Final targeted `artisan test --compact --filter=TableOrderIntegration` after local formatting/UI-empty-state change.
- Node22 `node --test tests/JavaScript/table-menu.test.mjs`.
- `node --check resources/js/staff/table-menu.js`.
- `npm.cmd run build` using existing Laragon Node.
- Pint on new integration classes/test/probe; route list; git diff/check/status review.
- MySQL owned-schema probe attempts and connection audit attempt, details below.

## Test Results
| Check | Actual |
| --- | --- |
| Full PHP suite |401 PASS,2 SKIPPED,403 total,5665 assertions; no failures |
| New PHP integration suite,final rerun |7 PASS,77 assertions |
| JavaScript behavioral tests |3 PASS: partial success retention, uncertain-result no retry, double click/leave warning |
| Syntax/build |PASS |
| Route listing |New scoped GET/POST present |
| Diff check |PASS; pre-existing task9.9 CRLF notice |
| Browser acceptance |NOT RUN |
| MySQL write acceptance |PASS on fresh owned schema, with actual migrations and backend persistence |

PHP tests cover both active roles, backend-owned price, no new Order/table-state mutation, Catalog search/filter/unavailable Product, mismatched IDs, completed/multiple/maintenance/inactive cases, invalid quantity/note/variant, actor recheck, price change, partial per-item outcome, detail none/one/multiple, CSRF and safe SQL failure. Guest and inactive-account checks remain in updated existing tests. The original Admin-forbidden assertions were intentionally changed only for the newly approved actor policy; explicit active-Admin preparation test validates unchanged stock workflow.

Full suite ran before a final type import/format and explicit empty-saved-state UI text change; new PHP suite, JS tests and build reran after those changes. No unrelated fixes to make tests green. Build retains existing optional fontaine and unresolved poster warnings; no install performed.

### MySQL probe incident — resolved and cleanup verified
The first attempts failed with DomainException/UniqueConstraintViolationException. The cloned connection retained the original name, which could propagate into hydrated/saved Eloquent instances. Script now explicitly sets name=task95_probe, validates selected schema/model connections and blocks original-connection queries during testing. A subsequent bootstrap import-order issue in the manual script was corrected before the successful run; no runtime application change was needed.

After the user restarted MySQL, audit confirmed six fixture records were created in the original database at stored timestamp2026-10-20 04:07:02: users#7(probe@example.test), areas#4(Test area), cafe_tables#6(PROBE), categories#4(Test category), products#5(slug probe), product_variants#8(Large). No Order/Reservation/Payment/Inventory transaction or recipe/item used these fixtures. Original table#1 still had its old unchanged updated_at timestamp; no additional affected business record was identified by these checks.

The user explicitly approved deleting exactly those six records. Cleanup transaction verified local MySQL/database laravel, locked each record, checked IDs/names/parent IDs/status/created_at/updated_at/deleted_at, inspected all actual incoming FK references and active sessions, then deleted children before parents. Any mismatch/reference would abort. Exactly one row per named fixture table was removed. No reseeding, truncation or counter reset.

Post-cleanup read-only audit: all six approved fixtures absent; zero coffee_task95_probe_* schemas remain. Business counts unchanged by cleanup: orders7,order_items7,payments4,reservations6,inventory_transactions29. This resolves the known fixture pollution; no broad historical-data comparison or unsupported restoration claim is made.

Corrected isolated MySQL test PASS: real users/Area/Table/Catalog/Order/Items migrations in a fresh owned schema, active Admin backend create, item save through TableOrderContext→OrderService, persisted total24.70, and maintenance rejection without another saved item. The owned schema was removed in finally; original connection queries were blocked throughout that probe. Manual script syntax and Pint checks PASS. No live Order was created for verification.

## Acceptance Criteria
| Criterion | Result / evidence |
| --- | --- |
|1–3|PASS: contracts/source surveyed and explicit user overrides recorded |
|4–7|PASS automated: real menu context and Table/Order identity guards |
|8–14|PASS automated: database Catalog/variants/prices/search/category/availability; isolated MySQL persistence PASS; browser acceptance pending |
|15–20|PASS implementation + Node tests: memory draft, quantity/remove/note, separate saved state, no auto-write |
|21|PASS: new flow never creates Order |
|22–24|PASS automated: existing OrderService delegation; isolated actual-MySQL persistence verification also PASS |
|25–29|PASS automated: validated inputs, safe text, server prices/totals |
|30–32|PASS automated: errors/partial retention/no fake acknowledgement |
|33–34|PASS Node behavior: busy controls, duplicate click guard |
|35|PASS as disclosure: cross-tab/request idempotency not guaranteed; real concurrent MySQL test UNVERIFIED |
|36–40|PASS feature tests: actor/CSRF/identity/tampering |
|41|PASS within approved exception: Order business calculation unchanged; actor guard expanded as approved |
|42|PASS: Catalog source logic unchanged |
|43|PASS within approved actor expansion: Inventory quantities/transitions unchanged; Admin guard aligned and tested |
|44–47|PASS: no Payment/table status/schema/migration edits by this flow |
|48|UNVERIFIED browser; responsive CSS implemented |
|49|PASS markup; actual browser keyboard/contrast UNVERIFIED |
|50|PASS local full regression suite |
|51–52|PASS: no commit/push/merge or false test claims |
|53|PASS: real single-item backend exists; no batch success emulation |
|54|PASS: this report |
|55|PARTIAL: core automated behavior passed; MySQL probe/audit complete; browser acceptance incomplete |
|56|PASS: no Checkout/Payment/Receipt/Invoice completion claim |

## Risks
- Known MySQL probe fixture pollution was audited and cleaned with explicit user approval. Retain the corrected guards for future probes.
- No idempotency across tabs/devices or arbitrary HTTP retries; accepted limitation.
- Draft is memory-only and may be discarded after leave confirmation; uncertain draft remains locked until user checks it.
- Browser responsive/a11y and real MySQL concurrency acceptance pending.
- New Continue flow is implemented, new floor-based Order creation remains deliberately deferred for Reservation policy.

## Blockers
None for the implemented Continue-only backend. Browser acceptance remains unverified and prevents reporting full SUCCESS; MySQL audit and corrected probe are complete.

## Assumptions
Only the three explicit user-approved decisions govern new behavior. No new Reservation/Payment/stock or Category saleability rule inferred. Existing saved-item edit operations retain their own backend rules.

## Out-of-Scope Findings
Earlier task9.1–9.4 work and task9.9 document modifications remain in worktree and were not reset. Existing build warnings persist. Cross-tab exactly-once and floor Create/Reservation integration require separate backend policy work.

## Unverified Items
Actual concurrent requests; real browser1440/768/375px, keyboard/touch and overflow. Two skipped PHP tests were not executed. Remote CI PHP8.4/8.5 not run locally.

## Engineer Review Required
Review actor guard expansion (including Inventory actor-only change), TableOrderContext locks and queue acknowledgement/error semantics. Review completed MySQL audit/cleanup and guarded probe evidence, then perform browser acceptance. Review UI as Admin: /admin/tables?view=floor → Xem chi tiết → Tiếp tục gọi món (only exactly one eligible pending dine_in Order). Staff can open existing Order detail → Gọi món tại bàn. No hardcoded Order IDs. Audit/MySQL checks are resolved; do not accept the entire task until browser checks are completed.

## Recommended Next Task
Complete browser acceptance for this task; do not implement task9.6 automatically. Payment readiness is unchanged.
