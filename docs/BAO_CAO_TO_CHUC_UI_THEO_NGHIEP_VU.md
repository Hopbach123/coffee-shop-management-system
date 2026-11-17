# Rà soát và tổ chức UI theo quy trình quán cà phê

Ngày 26/10/2026, nhánh feature/schema-template-sync. Phạm vi ba cách mua counter/scheduled/online lấy tại quán; không shipper/gateway/slot/holds. Kiểm chứng bằng routes, Controller, Blade và Services hiện tại, không suy luận chức năng đã có chỉ từ bảng database.

## Kết luận rà soát trước triển khai UI

| Nghiệp vụ | Bằng chứng hiện tại | Vấn đề cần xử lý | Vị trí đúng / owner |
| --- | --- | --- | --- |
| Auth/User/Customer/Area/Table | routes admin + views CRUD tương ứng | Có UI, giữ nguyên; Admin vào POS còn bị chặn trong backend | Admin/Toàn |
| Catalog/Product/Variant/Topping | CRUD views và public menu/show | Có UI; khách chỉ xem giá, chưa chọn vào giỏ web | Admin và Khách/Hợp |
| Recipe | Model/table có, dev không có recipe Controller/routes/views | Thiếu màn truy cập công thức; nhánh product-recipes đã làm trước, cần merge/review thay vì code CRUD lần hai | Nút Công thức trên từng Variant/Hợp |
| Ingredient | CRUD đã có | Chưa có màn đọc ledger; không thêm input sửa current_stock vào CRUD | Từ Nguyên liệu → Lịch sử kho/Hợp |
| Inventory movements | InventoryService chỉ consume khi pha | Chưa có ghi nhập/waste/adjustment qua Service; UI lịch sử đọc được, chưa nút ghi biến động | Admin Kho/Hợp |
| POS/Order/items/toppings | staff.home/orders/create/show, endpoint mutations có | Đã có UI; “checkout” POS tạo đơn chưa phải thu tiền; thiếu lịch sử Payment tại show | Staff POS → Chi tiết đơn/Ninh, Đạt |
| Pha chế | staff/brewing chỉ cards và hint chưa tích hợp | Endpoint Order status và InventoryService đã có nên có thể nối bắt đầu pha/sẵn sàng; không phục vụ/thu tiền từ queue | Staff → Pha chế/Ninh |
| Web online/hẹn | Schema migrations mới, không public cart/submit/tracking routes | Chưa có backend submit/accept; không thể biến menu thành web checkout chỉ bằng Blade | Khách: Menu → Giỏ → Checkout → Tra cứu/Ninh |
| Inbox request | pickup-requests dùng Model PickupRequest/bảng ngoài schema chuẩn, PATCH chỉ đổi status | Không nối schema online_order_requests; không bật accept kiểu cũ làm người dùng tưởng đã tạo Order | Staff → Online & hẹn → Chi tiết yêu cầu/Ninh |
| Payment/Promotion trên đơn | payments table/PromotionService có; không Payment command/routes | Chỉ có bảng không đủ cho nút xác nhận đã thu. Hiển thị receipt thực tế, chưa bật thu tiền | Chi tiết Order → Thanh toán/Đạt |
| Reservation | Admin create/show/approve/cancel và Staff list | Chưa đầy đủ seat/complete/public gửi đặt bàn; không dùng màn này cho hẹn lấy đồ uống | Admin/Staff Bàn/Toàn |
| Dashboard/menu Admin | Dashboard views và menu DB registry đã có | Màn mới phải được whitelist hoặc link từ màn cha, không tự sửa menu DB | Admin/Toàn |

## Bố trí điều hướng và trình tự thao tác

### Khách

Giữ menu công khai; khi backend hoàn chỉnh: chọn size/số lượng/topping → Giỏ → Checkout chọn “Lấy sớm” hoặc “Hẹn giờ”, tên/SĐT, pickup_at nếu hẹn, cash/bank và timing → tóm tắt giá server → gửi một lần → trang tra cứu token. Không bắt khách đăng nhập nội bộ. Màn tra cứu tách yêu cầu (chờ/nhận/từ chối), món và tiền; trả trước không báo đơn hoàn tất. Token không đặt trong analytics/log nhạy cảm. Chưa triển khai form giả gửi request hoặc expose danh sách đơn khách.

### Staff

Sidebar: POS → Đơn hàng → Online & hẹn → Pha chế → Bàn → Đặt bàn. POS chỉ mua tại quầy. Inbox online/hẹn xử lý request; accepted mở đúng Order, không tạo lại từ POS. Đặt bàn là reservation, không phải scheduled pickup. Chi tiết Order là trung tâm sửa pending/món/topping → pha → xem tiền → phục vụ/nhận đồ → hoàn tất đúng điều kiện. Màn Pha chế ưu tiên code/size/quantity/note; chỉ pending Orders, tránh pha đơn cancelled/completed.

