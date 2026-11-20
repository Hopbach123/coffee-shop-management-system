# Resolve dev × Sales/Finance integration conflicts

## Task Status
SUCCESS for conflict resolution and verified application integration. Browser visual/responsive acceptance remains unverified.

## Task ID
MERGE-DEV-SALES-FINANCE-20261121

## Summary
Resolved the 27 unmerged paths while merging `dev` commit `8fa8fd2b` into `feature/order-requests-scheduled-pickup` at `a8fc334d`. Kept the existing Admin V5, fixed navigation and scheduled pickup alongside incoming delivery, reviewed prepaid POS, table operations and Finance contracts. No reset, stash, dependency installation, commit or push.

## Files Created
- `tests/Feature/SalesReceiptWorkflowTest.php`: preserves the previous branch's receipt regression suite; incoming `PaymentWorkflowTest` is also retained.
- This report.
- Ignored evidence/helpers under `storage/logs/merge-*`, `verify-merge-*`, `resolve-merge.py`, `resolve-finance.py`, `start-merged-demo.py`. The SQL backup is local and ignored; do not publish it.

## Files Modified
Original conflict paths:
- Controllers: Public `CartController`, `MenuController`; Staff `OrderController`, `PosController`.
- `app/Http/Requests/Payment/RecordPaymentRequest.php`, `app/Models/Order.php`, `app/Services/OrderService.php`, `app/Services/PaymentService.php`.
- `database-design.md`, `routes/staff.php`, `routes/web.php`.
- `resources/css/internal/sneat.css`, `resources/js/app.js`.
- Views: `admin/areas/index`, `admin/dashboard/index`, `admin/tables/index`, `components/public/navbar`, `layouts/internal`, `public/cart/checkout`, `public/cart/index`, `public/menu/show`, `staff/home`, `staff/orders/index`, `staff/orders/show` (all Blade).
- Tests: `CustomerPickupFlowTest`, `PaymentWorkflowTest`, `PosOrderWorkflowTest`.

Additional integration fixes:
- Staff `PaymentController`: one payment endpoint dispatches keyed Sales receipts and incoming channel commands without duplicate routes.
- Staff `OnlineRequestController`: selects the contact phone rendered by the populated list.
- Receipt migrations dated 17/11 and 18/11: handle shared columns in either deployment order and preserve shared columns on downgrade; populated operation history blocks destructive downgrade.
- `resources/views/public/cart/pickup-index.blade.php`, `_pickup-form.blade.php`, `confirmation.blade.php`: separate pickup/delivery carts, preserve invalid-line recovery, topping/note choices and avoid an invalid reference-as-token tracking link.
- Tests: `AuthenticationTest`, `PublicMenuTest`, `OnlineOrderWorkflowTest`, `StaffOrderItemManagementTest`, `SalesFinanceMySqlTest`: integrated dashboard fixtures, nonempty pickup list regression, merged payment fixture, paid handover lifecycle and Finance-first migration coverage.

Incoming staged additions/changes were already part of the merge. They were preserved; this report does not claim their original implementation as new work in this task. Two empty command-redirection artifacts created during this task were verified and removed; neither was staged.

## Files Deleted
None.

## Implementation Details
- Keep `/admin` as canonical overview and the existing Dashboard compatibility behavior. Retain the source-configured 12-item Admin sidebar and separate Staff workspace; add the incoming web-delivery destination to Staff navigation.
- Preserve new POS controller/view/request/service as a matching set (server quote, checkout fingerprint, receipt and preparation transaction). Keep the existing `createPosOnce` service contract for previous callers.
- Keep delivery `/cart` independent from pickup `/pickup/cart`; existing pickup POST/checkout and capability tracking remain connected. Preserve both `onlineOrderRequest` and `customerRequest` relations.
- Payment compatibility boundary accepts existing Order/array settlement callers and incoming integer/DTO callers. Keyed counter/pickup receipts remain settlement-only; incoming delivery and prepaid POS keep their channel orchestration. Both writers protect the same bank reference namespace.
- Counter/pickup handover requires full settlement. COD served states and receipt remain one delivery-confirmation transaction; failure rolls everything back. Paid alone does not complete an unserved Order. Completion requires actual full settlement and served noncancelled items; repeat explicit completion is safe.
- Keep paid mutation guards, table menu/totals links, item quantity/removal and topping editing. Web delivery cannot use the counter receipt form or direct ready-to-served action.
- Two branches added `received_amount`/`change_amount`: both migrations now add only missing shared columns. Rolling back one extension preserves fields owned by the other. This compatibility change does not replay already applied DDL; it permits previously unapplied migrations on either baseline.
- Fixed a real HTTP 500 in the populated pickup inbox: Blade used `contact_phone` but the list projection omitted it.

