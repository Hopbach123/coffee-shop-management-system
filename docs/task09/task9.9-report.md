## Task Status
PARTIAL — đã thực hiện kiểm thử/tổng hợp nghiệm thu trong phạm vi hỗ trợ. **Final acceptance: NOT READY FOR REVIEW** theo hardRule40/AC56: còn replay item, release/Reservation risk và Payment/Receipt blockers ngăn POS end-to-end. Không merge/deploy, không coi suite xanh là nghiệm thu toàn module.

## Task ID
task9.9 — Testing & Final Integration, Area & Table POS Management

## Summary
Thêm5 test/diagnostic tích hợp vào fixture hiện có: cross-page flow, CSRF missing/wrong/valid với middleware thật, legacy Order create lặp, performance1→12 rows, và chẩn đoán replay/release. Chạy toàn bộ regression, Node/build/routes, MySQL cô lập/SELECT-only và Edge fixture responsive/print. Không sửa application source, backend, auth hay database schema. Test matrix: **19 PASS,2 FAIL,4 BLOCKED,1 NOT RUN** trên26 nhóm; hai FAIL là finding nghiệp vụ đã tái hiện, không phải PHPUnit assertion failure.

## Files Created
- docs/task09/task9.9-test-matrix.md — inventory, contracts, mini plan trước code và26 nhóm kiểm thử cuối.
- docs/task09/task9.9-bugs.md — repro, severity, owner, scope/fix/retest của các finding.
- docs/task09/task9.9-report.md — final integration/readiness report này.

## Files Modified
- tests/Feature/TableOrderIntegrationTest.php — thêm5 method kiểm thử/diagnostic, import DB; tái sử dụng fixture đang có. Evidence JSON chỉ ghi vào storage/logs nếu TASK99_EVIDENCE=1.

Worktree trước task9.9 chứa task9.1–9.8 và engineer đã sửa docs/task09/task9.9.md. Giữ nguyên toàn bộ; không ghi đè prompt. Các controller/services/routes/models/views/CSS/JS trong toàn bộ Git diff là pre-existing work, không phải thay đổi task9.9. Bản baseline test trước lượt này: storage/app/task99-before/TableOrderIntegrationTest.php (ignored).

## Files Deleted
None

## Implementation Details
### A. Project overview / inventory
Coffee Shop Management System, Laravel13.25/Blade/Eloquent + MySQL, branch feature/area-table-pos-ui. Read authority docs và source trong chuỗi task hiện tại; task9.9 đối chiếu prompt, reports, migrations/relationships đã khảo sát, controllers/services, request validation, middleware/routes và tests thực tế. Không thêm framework/package.

Admin Area/Floor/detail có middleware web,auth:web,role:admin. Active Admin/Staff dùng table-order GET/POST và table-checkout GET. Staff không được vào Admin Floor theo policy hiện tại: sơ đồ Staff Login→Admin Area của prompt không phải authorization contract được mở rộng tự động. Authentication regression riêng có Login; cross-page test mới dùng actingAs nên không được gọi là một browser login/payment transaction end-to-end.

### B. Task completion matrix
| Task | Verified now | Remaining / status |
| --- | --- | --- |
| 01 Architecture survey | Source/contracts/owner inventory | Report lịch sử không chứng minh API proposal đã hiện thực; current routes/source được đối chiếu lại |
| 02 Area UI | CRUD/search/count/auth regression | Responsive fixture, chưa real device |
| 03 Floor | Filters/status/count/empty/error/query bounds | Không phải availability lock |
| 04 Detail | ID/404/auth/candidate0/1/multiple | No-create từ sơ đồ vẫn policy task5 |
| 05 Order UI | Admin/Staff continue, Catalog, save/read/server total | Replay/multi-client idempotency HIGH-RISK; create-from-floor chưa có |
| 06 Checkout/print | Saved summary/reference records/proforma GET | Payment/official receipt BLOCKED |
| 07 Status | Chung CASE, read-only, Reservation/current/future, counts | Payment/release authority BLOCKED; release diagnostic FAIL |
| 08 UI/UX |32 viewport combinations, native cart, Node/print | Shared sidebar a11y, hardware/browser coverage còn hạn chế |
| 09 Final integration | Tests/matrix/bugs/report hoàn thành phần kiểm chứng | Full acceptance PARTIAL / NOT READY |

