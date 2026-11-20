## Task Status
PARTIAL — phần hiển thị và refresh chỉ đọc đã triển khai, READY FOR REVIEW trong phạm vi engineer duyệt. Chưa hoàn thành xác nhận Payment thành công, Order completion/release hoặc kiểm tra trình duyệt thực tế.

## Task ID
TASK 9.7 — Table Status Synchronization

## Summary
Floor Plan, chi tiết bàn và thống kê Area dùng chung TableStatusReadService. Sáu nhóm hiển thị được engineer duyệt: Trống, Có khách, Đã đặt, Bảo trì, Cần kiểm tra, Chưa xác định. Không thêm persisted enum. GET refresh giữ query/bộ lọc; quay lại từ bfcache tải lại một lần. Không polling/WebSocket và không ghi dữ liệu.

## Files Created
- app/Services/TableStatusReadService.php — nguồn đọc/classification chung.
- resources/views/admin/tables/components/status-freshness.blade.php — thời điểm đọc, GET refresh và loading.
- resources/js/admin/table-status.js — refresh khi khôi phục bfcache.
- tests/Concerns/CreatesTableStatusSources.php — fixture nguồn đọc cho test SQLite cũ.
- tests/Feature/TableStatusSynchronizationTest.php — matrix, thời gian, Payment tham chiếu, reconciliation, stale read.
- tests/JavaScript/table-status.test.mjs — GET/bfcache và trang không liên quan.
- tests/manual/table-status-read.php — kiểm chứng MySQL SELECT-only; từ chối câu lệnh khác.
- docs/task09/task9.7-state-matrix.md — khảo sát, bảng chính sách đã duyệt và giới hạn.
- docs/task09/task9.7-report.md — báo cáo này.

## Files Modified
- app/Http/Controllers/Admin/{AreaController,CafeTableController,TableDetailController}.php — dùng nguồn đọc chung, filter/count display; CRUD thường giữ nguyên.
- resources/views/admin/areas/index.blade.php — đếm sáu nhóm theo display.
- resources/views/admin/tables/{floor-plan,detail}.blade.php — freshness; ẩn liên kết tiếp tục khi display mâu thuẫn.
- resources/views/admin/tables/components/{table-card,status-summary}.blade.php — nhãn/icon, persisted đối chiếu, tín hiệu Reservation/Payment.
- resources/css/admin/table-floor-plan.css — màu các nhóm mới từ tokens hiện có.
- resources/js/app.js — khởi tạo refresh enhancement.
- tests/Feature/{AreaIntegrityTest,AreaManagementTest,AreaManagementUITest,AreaUpdateTest,TableDetailOrderEntryTest,TableFloorPlanTest,TableOrderIntegrationTest}.php — bổ sung fixture nguồn đọc; kỳ vọng UI mới và read-only assertions.

Worktree đã có thay đổi task9.1–9.6 và tài liệu task9.9 từ trước. Các sửa OrderService/InventoryService, routes Staff/Admin, Model, checkout/menu và test Order trong toàn bộ Git diff thuộc công việc trước; task9.7 không sửa các writer đó. Không được xem toàn bộ diff là task9.7.

## Files Deleted
None

## Implementation Details
### Status architecture và mapping
Persisted cafe_tables.status vẫn available/occupied/maintenance; migration là source schema. Display là alias SELECT, không lưu database. Bảng chính sách chi tiết nằm trong task9.7-state-matrix.md và đã được engineer duyệt qua ba câu trả lời trong chat ngày 20/10/2026.

Một CASE SQL duy nhất ưu tiên Area inactive/missing/deleted hoặc status lạ → unknown; dữ liệu Order/Reservation mâu thuẫn → conflict; available với lịch hiện tại, không Order → reserved; còn lại giữ persisted. Pending Order phải là dine_in, đúng table_id; completed/cancelled/takeaway không chiếm nhóm Order phục vụ. Reservation pending/confirmed/arrived với start<=observedAt<end; lịch tương lai chỉ đếm riêng. Mỗi request dùng cùng observedAt theo timezone ứng dụng.

Payment pending/paid chỉ là số bản ghi tham chiếu trên Order pending dine_in, không hiển thị thông tin giao dịch nhạy cảm, không kết luận settled hay tính công nợ. Failed/refunded không giải phóng bàn. Chưa có waiting_payment hoặc xác nhận thành công từ Payment owner.

