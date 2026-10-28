# Schema cho counter, scheduled và online pickup

## Task Status
PARTIAL: phần schema và kiểm thử MySQL đạt; full regression còn hai lỗi quyền có sẵn, chưa áp migration lên coffee_shop.

## Task ID
CAFE-DB-ONLINE-001, ngày 26/10/2026.

## Summary
Thêm ba bảng request/món/topping và hai cột orders cho phạm vi đơn giản mục 7. Giữ baseline tài khoản/catalog/kho/payment/reservation. Không shipper/gateway/slot/holds. Schema là nền cho implementation module sau, không phải toàn bộ chức năng đã hoạt động.

## Files Created
- database/migrations/2026_10_26_000001_create_online_order_requests_table.php
- database/migrations/2026_10_26_000002_create_online_order_request_items_table.php
- database/migrations/2026_10_26_000003_create_online_order_request_item_toppings_table.php
- database/migrations/2026_10_26_000004_add_online_purchase_fields_to_orders_table.php
- tests/Feature/OnlinePickupSchemaMySqlTest.php
- docs/SCHEMA_COUNTER_SCHEDULED_ONLINE.md
- reports/task-reports/schema-counter-scheduled-online__report.md

## Files Modified
requirements.md, database-design.md, plan.md: extension được user yêu cầu, đặc tả và task/test scope.

## Files Deleted
None.

## Implementation Details
Inspect tài liệu/source/migrations/tests trước code. Worktree trước task có file kế hoạch docs/KE_HOACH_SCHEMA_ONLINE_PICKUP_DELIVERY.md untracked từ task trước; giữ nguyên, không nhận là thay đổi lần này. Bảng phân công ngoài repo không bị chỉnh trong lượt schema.

Snapshots request giữ tên/giá lúc gửi, customer optional, giờ scheduled, payment timing tách method, người/thời điểm accept, idempotency và token hash. FK RESTRICT/NO ACTION; check quantity/price, scheduled/online time, acceptance/rejection, nonblank contact và token hex. Unique pair topping, request–Order, codes và submission/token. Index phục vụ inbox/giờ nhận/loại đơn. Không sửa migration cũ/generated Models/dependencies/UI; không update trạng thái/tiền cũ.

orders ALTER một statement thêm defaults/index/FK/CHECK. down từ chối xóa schema nếu đã có lịch sử mới. Đây không phải migration toàn bộ mô hình phức tạp ở phần cũ assignment. MySQL-only extension theo đích dự án; chưa sửa tests SQLite baseline.

## Tests / Checks Executed
- MySQL opt-in OnlinePickupSchemaMySqlTest, dùng database ngẫu nhiên riêng và xóa đúng database test sau chạy.
- Fresh toàn bộ migrations; rollback 4 migration mới trên schema rỗng; thêm legacy Order/Payment/catalog; migrate upgrade; kiểm constraint, unique, FK, snapshot và dữ liệu cũ; từ chối down có lịch sử.
- Full php artisan test với ONLINE_SCHEMA_MYSQL_TEST=1.
- Hai tests quyền thất bại chạy riêng để xác nhận độc lập schema mới.
- Pint --test cho 5 PHP mới; git diff --check/status/review.
- artisan migrate:status trên local đọc-only, 4 migrations mới Pending.

## Test Results
- MySQL schema: PASS, 1 test / 54 assertions, 7399ms. Không chạy migrate trên coffee_shop.
- Full: 377 tests, 373 passed, 2 failed, 2 skipped, 5375 assertions. Không báo full suite PASS.
- Fail: RoleAuthorizationTest::test_admin_can_use_both_interfaces và UserManagementIntegrationTest::test_edit_role_and_email_are_used_by_authentication_and_authorization: /staff kỳ vọng 200 nhưng 403. Chạy riêng hai tests vẫn fail; fixture chỉ migration users SQLite, không dùng migrations mới. Controller/Service staff-only không bị sửa trong task, là bất nhất baseline đã audit.
- Hai skipped tests môi trường/concurrency không coi PASS.
- Một lần test mới thất bại ở bước kiểm DB trống vì API schema listing liệt kê nhiều databases. Đã thay bằng information_schema query lọc đúng tên DB tạm, chạy lại MySQL PASS. Không ghi database người dùng trong lần lỗi.
- Pint PASS, diff whitespace PASS. Build không áp dụng vì không sửa frontend/build sources.

## Acceptance Criteria
| Criterion | Result |
| --- | --- |
| Schema ba kiểu mua và request/món/topping | PASS — fresh migration/constraint tests |
| FK/unique/chống liên kết trùng và dữ liệu sai | PASS — MySQL insert/update rejection tests |
| Dữ liệu Order/Payment cũ giữ nguyên/default counter | PASS — upgrade snapshot comparison |
| Snapshot không đổi khi catalog đổi tên/giá | PASS |
| Empty rollback và guarded populated rollback | PASS |
| Toàn bộ regression xanh | FAIL — hai lỗi quyền baseline |
| Áp dụng DB local | UNVERIFIED/not executed; 4 migrations Pending |
| Online Service/UI/Payment mới | Out of scope, chưa triển khai |

## Risks
MySQL DDL không atomic toàn chuỗi, up tạo bảng rồi ALTER CHECK có thể cần forward repair nếu dừng giữa chừng. down trên dữ liệu mới bị chặn; không xóa lịch sử để rollback. SQLite không hỗ trợ extension mới, opt-in MySQL cần quyền tạo/xóa DB tạm. Các quan hệ cross-row/phân quyền/giờ/accept/status/payment phải do workflow khóa xử lý, không được hiểu FK là đầy đủ nghiệp vụ. Live DB có pickup_requests ngoài migration baseline được thấy trong bước kiểm schema; chưa đọc/migrate/gộp dữ liệu bảng đó.

## Blockers
Không cho schema tests. Hai lỗi quyền ngăn full regression xanh và cần task sửa riêng; không mở rộng scope để sửa Auth/POS ở đây.

## Assumptions
User chốt mục 7 rút gọn, online là đến lấy sớm; scheduled hẹn lấy đồ không phải đặt bàn; VND và cash/bank đơn giản. Snapshot request có thể giữ nhiều dòng cùng variant khác tùy chọn. payment_timing được thêm vì bank tại quầy và bank trả trước không thể phân biệt chỉ qua method.

## Out-of-Scope Findings
Admin bị chặn khu Staff; các thiếu Payment/Recipe integration đã ghi audit cũ; bảng pickup_requests local ngoài migration. Không sửa code hoặc dữ liệu các phần này.

## Unverified Items
MySQL versions khác 8.4, upgrade trên bản sao đầy đủ dữ liệu coffee_shop, execution trên coffee_shop, Service concurrency, UI, seed v2 và remote CI. Test legacy fixture là bằng chứng nâng cấp kiểm soát, không thay thế audit toàn DB thật.

## Engineer Review Required
Review extension/spec và migration, backup/kiểm target trước migrate. Xem mapping nghiệp vụ tại docs/SCHEMA_COUNTER_SCHEDULED_ONLINE.md. Xử lý hai lỗi quyền theo task riêng. Chưa stage/commit/push.

## Recommended Next Task
Sửa quyền Admin baseline hoặc triển khai request/accept theo task riêng sau review schema; không tự triển khai tiếp.
