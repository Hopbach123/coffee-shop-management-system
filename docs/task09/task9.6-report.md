# Task9.6 — Checkout & Invoice Printing Integration

## Task Status
PARTIAL — read-only checkout summary and approved proforma printing implemented and tested. Payment Action, confirmed-payment receipt and electronic invoice integration are BLOCKED because the corresponding backend contracts do not exist. Actual browser/physical printing acceptance remains UNVERIFIED.

## Task ID
Task9.6 / Area & Table POS Management — TASK06.

## Summary
Added a saved-Order summary with actual Table/Area context, exact stored line amounts, discount/tax/Order total and scoped Payment reference records. Added a separately authorized print preview labeled Phiếu tạm tính, explicitly not a payment receipt or electronic invoice. Reading/refreshing/printing never creates a Payment, completes an Order or releases a Table. No Payment button or fake method selection.

## Approval
The user explicitly approved: print Phiếu tạm tính, not a paid receipt/electronic invoice; use saved Order items/amounts and existing VNĐ presentation; browser dialog only; no data writes. This resolves the print/currency presentation decision. It does not authorize inventing a Payment backend or claiming a transaction result.

## Files Created
- app/Http/Controllers/Staff/TableCheckoutController.php — read-only summary and estimate preview HTTP coordination.
- resources/views/staff/orders/checkout-summary.blade.php — saved summary, Payment reference page and missing-backend state.
- resources/views/staff/orders/estimate-print.blade.php — standalone estimate preview without navigation/customer data/Payment history.
- resources/views/staff/orders/components/estimate-items.blade.php — shared saved-line/topping details and stored Order amounts.
- resources/css/staff/checkout-summary.css — scoped screen/print styling.
- resources/js/staff/estimate-print.js — opens browser print dialog with honest error/status feedback.
- tests/Feature/TableCheckoutIntegrationTest.php — seven tests.
- tests/JavaScript/estimate-print.test.mjs — three behavioral tests.
- docs/task09/task9.6-report.md — this report.

## Files Modified
- routes/staff.php — two GET routes inside the existing Admin+Staff group; no middleware/policy change in this task.
- resources/views/staff/orders/table-menu.blade.php — Xem tạm tính link; existing unsaved-draft leave warning still applies.
- resources/views/staff/orders/show.blade.php — Xem tạm tính link for dine_in Orders.
- resources/css/app.css — stylesheet import.
- resources/js/app.js — print enhancement import/initialization.

## Files Deleted
None.

## Implementation Details
### Source Survey / Existing Work
Read task9.6, preceding task implementation/context, Payment/Order/OrderItem models, Payment migration, current routes/services/controllers/views and RevenueStatisticsService. No Payment/Checkout/Receipt/Invoice controller/service/route/template was found. Only Payment schema/model and promotion hooks exist. PosController::checkout creates an Order with items; it is not a Payment-confirmation operation.

Existing task9.1–9.5 staged/unstaged work and unrelated task9.9 document changes were preserved. Git diff includes previous work, so only the file changes above belong to task9.6. Authority documents and prior architecture context were retained from this session. No dependency, schema, generated Base model, vendor, node_modules, OrderService, InventoryService, Payment or Auth logic changed by this task. No commit/push/merge.

### Checkout Architecture / Identity / Authorization
GET summary and GET print preview use auth:web + existing role:admin,staff, plus active-actor check. Numeric Table ID and Order ID are independently resolved; Order must be dine_in and match the specified table_id. Unknown/mismatched IDs404; inactive actor403; Guest redirects to login. No arbitrary payment_id/receipt_id, client amount/status/paid query parameter is accepted as an authority.

The read-only summary allows pending/completed/cancelled Orders and does not assert operational checkout eligibility. Cancelled Orders carry an explicit reference-only warning. It uses the normal nondeleted Table lookup and the existing historical Area relationship; deleted/missing Area name has a factual fallback. Existing task9.5 TableOrderContext is intentionally not used as an operational eligibility resolver: its pending-only menu restrictions are not the same as permission to read an old Order summary. No new mutation is introduced.

