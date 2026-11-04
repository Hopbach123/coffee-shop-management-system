# Fix PR #31 CI — 05/11/2026

## Task Status
SUCCESS cho sửa lỗi được tái hiện local; GitHub matrix chưa chạy lại.

## Task ID
CAFE-MENU-FIX-CI-002

## Summary
feature/product-catalog tại e3c653b8, working tree ban đầu sạch. Log GitHub #74 báo 25 failed, 5 skipped, 361 passed. Đã sửa lỗi tích hợp template và chạy lại suite sau khi xóa cache Blade; không thay tests để né lỗi.

## Files Created
reports/task-reports/fix-product-catalog-ci__report.md

## Files Modified
- resources/views/components/public/navbar.blade.php
- resources/views/components/public/footer.blade.php
- resources/views/public/home/index.blade.php
- resources/views/public/menu/show.blade.php
- resources/views/public/menu/_product-card.blade.php
- resources/views/layouts/internal.blade.php
- resources/views/staff/brewing/index.blade.php

## Files Deleted
None.

## Implementation Details
- Route reservation.create chưa tồn tại: dùng section home#reservation khi chưa có route. Áp dụng ở navbar, footer và CTA trang chủ.
- Chỉ hiển thị link cart.index/form cart.add khi route thật tồn tại; không tạo route placeholder hoặc thực thi mua hàng giả.
- Footer sử dụng lại shop-location đã có thay đoạn địa chỉ tĩnh, giữ Maps config và các kiểm tra URL an toàn của component.
- Layout Admin sử dụng lại admin-sidebar động có Service/quyền/recovery; giữ sidebar Staff, bổ sung link Dashboard thật. Không sửa Service hoặc registry.
- Card và chi tiết món dùng cùng fallback M khi file ảnh thiếu, giữ imageUrl và ảnh thật.
- Đồng bộ empty-state pha chế với hợp đồng hiện có: Chưa có món nào.

## Tests / Checks Executed
- Đọc log CI đính kèm, source và tests liên quan.
- php artisan view:clear trước suite cuối, tránh kết quả từ compiled Blade cũ.
- PHP 8.3.30: php artisan test --compact; phpunit.xml dùng SQLite :memory: và session array.
- npm run build.
- git diff/status và git diff --check.
- RTK không có trong PATH; dùng lệnh trực tiếp sau khi xác minh.

## Test Results
- Suite cuối: 391 tests, 386 passed, 5 skipped, 5435 assertions, 0 failed; 33.377 giây. Log ignored: storage/logs/catalog-ci-pass.log.
- Build thành công; cảnh báo optional fontaine và ảnh poster/auth được resolve runtime còn nguyên, không cài dependency.
- Diff check không lỗi whitespace.
- Các lượt đầu dùng cache cho kết quả không đủ tin cậy; chỉ dùng lượt cuối sau view:clear làm evidence.

## Acceptance Criteria
- PASS: route chưa có không làm các trang dùng template public lỗi 500.
- PASS: sidebar động, địa điểm, fallback ảnh và empty-state đáp ứng tests hiện tại.
- PASS: toàn bộ suite local không có failed và build thành công.
- UNVERIFIED: PHP 8.4/8.5 và GitHub CI sau push.

## Risks
5 tests opt-in bị skip; chưa kiểm browser responsive. Không coi suite local thay thế nghiệm thu toàn bộ nghiệp vụ.

## Blockers
None cho local fix.

## Assumptions
Module public cart/reservation chưa có route thì ẩn thao tác cart và dùng section đặt bàn hiện có; khi route được tích hợp, template tự sử dụng route thật.

## Out-of-Scope Findings
Không triển khai backend cart/reservation hoặc thay chính sách tiền/kho. Không migration/seed/thay DB dự án.

## Unverified Items
Remote matrix, browser, 5 skipped tests.

## Engineer Review Required
Review 7 Blade files; commit/push rồi quan sát GitHub Actions. Chưa commit/push trong task.

## Recommended Next Task
Chạy lại CI sau khi engineer push. Không tự thực hiện task khác.