### C. Workflow / actual API contracts
1. Admin GET /admin/areas → /admin/tables?view=floor&area_id={id} → /admin/tables/{id}/detail. HTTP tests kiểm tra IDs/counts, không chỉ link text.
2. Candidate pending+dine_in đúng table_id; duy nhất1 Order. GET /staff/tables/{table}/orders/{order}/menu đọc Catalog; POST .../items qua FormRequest/TableOrderContext/OrderService trả201 + saved_item_id + snapshot. User-supplied unit_price/total/status/table_id không ghi theo payload.
3. Cross-page fixture lưu quantity3 ×12.35 =37.05, note<script> được lưu text nhưng render escaped; GET JSON đọc lại đúng1 item. Không thêm Order/Payment do tiếp tục hoặc đọc.
4. GET .../checkout/estimate dùng cùng đúng Order/Table và server money; proforma rõ nhãn. Query log chứng minh các lượt summary/print/Floor/detail/Area không INSERT/UPDATE/DELETE; attributes Order/Table/items không đổi, payments0.
5. Refresh ba trang đều occupied khi một pending dine_in; không tự giải phóng. Đây là supported HTTP integration, không bao gồm payment success.
6. Tạo Order mới kiểm tra qua POST /staff/orders hiện có: bàn available→một pending Order/occupied; request thứ hai bị422, không tạo Order thứ hai. Không thêm tạo đơn qua GET hoặc từ Floor.

