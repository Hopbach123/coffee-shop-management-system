# Product Catalog — Bạch Ngọc Hợp

## Task Status
PARTIAL — đã triển khai và rà logic tĩnh; chưa nghiệm thu runtime theo giới hạn của task.

## Task ID
CAFE-MENU-FIX-001 — feature/product-catalog, 29/10/2026.

## Summary
Baseline dev d088fc0c, trùng origin/dev tại thời điểm đọc ref, không fetch. Ban đầu đang ở feature/product-recipes 16815e65, working tree sạch. Tạo feature/product-catalog từ dev, không mang công thức chưa merge sang. Chia xử lý: đọc/truy vết; sửa menu; sửa quan hệ/nhập liệu; rà diff. Không commit/push/merge dev.

## Files Created
- reports/task-reports/feature-product-catalog__report.md

## Files Modified
- app/Http/Controllers/Public/MenuController.php
- app/Http/Requests/Admin/StoreProductVariantRequest.php
- resources/js/public/menu-search.js
- resources/views/public/menu/index.blade.php
- resources/views/public/menu/_search-results.blade.php
- resources/views/admin/categories/_form.blade.php
- resources/views/admin/products/_form.blade.php
- resources/views/admin/product-variants/_form.blade.php
- resources/views/admin/toppings/_form.blade.php

## Files Deleted
None.

## Implementation Details
### Đã có và được giữ
- routes/web.php: Admin CRUD qua auth:web + role:admin; public menu GET không yêu cầu login. routes/admin.php nối CRUD thật của Category/Product/ProductVariant/Topping. Form mutation có CSRF, PATCH/DELETE và xác nhận xóa trong index hiện có.
- Controllers/Admin tương ứng dùng validated/safe whitelist; FormRequests giới hạn tên, FK chưa xóa, SKU unique toàn cục, tên biến thể unique theo sản phẩm, giá 12,2. Update ignore đúng model đã bind, không tin ID ignore do client gửi.
- ProductController::store/update kiểm loại ảnh JPG/PNG/WebP, 5MB qua StoreProductRequest; ghi ảnh mới rồi save DB, lỗi save dọn ảnh mới; chỉ xóa ảnh cũ sau save thành công. Soft delete giữ ảnh. Không thay đổi workflow ảnh trong task này.
- Custom Models kế thừa Base SoftDeletes; lịch sử đơn có snapshot và FK giữ tham chiếu. Không thêm hard delete/cascade.
- Menu/detail giữ baseline: danh mục active/chưa xóa; sản phẩm/biến thể soft-deleted bị ẩn; unavailable vẫn hiển thị nhãn hết món/tạm hết. Không thêm trạng thái ngừng bán mới hoặc mapping topping–sản phẩm.

### Vừa sửa
1. Full GET search trước đây tính searchCategories nhưng Blade lại render categories chưa lọc. Nay GET và AJAX dùng chung truy vấn có tìm tên và category active; kết quả rỗng dùng partial hiện có.
2. Thêm phân trang 12 sản phẩm/trang phía server, nhóm theo danh mục trên trang, giữ thứ tự display_order/category ID/product ID; eager load variants. Giữ search/category trong links; page sai kiểu/âm bị validation, vượt trang cuối trả 404. Không tải toàn bộ sản phẩm để chỉ ẩn bằng JS.
3. Category pills dùng URL GET thật thay anchor lọc trên DOM. Form có nút submit, hidden category; vẫn hoạt động không JS. AJAX giữ category, cập nhật link danh mục theo từ khóa mới; xóa tìm kiếm tải lại danh sách gốc để tránh tái hiện DOM chỉ chứa kết quả cũ. Links của fragment quay về menu.index có search/category.
4. Sửa variant không cho thay product_id: server Rule::in theo variant bind, form khóa select và gửi parent gốc. Unique tên vẫn scoped parent gốc, ignore đúng variant. Product ID dạng array không đi vào điều kiện query unique. Đây là thay đổi theo task hiện tại, khác hành vi reparent cũ.
5. Bốn form Catalog chỉ render old input scalar/null; input array bị validation không gây lỗi escape HTML hoặc ép array thành string khi render lại. Thông báo validation và dữ liệu hợp lệ vẫn giữ nguyên.

## Tests / Checks Executed
Chỉ đọc source/tests/migration/tài liệu; git status/log/branch/rev-list/diff và git diff --check. Không thực thi application, test, lint/harness, build, browser, server, migration, seed hoặc request ghi dữ liệu.

## Test Results
git diff --check: không có lỗi whitespace. Runtime NOT RUN; không có kết quả runtime PASS.

## Acceptance Criteria
- PASS (static): nhánh đúng baseline, giữ worktree ban đầu; không thay module ngoài Catalog/menu.
- PASS (static): CRUD hiện có được tái sử dụng; thêm/sửa/xóa/status nối backend, HTTP method/CSRF/quyền server hiện có.
- PASS (static): giá/precision/unique/FK validation; khóa parent variant; xử lý old input sai kiểu.
- PASS (static): GET/AJAX search, category filter, pagination và query preservation được nối source.
- PASS (static): không sửa schema/DB/dependency/shared assets hoặc lịch sử giao dịch.
- UNVERIFIED: render Blade, SQL thực tế, upload/filesystem failures, responsive/keyboard, hành vi trình duyệt và hồi quy.

## Risks
- Tests cũ ProductVariantManagementTest có kỳ vọng reparent và khôi phục variant qua đổi parent; PublicMenuTest có kỳ vọng anchor category. Những kỳ vọng này cần review/cập nhật ở task kiểm thử sau; không sửa tests theo lệnh hiện tại.
- JS chưa build nên bundle local cũ chưa chứa thay đổi. Chưa xác nhận template pagination/styles ở 375/768/1440.
- Chỉ khóa việc đổi parent bằng request trong UI phẳng Admin hiện tại; không thêm route lồng sản phẩm/variant hoặc hệ quyền thứ hai. Admin vốn có quyền quản trị mọi biến thể.
- Upload hiện có chưa được kiểm chứng lại; trường hợp upload/update đồng thời cần kiểm thử riêng trước nghiệm thu.

## Blockers
None cho sửa source tĩnh. Không có quyền kiểm thử runtime trong task này.

## Assumptions
12 món/trang là lựa chọn trình bày; không đổi giới hạn nghiệp vụ. unavailable vẫn nhìn thấy như baseline; soft delete/danh mục inactive mới loại khỏi public menu. Topping dùng chung.

## Out-of-Scope Findings
Ninh cần review giới hạn cents của OrderService so với DECIMAL(15,2), rule category inactive trên POS, cùng việc Sales kiểm giá/availability server. Không sửa OrderService/Payment/Promotion/Auth/kho/công thức.

## Unverified Items
Toàn bộ runtime/test/build/browser/SQL/upload concurrency. Không tái sử dụng báo cáo test cũ làm kết quả mới.

## Engineer Review Required
Review diff, đặc biệt thay đổi không cho reparent variant; sau khi cho phép kiểm thử cần cập nhật kỳ vọng cũ và xác minh menu/filter/pagination/AJAX/upload/quyền/lịch sử.

## Recommended Next Task
Kiểm chứng riêng nhánh product-catalog khi engineer cho phép. Không tự triển khai hoặc chuyển nhánh.

Chỉ rà logic từ source/diff; không chạy test, build, browser, migration hoặc seed.