| Method | Route name / path | Contract |
| --- | --- | --- |
| GET/HEAD | staff.table-checkout.show / staff/tables/{table}/orders/{order}/checkout | Saved Order summary and paginated Payment references |
| GET/HEAD | staff.table-checkout.print / staff/tables/{table}/orders/{order}/estimate | Proforma preview, browser print enhancement |

Other methods on the new summary route receive405. No new POST/Payment/receipt route. CSRF is N/A for these read-only actions; existing Order write protection remains unchanged.

### Order Summary / Pricing
- Uses only persisted OrderItems and topping snapshots, not session pos_cart or JS draft.
- Displays product_name, variant_name, quantity, unit_price, topping_amount, subtotal, note and status; toppings show stored name/quantity/unit price.
- Order subtotal/discount_amount/tax_amount/total_amount are displayed as backend decimal strings, retaining two decimals. No float arithmetic, discount/tax recalculation, client total or new monetary aggregate.
- VNĐ presentation explicitly approved; no currency column/config was invented. No new fee field or fabricated zero fee.
- Reading again refreshes data. Summary is a point-in-time read, not a frozen transaction receipt. Separate eager reads may observe concurrent edits; no new snapshot/idempotency guarantee or Payment action is offered on that basis.

### Payment Contract / Financial Safety
Migration verifies payment_method cash/bank_transfer and status pending/paid/failed/refunded. These enum values alone do not establish runnable payment methods/gateway capability. UI does not offer method selection, QR, transfer account, Payment submit or automatic retry.

Existing scoped Payment rows are loaded through Order::payments, ten per page. Selected fields only: id,order_id,payment_code,payment_method,amount,status,paid_at. transaction_code and created_by are not selected/rendered. Raw stored states and dates are labeled as reference data, not confirmed transaction outcome. No paid_at fallback to current time. Another Order's Payment cannot be selected through a query parameter.

Authoritative paid/outstanding fields and an Order-level Payment-result resolver do not exist. UI shows Chưa xác định rather than deriving paid/due by summing paid rows or subtracting refunds; this also avoids falsely resolving duplicate paid records. Empty Payment history is not called proof of unpaid/amount due. Existing RevenueStatisticsService has an aggregate KPI/duplicate guard, but it is not an authoritative checkout/settlement API and is not reused for that purpose.

Payment Integration status: MISSING/BLOCKED. Need the Payment owner to provide an authorized create/result contract, server-derived amount/currency, idempotency and stale-Order checks, pending/failed/unknown resolution, supported method configuration, refund/partial-payment semantics if supported, promotion hooks and completion policy. No API was fabricated or message sent externally.

### Receipt / Printing
Paid receipt/backend and electronic invoice issuance are MISSING/BLOCKED. Only the explicitly approved proforma is implemented. Preview uses the real Order code/ID, table/Area and saved money/items; no receipt number, tax ID, legal invoice reference or transaction success is invented. It omits Payment records entirely and prominently states: Không phải biên nhận thanh toán hoặc hóa đơn điện tử. Phiếu này không chứng minh đã thanh toán.

Summary offers preview only when saved items exist. Direct empty-Order preview displays the empty state and no print button. Cancelled Order preview is clearly reference-only. Preview can be opened again to read current data; this is not reissuing/reprinting a paid receipt.

Browser printing is triggered only by an explicit click, never automatically on page load. Button disables around window.print, reports only that the dialog was requested and does not claim physical output or Payment completion. Failures show an instruction to use browser Print; no rollback/mutation. No printer service/package/network printer integration.

Scoped print CSS hides controls, removes screen background, uses fixed table layout, repeated table header and avoids breaking rows/totals. Standalone preview contains no internal navigation to hide. No global @page rule or unrelated layout redesign. Physical paper, driver, A4/thermal behavior and actual browser preview are not verified.

### UI States / Performance / Security
Saved totals, missing Payment action, empty history, empty items, cancelled/completed Order, errors and retry/read navigation are implemented. Loading state is N/A: synchronous server navigation, no async Payment request. Query failures render unavailable rather than zero money and log static text/SQLSTATE only. No sensitive exception/bindings are exposed. Blade escaping covers saved notes/product/Payment reference data; JS only updates fixed text.

