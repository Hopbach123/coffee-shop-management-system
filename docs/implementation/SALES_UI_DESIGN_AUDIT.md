# MAISON DU CAFÉ — BÁO CÁO RÀ SOÁT GIAO DIỆN NINH (N01–N06)
**Tài liệu:** `docs/implementation/NINH_UI_AUDIT.md`  
**Ngày thực hiện:** 17/11/2026  
**Nhánh:** `dev`  
**Tác giả:** Senior UI/UX Designer & Frontend Integration Engineer  

---

## 1. TỔNG QUAN HỆ THỐNG THIẾT KẾ HIỆN CÓ

### 1.1. Triết lý Thiết kế (`frontend.md`)
- **Phong cách:** *Premium European Coffee Boutique* (ấm áp, sang trọng, tinh tế, editorial và thủ công).
- **Màu sắc chủ đạo:**
  - Nền chính: `--coffee-bg` (`#f4eddf` / `#f7f3ec`) — sắc giấy parchment ấm.
  - Khối bề mặt: `--coffee-surface` (`#fbf7ef` / `#ffffff`) — surface kem ngà sạch sẽ.
  - Viền & Ngăn cách: `--coffee-border` (`#c9b69a` / `#d9ccba`) — viền ấm, tương phản nhẹ.
  - Điểm nhấn & CTA: `--coffee-caramel` (`#ad7547`) — màu caramel hạt dẻ đặc trưng.
  - Chữ chính: `--coffee-text` (`#342820`), chữ đậm: `--coffee-espresso` (`#2c1b14`), chữ phụ: `--coffee-text-muted` (`#746458`).
  - Trạng thái: `--coffee-success` (`#2b5433`), `--coffee-danger` (`#9b493d`), `--coffee-warning` (`#8f5c1d`).
- **Typography:**
  - Tiêu đề & Heading: `--font-display` (Serif: *Iowan Old Style*, *Palatino*, *Georgia*).
  - Nội dung & Thao tác: `--font-body` (Sans-serif: *Instrument Sans*).
- **Bố cục & Khung nhìn:**
  - Public Storefront: Bố cục editorial, `.coffee-container` (max 82rem), `.commerce-layout` (2 cột: 1.5fr danh sách + 0.8fr sticky tóm tắt).
  - Staff / Admin Workspace: Bố cục `.sneat-app`, sidebar cố định bên trái, topbar với breadcrumb và avatar, khối thẻ `.sneat-card`, bảng dữ liệu `.sneat-table` và trạng thái `.sneat-pill`.

---

## 2. BẢNG ĐỐI CHIẾU HIỆN TRẠNG UI N01–N06

| Mã | Chức năng | Màn hình | Giao diện hiện tại | Giao diện tham chiếu | Vấn đề / Điểm cần nâng cấp |
|---|---|---|---|---|---|
| **N01** | Cart | `public/cart/index.blade.php` | Có danh sách cơ bản, còn thô sơ, chưa làm nổi bật topping & ghi chú | Public Menu (`public/menu/index.blade.php`), Storefront commerce styling | Đồng bộ thẻ món theo phong cách boutique; hiển thị rõ tag topping hạt caramel; bộ tăng giảm số lượng tinh tế; empty state đẹp mắt. |
| **N02** | POS / Order | `staff/home.blade.php` | Bố cục `.pos-grid` đã có khung, nhưng catalog sản phẩm và giỏ hàng chưa có filter danh mục nhanh | Staff Product/Category management, Sneat Cards | Bổ sung thanh tab lọc danh mục sản phẩm nhanh; thẻ sản phẩm hiển thị ảnh/icon ấm; giỏ hàng POS hiển thị dòng món rõ ràng, nút chọn loại đơn (Mang đi/Tại bàn) chuyển mượt. |
| **N03** | Online Request | `public/cart/checkout.blade.php` & `staff/online-requests/` | Form dùng nhiều inline style rời rạc; Staff Inbox dùng panel đơn điệu | Staff Category/Product Management tables & forms | Chuyển toàn bộ inline style sang scoped CSS; form chọn kênh (Online vs Hẹn) bằng 2 thẻ card tương tác cao cấp; Staff Inbox đồng bộ thẻ `.sneat-card` và badge `.sneat-pill`. |
| **N04** | Scheduled Pickup | `public/cart/checkout.blade.php` & `staff/online-requests/show.blade.php` | Input datetime-local cơ bản, hướng dẫn quy định còn nhỏ | Storefront reservation form (`resources/views/public/reservation/`) | Thẻ chọn ngày giờ có hướng dẫn khung giờ 07:00–22:00 rõ ràng; validation phản hồi trực quan; Staff xem giờ hẹn chuẩn múi giờ Việt Nam. |
| **N05** | Brewing Queue | `staff/brewing/index.blade.php` | Thẻ hàng đợi đã có, nhưng bố cục thông tin món, kích cỡ, topping còn dàn trải | Sneat Kanban / Kitchen grid cards | Thẻ pha chế chuẩn Barista: mã đơn nổi bật, tag kênh mua (Online/Hẹn/Tại quầy), highlight ghi chú khách; giữ nguyên form & button chuẩn test. |
| **N06** | Order Tracking & Completion | `public/orders/track.blade.php` & `staff/orders/show.blade.php` | Public tracking có timeline đơn sơ, dùng nhiều inline style; Staff order show thông tin dài | Customer confirmation view & Staff Order Detail (`staff/orders/show.blade.php`) | Xây dựng stepper timeline 4 bước trực quan cho khách; phân nhóm thông tin khách - món - thanh toán; Staff Order Show hoàn thiện form thanh toán và nút Complete nổi bật. |

---

## 3. THÀNH PHẦN TÁI SỬ DỤNG ĐÃ XÁC ĐỊNH
1. **Layouts:** `layouts.public` (cho khách) và `layouts.internal` (cho nhân viên / barista).
2. **Components:**
   - `<x-internal.icon />`: Bộ icon SVG đồng nhất (`coffee`, `bag`, `search`, `arrow`, `receipt`, `chef`, v.v.).
   - `<x-public.button />`: Nút bấm phong cách coffeehouse với các biến thể `primary`, `outline`.
   - `<x-internal.workflow-status />`: Trạng thái quy trình công việc chuẩn.
   - Thẻ huy hiệu `.sneat-pill` (`is-success`, `is-warning`, `is-info`, `is-danger`).
3. **Design Tokens:** Tận dụng 100% biến CSS có sẵn trong `:root` của `coffee-theme.css` và `sneat.css`.

