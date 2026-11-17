# Hoàn thiện điều chỉnh / kiểm kê kho — 06/11/2026

## Task Status
PARTIAL — implementation, workflow, MySQL concurrency, regression và build đạt; chưa nghiệm thu trực quan responsive/keyboard.

## Task ID
CAFE-INV-BE-005 — H05 / feature/inventory-adjustments.

## Summary
User yêu cầu hoàn thiện nhánh sau nhập kho. Ban đầu ở dev e6e926e7 (PR #33), worktree sạch. Nhánh feature/inventory-adjustments đã tồn tại trùng baseline; chuyển sang nhánh đó, không fetch nên không tuyên bố mới nhất remote. Không commit/push/merge. RTK không có trong PATH, dùng CLI trực tiếp. Git switch cần escalation do sandbox không ghi .git; đã thực hiện thành công.

Chia rules/Service → UI/routes → tests/build/report theo plan. Tận dụng ledger, integer milliunits, layout và history hiện có. Không sửa consume/import, migration, Base Model hoặc dữ liệu dự án.

## Files Created
- app/Http/Controllers/Admin/StockAdjustmentController.php
- app/Http/Requests/Admin/StoreStockAdjustmentRequest.php
- resources/views/admin/ingredients/adjustment.blade.php
- tests/Feature/StockAdjustmentWorkflowTest.php
- docs/HUONG_DAN_DIEU_CHINH_KIEM_KE_KHO.md
- reports/task-reports/feature-inventory-adjustments__report.md

## Files Modified
- app/Services/InventoryService.php: recordAdjustment, dùng lại quantity conversion hiện có.
- routes/admin.php: GET/POST adjustment trong Admin group hiện hữu.
- config/admin_menu.php: active parent cho trang adjustment.
- resources/views/admin/ingredients/index.blade.php
- resources/views/admin/ingredients/history.blade.php
- tests/Feature/InventoryImportConcurrencyMySqlTest.php: thêm ca adjustment vào harness cũ, giữ ca import.
- tests/Support/stock-import-worker.php: hỗ trợ command adjustment, kết quả từ chối nghiệp vụ không lộ SQL/config.
- requirements.md: ghi rõ workflow đã được user yêu cầu.
- plan.md: task charter/dependency/AC và ba bước triển khai.

## Files Deleted
None.

## Implementation Details
Admin active → nguyên liệu active → điều chỉnh tăng/giảm/waste/stocktake → xác nhận → lịch sử chỉ đọc. Note bắt buộc tối đa 1000; quantity dương trừ stocktake được 0; giới hạn DECIMAL(12,3). Stocktake ghi chênh lệch tuyệt đối dương vào adjustment, không thay schema. Không chênh lệch không ghi movement 0. Đây là kiểm kê một nguyên liệu để điều chỉnh, không có bảng biên bản hoặc quy trình hai người duyệt.

Token Crypt gắn purpose/actor/ingredient/unit/key, snapshot stock + ID ledger cuối. Request không tin actor/type/before/after client. Service đọc actor/ingredient dưới lockForUpdate, cùng transaction retry 3 lần; token đã ghi cùng dữ liệu trả ledger cũ, khác dữ liệu bị từ chối. Stocktake mới bị từ chối nếu mốc thay đổi, kể cả ABA (tăng rồi giảm về giá trị cũ). Waste/giảm không thể âm; tăng không overflow. Save bị event hủy hoặc lỗi ledger rollback cả tồn. Không cấp endpoint sửa/xóa ledger.

Form dùng layout/token/component hiện có, label/errors/old input an toàn khi array, CSRF/POST, confirm trước ghi và link mở phiếu mới khi kiểm kê lỗi thời. Không thêm CSS/JS/dependency hoặc sửa màn ngoài kho.

## Tests / Checks Executed
- Đọc AGENTS/RTK, architecture/requirements/plan/frontend, migration/ledger/Controller/Request/Blade/tests và assignment mục 7 đã đối chiếu ở lần rà trước.
- StockAdjustmentWorkflowTest: 4 tests PASS / 117 assertions / 5.785s.
- INVENTORY_IMPORT_MYSQL_TEST=1 InventoryImportConcurrencyMySqlTest: 2 tests PASS / 50 assertions / 9.295s.
- Full suite: 415 tests / 408 passed / 7 skipped / 5801 assertions / 0 failed / 102.824s.
- Pint 6 PHP files đã chạy; npm run build PASS; git diff --check PASS.
- Browser local khởi động tại 127.0.0.1:8000; URL adjustment chuyển về login do không có phiên. Không submit form vào database dự án.
- Logs ignored: storage/logs/adjustment-mysql.log, adjustment-regression.log, adjustment-build.log.

## Test Results
Workflow kiểm bốn mode, before/after/actor/type, gửi trùng/đổi note, bounds/arrays/token/quyền, stale stocktake/ABA, overflow/thiếu tồn, rollback khi save event hủy, unit/status/deleted, CSRF. MySQL hai workers kiểm duplicate decrease chỉ một log, hai waste tranh tồn chỉ một thành công, hai stocktake cùng snapshot chỉ một thành công và chain ledger liên tục. Harness xác minh tên DB ngẫu nhiên trước cleanup; không reset coffee_shop. Các test opt-in khác skip không được coi PASS; hai ca MySQL phía trên đã chạy riêng. Build còn warning fontaine optional và ảnh resolve runtime có sẵn; không cài dependency.

## Acceptance Criteria
- PASS: active Admin only, whitelist payload, token actor/ingredient/purpose, CSRF.
- PASS: tăng/giảm/waste/stocktake, note/precision/range, tồn không âm/overflow.
- PASS: atomic ledger/rollback, retry không ghi đôi; stale stocktake bị từ chối.
- PASS: render form/history navigation/old input qua HTTP tests, full regression/build.
- PASS: concurrency MySQL của adjustment và import cũ.
- UNVERIFIED: browser 375/768/1440, keyboard/confirm trực quan và remote CI PHP 8.4/8.5.

## Risks
Operation key ngẫu nhiên 18 chữ số, khóa ingredient và convention reference chống lặp; không thêm DB unique. SQL trực tiếp ngoài Service không được bảo vệ. Recipe/consume concurrency không được mở rộng trong nhánh này. Stocktake có thể yêu cầu mở lại phiếu khi có biến động hợp lệ, đây là bảo vệ dữ liệu chứ không tự merge chênh lệch cũ. Không lưu phiên kiểm kê không chênh lệch vì schema movement yêu cầu quantity > 0.

## Blockers
Không có blocker code/test. Browser thiếu phiên Admin nên chưa nghiệm thu UI thực tế.

## Assumptions
Admin ghi là xác nhận; chưa tạo request/approval hai người. Mỗi phiếu cho một nguyên liệu đang dùng; không quy đổi, supplier, batch hoặc accounting. Không thêm reserve/release theo phạm vi rút gọn.

## Out-of-Scope Findings
Low-stock indicator và recipe rỗng/consume review thuộc các task tiếp theo; không tự sửa. Không reset mật khẩu hoặc seed menu local.

## Unverified Items
Browser responsive/keyboard; remote CI; toàn bộ workflow kho xuyên Sales không được nghiệm thu chỉ từ test adjustment.

## Engineer Review Required
Review diff, đăng nhập Admin rồi Nguyên liệu → Điều chỉnh / kiểm kê theo hướng dẫn; xác nhận viewports và thao tác bàn phím. Không có commit/push tự động.

## Recommended Next Task
Nghiệm thu UI nhánh hiện tại. Theo thống nhất tiếp theo của user, gộp cảnh báo tồn thấp H03 và rà/hoàn thiện tiêu hao H04/H06 vào nhánh dự kiến feature/inventory-completion. Phần này mới là kế hoạch, chưa triển khai; chốt rule công thức rỗng trước khi code. Không tự triển khai. Chỉ mục tiến độ/report: [Bạch Ngọc Hợp](BACH_NGOC_HOP__TIEN_DO_USE_CASE.md).