### D. Functional evidence / commands
| ID / suite | Actual result | Evidence |
| --- | --- | --- |
| F01–F04/S03 | Cross-page IDs, persisted items37.05, ignored client money/state, escaped notes; all read-only checks PASS | test_final_cross_page_flow_persists_then_reads_same_order_without_payment_or_release |
| S02 | No token419, wrong token419, valid X-CSRF-TOKEN201; rejected calls leave items0, valid call items1 | test_final_csrf_missing_wrong_and_valid_token_with_actual_web_middleware |
| D01 | Legacy create first redirect/persist, repeat422, one active Order | test_final_existing_create_endpoint_rejects_repeated_create_for_occupied_table |
| Q01 | Fixed query count for1 and12 rows, durations recorded | test_final_read_performance_remains_bounded_for_one_and_twelve_rows |
| D02/D03 | Diagnostic observed replay2 rows; cancel persisted available with live Reservation1/display reserved | test_final_diagnostics_capture_replay_and_reservation_release_without_accepting_them; task99-diagnostics.json |
| TableOrderIntegrationTest |12 passed,192 assertions | storage/logs/task99-target.txt; initial new-test missing DB import fixed before final run |
| New5 methods after measurement refinement |5 passed,115 assertions | storage/logs/task99-final-target.txt |
| Full PHP suite |418 total:416 passed,2 skipped,5,938 assertions;54.737s | php artisan test --compact; storage/logs/task99-full.txt |
| Node suite |11 passed,0 failed | node --test tests/JavaScript/*.test.mjs; task99-node.txt |
| Vite build |PASS | npm run build; task99-build.txt |
| Routes |Actual middleware/method/path contracts present | php artisan route:list --json; task99-routes.json |
| MySQL isolated |Real migrations, Admin create, saved1 item quantity2/server24.70, maintenance409; owned test schema removed | php tests/manual/table-order-mysql.php; observed tool output |
| MySQL SELECT-only |5 tables/3 areas, six display buckets reconcile | php tests/manual/table-status-read.php; no DB write |
| Browser |32 viewport/page checks +3 interaction groups,0 runtime JS exception | render-pos-ui.php + pos-ui-browser.mjs; copied artifacts storage/app/task99-ui |
| Syntax/style/Git |Pint ordered import fixed; PHP lint/Node syntax/diff check pass | Commands run on changed/reused test harness files |

Feature database is SQLite :memory: from phpunit.xml; no .env.testing exists. Assert driver/database before fixture writes. CSRF test only switches app env tolocal to disable Laravel's test shortcut, retains isolated SQLite/session array, restores testing in finally. This tests token validation branch without Sec-Fetch-Site same-origin shortcut; auth/middleware are not bypassed.

Two skipped PHP methods are dedicated MySQL tests: DashboardRevenueMySqlTest requires coffee_dashboard_test_* via DASHBOARD_TEST_MYSQL_DATABASE, ReservationConcurrencyTest requires coffee_reservation_test_* via RESERVATION_TEST_MYSQL_DATABASE. Neither is counted PASS or silently configured to live database.

### E. Security review
- Authentication/role/active status: existing RoleAuthorization, User integration, menu/checkout/detail tests run in full suite. Guest JSON/HTML boundaries preserved; Admin-only Floor and shared Admin/Staff menu respected.
- CSRF: real token missing/wrong/valid verified on POST; read-only methods and405 checks covered by existing checkout tests.
- IDOR: wrong table/order association, invalid IDs, takeaway association, closed Order and wrong order's Payment history covered. No official Payment/Receipt object routes exist to claim separate IDOR acceptance.
- XSS: Area/Table names and menu/Order notes/proforma data escaped by Blade/textContent; regression and new<script> note test run. No raw HTML added.
- Validation: quantity0/negative/fraction/overflow, note501, invalid/unavailable variant, Area inactive, price change rechecked at save in existing tests; new flow confirms server-owned12.35 despite client0.01/status paid/table99999.
- Financial: no Payment write or fake success from query parameters; existing paid/pending/failed/refunded records remain reference-only. Processing/idempotency/settlement cannot be accepted without backend.

### F. Data integrity / concurrency
SQLite verifies real controller/request/service persistence and sequential state changes, not MySQL row-lock semantics. MySQL probe verifies actual migration/data types and one guarded create/save/maintenance rejection in a unique new schema. Probe checks connection name/selected schema and every model connection; original Laravel connection blocked during fixtures; cleanup only uniquely named schema created by this invocation. No test fixtures written into original database.

Repeated sequential Order create rejected. Frontend double-click/uncertain behavior PASS in Node/Edge; **two identical independent item POSTs each produce201/new row**, no accepted cross-client request identity. This diagnostic does not claim intentional two Add actions should merge. Full replay safety D02 FAIL until owner defines idempotency.

Stale closed Order/maintenance/inactive Area validated on write, plus multiple-candidate conflict tests. No simultaneous MySQL multi-process Order/item race executed (NOT RUN); simultaneous Payment checkout BLOCKED. Reader uses common observedAt, but separate SELECTs/pages are not a transactionally frozen snapshot. No claim of linearizability or realtime.

Cancellation diagnostic: pending Order/all pending items + current confirmed Reservation → cancelled Order, persisted table available, live Reservation kept, display reserved. Display protects presentation, does not make backend release safe. D03 FAIL; owner must settle/implement release/cleanup/Reservation policy. No backend fix under task9.9 scope.

### G. Performance report
Dataset: one Area/one Product/one pending Order; samples1 vs12 tables/variants/saved items,0 payments; SQLite memory, Laravel test client, withoutVite. Single request per route per size, no load test or warmup distribution, timings are observations not SLA.

| Route/read | Queries1→12 | Exact duplicate SQL+bindings | HTTP ms1→12 | DB ms1→12 |
| --- | --- | --- | --- | --- |
| Areas |3→3 |0→0 |18.737→16.558 |4.46→3.79 |
| Floor |5→5 |0→0 |16.027→27.203 |1.69→2.23 |
| Detail |3→3 |0→0 |11.853→10.147 |0.69→0.53 |
| Menu |10→10 |0→0 |14.854→19.478 |0.47→0.72 |
| Summary |6→6 |0→0 |10.374→11.216 |0.31→0.41 |
| Proforma |5→5 |0→0 |4.999→5.783 |0.31→0.40 |

No per-row query growth on these samples; aggregate subqueries can still cost more with large data. Do not infer production latency/capacity/index quality. Raw evidence storage/logs/task99-performance.json; no SQL bindings/PII emitted in report.

### H. UI/UX / print / regression
Re-ran Edge headless with isolated profile and real built assets on synthetic view fixtures, not authenticated live HTTP workflow. Widths375,768,1024,1440 ×900,8 pages: Area/Floor/detail/menu/checkout/estimate/empty/error. All32 checks passed overflow and visible controls height48px. Caption no longer collapses; keyboard summary Enter, focus3px, reduced-motion, resize/disclosure preserve draft quantity/note. Offline fetch stub keeps/locks uncertain draft, never a real save/payment. A4 PDF/media print keeps table rows/total, hides controls, no sidebar. Physical printer/80mm/multipage/browser diversity/hardware/screen-reader not tested.

Artifacts storage/app/task99-ui/browser-results.json,12 PNG screenshots and estimate-a4.pdf are copied from this run; synthetic data only, ignored by Git. Prior visual review task9.8 and browser assertions do not constitute accessibility certification. Sidebar has known focus containment/return-focus/inert limitations; native cart has no custom modal/overlay. No UI source bug newly fixed in task9.9.

Regression full suite covers Area/CafeTable CRUD/deletion/status, Reservation, Catalog integration, Order/POS, login/roles/user management, dashboard/menu/sidebar and tasks2–8 feature contracts. Payment processing and official Receipt have no implemented suite/contract, so not PASS. No auth/code/schema changes to make tests green.

## Tests / Checks Executed
See command/evidence table above. Environment variables TASK99_EVIDENCE=1 only during targeted run, then removed. No new dependency, migration, cache infrastructure or load test. PHP/Node checks and Git diff review completed. Browser launch used explicit tool approval for headless Edge with temporary fixture profile; no personal browser profile.

## Test Results
- Automated:416 PHP PASS,2 SKIPPED;11 Node PASS; build PASS.
- Integration matrix26 groups:19 PASS,2 FAIL,4 BLOCKED,1 NOT RUN, as listed in task9.9-test-matrix.md.
- PHP diagnostic method PASS means it executed requests/collected observations, **not** that business findings D02/D03 are safe. Bugs report preserves actual failures.
- Mocks/stubs only used for frontend network-error/dialog behavior; no mocked Payment success accepted as money transaction evidence.

## Acceptance Criteria
| AC | Result | Evidence / limitation |
| --- | --- | --- |
| 1,2,3 | PASS từng mục | Inventory/reports/source, final matrix, guarded isolated env |
| 4,5,6,7 | PASS từng mục | F01 + detail/candidate/filter regression |
| 8 | PASS supported APIs | Legacy create + continue HTTP/MySQL; no-create-from-Floor still limited |
| 9,10,11,12,13 | PASS từng mục | Matching IDs, repeated sequential create rejected, Catalog/quantity/note/persisted assertions |
| 14 | FAIL | Independent identical POST creates2 rows; idempotency absent, B99-01 |
| 15,16 | PASS từng mục |37.05 server total, correct checkout/proforma association |
| 17 | UNVERIFIED/BLOCKED | No Payment methods/processing endpoint to exercise |
| 18 | PASS read scope | No paid claim from UI/params, no Payment writer |
| 19,20 | UNVERIFIED/BLOCKED full processing | Reference-only status rendering PASS, no pending/failed lifecycle backend |
| 21 | PASS reporting requirement | Duplicate Payment backend absent explicitly BLOCKED/HIGH-RISK |
| 22 | UNVERIFIED official receipt | Proforma data PASS; receipt backend absent |
| 23,24,25,26 | PASS từng mục | Honest proforma, no GET writes, shared display contract |
| 27 | FAIL / owner policy BLOCKED | Cancellation release with live Reservation reproduced B99-02 |
| 28,29 | PASS tests executed | Current/future Reservation and maintenance checked; does not accept B99-02 |
| 30,31 | PASS từng mục | Shared reader/HTTP/MySQL reconciliation |
| 32,33,34,35,36 | PASS từng mục for tested routes | Auth/IDOR/CSRF/validation/XSS regression + new tests |
| 37 | PASS assessment | Replay/release/stale risks measured and reported; simultaneous races NOT RUN |
| 38 | FAIL final acceptance | Remaining high-risk blockers prevent core POS acceptance |
| 39,40 | PASS measured samples | Query/time data1→12; duplicates0; production scale not claimed |
| 41,42,43,44 | PASS review/tests performed | Browser32 cases, accessibility limitations/Node states/full regression reported |
| 45,46,47,48,49,50,51 | PASS từng mục | No schema/migration/owner backend/catalog/auth/Git mutation; actual evidence only |
| 52,53,54,55 | PASS từng mục | Bug summary/evidence/blockers/final reports present |
| 56 | PASS conclusion rule | NOT READY FOR REVIEW, no false ready claim |
| 57,58,59,60 | PASS từng mục | Payment not claimed complete, actual backend vs fixture separated, matrix/bugs/report/readiness delivered |

## Risks
Replay items across clients, Reservation/release inconsistency, missing financial backend, missing simultaneous mutation proof, partial browser/a11y/hardware coverage. UI draft memory only; guards cannot provide cross-tab idempotency. Shared sidebar focus limitation persists. Two skipped dedicated MySQL cases are not covered by the small probe. Build retains existing optional fontaine and unresolved video poster warnings, no new package installed.

## Blockers
| Dependency / owner | Severity | Needed next action |
| --- | --- | --- |
| Payment/Receipt — Đạt | P0 core blocker | Accepted methods/result/idempotency/settlement/receipt contract + real implementation/tests |
| Item replay — Đạt + Toàn | High | Define request identity and safe retries across clients before backend/UI task |
| Cancellation release / Reservation — Đạt + Toàn | High | Approve and implement authoritative release/cleanup/schedule guard; retest B99-02 |
| Floor create/Staff floor scope — engineer + owners | Integration limitation | Decide task boundaries/role access/Reservation create policy; do not open auth in acceptance task |
| Shared navigation a11y — frontend owner | P1 | Focus/inert/return-focus and hardware/screen-reader review |

## Assumptions
None promoted to business rule. Existing approved policies from tasks5–8 retained. Staff/Admin role access assessed from actual middleware, not inferred from prompt diagram. Test fixture direct model creation is setup only; assertions creating business Orders use actual POST/service. Synthetic browser fixtures are explicitly not full POS end-to-end proof.

## Out-of-Scope Findings
B99-01/B99-02 belong to Order/Reservation owner and are not silently fixed; B99-03 missing Payment/Receipt; B99-04 shared navigation. See task9.9-bugs.md. No business change requested or implemented through this acceptance test.

## Unverified Items
Simultaneous MySQL create/items (NOT RUN), Payment double checkout/lifecycle/official receipt (BLOCKED), physical/80mm/multipage print, touch devices, screen reader, browser diversity, production performance. Full core POS E2E with Staff login→Floor→Payment→Receipt→release not executable under current role/API/financial dependencies.

## Engineer Review Required
Review5 test methods and26-group matrix, inspect diagnostics rather than relying on green suite. Do not approve final POS release with B99-01/02/03 unresolved. Review worktree task9.1–9.8 separately; only test/report files changed by task9.9. No PR created. A narrowly scoped review of implemented read-only/UI work can proceed, but this is not full acceptance READY.

## Recommended Next Task
Owner-approved small tasks for Payment/Receipt contract, item idempotency and Reservation-aware release, then isolated concurrency + real workflow/browser retest. Do not implement automatically. Task9.9 ends with final matrix, bug report, integration report and **NOT READY FOR REVIEW** assessment; no commit/push/merge/deploy.
