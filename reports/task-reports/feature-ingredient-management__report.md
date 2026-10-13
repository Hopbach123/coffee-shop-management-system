# Quản lý nguyên liệu — feature/ingredient-management

## Task Status
PARTIAL: backend, Blade rendering, test và build PASS; kiểm chứng trực quan responsive chưa hoàn tất.

## Task ID
CAFE-INV-BE-001 / CAFE-INV-FE-001, ngày 13/10/2026.

## Summary
Admin quản lý nguyên liệu tại `/admin/ingredients`: danh sách, tạo, sửa, active/inactive, xóa mềm, tìm theo tên và phân trang 15 bản ghi. Tồn kho chỉ đọc; dữ liệu mới dùng mặc định 0 từ database. Task hiện tại được user yêu cầu riêng; không triển khai Recipe, movement hoặc consumption theo roadmap.

## Files Created
- app/Http/Controllers/Admin/IngredientController.php
- app/Http/Requests/Admin/StoreIngredientRequest.php
- app/Http/Requests/Admin/UpdateIngredientRequest.php
- resources/views/admin/ingredients/index.blade.php
- resources/views/admin/ingredients/create.blade.php
- resources/views/admin/ingredients/edit.blade.php
- resources/views/admin/ingredients/_form.blade.php
- tests/Feature/IngredientManagementTest.php
- reports/task-reports/feature-ingredient-management__report.md

## Files Modified
- routes/admin.php: resource routes và PATCH toggle-status.
- resources/views/layouts/internal.blade.php: link Nguyên liệu chỉ hiện cho Admin.

## Files Deleted
None.

## Implementation Details
Worktree sạch khi bắt đầu, đúng nhánh đã có. Đọc AGENTS, architecture, requirements, frontend, plan, schema Frozen và source/test Topping trước khi triển khai. Controller → Eloquent cho CRUD; không thêm Service cho metadata đơn giản. Middleware auth:web + role:admin; FormRequests kiểm tra quyền và whitelist dữ liệu. Validation theo migration: name tối đa 255, unit tối đa 50, minimum_stock không âm với tối đa 3 chữ số thập phân (DECIMAL 12,3), cost_price không âm với tối đa 2 chữ số thập phân (DECIMAL 12,2), status active/inactive. Không thêm unique name hoặc enum đơn vị tính.

current_stock không nằm trong validated payload; create không ghi trường này, update/status/delete giữ nguyên giá trị. Xóa mềm giữ record, không hard-delete hoặc cascade các quan hệ. Không thay migration, Model/Base Model, schema, dependency, `.env` hoặc dữ liệu local. Recipe/movement/ledger chưa được triển khai trong nhánh này.

Blade sử dụng layout internal, public.button và CSS quản trị hiện hữu; form có labels, lỗi với aria-invalid/aria-describedby, CSRF và method spoofing. Bảng sử dụng data-label cho chế độ mobile hiện có. Không thêm CSS/JS hoặc framework.

## Tests / Checks Executed
- `php artisan test --filter=IngredientManagementTest`: 8 tests / 181 assertions PASS.
- `php artisan test`: 125 tests / 1758 assertions PASS.
- `npm run build`: PASS.
- Pint trên PHP mới và routes/admin.php: PASS sau formatting.
- `php artisan route:list --path=admin/ingredients -v`: 7 routes, có web/auth:web/role:admin.
- `git diff --check`: PASS; review routes/layout/new source/tests.
- Edge headless thử chạy: không thu được DOM, báo lỗi OS encryption/registry; không coi là browser PASS.

## Test Results
Tests dùng SQLite :memory:, xác minh target trước khi tạo fixture với hai migrations users/ingredients thật. Không chạy migrate:fresh hoặc thao tác database persistent. Test UI render 200, phân quyền tất cả endpoints, whitelist và tồn không đổi, numeric/schema boundaries, old input/ARIA errors, trạng thái, soft delete/404, search/pagination và CSRF.

## Acceptance Criteria
| Criterion | Result |
| --- | --- |
| Admin tạo/xem/sửa nguyên liệu | PASS: CRUD và Blade render tests |
| Guest về login; Staff bị chặn | PASS: endpoint matrix |
| Validation đúng schema | PASS: create/update boundary tests |
| Không sửa tồn qua CRUD | PASS: injection tests create/update; status/delete giữ tồn |
| Active/inactive và xóa mềm | PASS: status/soft-delete tests |
| Search theo tên, pagination, empty states | PASS: query/render tests |
| Layout hiện hữu, accessible errors, CSRF | PASS: source review và rendering/security tests |
| Build và regression | PASS |
| Browser tại 1440/768/375, keyboard/no overflow | UNVERIFIED |

