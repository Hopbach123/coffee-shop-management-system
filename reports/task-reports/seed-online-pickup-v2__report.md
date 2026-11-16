# Cập nhật seed SQL cho schema online pickup

## Task Status
SUCCESS trong phạm vi cập nhật SQL và kiểm thử database tạm; không import vào coffee_shop.

## Task ID
CAFE-SEED-ONLINE-001, 26/10/2026.

## Summary
User yêu cầu cập nhật trực tiếp seed_data.sql. Giữ baseline V1, thêm V2 counter/scheduled/online fixtures phù hợp bốn migrations mới. Không tạo file seed song song, không shipper/gateway.

## Files Created
reports/task-reports/seed-online-pickup-v2__report.md.

## Files Modified
- database/seed_data.sql: UTC session, link guide đúng, request/item/topping và Orders/Payment online/hẹn.
- database/verify_seed_data.sql: thêm 5 SELECT checks, tổng 12.
- tests/Feature/OnlinePickupSchemaMySqlTest.php: thêm import/rerun/presence test.
- docs/Rp-seeddata-runMySQL.md: điều kiện schema, counts mới và giới hạn nâng cấp V1.

## Files Deleted
None.

## Implementation Details
Các migrations/spec/schema report đã có trong worktree từ task trước được giữ nguyên, không nhận là thay đổi seed task. Seed vẫn data-only, không ALTER/DROP/TRUNCATE/disable FK hoặc UPDATE dữ liệu đang dùng. New fixture inserts chỉ khi @demo_new; ID lấy từ LAST_INSERT_ID trong cùng connection.

6 requests gồm pending online/hẹn, accepted online, accepted scheduled prepaid, rejected, cancelled. 7 request lines/1 topping; 2 accepted requests nối đúng 2 takeaway Orders, 3 sold lines/1 topping; thêm 1 paid bank_transfer trên scheduled Order vẫn pending. Tổng 9 Orders/10 lines/5 Payments. Không tăng consume hoặc đổi stock vì V2 items pending. Counter dùng default Order schema.

Mật khẩu demo và tracking token là fixture công khai; DB chỉ lưu hash. Không nạp lại hay đổi mật khẩu local. Seed V1 đã tồn tại sẽ skip, không tự thêm V2 bằng mapping master có thể đã sửa. Thiếu V2 được báo rõ bởi result và presence verifier, không khẳng định dataset đầy đủ chỉ dựa một marker.

## Tests / Checks Executed
ONLINE_SCHEMA_MYSQL_TEST=1 php artisan test --filter=OnlinePickupSchemaMySqlTest; Pint; git diff --check và self-review SQL/docs.

## Test Results
2 tests / 80 assertions PASS trên MySQL 8.4 database ngẫu nhiên riêng; cleanup database test. Bao gồm schema 54 assertions và seed fresh/presence/rerun/stock/password checks. Fresh import cả 12 checks invalid_count=0. DB trống và fixture bị xóa có presence >0. Rerun giữ count, stock và mật khẩu đã thay. Một lần SQL test fail vì splitter ban đầu cắt dấu chấm phẩy trong chuỗi kết quả; sửa splitter theo kết thúc statement, chạy lại PASS. Pint/whitespace PASS.

Không chạy lại full suite vì thay đổi SQL/docs và test đã kiểm chứng đúng phạm vi; hai lỗi quyền Admin baseline ở report schema trước vẫn chưa sửa. Build không áp dụng vì không thay frontend.

## Acceptance Criteria
PASS: SQL có ba kiểu mua/request trạng thái, snapshot/multi-line/topping và bank prepaid. PASS: verifier phát hiện DB trống/fixture thiếu; fresh 12 checks bằng 0. PASS: chạy lại không trùng/đổi stock/reset mật khẩu. PASS: bảo toàn SQL baseline và chỉ đọc/ghi database test.

## Risks
V1 database không tự nâng cấp V2, phải dùng dev DB riêng hoặc task upgrade có review. Seed cần bốn migrations mới trước import; dừng lỗi/ROLLBACK cùng connection, không bỏ qua lỗi. Fixtures hẹn dựa NOW UTC+1 ngày, không chứng minh giờ mở cửa vì chưa có config chốt. V1 verifier vẫn có giới hạn lọc DEMO và đối chiếu recipe hiện tại; chỉ dùng snapshot ngay sau import, không chứng minh audit runtime hoàn chỉnh. Initial prepaid check sẽ không còn 0 sau khi vận hành hoàn tất fixture — cần phân biệt với corruption.

## Blockers
None cho SQL test. Migrations coffee_shop còn pending; không import vào đó trong task này.

## Assumptions
Online lấy tại quán; schema mới được áp trên database test; tiếp tục dùng public fixture credentials gốc và nhân viên xác nhận chuyển khoản.

## Out-of-Scope Findings
Hai lỗi quyền Admin baseline chưa xử lý. Không đổi SQL thành hệ thống upgrade/import framework, không thêm seed registry table.

## Unverified Items
Import V2 vào coffee_shop thực tế, browser, giờ mở cửa/config, full runtime reconciliation và CI.

## Engineer Review Required
Review SQL/guide, dùng database dev riêng để import toàn bộ file. Không chạy lại trên DB nhóm để kỳ vọng tự thêm V2. Chưa commit/push.

## Recommended Next Task
Review schema/seed rồi chọn workflow online/request theo task riêng, không tự triển khai.