### Routes / contracts
Không thêm route. Admin GET /admin/areas, /admin/tables?view=floor và /admin/tables/{cafe_table}/detail giữ middleware/authorization hiện có. Floor status filter áp dụng display_status, gồm sáu nhóm; bảng CRUD thường vẫn lọc persisted status như trước. Summary Floor áp dụng cùng search/Area/display filter trên toàn bộ trang. Area cards thống kê bàn chưa xóa theo từng Area chưa xóa; inactive Area có bàn thuộc unknown. Area navigation đếm tổng bàn, không giả là số bàn khả dụng.

### Refresh / concurrency / security
Refresh là liên kết GET đầy đủ query hiện tại, có thời điểm đọc và loading aria-live. pageshow.persisted chỉ reload một lần, không listener trên trang không có status panel; không can thiệp draft/menu/checkout. Request lỗi có trạng thái unavailable, không fake zero. Không có asynchronous response để ghi đè sai bàn và không có automatic retry/POST.

Đây là quan sát tại thời điểm đọc, không phải khóa giữ bàn. Summary, trang dữ liệu và candidates là các SELECT riêng, có thể thấy thay đổi commit ở giữa khi truy cập đồng thời; không hứa snapshot xuyên các request/trang. Writer Order hiện có tiếp tục recheck/lock server; task này không mở rộng protocol ghi. Các hành vi giải phóng/tất toán còn phụ thuộc owner.

### Performance / UI
Aggregate subqueries lấy counts, không hydrate lịch sử Order/Payment/Reservation theo mỗi bàn. Test Floor đo 5 query với 1 và 15 bàn; Area đo 3 query với 1 và 15 khu vực. MySQL strict-compatible aggregate xóa select wildcard trước COUNT. Đây là số query cố định, chưa benchmark execution plan trên dữ liệu lớn. Nhãn và icon hỗ trợ phân biệt ngoài màu; giữ grid/breakpoints hiện có. Chưa kiểm tra hình ảnh responsive thực tế.

## Tests / Checks Executed
- PHP artisan test --compact: 413 tests, 411 passed, 2 skipped, 5,823 assertions.
- Sau bổ sung fixture failed/pending/refunded và wording scope: targeted TableStatusSynchronization/TableFloorPlan/AreaManagementUI/TableDetailOrderEntry: 22 passed, 284 assertions.
- Node --test tests/JavaScript/table-status.test.mjs: 2 passed.
- PHP tests/manual/table-status-read.php: PASS, MySQL thực tế SELECT-only, 5 bàn/3 Area, tất cả buckets khớp tổng. Script chặn non-SELECT; không tạo fixture/database hay ghi dữ liệu thật.
- npm run build: PASS.
- Pint trên PHP task9.7 và các test chạm trực tiếp: PASS; chỉ sửa formatting/import trong những file này.
- Git diff --check: PASS, chỉ cảnh báo CRLF/LF. Đã review diff/status; không commit/push/merge.

## Test Results
SQLite :memory: dùng cho feature tests, kiểm tra driver/database trước tạo fixture. Matrix kiểm tra available/occupied/maintenance, zero/one/multiple pending dine_in, Area inactive/deleted, Payment cả bốn trạng thái không đổi persisted status, Reservation start inclusive/end exclusive, ba live/ba terminal statuses, lịch tương lai và xung đột. Shared-page test xác minh Floor/detail/Area đổi cùng nhóm sau Order chuyển completed nhưng persisted vẫn occupied. Test cũ giữ auth, 404, escaping, unavailable state, không mutation và bounded queries. MySQL kiểm chứng SQL/read reconciliation thực tế; không kiểm thử synthetic concurrency bằng ghi vào database thật.

