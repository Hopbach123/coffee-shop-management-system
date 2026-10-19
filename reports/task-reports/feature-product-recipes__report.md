# Quản lý công thức — feature/product-recipes

## Task Status
PARTIAL: nghiệp vụ, tests và build PASS; nghiệm thu trực quan responsive/keyboard chưa thực hiện.

## Task ID
CAFE-INV-BE-002, UI quản lý công thức theo yêu cầu user ngày 13/10/2026.

## Summary
Admin vào Biến thể sản phẩm → Công thức để xem/thêm nguyên liệu, sửa định lượng và bỏ nguyên liệu khỏi công thức cho một phần của biến thể. Không thay đổi tồn kho hoặc ledger.

## Files Created
- app/Http/Controllers/Admin/ProductRecipeController.php
- app/Http/Requests/Admin/StoreProductRecipeRequest.php
- app/Http/Requests/Admin/UpdateProductRecipeRequest.php
- resources/views/admin/product-recipes/index.blade.php
- tests/Feature/ProductRecipeManagementTest.php
- reports/task-reports/feature-product-recipes__report.md

## Files Modified
- routes/admin.php: 4 nested routes, scopeBindings, middleware Admin hiện hữu.
- resources/views/admin/product-variants/index.blade.php: nút Công thức cho từng biến thể.
- requirements.md: ghi phạm vi và hành vi được user yêu cầu.

## Files Deleted
None.

## Implementation Details
Worktree sạch khi bắt đầu, đúng nhánh feature/product-recipes. Tái sử dụng Models, relationships, migrations và CSS/component/layout có sẵn. CRUD cấu hình dùng Controller → Eloquent; không thêm Service, package, schema hoặc sửa generated layer. FormRequests authorize Admin và whitelist payload. Variant lấy từ route, không nhận variant_id do client gửi. Update chỉ sửa quantity, không đổi ingredient_id.

Định lượng min 0.001/max 999999999.999, tối đa 3 chữ số thập phân theo DECIMAL(12,3). Chỉ thêm Ingredient active chưa soft-delete. Unique pair được validation và database unique index bảo vệ; collision unique sau validation được trả lỗi nhập liệu. Nested scoped binding chặn sửa/xóa recipe của variant khác. Đơn vị lấy từ Ingredient; không nhập đơn vị riêng. Biến thể unavailable vẫn cấu hình được; deleted variant trả 404. Các Ingredient cũ inactive/deleted vẫn hiển thị cảnh báo, Admin có thể sửa định lượng hoặc bỏ dòng cấu hình. Recipe không có soft delete theo Frozen Design; xóa dòng không xóa Ingredient hoặc history.

UI có empty state, labels, CSRF, method spoofing, accessible errors, dropdown dữ liệu từ database, xác nhận bỏ nguyên liệu và old input an toàn với arrays. Link truy cập từ danh sách biến thể. Không query database trong Blade, không thêm CSS/JS.

## Tests / Checks Executed
- Full php artisan test sau sửa cuối.
- Targeted ProductRecipeManagementTest trước bổ sung case history.
- npm run build.
- Pint PHP mới/tests/routes.
- php artisan route:list --path=recipes -v.
- git diff --check, git status và self-review source/diff.

## Test Results
Full suite: 167 tests / 2443 assertions PASS. Product Recipe có 7 tests. Build PASS; cảnh báo fontaine optional có sẵn. Pint PASS. 4 routes có web/auth:web/role:admin. Diff check PASS.

Fixture xác minh SQLite :memory: trước khi dùng migrations thật; bỏ ALTER CHECK chỉ hỗ trợ MySQL. Không migrate:fresh, không sửa hoặc import lại dữ liệu Laragon. Tests cover guest/staff, rendering/navigation, CRUD, quantity boundaries, arrays, inactive/deleted/duplicate Ingredient, same Ingredient in distinct variants, scoped binding/404, CSRF, old input và stock/ledger preservation.

## Acceptance Criteria
| Criterion | Result |
| --- | --- |
| Admin xem/thêm/sửa/xóa công thức theo variant | PASS: feature tests |
| Guest về login, Staff 403 | PASS: endpoint matrix |
| Định lượng dương, đúng precision/limit | PASS: create/update invalid/boundary matrix |
| Không duplicate Ingredient trong variant | PASS: validation test, schema unique và collision handling review |
| Ingredient mới active/not deleted, unit từ CSDL | PASS: selection/query/render/rejection tests |
| Không sửa công thức variant khác | PASS: scoped binding tests |
| Không đổi stock/ledger | PASS: injection và history preservation tests |
| Build/regression/formatting | PASS |
| Browser 1440/768/375, keyboard/no overflow | UNVERIFIED |

## Risks
Concurrent ingredient status/delete/unit changes giữa validation và save chưa có integration test/locking chung; cần phối hợp với task workflow kho và tiêu hao. Database unique index bảo vệ duplicate pairs. SQLite tests không chứng minh MySQL CHECK hoặc concurrency. Recipe thay đổi là cấu hình hiện hành; không có version history vì Frozen schema không cung cấp.

## Blockers
None cho backend/build; browser verification chưa hoàn tất.

## Assumptions
Định lượng cho một phần biến thể; không quy đổi đơn vị. Phạm vi user đã chốt là cấu hình độc lập, không consumption hoặc Order integration.

## Out-of-Scope Findings
Guard đổi đơn vị Ingredient trước đây chưa atomic với tạo recipe đồng thời; không refactor module nguyên liệu trong task này. Roadmap tích hợp Order/movement vẫn thuộc task sau.

## Unverified Items
Browser responsive/keyboard, MySQL integration, simultaneous-request race cases, remote CI và engineer acceptance.

## Engineer Review Required
Đăng nhập Admin → Biến thể sản phẩm → Công thức. Thử thêm Ingredient/định lượng, sửa, bỏ; thử số 0/âm/quá 3 chữ số thập phân và Ingredient đã có. Xác nhận tồn không đổi; Staff truy cập URL bị 403. Nghiệm thu viewports 1440/768/375 và keyboard. Chưa stage/commit/push.

## Recommended Next Task
Nghiệm thu UI, sau đó stock movement theo task riêng. Không tự triển khai.
