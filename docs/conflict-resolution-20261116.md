# Báo cáo xử lý xung đột ngày 16/11/2026

## Task Status

PARTIAL — đã xử lý toàn bộ xung đột source và Git index; các kiểm tra tự động đã chạy đều đạt. Chưa hoàn tất xác minh responsive bằng trình duyệt và kiểm thử MySQL riêng.

## Task ID

CAFE-INTEGRATION-FIX-001 — nhãn theo dõi cho yêu cầu hiện tại của user, không thay trạng thái roadmap.

## Summary

Nhánh `feature/area-table-pos-ui`, HEAD `3cb04e63`. Ban đầu có 16 unmerged paths, marker `Updated upstream` / `Stashed changes`; không có MERGE_HEAD. Các thay đổi Area/Table/POS còn lại đã tồn tại và được stage trước phiên này. Đây là xử lý xung đột khi áp dụng stash lên code đã cập nhật, không tạo merge commit.

Chia phạm vi thành ba bước: khảo sát hai phía và tài liệu; hợp nhất 16 file; hồi quy/build/review. Không thay schema, dependency, cấu hình môi trường hoặc triển khai module mới.

## Files Created

- `docs/conflict-resolution-20261116.md`: báo cáo này.
- Bản sao trước khi sửa của 16 file conflict nằm tại `storage/app/conflict-resolution-20261116/`, chỉ dùng local, không stage.

## Files Modified

Các file có conflict được xử lý trong phiên này:

- `app/Http/Controllers/Staff/OrderController.php`
- `app/Http/Controllers/Staff/PosController.php`
- `app/Http/Requests/Order/StoreOrderItemRequest.php`
- `app/Http/Requests/Order/StoreOrderItemToppingRequest.php`
- `app/Http/Requests/Order/StoreOrderRequest.php`
- `app/Http/Requests/Order/UpdateOrderItemQuantityRequest.php`
- `app/Http/Requests/Order/UpdateOrderItemStatusRequest.php`
- `app/Http/Requests/Order/UpdateOrderItemToppingQuantityRequest.php`
- `app/Services/InventoryService.php`
- `app/Services/OrderService.php`
- `resources/views/staff/orders/show.blade.php`
- `routes/staff.php`
- `tests/Feature/PosOrderWorkflowTest.php`
- `tests/Feature/StaffOrderCancellationTest.php`
- `tests/Feature/StaffOrderItemManagementTest.php`
- `tests/Feature/StaffOrderManagementTest.php`

Thêm test hồi quy vào `tests/Feature/TableOrderIntegrationTest.php`, file vốn đã được stage trước phiên này.

Một số file giải quyết conflict khớp lại HEAD nên không xuất hiện trong diff cuối. `PosController.php` chỉ khác HEAD ở newline cuối file. Các file staged có sẵn khác giữ nguyên; các thay đổi thử nghiệm trong hai script manual đã được hoàn nguyên về đúng nội dung staged ban đầu.

## Files Deleted

None.

## Implementation Details

- Giữ `User::canOperateSales()` từ upstream: Admin/Staff đang active, chưa xóa; không thay bằng các kiểm tra rời rạc cũ.
- Giữ lock tài khoản và toàn bộ kiểm soát Inventory hiện có: công thức bắt buộc, kiểm tra tồn, transaction và ledger, chống trừ kho hai lần.
- Giữ POS tạo đơn qua `OrderService::createWithItems()` hiện hành; không đưa persistence cũ trở lại Controller.
- Giữ bốn route gọi món/tạm tính/in tạm tính theo bàn từ stash cùng các route online request và pha chế của upstream.
- Ghép màn hình chi tiết đơn: giữ layout, thông tin đơn và lịch sử Payment mới; khôi phục thêm/sửa/xóa món, topping, chuyển tiến độ pha chế và hủy đơn khi đủ điều kiện; bổ sung lại các liên kết gọi món/tạm tính theo bàn.
- Hiển thị topping snapshot cả khi đơn đã đóng. Tải trước Area của bàn để thông tin vị trí trong giao diện hiện hành hiển thị đúng.
- Giữ các test upstream và test Admin pha chế riêng từ stash; thêm một test tích hợp xác minh các thao tác và lịch sử thanh toán cùng tồn tại, đơn completed không còn các thao tác ghi.
- Đánh dấu 16 file đã resolve bằng `git add`, không commit/push.

## Tests / Checks Executed

PHP CLI dùng executable có sẵn `D:/Applications/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe` vì `php` chưa có trên PATH.