Order items/toppings and Area eager loaded; no per-item queries. Payment list paginated10. Print never queries Payment history. All saved lines must be read for the proforma; production-volume latency/physical output not benchmarked. Authorization is enforced at both new GET endpoints, not merely by hiding links.

## Tests / Checks Executed
- PHP8.3.33: `artisan test --compact --filter=TableCheckoutIntegration`.
- Full `artisan test --compact` after first six new tests.
- Node22: `node --test tests/JavaScript/estimate-print.test.mjs tests/JavaScript/table-menu.test.mjs`.
- `npm.cmd run build` using existing Laragon Node22.
- Read-only MySQL controller/render reconciliation for live dine_in Orders; query callback rejected INSERT/UPDATE/DELETE/DDL.
- Route listing, PHP syntax, Pint on new controller/test, git diff/check/status review.

## Test Results
| ID | Scenario | Result |
| --- | --- | --- |
| CO-01 | Admin/Staff saved money/context, draft exclusion, client amount/status ignored, escaping | PASS |
| CO-02 | Paid/pending/failed/refunded reference rows, no aggregates/secret reference/false receipt | PASS |
| CO-03 | Print/read no writes or Order/Table state changes; no mutation endpoint | PASS |
| CO-04 | Wrong table/Order/type/actor/Guest denied on summary/print | PASS |
| CO-05 | Cancelled/completed/empty Order handled without false paid status | PASS |
| CO-06 | SQL failure unavailable state, no fake money/SQL leak | PASS |
| CO-07 | Payment pagination10 and another Order's Payment excluded even with injected payment_id | PASS |
| JS-01 | Opens provided dialog once, disables during invocation, no physical success claim | PASS |
| JS-02 | Print error feedback, no transaction retry | PASS |
| JS-03 | No print controls means no side effects on other pages | PASS |
| REG-01 | Full PHP suite |407 PASS,2 SKIPPED,409 total,5768 assertions |
| REG-02 | Final new test selection after seventh pagination test and stricter fixture guard |7 PASS,120 assertions |
| REG-03 | JS new+task9.5 suite |6 PASS |
| MYSQL-01 | Four live read-only renders: summary+print for two Orders; exact Order money/items | PASS; no writes |
| BUILD-01 | Asset build |PASS; existing optional fontaine/poster warnings |
| CHECK-01 | Syntax/Pint/route list/diff check |PASS; pre-existing task9.5/task9.9 CRLF notices |
| UI-01 | Browser1440/768/375px, print dialog and physical printer |NOT RUN / UNVERIFIED |
| PAY-01 | Payment create/success/failed/pending/unknown/idempotency/concurrent checkout |BLOCKED: backend absent |
| RECEIPT-01 | Official paid receipt/electronic invoice/reprint |BLOCKED: backend absent |

New PHP fixtures enforce SQLite and :memory: before writes. No live Payment was created for tests. MySQL probe reads only; no new fixture cleanup/migration was needed in this task. Initial MySQL print-marker check failed due Unicode text passed through PowerShell stdin; reran with an ASCII markup marker. Exact Vietnamese disclaimer/XSS is tested directly by PHP feature tests reading UTF8 files. This was a probe assertion/encoding correction, not a change to the disclaimer.

Full suite preceded the seventh test/stricter fixture guard; only tests changed afterward and final targeted selection passed. Build/JS checks reflect final runtime source. No tests for real transaction success were fabricated. Full-suite skipped tests remain unexecuted.

