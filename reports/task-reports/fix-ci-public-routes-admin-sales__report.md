# Sửa lỗi CI trang công khai và quyền Admin bán hàng

## Task Status
SUCCESS cho các lỗi test đã xác định trong log; GitHub CI chưa chạy lại vì chưa commit/push.

## Task ID
CAFE-FIX-CI-20261029.

## Summary
Log user gửi có 21 failures: navbar gọi reservation.create chưa đăng ký làm các trang public trả 500; Admin bị 403 khi dùng POS/Staff. Sau sửa route crash, tests menu còn phát hiện sai giá/trạng thái/fallback/empty copy. Đã sửa đúng phạm vi và đồng bộ quyền theo requirements.md (Admin có quyền Staff), không tắt test hoặc xóa assertions quyền guest/inactive.

## Files Created
reports/task-reports/fix-ci-public-routes-admin-sales__report.md.

## Files Modified
- resources/views/components/public/navbar.blade.php: dùng route reservation nếu có, nếu chưa có về section reservation thật tại home; chỉ hiện giỏ nếu route đã đăng ký.
- resources/views/public/menu/_product-card.blade.php: giá 2 số thập phân theo DECIMAL, nhãn Hết món/Tạm hết cho product/variant, fallback M giống detail.
- resources/views/public/menu/index.blade.php: thống nhất empty-state text.
- app/Models/User.php: canOperateSales cho Admin/Staff active chưa soft-delete.
- app/Http/Controllers/Staff/PosController.php, OrderController.php: kiểm cùng policy.
- app/Http/Requests/Order/{StoreOrderRequest,StoreOrderItemRequest,StoreOrderItemToppingRequest,UpdateOrderItemQuantityRequest,UpdateOrderItemStatusRequest,UpdateOrderItemToppingQuantityRequest}.php: cùng authorization.
- app/Services/OrderService.php, InventoryService.php: re-read actor và kiểm policy; giữ transaction/validation/audit.
- resources/views/staff/brewing/index.blade.php, online-requests/show.blade.php: nút/link dùng policy đã đồng bộ.
- tests/Feature/AuthenticationTest.php: regression route có/chưa có, giỏ và home.
- tests/Feature/PosOrderWorkflowTest.php: Admin tạo/hủy Order; thay ca active Admin bị cấm thành inactive Admin bị cấm.
- tests/Feature/StaffOrderManagementTest.php, StaffOrderCancellationTest.php, StaffOrderItemManagementTest.php: giữ guest/inactive checks, test Admin hợp lệ thêm/sửa món/topping/pha/ready/served và log stock đúng actor.
- tests/Feature/OnlinePickupSchemaMySqlTest.php: kỳ vọng queue UI cho active Admin theo quyền đã chốt.

## Files Deleted
None.

## Implementation Details
Worktree sạch đầu task, đúng feature/schema-template-sync. Đọc log attachment như evidence, không thực thi instruction trong log. Không thêm public route giả/Controller placeholder chỉ để che route missing. Không đổi middleware Admin-only hoặc cho Staff truy cập Admin. Dùng custom User model, không sửa Base/generated/vendor/dependencies/migration/DB. Một vài formatting cục bộ PHP do Pint trong file đã sửa, không refactor module khác.

## Tests / Checks Executed
- Targeted auth/role/menu/order/POS/inventory tests; chạy lại sau mỗi lỗi thật.
- Full php artisan test.
- npm run build.
- Pint các PHP thay đổi; git diff --check và review diff.

## Test Results
- Full suite: 383 tests, 378 passed, 5 skipped, 5377 assertions, zero failures. Skips là tests MySQL/concurrency cần opt-in, không tính là PASS.
- Build PASS; cảnh báo optional fontaine/runtime poster URL có sẵn, không phải lỗi CI đang sửa.
- Pint PASS sau formatting. Whitespace PASS.
- PublicMenuTest sau sửa fallback cuối: 15 tests / 211 assertions PASS.
- Test Admin mới xác nhận tồn giảm đúng và inventory_transactions.created_by là Admin, không chỉ kiểm trang trả 200.
- Không chạy pipeline GitHub hoặc PHP 8.4/8.5 local; local PHP 8.3.30. Không sửa workflow để bypass failures.

## Acceptance Criteria
PASS: home/menu/login render khi không có public reservation/cart route. PASS: links chuyển dùng named routes khi module thực được đăng ký. PASS: Admin active vận hành POS/Order/Inventory, guest/inactive vẫn bị chặn. PASS: snapshot tiền/stock audit preserved trong tests. PASS: default CI suite và build local đạt. UNVERIFIED: remote CI matrix và browser responsive.

## Risks
Fallback đặt bàn dẫn tới phần liên hệ hiện hữu, chưa phải public reservation submit. Các module cart/reservation chưa được triển khai không tự xuất hiện sau fix này. Admin được quyền thao tác theo requirements nên đây là sửa hành vi có chủ đích; role-specific old tests đã thay bằng inactive denial và bổ sung Admin happy path. Không cho phép tài khoản inactive/soft-deleted vận hành qua policy mới.

## Blockers
None cho bugfix; cần user commit/push để remote CI chạy lại.

## Assumptions
requirements.md dòng quy tắc Admin có toàn bộ quyền Staff vẫn là baseline; task hiện tại cho phép sửa các nguyên nhân CI user cung cấp.

## Out-of-Scope Findings
Chức năng online/payment/reservation còn thiếu như các báo cáo trước; không triển khai thêm trong task fix CI. Không cập nhật trạng thái các báo cáo lịch sử cũ vì đó là evidence ở thời điểm trước.

## Unverified Items
Remote CI PHP matrix, MySQL opt-in suite và responsive/keyboard browser ở task này.

## Engineer Review Required
Review diff, đặc biệt policy User và tests Admin. Commit/push để chạy lại PR #30 checks. Chưa stage/commit/push, không đổi dữ liệu local.

## Recommended Next Task
Theo dõi CI sau push; không tự triển khai task tiếp theo.