## Tests / Checks Executed
- PHP syntax: 202 application/route/migration files, no syntax errors.
- Full default PHPUnit suite: **601 tests; 581 passed, 20 skipped; 0 failures/errors**, 7,986 assertions. SQLite `:memory:` is enforced by test configuration. Opt-in suites are not counted as passed.
- After the final inbox/menu compatibility edits: **52 targeted public/cart/pickup tests passed**, 622 assertions, including the nonempty contact-phone list assertion.
- Real isolated MySQL `SalesFinanceMySqlTest`: **9 passed**, 219 assertions, no skips/failures/errors. Includes concurrent cash/bank/replay, bank reference reuse, cancel/serve/complete/promotion races, last-stock race, legacy upgrade, all pickup channels, repeatable-read settlement and reservation/completion locking. Added Finance-first → Sales migration order, preserving receipt values.
- JavaScript: **11/11 passed**, covering catalog state, table menu, estimate and table refresh behavior.
- `npm run build`: passed. Existing unresolved image warnings retained; no dependencies installed.
- Routes: 166 registered; no duplicate nonempty route names. Removed the duplicate POST payment route.
- Conflict-marker scan and `git diff --check`: clean (Git may print CRLF normalization warnings).
- Live HTTP: all 12 Admin navigation destinations return 200 with one active item. `/staff`, `/staff/orders`, nine seeded order details, online pickup inbox, delivery inbox, brewing, tables, reservations, public menu, delivery cart and pickup cart return 200. Finance and live floor plan also return 200. Served CSS matches the current Vite manifest.

## Test Results
Passed within the coverage above. Browser rendering, pointer/keyboard and responsive visual QA were not performed; HTTP verification is not a visual acceptance claim.

## Acceptance Criteria
- PASS: all 27 conflict paths reconciled; no conflict markers remain.
- PASS: both branches' contracts and regression suites retained, with documented channel-specific behavior.
- PASS: build, syntax, default tests, targeted regressions and isolated MySQL integration checks.
- PASS: app serves the merged F-drive source at `http://127.0.0.1:8000`.
- UNVERIFIED: manual browser visual acceptance.

## Risks
- The default suite skips 20 opt-in cases. The 9 real MySQL tests above supplement it but do not represent every possible concurrent scenario.
- Existing migrations were made compatible with the other branch's shared receipt fields; review both deployment orders before team rollout. Existing duplicate paid receipts/bank references correctly block the Sales migration for manual review.
- Finance's older unkeyed served-payment contract and keyed settlement-only contract coexist for compatibility; callers must use the intended channel. Do not remove either without coordinating Sales/Finance.
- Browser visual QA remains outstanding. No claim of redesigned-screen acceptance.

## Blockers
None for conflict resolution. Browser visual verification remains unavailable in this session.

## Assumptions
Incoming approved Customer/POS and Finance extensions in current requirements coexist with the simple pickup channel; delivery does not replace pickup.

## Out-of-Scope Findings
The primary Laragon database and `.env` were not changed. No new shipper/gateway/refund engine was implemented. Build warnings for referenced images predate this integration.

## Unverified Items
Production deployment, primary Laragon upgrade, every opt-in MySQL suite, and visual/responsive browser acceptance.

## Engineer Review Required
Review resolved/staged changes and this report, especially payment adapters and receipt migration compatibility. The merge remains ready for the engineer's merge commit; no commit/push was made by this task.

## Recommended Next Task
Review the running demo at normal zoom, then finalize the merge commit when satisfied. Do not automatically implement another feature.

## Local Demo / Data Safety
Only the pre-existing owned isolated MySQL demo was upgraded after verifying its temporary datadir and schema name. A new local SQL backup was created before migration and not overwritten. Additive migrations succeeded; `.env`, original sessions/source history and primary Laragon data were preserved. Demo app: `http://127.0.0.1:8000`.