- Đọc AGENTS, architecture, requirements, plan, frontend; đối chiếu source, index stages và diff.
- `php artisan test --filter='PosOrderWorkflowTest|StaffOrder|TableOrder|TableCheckout|TableDetail|TableStatus|AreaManagement|TableFloorPlan' --compact`
- `php artisan test --filter=TableOrderIntegrationTest --compact`
- `php artisan test --compact` sau thay đổi cuối.
- `node --test tests/JavaScript/*.test.mjs`
- `npm.cmd run build`
- `php artisan route:list --path=staff --except-vendor`
- `php artisan view:cache`
- `php -l` cho 28 file PHP thay đổi so với HEAD, không tính Blade.
- Render fixture giao diện synthetic bằng SQLite memory, không ghi dữ liệu nghiệp vụ.
- Chạy browser script với Edge, thử lại bằng Chrome: đều dừng ở `CDP timeout: Page.enable`.
- Quét conflict marker trong app/resources/routes/tests; `git diff --name-only --diff-filter=U`, `git ls-files -u`, `git diff --check`, `git diff --cached --check`, status/diff review.

## Test Results

- Nhóm hồi quy ban đầu: PASS, 102 test / 1.247 assertions.
- Nhóm TableOrder sau thêm hồi quy: PASS, 13 test / 235 assertions.
- Full suite cuối: 466 test được phát hiện, 458 PASS, 8 SKIPPED, 6.542 assertions, 0 failure.
- JavaScript: PASS, 11/11.
- Frontend build: PASS. PowerShell chặn npm.ps1; sử dụng npm.cmd có sẵn thành công, không đổi execution policy.
- Staff routes: PASS, 27 route; đủ bốn route theo bàn và các route đang có.
- PHP syntax và Blade compile: PASS.
- Conflict marker / unmerged index / whitespace checks: PASS, không còn conflict hoặc lỗi diff được phát hiện.
- Browser: UNVERIFIED, chưa có viewport nào kiểm chứng vì timeout kết nối CDP; không coi fixture render là bằng chứng responsive.

## Acceptance Criteria

| Criterion | Kết quả | Evidence |
| --- | --- | --- |
| Xử lý toàn bộ 16 conflict, Git không còn unmerged paths | PASS | Marker scan, index/status và diff review |
| Giữ quyền Admin/Staff, POS/Order và Inventory hiện hành | PASS trong phạm vi test | Full suite, test authorization/stock/rollback |
| Giữ thao tác món/topping, luồng theo bàn và lịch sử thanh toán | PASS trong phạm vi test | Test tích hợp mới cho Admin và Staff, pending/completed |
| Source boot/render/build được | PASS | Routes, Blade compile, feature renders, lint, Vite |
| Không làm mất staged work ngoài conflict, không đổi dependency/schema/dữ liệu dự án | PASS | Diff/status; chỉ test SQLite memory, không migrate MySQL |
| Responsive và thao tác thực tế trên 375/768/1440px | UNVERIFIED | Browser bị timeout CDP |
| Hồi quy MySQL/concurrency trong môi trường riêng | UNVERIFIED | Các test MySQL opt-in chưa được bật/cấu hình |

## Risks

Chưa thể bảo đảm không có lỗi trên mọi dữ liệu hoặc môi trường chỉ bằng test tự động. Giao diện đã ghép cần review responsive và thao tác thực tế; SQLite không chứng minh cơ chế lock/concurrency MySQL.

## Blockers

Không còn blocker xử lý conflict. Kiểm chứng browser bị chặn bởi timeout CDP ở Edge/Chrome. Các test MySQL cần cấu hình database disposable riêng và opt-in tương ứng.

## Assumptions

Code upstream tại HEAD là nền mới; các chức năng Area/Table/POS trong stash cần được tích hợp đồng thời. Không lựa chọn toàn bộ một phía chỉ vì nhãn mới/cũ; giữ logic và regression độc lập từ cả hai phía.

## Out-of-Scope Findings

- Build đã có cảnh báo optional fontaine chưa được cài và hai asset `/assets/video/maison-coffee-poster.jpg`, `/images/auth-bg-golden.jpg` không resolve tại build time; không thêm dependency hoặc sửa asset ngoài task.
- Tám test MySQL bỏ qua thuộc DashboardRevenueMySqlTest, InventoryImportConcurrencyMySqlTest và OnlinePickupSchemaMySqlTest do yêu cầu cấu hình/opt-in riêng.
- Luồng xác nhận thu tiền chưa có backend command trong phần source đang ghép; giao diện tạm tính và Payment history giữ đúng vai trò đọc, không thêm hành vi thanh toán giả.

## Unverified Items

Responsive/keyboard/touch ở trình duyệt; chạy với dữ liệu MySQL thực tế và tranh chấp đồng thời; các test MySQL bị skip. Không đọc hoặc sửa `.env`.

## Engineer Review Required

Review diff màn hình chi tiết đơn và các thay đổi đã stage từ trước; chạy browser và MySQL test riêng trước acceptance. Engineer quyết định commit/merge; phiên này không commit/push.

## Recommended Next Task

Khôi phục môi trường browser CDP, kiểm tra 375/768/1440px và chạy MySQL regression trên schema disposable riêng. Do not implement automatically.