## Acceptance Criteria
| Criterion | Result / evidence |
| --- | --- |
|1|PASS: previous source/context reviewed |
|2|PASS as survey: Payment schema/model present, operational API missing |
|3|PASS as disclosure: paid Receipt/eInvoice backend BLOCKED |
|4–7|PASS feature tests: read-only checkout UI/context/identity |
|8–11|PASS feature/MySQL: saved snapshots/backend totals only, existing discounts/tax |
|12|BLOCKED: schema methods do not provide operational method capability |
|13|PASS: no fake method/QR/gateway |
|14–15|BLOCKED: no Payment backend/action |
|16–18|PASS: no inserts or Order/Payment state updates |
|19|PASS boundary: no success claim without verified backend result |
|20|PARTIAL: raw stored states shown; actual transaction result workflow BLOCKED |
|21|N/A synchronous read; print button disabled during dialog call; Payment states BLOCKED |
|22|PASS: no auto Payment request/retry |
|23|PASS as blocker disclosure: Payment idempotency not supplied, no action enabled |
|24–26|PASS: approved proforma preview, explicit document distinction, no eInvoice claim |
|27|PASS implementation/JS tests: explicit browser print; actual dialog/printer UNVERIFIED |
|28|UNVERIFIED visual print layout; print CSS statically reviewed |
|29–32|PASS: readonly tests preserve Payment/Order/Table and enforce roles |
|33|N/A: no new write request |
|34–35|PASS feature tests: bound Table/Order and scoped Payment list; no receipt/payment ID resolver to bypass |
|36–37|PASS: client money/status ignored, transaction_code/creator omitted |
|38–40|UNVERIFIED browser: responsive CSS implemented |
|41|PASS read states: unavailable/empty/cancelled/completed; Payment conflict workflow BLOCKED |
|42–48|PASS: no Order/Payment/Catalog/Auth/schema/migration/release changes |
|49|PASS selected full regression suite, with two skipped tests disclosed |
|50–52|PASS: safe fixtures, no commit/push/merge, exact evidence recorded |
|53–54|PASS as disclosure: Payment/Receipt dependencies BLOCKED |
|55|PASS: this report |
|56|PARTIAL: read/proforma verified; operational Payment integration and browser/physical print acceptance incomplete |
|57–59|PASS: no fake transaction success, no task07 sync or entire POS completion claim |

## Risks
- Missing Payment API prevents safe settlement and official paid-receipt issuance. Raw reference rows must not be treated as complete settlement proof.
- Independent reads are not an immutable receipt snapshot; Order may change. Reload to see current values; no action charges money from this view.
- Browser/paper/driver/physical-print checks outstanding.
- Existing build warnings persist; no dependency installed.

## Blockers
Payment create/result/idempotency contract, server paid/outstanding resolver, paid-receipt/eInvoice backend and reprint policy are missing. These are outside permitted implementation scope. Full task cannot be SUCCESS.

## Assumptions
Only user-approved proforma and VNĐ presentation added. Reuse existing Admin/Staff read rights; no new permission. No assumption about cash/transfer availability, refunds, partial settlement, Order completion or Table release.

## Out-of-Scope Findings
Payment enum/data/promotional hooks are not a Payment processor. Existing POS checkout name refers to Order creation. Prior task9.5 MySQL probe incident was resolved in that task; no such write probe in task9.6. Pre-existing worktree changes retained.

## Unverified Items
Actual browser responsive/a11y/overflow/print preview, physical printer output, Payment transaction/concurrency/receipt success. Production-size performance and remote CI runtime versions not measured. Two skipped tests not executed.

## Engineer Review Required
Review read-only boundaries, exact decimal display, reference-only Payment wording and printed disclaimer. Test /staff/orders/{realOrderId} → Xem tạm tính → Xem / in phiếu tạm tính. Alternatively from task9.5 menu use Xem tạm tính; no hardcoded IDs. Verify widths1440/768/375px and chosen paper/driver. Request authorized Payment/receipt contracts from the owner before enabling settlement or paid-document UI. No external message was sent.

## Recommended Next Task
Review/accept read-only/proforma portion and resolve Payment contracts. Do not implement task9.7 automatically.

### Next Task Handoff
No Payment event/completion/release signal was introduced. Task07 must not treat printing or viewing summary as release conditions. Order status and Table status stay persisted values read from backend; once an actual Payment lifecycle exists, owner must define recheck/completion/release and refresh policy. No fake transition for task07.