## Acceptance Criteria
| AC | Result | Evidence / limitation |
| --- | --- | --- |
| 1, 2, 3 | PASS từng mục | Khảo sát tasks/schema/Order writers; state matrix |
| 4 | UNVERIFIED | Payment backend/lifecycle chưa tồn tại đủ để xác nhận; BLOCKED owner |
| 5, 6, 7, 8 | PASS từng mục | Reservation/Maintenance source + matrix engineer duyệt |
| 9, 10 | PASS từng mục | Không enum persisted/cột mới |
| 11, 12, 13, 14, 15 | PASS từng mục | Chung CASE; shared-page tests, SQLite/MySQL reconciliation |
| 16 | PASS | Chỉ đọc Order đã lưu; không optimistic create |
| 17, 18 | PASS từng mục | Payment reference-only; matrix tests, không release |
| 19 | UNVERIFIED | Không có Payment success backend có thẩm quyền |
| 20 | PASS | Không writer/release trong refresh |
| 21, 22, 23, 24 | PASS từng mục | Reservation boundaries, maintenance, multiple Orders, unknown/conflict |
| 25, 26 | PASS từng mục | GET + pageshow.persisted, Node tests; browser thực tế chưa kiểm tra |
| 27, 28, 29, 30 | PASS từng mục | No-mutation assertions và MySQL non-SELECT guard |
| 31 | PASS | Timestamp, GET đọc lại và stale-data conflict; không hứa realtime |
| 32 | UNVERIFIED | Không kiểm chứng concurrent writers end-to-end; giữ protocol hiện có |
| 33, 34, 35, 36 | PASS từng mục | Không writer/auth changes; auth/escaping tests, counts không secret |
| 37, 38 | PASS từng mục | Query count cố định; không polling |
| 39 | PASS | Loading Node tests, unavailable/conflict feature tests |
| 40 | UNVERIFIED | Chưa kiểm tra trình duyệt các viewport |
| 41 | PASS | Full suite regression; 2 test skipped không được tính PASS |
| 42, 43, 44, 45 | PASS từng mục | Không thay Reservation/auth/schema/migration |
| 46 | PASS | Feature fixtures SQLite :memory:; MySQL chỉ đọc |
| 47, 48, 49, 50 | PASS từng mục | Không Git mutation; evidence thật; blockers và report này |
| 51 | UNVERIFIED | Chưa đạt toàn bộ task do Payment/concurrency/browser |
| 52, 53, 54 | PASS từng mục | Báo PARTIAL, không tuyên bố realtime hoặc toàn bộ POS hoàn thành |

## Risks
Read snapshot có thể cũ ngay sau khi đọc; không giữ bàn. Không tự reconcile dữ liệu mâu thuẫn. Count queries cần benchmark/index review khi dữ liệu lớn. Thiếu Payment authority khiến không suy ra chờ thanh toán/đã thanh toán/giải phóng bàn. Build có cảnh báo fontaine optional và poster /assets/video/maison-coffee-poster.jpg chưa resolve; không thêm dependency hoặc sửa asset ngoài scope.

## Blockers
Payment owner cần cung cấp lifecycle, success/idempotency/settlement và release authority. Order/Reservation owner cần chốt release/cleanup và liên hệ Reservation với Order trước tự động hóa. Các phần này chưa được triển khai.

## Assumptions
None ngoài ba chính sách đã duyệt. UTC/timezone ứng dụng giữ nguyên. Pending dine_in là candidate theo task9.5 đã duyệt.

## Out-of-Scope Findings
OrderService::cancel hiện có nhánh đổi occupied→available khi không còn pending dine_in nhưng chưa kiểm tra Reservation/cleanup. Ghi nhận để owner review riêng; task9.7 không sửa Order Service. Không triển khai realtime, Payment backend hay task9.8.

## Unverified Items
Browser back/forward và manual refresh trên browser thực tế; responsive 375/768/1440; screen-reader/keyboard thực tế; multi-user concurrent mutation; Payment success/release end-to-end; hai test skipped trong suite; performance dữ liệu lớn.

## Engineer Review Required
Review scope delta task9.7 riêng với worktree trước; kiểm tra Floor /admin/tables?view=floor, /admin/tables/{id}/detail và /admin/areas. Đối chiếu nhãn bảo thủ với dữ liệu thật, thử refresh và bfcache, xác nhận responsive. Không dùng nhãn display để cấp phép ghi; backend tiếp tục quyết định.

## Recommended Next Task
Hoàn tất kiểm chứng browser và handoff Payment/release policy cho owner; sau đó review dependency task9.8/9.9. Không triển khai tự động.
