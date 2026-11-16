# Nhập kho nguyên liệu và fix bàn giao công thức

## Task Status
PARTIAL — code nhập kho, tests local, build và hai ca concurrency MySQL đã đạt; UI responsive chưa nghiệm thu.

## Task ID
CAFE-INV-BE-003-IMPORT — 05/11/2026.

## Summary
User duyệt sửa lỗi bàn giao công thức và triển khai đầy đủ nhánh inventory-transactions. Bắt đầu ở feature/inventory-transactions e629652d, worktree sạch. Nhận dev 1ba4b8fa (đã merge PR #32 công thức) bằng merge --no-commit. Hai conflict add/add ở InventoryService và InventoryServiceTest được hợp nhất, giữ nguyên logic consume của dev và nhập kho của nhánh cũ. Test nhập kho cũ tách tên InventoryImportServiceTest để không ghi đè bộ tiêu hao.

Git còn merge chưa commit; nhiều file staged là code đã có từ dev, không phải phần triển khai mới. Không commit/push/merge vào dev. RTK không có trong PATH; dùng CLI trực tiếp. Một lượt permission review timeout, lần retry đánh dấu resolved thành công; không có rejection do rủi ro.

## Files Created
- app/Http/Controllers/Admin/StockImportController.php
- app/Http/Requests/Admin/StoreStockImportRequest.php
- resources/views/admin/ingredients/import.blade.php
- tests/Feature/InventoryImportServiceTest.php (giữ tests nhập kho cũ, đổi tên class)
- tests/Feature/StockImportWorkflowTest.php
- docs/HUONG_DAN_NHAP_KHO.md
- reports/task-reports/feature-inventory-transactions__report.md
- tests/Feature/InventoryImportConcurrencyMySqlTest.php
- tests/Support/stock-import-worker.php

## Files Modified
So với dev, phần task trực tiếp:
- app/Services/InventoryService.php: thêm recordImport/historyFor, giữ consume hiện tại.
- routes/admin.php: GET/POST nhập kho theo Ingredient bind.
- config/admin_menu.php: active route recipe và import trong sidebar động.
- resources/views/admin/ingredients/index.blade.php: nút nhập kho cho nguyên liệu active.
- resources/views/admin/ingredients/history.blade.php: mở phiếu mới từ lịch sử.
- tests/Feature/DynamicAdminSidebarTest.php: kiểm active recipe/import.
- reports/task-reports/feature-product-recipes__report.md: đính chính baseline bàn giao đã commit/merge.

package-lock.json được trả đúng nội dung dev để loại thay đổi dependency không liên quan từ nhánh nhập kho cũ. tests/Feature/InventoryServiceTest.php giữ đúng bộ tiêu hao của dev. Các file còn lại trong merge index là tích hợp baseline, không viết lại trong task.

## Files Deleted
None.

## Implementation Details
- Admin active → Nguyên liệu → Nhập kho → nhập lượng/note → ledger chỉ đọc. Quyền auth/role do group hiện có kiểm tra; FormRequest kiểm lại actor.
- Quantity dương, tối thiểu .001, tối đa 999999999.999, 3 thập phân; kiểm tổng tồn sau nhập. Mảng/chữ/quá dài/âm/0 bị chặn. Không tự đổi đơn vị hoặc giá vốn.
- Service đọc lại actor và ingredient dưới lockForUpdate, cộng bằng integer phần nghìn; tồn + ledger cùng DB transaction retry tối đa 3. Lỗi/cancel save bất kỳ không báo success giả và rollback.
- Phiếu có token mã hóa gắn key ngẫu nhiên, ingredient, actor, unit. Controller luôn bắt buộc token. Ledger lưu reference_type stock_import/reference_id key; lookup dưới khóa ingredient chống double-submit. Retry cùng dữ liệu trả ledger đã có, đổi dữ liệu từ chối.
- Token không dùng cache/session làm nguồn kết quả nên lỗi ghi session không làm mất dấu chứng từ. Đơn vị thay đổi trước lần ghi đầu tiên khiến phiếu cũ bị từ chối.
- Mã actor/type/current_stock client gửi bị bỏ qua. Nguyên liệu inactive hoặc deleted không nhập mới; old input sai kiểu được render an toàn; CSRF và đúng method.
- Sidebar sửa tại config thay vì đoạn loop Staff không chạy cho Admin. Report công thức ghi rõ trạng thái hiện tại thay vì lấy kết quả lịch sử làm evidence.
- Không thêm schema, migrate, import SQL, seed dữ liệu vào coffee_shop hoặc sửa Order/Payment/Recipe lifecycle.

## Tests / Checks Executed
- Đọc source, migrations, PHP casts, route/Blade và tests hiện có.
- phpunit.xml dùng SQLite :memory:, array session/cache; các test tự assert target và tạo fixtures riêng.
- Nhóm InventoryService/InventoryImportService/StockImportWorkflow: 13 tests, 127 assertions PASS.
- Hồi quy toàn dự án trước test sidebar mới: 408 tests, 403 passed, 5 skipped, 5674 assertions, 0 failed; log ignored storage/logs/inventory-full.log.
- Sidebar sau bổ sung: 9 tests, 78 assertions PASS; log inventory-sidebar.log.
- Full suite cuối với test sidebar mới: 409 tests, 404 passed, 5 skipped, 5684 assertions, 0 failed; 30.222 giây. Log ignored: storage/logs/inventory-final.log.
- Pint cho Service/Controller/Request/tests nhập kho; npm run build thành công.
- git diff dev/status/diff check; không còn unmerged paths.

## Test Results
Các nhóm đã hoàn tất ở trên đạt. Build có warning optional fontaine và poster/auth image được resolve runtime, không cài dependency. Test session dùng môi trường test, không ghi database dự án. Các lượt bị timeout không được tính là test pass.

## Acceptance Criteria
- PASS: tận dụng Service cũ và giữ tiêu hao dev, không duplicate class tests.
- PASS: form nhập/lịch sử/quyền/validation/token và bảo vệ dữ liệu lỗi qua feature tests.
- PASS: ledger actor/before/after chính xác, rollback khi insert lỗi hoặc save bị hủy, retry không cộng hai lần.
- PASS: active sidebar cho recipe/import và report được đính chính.
- PASS: build và regression local đã chạy không failed.
- PASS: MySQL 8.4 hai workers cùng phiếu chỉ cộng tồn một lần; hai phiếu của hai Admin cộng đủ, ledger nối đúng (1 test/21 assertions).
- UNVERIFIED: browser 375/768/1440 và keyboard, PHP 8.4/8.5 remote CI.

## Risks
- SQLite không mô phỏng lockForUpdate. Đã bổ sung test MySQL với hai PHP workers, kiểm duplicate và independent imports trên cùng ingredient dưới khóa; không thay thế kiểm thử tải hoặc mọi trường hợp đồng thời khác. Phải dùng cùng Service cho caller mới.
- Lời gọi recordImport cũ không operationKey vẫn là lần nhập độc lập. HTTP mới luôn yêu cầu token.
- Reference key là số ngẫu nhiên 18 chữ số, không có unique constraint mới; xác suất va chạm thấp và payload khác bị từ chối thay vì overwrite. Chống lặp dựa vào khóa ingredient và convention, không bảo vệ SQL viết trực tiếp ngoài Service.
- Đã xem form nhập kho trên Edge local ở cửa sổ hiện tại (ảnh 791×842, zoom 80%); chưa kiểm đủ responsive/keyboard. Không coi build PASS là bằng chứng layout.
- Có nhiều staged changes do merge baseline; review diff so dev để thấy phần nghiệp vụ task, rồi review merge trước commit.

## Blockers
None cho implementation/tests local. Chưa hoàn tất nghiệm thu browser.

## Assumptions
Nhập kho chỉ cho nguyên liệu đang dùng; ghi chú tùy chọn tối đa 1000 ký tự. Không hỗ trợ quy đổi đơn vị; tạo phiếu mới cho lần nhập độc lập. Không thêm nhà cung cấp/phiếu nhiều dòng/giá mua hay điều chỉnh tồn trong nhánh này.

## Out-of-Scope Findings
Xuất/hao hụt/kiểm kê và kiểm recipe rỗng khi pha thuộc các task kế tiếp; không tự triển khai. Không thay lịch sử hoặc reset tài khoản mẫu.

## Unverified Items
Các opt-in tests không bật trong suite mặc định bị skip; browser responsive/keyboard; remote CI. Test concurrency nhập kho đã chạy riêng có opt-in.

## Engineer Review Required
Review diff so dev, kiểm thao tác tại /admin/ingredients → Nhập kho và Lịch sử kho theo docs/HUONG_DAN_NHAP_KHO.md. Merge chưa commit; chỉ stage các file đã review rồi tạo merge commit khi engineer quyết định.

## Recommended Next Task
Nghiệm thu UI/concurrency nhập kho rồi chọn điều chỉnh/hao hụt theo task riêng. Không tự triển khai nhánh kế tiếp.

## Kiểm chứng bổ sung sau yêu cầu tiếp tục

- MySQL ban đầu tắt: lần đầu lỗi connection refused, không tạo database thành công. Đã bật MySQL Laragon hiện có.
- Lượt tiếp theo lỗi ở đọc pipe stdout của worker trên Windows; không tính PASS. Harness đổi sang polling file output tạm chỉ chứa READY/ID, xóa sau khi thu kết quả; cấu hình kết nối chỉ truyền qua stdin.
- Lượt cuối `INVENTORY_IMPORT_MYSQL_TEST=1`: 1 test PASS, 21 assertions, 6.423 giây (log ignored storage/logs/import-mysql.log). Database ngẫu nhiên chỉ dùng test và teardown drop đúng tên/prefix đã tạo. Không chạy migration/seed trên coffee_shop.
- Service không phải sửa để qua test MySQL. Test giữ nguyên liệu dưới khóa tới khi cả hai worker READY, sau đó thả và kiểm tồn/ledger; timeout/worker failure làm test FAIL, không skip giả.
- Hồi quy sau bổ sung test MySQL opt-in: 410 tests, 404 passed, 6 skipped, 5684 assertions, 0 failed; 39.123 giây. Một skipped mới là ca MySQL đã chạy riêng phía trên. Log ignored storage/logs/inventory-final-regression.log.
- Lần Computer Use trước bị dừng do không xác định URL đủ tin cậy. Lần tiếp tục đã đăng nhập bằng nút tài khoản Admin mẫu, vào danh sách nguyên liệu và mở liên kết Nhập kho của nguyên liệu 1. Form hiển thị tên, tồn 9860.000 g, đơn vị g, quantity, note, lịch sử, lưu/hủy; không quan sát thấy chồng lấn trong cửa sổ hiện tại. Không submit phiếu vào DB dự án. Sidebar hiện báo chưa có menu hiển thị (dữ liệu menu của môi trường local); không tự seed để thay đổi dữ liệu. Chưa kiểm đủ 375/768/1440 hoặc keyboard, nên không gọi responsive PASS.