Chi tiết request: mã/loại/status/giờ/liên hệ; danh sách snapshot món và topping; tiền dự kiến, preference/timing; link Order nếu có. Trạng thái chưa migrate khác danh sách rỗng; lỗi schema không hiển thị như “không có đơn”. Chỉ nội bộ Admin/Staff được xem PII. Accept/reject cần Service transaction, không dùng route cũ đổi status trực tiếp.

### Admin

Giữ menu động hiện có. Nguyên liệu → Lịch sử kho để xem before/change/after/type/actor/time; Variant → Công thức để đọc recipe. Không tạo menu riêng cho mỗi dòng/biến thể. Đơn/Payment là nghiệp vụ vận hành ở Staff, Admin có quyền theo requirements nhưng backend staff-only cần task sửa đồng bộ. Dashboard báo cáo không thay màn thu tiền.

## Quy tắc thiết kế

Tái dùng layouts.public/internal, CSS category/Sneat, tokens hiện hữu, button/pagination/alert. Không đổi theme/thêm framework. Table có data-label và caption, form có label, focus ring, errors gần field, state có text. Screen widths 375/768/1440 phải nghiệm thu. Tiền hiển thị VND, giữ precision khi cần. Ngày DB UTC cần ghi ngữ cảnh khi hiển thị, không tự đổi ý nghĩa timestamp cũ.

Mọi màn có empty/loading/error/success phù hợp. Read-only không chứa nút xác nhận giả; thiếu command ghi chú ngắn ở vùng thao tác. Không expose lỗi SQL/credential. Không cho thao tác tiêu hao/paid trong JavaScript; submit backend giữ validation/auth/transaction.

## Đợt triển khai UI hiện tại

1. Nối sidebar Staff vào các màn hiện hữu. Bổ sung inbox/chi tiết request đọc schema mới, phân trang/filter và trạng thái chưa migrate, không PATCH kiểu cũ từ UI mới.
2. Nâng màn pha chế: lọc pending Order, dùng endpoint status hiện có cho Staff; Admin chỉ xem do backend hiện chặn Admin mutation. Không âm thầm mở quyền trong task UI.
3. Chi tiết Order đọc thêm payments thật, hiển thị payment attempts/trạng thái và loại mua nếu schema có, không suy luận paid từ total hoặc giả nút thu.
4. Admin đọc công thức theo Variant và lịch sử kho từ Ingredient, link trong màn cha. Không nhân bản CRUD recipe từ nhánh chưa merge hoặc thêm movement workflow không có Service.

Các bước trên sửa UI/query thực tế, không triển khai toàn bộ backend online/payment. Phần public giỏ/checkout/tracking và accept/payment/movement writes là phần còn lại bắt buộc trước nghiệm thu end-to-end; không được gọi là đủ UI chức năng chỉ vì có inbox read-only.

## Bàn giao các bước tiếp theo

| Ưu tiên | Task / owner | Điều kiện để bật UI |
| --- | --- | --- |
| 1 | Online submit/accept/reject/Ninh | Migrations, Models và Services/transaction/idempotency + token ownership; sau đó giỏ/checkout/tracking public và action inbox |
| 1 | Payment confirmation/Đạt | Cash/bank thực nhận, Order lock, paid idempotency, promotion usage; sau đó form thu tiền trong show Order |
| 1 | Admin staff permissions/Toàn phối hợp Ninh | Sửa đồng bộ Requests/Controller/Services/tests, không chỉ middleware |
| 2 | Recipe CRUD/Hợp | Review/merge feature/product-recipes, dùng entry link Variant đã thiết kế |
| 2 | Movement write/Hợp | Transaction before/after + audit, rồi form nhập/waste/adjustment |
| 2 | Reservation seat/lifecycle/Toàn | Service thực tế và Order link, không đưa chức năng hẹn lấy vào đây |

## Tiêu chí và giới hạn

Kiểm tra routes/quyền, đọc dữ liệu/empty/unavailable, escaping và link đúng quan hệ; test queue không chứa Order đã kết thúc; không expose request cho guest; không ghi DB qua GET. Build và feature tests; browser mobile/desktop/keyboard riêng. Không sửa seed/DB để tạo ảnh đẹp. Không commit/push tự động. Kết quả test/file chi tiết sẽ ghi trong task report, phần browser không chạy được phải ghi UNVERIFIED.
