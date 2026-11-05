# Hoàn thiện cảnh báo tồn và tiêu hao khi pha

Ngày 06/11/2026 · `feature/inventory-completion` · CAFE-INV-BE-006.

## Baseline và mục tiêu

HEAD khi bắt đầu: `feac9ce2` (PR #34 adjustments), worktree sạch, đã ở đúng nhánh. Không fetch nên không khẳng định mới nhất GitHub. Đối chiếu mục 7 bảng phân công: H03 cảnh báo tồn; H04/H06 recipe và consume khi pending → preparing. Không triển khai giữ/release nguyên liệu, snapshot/version engine, topping recipe, shipper hoặc Payment.

Code đã có: Ingredient CRUD và ngưỡng tối thiểu; recipe CRUD theo Variant; nhập/điều chỉnh/hao hụt/kiểm kê và ledger; OrderService.transitionItemStatus gọi InventoryService.startPreparing. Không viết lại các workflow đó. Hàng đợi pha đã hiển thị món nhưng chưa có nút thực thi, còn nội dung nói chưa tích hợp.

## Kế hoạch và thay đổi

| Bước (30–90 phút/bước) | Phần tận dụng | Bổ sung/sửa | Điều kiện kiểm chứng |
| --- | --- | --- | --- |
| 1. Cảnh báo kho | IngredientController index, model custom, bảng/filter/phân trang hiện có | Scope/filter stock; nhãn hết/dưới/đủ ngưỡng, empty state kết hợp tên | 0, sát ngưỡng, bằng ngưỡng, inactive/deleted, array input, giữ query khi đổi trang |
| 2. Hoàn thiện tiêu hao | OrderService và InventoryService hiện có | Khóa actor/đơn/món; kiểm đơn pending ngay tại Service; khóa recipe và ingredients; chặn recipe rỗng/0; chống consume lại; xử lý save bị hủy | Tồn đủ/thiếu, multi-ingredient, actor/order sai, duplicate, failed write rollback |
| 3. Tích hợp và bàn giao | Endpoint PATCH hiện có và MySQL worker harness | Nút Bắt đầu pha tại queue; sửa hướng dẫn recipe; regression, tranh tồn và report | Nhập → consume → waste → stocktake giữ chain; hai requests/Orders/waste tranh tồn; build, diff và UI |

Scope code: Ingredient model/index, InventoryService, hai Blade recipe/queue, tests liên quan và tài liệu. Không thay OrderService, Payment, schema/seed/DB dự án, generated Models hoặc dependencies.

## Quy tắc tồn kho

| Tình trạng | Điều kiện |
| --- | --- |
| Hết tồn | Nguyên liệu active, current_stock = 0, kể cả minimum_stock = 0 |
| Dưới ngưỡng | Active, 0 < current_stock < minimum_stock |
| Đủ ngưỡng | Active, current_stock > 0 và >= minimum_stock |
| Ngừng dùng | Không phát cảnh báo và không nằm trong bộ lọc tồn hoạt động |

Đã xóa không hiển thị. Cảnh báo là thông tin vận hành, không tự ngừng bán món hoặc bảo đảm tồn cho đơn online/hẹn. Không có holds nên khả năng pha được kiểm lại tại lúc bắt đầu. So sánh số lượng chính xác theo phần nghìn/DECIMAL, không tính float.

## Luồng thực thi

1. Admin cấu hình Ingredient/đơn vị/ngưỡng → nhập kho → cấu hình nguyên liệu và định lượng cho một phần Variant.
2. Staff/Admin vào Pha chế hoặc dùng endpoint chuyển trạng thái hiện có; chọn **Bắt đầu pha** cho món pending.
3. Endpoint giữ kiểm quyền, validation và quan hệ Order–Item của Sales. InventoryService đọc lại actor active, đơn pending, món pending; chặn khi món đã có consume ledger.
4. Đọc recipe dưới khóa. **Recipe rỗng bị chặn**, không tự chuyển sang preparing. User đã giao chọn chính sách phù hợp; dự án chưa có cờ untracked nên không âm thầm bỏ qua kho. Nếu tương lai có đồ đóng chai không cần recipe, cần một task thay chính sách rõ ràng, không thêm ngoại lệ giả trong nhánh này.
5. Tổng định lượng = recipe quantity × số phần; gom theo Ingredient, kiểm giới hạn trước nhân/cộng; lượng phải dương. Khóa ingredients theo ID tăng dần, kiểm active/chưa xóa và đủ tất cả nguyên liệu.
6. Trừ tồn, ghi consume ledger và chuyển món preparing cùng transaction. Bất kỳ lỗi DB hoặc event hủy save đều rollback. Gửi lặp bị từ chối, không trừ lần hai. Lỗi nghiệp vụ hiện qua validation; nhân viên nhờ Admin sửa công thức/nhập kho rồi thử lại.
7. Ledger giữ ingredient, quantity, before/after, actor và reference tới Order Item. Công thức sửa sau không tính lại log cũ. Chưa trừ nguyên liệu topping; không dùng recipe mới để kiểm lại lịch sử cũ.

Khóa thống nhất tại luồng tiêu hao: actor → Order → Order Item → recipe theo ingredient_id → Ingredient theo ID. Wrapper OrderService đã khóa actor/Order/Item trước khi gọi; khóa lại cùng transaction không tạo workflow thứ hai. Nhập/adjustment dùng actor → Ingredient. Không gọi mạng hoặc gửi thông báo trong transaction. Transaction retry deadlock theo cấu hình hiện có (3 lần).

## Cách test thủ công trên dữ liệu development

- **Admin → Nguyên liệu:** chọn Hết tồn/Dưới ngưỡng/Đủ ngưỡng, kết hợp tên; đổi trang vẫn giữ query; xóa lọc. Nguyên liệu inactive vẫn xuất hiện ở tất cả nhưng không vào filter tồn.
- Tạo recipe 0.125 g/phần, tồn 1 g, món 2 phần: pha thành công còn 0.750 g và một log consume 0.250 g. Gửi lặp không thêm log; đủ đúng lượng được phép về 0.
- Món không recipe, nguyên liệu inactive/deleted hoặc thiếu một trong nhiều nguyên liệu: báo lỗi, món pending, tất cả tồn/log giữ nguyên. Bổ sung recipe/nhập kho rồi thử lại.
- Nhập → pha → hao hụt → kiểm kê; kiểm tồn trước/sau từng chứng từ và actor. Đổi recipe sau đó không đổi log tiêu hao cũ.
- Staff không vào quản trị nguyên liệu/công thức. Admin được thao tác pha như Staff. Thử sai Order–Item ở URL phải bị endpoint Sales từ chối.
- Kiểm UI 375/768/1440: filter không tràn ngang, bảng responsive; nhãn không chỉ dùng màu; Tab đến select/nút pha, Enter submit, validation và success đọc được. Không dùng test dữ liệu thật cần giữ.

## Kiểm chứng tự động và giới hạn

- `php artisan test --compact --filter="InventoryServiceTest|IngredientManagementTest|StaffOrderItemManagementTest"`: kiểm Service, HTTP/quyền, filter và integration chain trên SQLite `:memory:`.
- CMD: `set INVENTORY_IMPORT_MYSQL_TEST=1` rồi `php artisan test --compact --filter=InventoryImportConcurrencyMySqlTest`; sau đó `set INVENTORY_IMPORT_MYSQL_TEST=`. Harness tạo/drop riêng DB ngẫu nhiên `inventory_import_test_<16hex>`; cấu hình lấy từ MySQL hiện có, không migrate/seed DB dự án. Cần MySQL đang chạy và quyền tạo/drop DB test.
- MySQL test dùng hai PHP workers tranh lock thật: duplicate prepare một món; hai món tranh tồn; prepare cạnh waste; đồng thời giữ lại test import/adjustment cũ. So chain before/after toàn bộ fixture, không chỉ kiểm số log.
- Full suite, Pint PHP thay đổi, `npm run build`, `git diff --check` và review diff. Runtime PHP 8.3 local không chứng minh remote CI 8.4/8.5.
- Browser host báo unavailable tại lúc kiểm tra; nghiệm thu responsive/keyboard chưa xác nhận. Render qua HTTP tests không thay thế quan sát UI.

Không thay đổi schema, không nạp seed, không reset tài khoản hoặc tự commit/push/triển khai nhánh khác.