## Risks
Responsive/keyboard chưa kiểm chứng bằng browser; tests render không thay thế visual review. SQLite tests không thay thế MySQL constraints/FK review. Thay đổi đơn vị tính không thực hiện chuyển đổi tồn hoặc công thức; cần quy tắc riêng khi triển khai Recipe/movement. Build cảnh báo optional fontaine hiện hữu; không cài package.

## Blockers
None cho backend/build. Browser verification chưa có kết quả vì Edge headless không hoạt động trong phiên kiểm tra.

## Assumptions
Phạm vi hiện tại là metadata Ingredient Admin; nhập/xuất/tự động trừ kho thuộc nhánh tiếp theo. Page size 15 và CSS dùng convention module hiện hữu.

## Out-of-Scope Findings
plan.md vẫn có dependency inventory tổng thể trên Order; user đã yêu cầu CRUD Ingredient độc lập trước tích hợp Order. Không sửa roadmap hoặc triển khai Order/Recipe/InventoryService.

## Unverified Items
Browser responsive/keyboard, MySQL integration, sự bảo toàn ledger/recipe trong workflow chưa triển khai, remote CI và engineer acceptance.

## Engineer Review Required
Review diff; đăng nhập Admin → Nguyên liệu → thêm/sửa/đổi trạng thái/xóa/tìm kiếm. Thử Staff truy cập trực tiếp URL để xác nhận 403. Nghiệm thu các viewport 1440/768/375 và keyboard. Chưa commit/push.

## Recommended Next Task
Nghiệm thu UI, sau đó feature/product-recipes. Không tự triển khai.

## Rà soát và bổ sung nhập liệu — 13/10/2026

Phần này cập nhật kết luận ở trên: đơn vị không còn nhập tự do. Trạng thái task vẫn PARTIAL do chưa nghiệm thu trực quan bằng browser.

### Summary / Implementation Details
- Danh sách đơn vị chuẩn dùng chung backend và select: g, kg, ml, l, cái. Danh sách nằm trong config ứng dụng, không truy vấn bảng đơn vị vì schema không có bảng này. Không thêm migration/enum và không tự quy đổi.
- Update FormRequest chặn đổi đơn vị khi tồn khác 0, đã có công thức hoặc đã có lịch sử kho, kể cả tồn đã về 0. Metadata khác vẫn sửa được nếu giữ nguyên đơn vị.
- Giữ validation số không âm, giới hạn DECIMAL, trạng thái, quyền Admin và bảo vệ current_stock hiện có.
- Sửa form để input sai kiểu array trả lỗi validation và render lại an toàn, tránh lỗi 500 khi escape old input.
- Ghi phạm vi nhập liệu trong requirements.md. Không áp quy tắc tên duy nhất vì baseline không quy định.

### Files Created / Modified / Deleted
- Created: config/inventory.php.
- Modified trong lần rà soát: StoreIngredientRequest.php, UpdateIngredientRequest.php, ingredients/_form.blade.php, IngredientManagementTest.php, requirements.md và report này.
- Deleted: None.
- Các source CRUD, SQL seed/verify, hướng dẫn SQL, report seed, routes và sidebar đã tồn tại trong worktree trước lần rà soát này; giữ nguyên. Không reset staging hoặc stash của user.

### Tests / Checks Executed / Test Results
- Full suite sau sửa cuối: PASS, 129 tests / 1804 assertions. Bao gồm 12 tests Ingredient.
- npm run build: PASS; warning optional fontaine hiện hữu.
- Pint các PHP chỉnh sửa và config: PASS.
- git diff --check và git diff --cached --check: PASS.
- Fixture SQLite :memory: xác minh target; dùng schema migrations thật, bỏ ALTER CHECK riêng MySQL. Không sửa dữ liệu Laragon trong lần này.
- Một lần test fixture thất bại vì categories không có slug; đã sửa fixture theo migration và chạy lại full suite PASS.

