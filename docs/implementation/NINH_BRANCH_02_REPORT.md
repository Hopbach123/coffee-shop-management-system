# Branch 02 logic — N04–N06 / integration

## Task Status
PARTIAL — chưa đạt toàn bộ tiêu chí demo end-to-end.

## Task ID
NINH-INTEGRATION-02 — 17/11/2026

## Summary
Nhánh `feature/order-requests-scheduled-pickup`, HEAD baseline `fb7e77fd`. Giữ và phát triển trên toàn bộ thay đổi trước đó theo xác nhận owner. Chia báo cáo logic, không tạo/merge nhánh riêng.

## Files Created
`app/Services/ScheduledPickupService.php`, `config/shop.php`, `tests/Feature/ScheduledPickupTimeTest.php`, ba report NINH_CODE_REVIEW/NINH_BRANCH_01_REPORT/NINH_BRANCH_02_REPORT.

## Files Modified
CartController; OrderService; Order; OrderTrackingController (untracked có sẵn); Staff/OrderController; WorkspaceController; UpdateOrderItemStatusRequest; checkout; brewing/index; online-requests/show; orders/show; public/orders/track. Tests CustomerPickupFlowTest/StaffOrderItemManagementTest/OnlineOrderNinhHopWorkflowTest. Progress/final report thêm đính chính. Pint còn format các file integration đang dirty MenuController/PosController/Models mới/routes. Không coi các file có sẵn là mới tạo ở lượt này.

## Files Deleted
None.

## Implementation Details
### Changes / Reused code
Tận dụng Models request và OrderService/InventoryService hiện có, routes/Blade/component hiện có. Không sửa InventoryService, stock ledger hoặc migrations. Không gọi consume khi accept. Scheduled đọc datetime-local giờ Việt Nam, validate tương lai/giờ config và lưu UTC, kiểm lại giờ khi staff accept. Không thêm slot/scheduler/capacity. Scope chỉ takeaway giữ như nền trước.

### API/UI
Giữ route cart checkout, staff online accept/reject, PATCH staff.orders.items.status.update, complete, tracking. preparing có Pha xong; ready mở chi tiết đơn để giao. Served kiểm payment và request_code online, không tự completed. Hiển thị giờ hẹn Việt Nam trong queue/detail/tracking. Sai transition trả validation. Layout/components không redesign; browser chưa xác minh.

### Inventory integration
Vẫn gọi InventoryService::startPreparing. Primitive kiểm recipe/tồn, transaction/khóa và reference order_item chống consume lại. Targeted tests kiểm consume/thiếu tồn/replay; chưa chạy MySQL tranh tồn trong lượt này. Không invent StockMovement Model/unique constraint như report cũ từng mô tả sai; ledger thực tế là inventory_transactions.

### Finance integration
Không tìm thấy PaymentService thật. Đã loại persistence Payment khỏi Sales::recordPayment và hiển thị dependency thay form thu tiền. Phương thức vẫn tồn tại nhưng fail closed. Completion đọc paid thực tế; test dùng fixture Finance trong SQLite, không tạo thanh toán production. Cash/bank thu mới và demo xuyên suốt bị BLOCKED cho tới khi Đạt có writer contract. Không fake paid, refund hay hồi kho.

## Tests / Checks Executed
- PHP 8.3.30 tại Laragon; phpunit.xml SQLite :memory:, không DB dự án.
- Targeted filter CustomerPickupFlowTest|StaffOrderItemManagementTest|OnlineOrderNinhHopWorkflowTest|ScheduledPickupTimeTest: 47 tests, 399 assertions PASS; `storage/logs/ninh-targeted.json`.
- Full suite sau sửa: 441 tests, 433 pass, 8 skipped, 5986 assertions, exit 0 (~31.9s); `storage/logs/ninh-integration-regression.json`.
- Lần full đầu fail 4 do fixture/giả định cũ; đã sửa fixture paid trước served, thay test queue ready action và input giờ Việt Nam. Không hạ rule runtime để test xanh.
- npm run build PASS. Warnings có sẵn: fontaine optional, runtime poster/auth background. Không cài package.
- Pint --dirty đã chạy; git diff --check PASS. Browser / migration / seed / MySQL: NOT_RUN.

