# Báo cáo luồng đăng nhập và UI nội bộ

## Task Status
PARTIAL — chức năng và build đạt; chưa kiểm chứng trực quan trên trình duyệt ở các viewport.

## Task ID
ROLE-UI-WORKFLOW — feature/role-authorization.

## Summary
Staff đăng nhập mặc định vào `/staff`. Admin vào `/admin` và được truy cập cả hai giao diện. Giữ redirect intended; truy cập trái quyền trả 403. Logout hủy session và tạo lại CSRF token.

## Files Created
- resources/views/layouts/internal.blade.php
- resources/views/admin/home.blade.php
- resources/views/staff/home.blade.php
- resources/css/auth/internal.css
- docs/REPORT_ROLE_UI_WORKFLOW.md

## Files Modified
- app/Http/Controllers/Auth/AuthenticatedSessionController.php
- bootstrap/app.php
- routes/admin.php
- routes/staff.php
- resources/css/app.css
- tests/Feature/AuthenticationTest.php
- tests/Feature/RoleAuthorizationTest.php

## Files Deleted
None. Script tạo tài khoản tạm đã được xóa sau khi chạy.

## Implementation Details
Layout Blade chung có điều hướng theo vai trò, thông tin người dùng và form logout POST với CSRF. Middleware backend vẫn quyết định quyền truy cập. Chỉ tạo khung trang đích; không thêm CRUD/POS/dashboard nghiệp vụ. Không đổi schema hoặc dependency.

## Tests / Checks Executed
`php artisan test`, `npm run build`, Pint trên PHP thay đổi, `git diff --check`, kiểm tra hash mật khẩu hai tài khoản mới trong MySQL local `coffee_shop`.

## Test Results
30 tests / 186 assertions PASS. Build PASS. Pint PASS. Diff check PASS. Hai tài khoản active được lưu bằng User model với password hashed; không ghi mật khẩu vào source.

## Acceptance Criteria
- PASS: Staff mặc định vào UI Staff; Admin mặc định vào UI Admin.
- PASS: Admin truy cập cả hai UI; Staff truy cập Admin nhận 403.
- PASS: Guest cần login; logout mất quyền vào trang nội bộ.
- PASS: Khung Blade dùng chung, điều hướng theo vai trò và build được.
- PASS: Tạo hai tài khoản local để thử đăng nhập.
- UNVERIFIED: Hiển thị và tương tác trực quan tại 1440/768/375 px.

## Risks
Chưa kiểm chứng trực quan responsive. Build có cảnh báo optional fontaine có sẵn; không cài thêm dependency.

## Blockers
None đối với backend/build; cần manual browser review để nghiệm thu UI.

## Assumptions
“User” trong task là Staff nội bộ; Customer tiếp tục sử dụng trang công khai.

## Out-of-Scope Findings
None.

## Unverified Items
Browser visual/responsive review.

## Engineer Review Required
Review diff và thử hai vai trò trên trình duyệt. Chưa commit/push. Các thay đổi workflow/UI đã có trong worktree khi tiếp tục phiên này; phiên này hoàn thiện tests và tạo tài khoản local.

## Recommended Next Task
Nghiệm thu khung UI trước khi giao triển khai từng module. Không tự triển khai task tiếp theo.
