# Inventory completion — 06/11/2026

## Task Status
PARTIAL — code, tests chức năng/concurrency/hồi quy và build đạt; responsive/keyboard chưa được nghiệm thu.

## Task ID
CAFE-INV-BE-006 — H03/H04/H06, feature/inventory-completion.

## Summary
User yêu cầu lập kế hoạch dựa code hiện có và triển khai toàn bộ nhánh gộp. Đang ở đúng nhánh feac9ce2 (PR #34), worktree sạch ban đầu. Tận dụng low-stock data, recipe CRUD, InventoryService và endpoint Sales đã có; không viết workflow thứ hai. User giao chọn chính sách thiếu recipe; chọn chặn pha trước khi recipe hợp lệ để không bỏ qua kho khi chưa có stock_mode. Đã ghi vào requirements/plan trước phần triển khai rule mới.

Không fetch/commit/push/merge; không tuyên bố baseline remote mới nhất. RTK không có trong PATH, dùng CLI trực tiếp. Chi tiết kế hoạch/luồng/kiểm thử: [hướng dẫn](../../docs/KE_HOACH_VA_HUONG_DAN_INVENTORY_COMPLETION.md).

## Files Created
- docs/KE_HOACH_VA_HUONG_DAN_INVENTORY_COMPLETION.md
- reports/task-reports/feature-inventory-completion__report.md

## Files Modified
- app/Models/Ingredient.php: stock level scope/accessor tại custom layer.
- app/Http/Controllers/Admin/IngredientController.php: filter validation/query, giữ search/pagination.
- app/Services/InventoryService.php: harden consume hiện có.
- resources/views/admin/ingredients/index.blade.php: filter/nhãn/empty state.
- resources/views/admin/product-recipes/index.blade.php: thông báo recipe rỗng.
- resources/views/staff/brewing/index.blade.php: nút gọi PATCH thật và hướng dẫn thực tế.
- tests/Feature/IngredientManagementTest.php
- tests/Feature/InventoryServiceTest.php
- tests/Feature/StaffOrderItemManagementTest.php
- tests/Feature/InventoryImportConcurrencyMySqlTest.php
- tests/Support/stock-import-worker.php
- requirements.md, plan.md
- reports/task-reports/BACH_NGOC_HOP__TIEN_DO_USE_CASE.md

## Files Deleted
None.

## Implementation Details
Nguyên liệu active: tồn = 0 là hết, 0 < tồn < tối thiểu là dưới ngưỡng, tồn dương >= tối thiểu là đủ; inactive không cảnh báo, deleted bị scope loại. Bộ lọc kết hợp tên và giữ query phân trang; accessor dùng số nguyên phần nghìn, query so DECIMAL tại DB. Không auto unavailable, không giữ kho hoặc đổi đơn vị.

Consumption giữ kiến trúc hiện có: OrderController → OrderService → InventoryService. Thêm kiểm actor từ DB dưới khóa, pending Order ngay tại Service để direct caller không bypass; Item đúng parent/pending; chặn ledger consume đã tồn tại. Đọc recipe dưới lock, recipe rỗng/0 bị chặn; regex conversion giới hạn 9 chữ số nguyên và 3 thập phân trước cast. Tất cả ingredients active/chưa xóa/đủ tồn, khóa ID tăng dần; stock + ledger + preparing nguyên tử. Kiểm cả boolean trả về saveOrFail để event false không báo thành công giả. Recipe edits không recalculate history. Giữ retry transaction 3 lần.

UI queue trước chỉ đọc và nói chưa tích hợp; nay pending có Bắt đầu pha dùng route hiện hữu, POST/CSRF/PATCH, không tạo endpoint hoặc quyền mới. Service lỗi trả validation qua Controller có sẵn; thành công về chi tiết Order theo baseline. Reuse component/styles, không đổi shared CSS/JS.

## Tests / Checks Executed
- Đối chiếu AGENTS/RTK, architecture/requirements/plan/frontend đã đọc trong phiên; source/migration/model/route/UI/tests và phân công mục 7.
- Targeted InventoryServiceTest + IngredientManagementTest + StaffOrderItemManagementTest: 51 tests PASS, 640 assertions, 8.081s.
- MySQL opt-in InventoryImportConcurrencyMySqlTest: 3 tests PASS, 84 assertions, 15.947s. Database ngẫu nhiên tách biệt, cleanup theo prefix/tên chính xác.
- Full suite: 423 tests, 415 passed, 8 skipped, 5895 assertions, 0 failed, 62.583s.
- Pint 8 PHP files; npm run build PASS; git diff --check PASS.
- Browser access host báo unavailable khi discovery; native Edge còn tab login local. Đã yêu cầu user đăng nhập thủ công để xem tiếp, chưa xác nhận UI responsive.
- Logs ignored: storage/logs/completion-targeted.log, completion-mysql.log, completion-regression.log, completion-build.log.

## Test Results
PASS cho filter 0/min0/sát-ngưỡng/bằng-ngưỡng/inactive/deleted, malformed filter, search/pagination; HTTP missing recipe và form queue nối endpoint thật. Service kiểm thiếu/0/deleted ingredient/inactive-deleted actor, parent cancelled/completed, duplicate ledger, overflow/exact stock, multi-ingredient rollback ở từng save stage, chuỗi nhập → consume → waste → stocktake và history bất biến sau recipe edit. MySQL kiểm hai actors gửi pha cùng món, hai đơn tranh tồn và consume cạnh waste, đồng thời regression import/adjustment cũ.

Hai lượt targeted đầu fail ở assertion mới dùng object stale và assertDontSee text hướng dẫn thay vì form action; sửa assertion kiểm trạng thái DB trước lệnh và action form. Không thay nghiệp vụ để né test. Các pass ghi trên là lượt sau sửa. Test opt-in mặc định skip không tính PASS; MySQL ba ca đã chạy riêng. Build warning optional fontaine và ảnh resolve runtime có sẵn, không cài dependency.

## Acceptance Criteria
- PASS: low/out/sufficient đúng biên và trạng thái, query/pagination/empty.
- PASS: rule recipe rỗng, quyền actor/parent/Item, tồn/precision/bounds.
- PASS: rollback stock/log/status; không double consume; MySQL tranh tồn/ledger.
- PASS: chain kho và history không phụ thuộc recipe hiện tại.
- PASS: UI render/nút thật/CSRF qua HTTP tests, regression/build/format/diff.
- UNVERIFIED: responsive 375/768/1440, keyboard trực quan, remote CI 8.4/8.5 và engineer acceptance.

## Risks
Rule chặn recipe rỗng thay hành vi cũ cho pha không log. Biến thể cũ chưa có recipe sẽ bị chặn khi bắt đầu pha; Admin cần cấu hình trước, không tự seed recipe giả. Chưa có cờ untracked cho đồ đóng chai. Khóa recipe chỉ bảo vệ cấu hình tại lệnh hiện tại, không phải version/snapshot engine. Không tự trừ topping. Không bảo vệ SQL viết trực tiếp ngoài Service; không sửa/reset dữ liệu lịch sử sai sẵn có. Browser render test không chứng minh responsive.

## Blockers
Không có blocker backend/test/build. Browser host unavailable; chưa có phiên Admin đã xác nhận cho kiểm trực quan.

## Assumptions
Stock warning là thông tin quản trị; bằng ngưỡng đủ, min0-stock0 vẫn hết tồn. Staff chỉ pha qua quyền Sales hiện có; Admin thao tác được như Staff. Không mở rộng quyền quản trị cho Staff. Công thức current khi bắt đầu pha, không reserve ở accept.

## Out-of-Scope Findings
Ingredient đổi đơn vị/status và recipe CRUD có race giữa FormRequest validation và save ở baseline; chưa refactor CRUD/approval trong nhánh consume. Màn chi tiết Order đang là template đọc, chưa hiển thị đầy đủ thao tác Sales từ backend; queue có thể bắt đầu pha nhưng các bước ready/served thuộc bàn giao Ninh. Không viết lại Sales/Payment UI trong nhánh kho.

## Unverified Items
Browser viewports/keyboard; remote CI; mọi race với recipe CRUD/status edit không được chứng minh chỉ từ ba concurrency cases. Không coi toàn bộ hệ thống nghiệm thu từ test module.

## Engineer Review Required
Review diff, xác nhận ảnh hưởng recipe rỗng với dữ liệu đang dùng; thử theo hướng dẫn và nghiệm thu UI. Không commit/push tự động.

## Recommended Next Task
Nghiệm thu nhánh này và phối hợp Ninh kiểm workflow pha → ready/served qua UI; không tự triển khai task khác.

## Bổ sung kiểm tra trình duyệt sau khi user đăng nhập

- Đã bật lại artisan serve tại 127.0.0.1:8000 vì tiến trình cũ dừng. User đăng nhập thủ công; lần đầu phiên nằm trong browser VS Code, sau đó đã xác nhận Admin trên Edge.
- Xem danh sách thực: Trà đen tồn 1000.000 g, tối thiểu 1500.000 g hiển thị Dưới ngưỡng; nguyên liệu đủ tồn hiển thị Đủ ngưỡng.
- Điều hướng bằng bàn phím tới `?stock=low`: trang chỉ còn Trà đen, select đúng Dưới ngưỡng, có Xóa lọc. `?stock=out`: không có nguyên liệu phù hợp và hiện empty state cùng Xem tất cả/Xóa lọc đúng dữ liệu hiện tại. Không ghi phiếu, pha hoặc thay dữ liệu DB dự án.
- Quan sát ảnh Edge 791×842, zoom 80%: nhãn/filter/nút/empty state hiển thị, không thấy chồng lấn trong cửa sổ quan sát. Không suy ra viewport CSS hoặc kết luận đủ 375/768/1440 từ kích thước cửa sổ này.
- Công cụ click indexed gặp `coordinate input geometry is unavailable`; điều hướng URL qua bàn phím hoạt động. Chưa xác minh submit dropdown/Tab toàn form, responsive ba viewport và nút pha bằng browser. Các mục UNVERIFIED/Task Status PARTIAL giữ nguyên; không cần chạy lại suite vì lượt bổ sung chỉ đọc UI và cập nhật tài liệu.