## Test Results
8 skipped, không phải PASS: DashboardRevenueMySqlTest (1, thiếu dedicated DB); ReservationConcurrencyTest (1, thiếu dedicated DB); OnlinePickupSchemaMySqlTest (3, opt-in ONLINE_SCHEMA_MYSQL_TEST chưa bật); InventoryImportConcurrencyMySqlTest (3, opt-in INVENTORY_IMPORT_MYSQL_TEST chưa bật). Nhóm cuối gồm tranh tồn/consume nên SQLite không thay bằng chứng concurrency MySQL.

## Acceptance Criteria
| UC | Before | Fixed | Verified | Remaining |
| --- | --- | --- | --- | --- |
| N01 | Cart/options/quote có sẵn | Topping count và duplicate legacy | Tests HTTP | Retry submit, quote từng dòng/availability |
| N02 | POS/Order có sẵn | Một phần paid edit guard | Existing suite | Rà addItem sau paid, UI counter |
| N03 | Accept/reject có sẵn | Category guard | Replay/no consume tests | MySQL concurrent accept |
| N04 | Hardcode/parse timezone sai | Local → UTC, config, accept recheck, hiển thị giờ | Time boundary/config tests | Toàn cung cấp giờ; browser |
| N05 | Chỉ bắt đầu pha ở queue | Ready action, paid/code served guard | Lifecycle/consume/thiếu tồn/replay | Browser, MySQL concurrency, test mã nhận HTTP đầy đủ |
| N06 | Complete/token có sẵn | Cancel paid guard, token headers, chặn Finance write | Served+paid complete fixture; unpaid reject | Finance writer, all AC manual flow |

## Risks
Chưa đủ test mọi case T01–T20. Quote tổng có hạn chế; submission_key sinh mỗi submit chưa là retry key ổn định. Shared cart view còn inline fixed grid từ nền cũ, responsive chưa nghiệm thu. Model money comparison còn float từ nền trước; cần exact decimal read contract từ Finance. Không che các gap này bằng trạng thái SUCCESS.

## Blockers
Đạt: writer cash/bank xác nhận thực nhận, chống lặp, settlement contract. Toàn: giờ quán/config chuẩn. Chưa cấu hình thì scheduled từ chối rõ; không giả định 07–22. Không blocker schema cần migration mới trong lượt này.

## Assumptions
Lưu UTC/render Asia/Ho_Chi_Minh theo thiết kế. Config hiện hỗ trợ một khoảng mở cùng ngày; giờ qua đêm cần Toàn chốt. Request code dùng để đối chiếu giao; token random hash là khóa truy cập public.

## Out-of-Scope Findings
Table lifecycle cần Toàn review; không tự đổi policy bàn. Không gateway/shipper/slot/holds/refund engine.

## Unverified Items
MySQL schema upgrade/constraints/concurrency, responsive 375/768/1440, browser/action/keyboard, toàn bộ production finance.

## Engineer Review Required
Đọc NINH_CODE_REVIEW và diff phân biệt work có sẵn; review Finance failure và config trước demo. Không merge khi hiểu nhầm tất cả đã DONE.

## Cách chạy demo
Sau khi Toàn xác nhận giờ quán, cấu hình SHOP_OPENING_TIME/SHOP_CLOSING_TIME (HH:mm) qua môi trường local; không dùng mặc định ngầm. Chạy server/build theo hướng dẫn dự án, chọn món → cart → scheduled datetime-local → staff inbox accept → queue bắt đầu pha → Pha xong → detail giao bằng mã yêu cầu, chỉ khi đã có paid hợp lệ từ Finance. Hiện chưa demo thu tiền end-to-end vì thiếu contract; không insert DB thủ công để vượt blocker.

## Recommended Next Task
Hoàn thiện các gap N01/N02 và test delivery code, nối contract Finance khi có, rồi MySQL/browser verification. Không tự commit/push/merge hay chuyển nhánh.