### Acceptance Criteria
- PASS: chỉ nhận đơn vị chuẩn, select thay input tự do; chống payload tự gửi đơn vị ngoài danh sách.
- PASS: chặn đổi đơn vị khi có stock/recipe/history; cho đổi nguyên liệu chưa dùng và giữ nguyên history khi sửa metadata.
- PASS: ràng buộc bắt buộc/kiểu dữ liệu/giới hạn số; malformed arrays không gây lỗi render.
- PASS: regression phân quyền, CRUD, soft delete, stock injection, tìm kiếm và phân trang.
- UNVERIFIED: visual/keyboard tại 1440/768/375; browser chưa có kết quả thành công.

### Risks / Blockers / Assumptions
- Risks: kiểm tra quan hệ ở request chưa bảo đảm atomic với workflow kho/công thức chạy đồng thời trong tương lai; khi triển khai các workflow đó cần phối hợp transaction/locking trên Ingredient và test concurrency.
- Risks: dữ liệu cũ có đơn vị ngoài danh sách sẽ không lưu được qua form nếu giữ đơn vị đó; nguyên liệu đã dùng cần quy trình chuyển đổi riêng, không sửa trực tiếp lịch sử.
- Blockers: None cho validation/test/build; browser verification chưa hoàn tất.
- Assumptions: danh sách chuẩn phục vụ nguyên liệu mẫu hiện tại và các đơn vị khối lượng/thể tích/cái; chưa hỗ trợ quy đổi hay đơn vị bao/thùng.

### Out-of-Scope Findings / Unverified Items
- Recipe CRUD, movement, consumption, concurrency integration và remote CI không thuộc lần sửa này.
- MySQL integration của validation và nghiệm thu UI: UNVERIFIED trong lần này. SQL import đã được ghi nhận ở report seed riêng; không import lại.

### Engineer Review Required / Recommended Next Task
Review danh sách đơn vị, diff và kiểm thử giao diện tại các viewport. Stage lại các file có trạng thái AM/M và config mới trước commit; chưa commit/push. Nhiệm vụ đề xuất tiếp theo: nghiệm thu UI, sau đó triển khai recipe theo task riêng; không tự thực hiện.

## Bổ sung đơn vị Khác theo yêu cầu user

- Task Status: PARTIAL; backend/tests/build PASS, browser responsive vẫn UNVERIFIED.
- Task ID: CAFE-INV-BE-001 / CAFE-INV-FE-001 — custom unit input.
- Summary / Implementation Details: thêm lựa chọn Khác và field custom_unit. Chỉ khi chọn Khác mới dùng field này, chuẩn hóa chữ thường/khoảng trắng và lưu tên cụ thể vào ingredients.unit; không lưu sentinel other, không thêm schema. Validation required/string/max50, có chữ cái, chỉ cho phép chữ/số/khoảng trắng/dấu chấm/gạch ngang/dấu /. Payload custom_unit bị bỏ qua khi chọn đơn vị chuẩn. Guard stock/recipe/history áp dụng sau chuẩn hóa như trước.
- Files Created: None. Files Modified: StoreIngredientRequest.php, ingredients/_form.blade.php, IngredientManagementTest.php, requirements.md, report này. Files Deleted: None. Các thay đổi staging đã có được giữ nguyên.
- Tests / Checks Executed / Test Results: Ingredient 14 tests PASS; full suite 131 tests / 1833 assertions PASS; npm run build PASS; Pint PASS; git diff --check PASS.
- Acceptance Criteria: PASS lưu/hiển thị lại đơn vị Khác; PASS chuẩn hóa và từ chối rỗng/array/quá dài/chỉ số/HTML; PASS giữ nguyên đơn vị custom khi đã có tồn nhưng chặn đổi; PASS bỏ qua custom field khi chọn chuẩn. Browser/keyboard: UNVERIFIED.
- Risks: không có quy đổi đơn vị hoặc danh mục đơn vị riêng; concurrent movement/recipe cần phối hợp locking trong task tích hợp tiếp theo. Blockers: None cho backend. Assumptions: Khác là đơn vị tính, không phải loại nguyên liệu.
- Out-of-Scope Findings: None mới. Unverified Items: responsive/browser và concurrency integration. Engineer Review Required: kiểm tra thêm/sửa với đơn vị chai/túi/hộp và lỗi nhập liệu. Recommended Next Task: nghiệm thu UI, không tự triển khai task tiếp theo. Chưa commit/push.
