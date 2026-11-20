# Áp mã thực tế và bảo vệ snapshot

## Task Status

PARTIAL — các tiêu chí backend/HTTP đạt, nghiệm thu trực quan cần đăng nhập Admin.

## Task ID

CAFE-FINANCE-PROMO-001, thuộc CAFE-FINANCE-COMPLETE-001, 18/11/2026.

## Summary

Áp mã thực tế và bảo vệ snapshot. Phạm vi và xác nhận xem prompts/CAFE-FINANCE-COMPLETE-001.md. Worktree ban đầu sạch trên feature/payment-management, HEAD 914e6398.

## Files Created

- app/Http/Requests/Payment/ApplyOrderPromotionRequest.php

## Files Modified

- app/Http/Controllers/Staff/PaymentController.php
- app/Services/PromotionService.php
- resources/views/staff/payments/show.blade.php
- routes/staff.php
- tests/Feature/PaymentWorkflowTest.php
- tests/Feature/PromotionServiceTest.php

## Files Deleted

None.

## Implementation Details

Áp mã thực tế và bảo vệ snapshot. Chi tiết primitives, schema, receipt semantics, trường hợp tương thích và ownership nằm trong báo cáo cha CAFE-FINANCE-COMPLETE-001__report.md cùng contract cập nhật. Các thay đổi shared được liệt kê tại từng task liên quan.

## Tests / Checks Executed

- Targeted Finance/Promotion/POS/web trên SQLite :memory: — 74/74 pass, 736 assertions.
- Full suite mặc định — 499 tests, 488 pass, 11 skip, 6754 assertions, 0 fail/error.
- FinanceMySqlTest opt-in — 1/1 pass, 60 assertions trên database QA riêng và hai process.
- npm.cmd run build — PASS. Có warning optional fontaine và hai đường dẫn ảnh chưa resolve ở build time.
- PHP lint cho file PHP/Blade đã đổi — PASS. git diff --check — PASS.
- route:list — 157 routes, 0 named route trùng. GET admin/finance và POST staff/orders/{order}/promotion đã được khai báo.
- Migration Finance local — batch 4 Ran. Đã kiểm tra 5 Payment trước/sau, metadata lịch sử giữ null.
- MySQL local read-only report năm 2026 — total 163000.00, cash 63000.00, bank 100000.00, 3 paid Order.

## Test Results

HTTP apply với server discount, guest/inactive, paid/web guards, quota regression đều PASS.

## Acceptance Criteria

- PASS — HTTP apply với server discount, guest/inactive, paid/web guards, quota regression đều PASS.
- PASS — tests/build/routes/lint/diff checks.
- UNVERIFIED — visual 1440/768/375 và keyboard sau login.

## Risks

- Cần chạy migration mới trước khi triển khai source receipt mới trên máy khác.
- Rollback migration làm mất metadata khóa/tiền nhận/tiền thừa, cần bảo toàn trước khi downgrade.
- Chứng từ lịch sử giữ bank_reference_hash null. Writer đối chiếu tham chiếu cũ, unique hash bảo vệ các tham chiếu được ghi mới.
- Các operations POS/web/COD hiện hữu tiếp tục được gọi bởi UI hiện tại. API Finance mới sẵn cho caller Sales, tích hợp vào nhánh khác của Ninh cần review source nhánh nhận.
- 11 tests opt-in bị skip trong full suite mặc định. Finance MySQL mới đã chạy riêng, các nhóm opt-in cũ còn theo environment của chúng.

## Blockers

Browser đã mở ứng dụng local và đến trang login. Bước đăng nhập Admin cần người dùng thực hiện theo skill Computer Use. Đã gửi yêu cầu hỗ trợ đăng nhập. Chưa kiểm tra trực quan các trang mới sau login ở 1440/768/375 hoặc keyboard, nên phần này UNVERIFIED. HTTP page render, authorization và build đã pass qua tests.

## Assumptions

Task thuộc yêu cầu triển khai tổng Finance hiện hành của Đạt. Các xác nhận về schema và reporting đã được ghi vào requirements §15.

## Out-of-Scope Findings

Xem báo cáo cha. Gateway/refund và các metric ngoài bộ chỉ số mới được duyệt vẫn theo roadmap.

## Unverified Items

Visual QA sau login và tích hợp vào nhánh nhận khác của Ninh.

## Engineer Review Required

Review file/diff, contract và các giới hạn nêu trên. Manual steps tại docs/FINANCE_MANUAL_TEST.md.

## Recommended Next Task

Nghiệm thu trực quan và review kết quả cùng task cha. Quyết định commit/merge thuộc engineer.
