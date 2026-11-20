# Báo Cáo Kiểm Toán & Kế Hoạch Tái Thiết Giao Diện (UI Redesign Audit)
**Dự án:** Maison du Café Management System  
**Ngày thực hiện:** 20/11/2026  
**Chuẩn thiết kế:** Warm Editorial Coffee System (v3)  

---

## 1. Mục Tiêu & Phạm Vi Kiểm Toán

Thực hiện rà soát toàn diện hiện trạng giao diện người dùng dựa trên bộ hồ sơ `MAISON_DU_CAFE_UI_REDESIGN_KIT.zip`, bao gồm 28 ảnh chụp hiện trạng, 8 mockups chuẩn màn hình (`01` đến `08`), quy chuẩn thiết kế `QUY_CHUAN_THIET_KE.md` và hướng dẫn chỉ đạo `MASTER_PROMPT_CHATGPT_PLUS_CODEX.md`.

Mục tiêu chính:
1. Chuyển hóa toàn bộ giao diện nội bộ (Admin & Staff) sang hệ thống định danh lớp chuẩn `.cafe-*`, loại bỏ phân mảnh giữa `sneat-*` cũ và CSS inline.
2. Sửa triệt để các lỗi giao diện: vỡ dòng mã đơn hàng/tiền tệ, nút bấm thiếu class base `.cafe-btn`, sidebar/topbar phân biệt rõ role xác thực và workspace làm việc.
3. Chuyển đổi màn Hàng đợi pha chế sang mô hình Kanban 3 luồng (Chờ pha · Đang pha · Sẵn sàng) tương ứng đúng luồng nghiệp vụ kho và chế biến.
4. Giữ toàn vẹn 100% logic nghiệp vụ backend, contract route, FormRequest validation và kết quả kiểm thử PHPUnit.

---

## 2. Bảng Ma Trận Kiểm Toán Giao Diện (Audit Matrix)

### A. Nhóm Màn Catalog & Kho (Hợp)

| UI ID | Tên Màn Hình | Route | Blade View | Quyền Hạn | Lỗi Hiện Trạng | Nguyên Nhân Gốc | Mức Độ | Trạng Thái Tái Thiết |
|---|---|---|---|---|---|---|---|---|
| **H-UI01** | Danh mục sản phẩm (List) | `admin.categories.index` | `resources/views/admin/categories/index.blade.php` | Admin | Bảng thô, nút bấm thiếu class nền, phân trang chưa đồng bộ token | Dùng class `sneat-*` cũ | Cao | **ĐÃ HOÀN TẤT** (dùng `cafe-table`, `cafe-badge`, `cafe-btn`) |
| **H-UI02** | Form Danh mục (Thêm/Sửa) | `admin.categories.create`, `.edit` | `resources/views/admin/categories/create.blade.php`, `edit.blade.php` | Admin | Form thiếu helper rõ ràng, thẻ card phẳng | Thiếu styling container chuẩn | Trung bình | **ĐÃ HOÀN TẤT** (`cafe-card`, `cafe-form-group`) |
| **H-UI03** | Danh sách sản phẩm (List) | `admin.products.index` | `resources/views/admin/products/index.blade.php` | Admin | Ảnh thumbnail thiếu tỉ lệ chuẩn, badge trạng thái chưa dịch nghĩa | Chưa có token cho media thumbnail | Cao | **ĐÃ HOÀN TẤT** (`cafe-table`, `cafe-avatar`, filter bar) |
| **H-UI04** | Form Sản phẩm (Thêm/Sửa) | `admin.products.create`, `.edit` | `resources/views/admin/products/create.blade.php`, `edit.blade.php` | Admin | Khung upload ảnh thô, nút action không cố định chân form | Thiếu responsive form grid | Cao | **ĐÃ HOÀN TẤT** (preview ảnh an toàn, chuẩn mockup 08) |
| **H-UI05** | Danh sách biến thể size | `admin.product-variants.index` | `resources/views/admin/product-variants/index.blade.php` | Admin | Tiền tệ không có `tabular-nums`, SKU bị xuống dòng | Thiếu CSS `white-space: nowrap` | Trung bình | **ĐÃ HOÀN TẤT** (`tabular-nums`, `nowrap` SKU) |
| **H-UI06** | Form biến thể size | `admin.product-variants.create`, `.edit` | `resources/views/admin/product-variants/create.blade.php`, `edit.blade.php` | Admin | Ràng buộc giá và SKU thiếu helper text trực quan | Layout form đơn lẻ | Thấp | **ĐÃ HOÀN TẤT** (`cafe-form-group`) |
| **H-UI07** | Định lượng công thức món | `admin.product-variants.recipes.index` | `resources/views/admin/recipes/index.blade.php` | Admin | Thiếu ngữ cảnh món, bảng nguyên liệu lộn xộn | Chưa nhóm theo size | Cao | **ĐÃ HOÀN TẤT** (`cafe-card` theo biến thể) |
| **H-UI08** | Danh sách Topping | `admin.toppings.index` | `resources/views/admin/toppings/index.blade.php` | Admin | Giá topping hiển thị thô, toggle trạng thái thiếu pill | Dùng text thông thường | Trung bình | **ĐÃ HOÀN TẤT** (`cafe-table`, `cafe-badge`) |
| **H-UI09** | Form Topping | `admin.toppings.create`, `.edit` | `resources/views/admin/toppings/create.blade.php`, `edit.blade.php` | Admin | Thừa thẻ lồng nhau không cần thiết | Cấu trúc Blade dư thừa | Thấp | **ĐÃ HOÀN TẤT** (tinh gọn, chuẩn base) |
| **H-UI10** | Tồn kho nguyên liệu | `admin.ingredients.index` | `resources/views/admin/ingredients/index.blade.php` | Admin | Số lượng tồn bị bẻ dòng, cảnh báo sắp hết hàng mờ nhạt | Thiếu alert token | Nghiêm trọng | **ĐÃ HOÀN TẤT** (Badge cảnh báo tồn ngưỡng đỏ/vàng) |
| **H-UI11** | Form nguyên liệu | `admin.ingredients.create`, `.edit` | `resources/views/admin/ingredients/create.blade.php`, `edit.blade.php` | Admin | Tồn kho bị nhầm là trường có thể gõ trực tiếp | Thiếu readonly rõ rệt | Cao | **ĐÃ HOÀN TẤT** (Readonly tồn, chỉ sửa cost/ngưỡng) |
| **H-UI12** | Nhập kho nguyên liệu | `admin.ingredients.purchase` | `resources/views/admin/ingredients/purchase.blade.php` | Admin | Số tồn sau nhập không preview rõ ràng | Thiếu bảng tóm tắt phiếu | Cao | **ĐÃ HOÀN TẤT** (Preview tính toán, ghi chú phiếu) |
| **H-UI13** | Điều chỉnh / Kiểm kê kho | `admin.ingredients.adjust` | `resources/views/admin/ingredients/adjust.blade.php` | Admin | Phân biệt tăng/giảm chưa rõ, thiếu highlight chênh lệch | Thiếu màu phân loại | Cao | **ĐÃ HOÀN TẤT** (Color-coded chênh lệch +/-) |
| **H-UI14** | Sổ cái lịch sử kho | `admin.ingredients.history` | `resources/views/admin/ingredients/history.blade.php` | Admin | Bảng lịch sử quá rộng, timestamp tràn viền | Không có horizontal scroll chuẩn | Trung bình | **ĐÃ HOÀN TẤT** (Read-only ledger, monospace code) |
| **H-UI15** | Thực đơn công khai (Menu) | `public.menu` | `resources/views/public/menu.blade.php` | Khách | Giữ nguyên hiện trạng theo quy tắc an toàn Public | Bảo vệ không gây regression | Nhẹ | **ĐÃ KIỂM CHỨNG AN TOÀN** |
| **H-UI16** | Chi tiết món khách | `public.product` | `resources/views/public/product.blade.php` | Khách | Giữ nguyên hiện trạng theo quy tắc an toàn Public | Bảo vệ không gây regression | Nhẹ | **ĐÃ KIỂM CHỨNG AN TOÀN** |

---

### B. Nhóm Màn Sales, POS & Staff (Ninh)

| UI ID | Tên Màn Hình | Route | Blade View | Quyền Hạn | Lỗi Hiện Trạng | Nguyên Nhân Gốc | Mức Độ | Trạng Thái Tái Thiết |
|---|---|---|---|---|---|---|---|---|
| **N-UI01** | Giỏ hàng công khai | `public.cart` | `resources/views/public/cart.blade.php` | Khách | Giữ nguyên hiện trạng Public | Phạm vi bảo vệ | Nhẹ | **ĐÃ KIỂM CHỨNG AN TOÀN** |
| **N-UI02** | Đặt món mang về/hẹn giờ | `public.checkout` | `resources/views/public/checkout.blade.php` | Khách | Giữ nguyên hiện trạng Public | Phạm vi bảo vệ | Nhẹ | **ĐÃ KIỂM CHỨNG AN TOÀN** |
| **N-UI03** | Xác nhận gửi yêu cầu | `public.order-confirmed` | `resources/views/public/confirmed.blade.php` | Khách | Giữ nguyên hiện trạng Public | Phạm vi bảo vệ | Nhẹ | **ĐÃ KIỂM CHỨNG AN TOÀN** |
| **N-UI04** | Tra cứu trạng thái | `public.order-tracking` | `resources/views/public/tracking.blade.php` | Khách | Giữ nguyên hiện trạng Public | Phạm vi bảo vệ | Nhẹ | **ĐÃ KIỂM CHỨNG AN TOÀN** |
| **N-UI05** | Bán hàng POS tại quầy | `staff.home` | `resources/views/staff/home.blade.php` | Staff / Admin | Thẻ món cao bất thường, giỏ hàng chiếm chỗ che khuất nút | Thiếu fixed height cho art card | Nghiêm trọng | **ĐÃ HOÀN TẤT** (Bố cục 2 cột chuẩn mockup 01) |
| **N-UI06** | Danh sách đơn hàng | `staff.orders.index` | `resources/views/staff/orders/index.blade.php` | Staff / Admin | Mã đơn `#DEMO-V2-...` bị bẻ thành 3 dòng, nút thiếu base | `overflow-wrap: anywhere`, thiếu `cafe-btn` | Nghiêm trọng | **ĐÃ HOÀN TẤT** (Chuẩn hóa nowrap, tiếng Việt UI) |
| **N-UI07** | Tạo đơn hàng mới | `staff.orders.create` | `resources/views/staff/orders/create.blade.php` | Staff / Admin | Giao diện form chia khối chưa tinh tế | Kế thừa layout cũ | Trung bình | **ĐÃ HOÀN TẤT** (`cafe-card`, `cafe-btn`) |
| **N-UI08** | Chi tiết đơn hàng | `staff.orders.show` | `resources/views/staff/orders/show.blade.php` | Staff / Admin | Bảng món lộn xộn, thanh toán thiếu phân cách tài chính | Trộn lẫn card thông tin | Cao | **ĐÃ HOÀN TẤT** (Grid 2 cột, bảng món, form thu tiền) |
| **N-UI09** | Yêu cầu online & Hẹn lấy | `staff.online-requests.index` | `resources/views/staff/online-requests/index.blade.php` | Staff / Admin | Bộ lọc nằm lệch hàng, bảng inline styles thô | Thiếu CSS component | Cao | **ĐÃ HOÀN TẤT** (`cafe-filter-form`, `cafe-table`) |
| **N-UI10** | Chi tiết yêu cầu online | `staff.online-requests.show` | `resources/views/staff/online-requests/show.blade.php` | Staff / Admin | Nút duyệt/từ chối chưa nổi bật, format giờ quốc tế | Dùng kiểu UTC thô | Cao | **ĐÃ HOÀN TẤT** (Giờ VN, tổng tiền, action card) |
| **N-UI11** | Hàng đợi pha chế (Kitchen) | `staff.brewing.index` | `resources/views/staff/brewing/index.blade.php` | Staff / Admin | Dàn 6 card ngang quá chật, không nhận biết trạng thái | Grid 6 cột không co giãn | Nghiêm trọng | **ĐÃ HOÀN TẤT** (3-lane Kanban Amber/Blue/Green) |
| **N-UI12** | Xác nhận giao đồ & mã nhận | `staff.orders.show` (section) | `resources/views/staff/orders/show.blade.php` | Staff / Admin | Ô nhập mã nhận bàn giao nằm lệch dòng | Thiếu input sizing | Trung bình | **ĐÃ HOÀN TẤT** (Khớp điều kiện thanh toán đủ) |
| **N-UI13** | Ghi nhận thu tiền & đối soát | `staff.orders.show` (section) | `resources/views/staff/orders/show.blade.php` | Staff / Admin | Form thu tiền cash/transfer thiếu giải thích đối soát | Chưa có card phụ trợ | Trung bình | **ĐÃ HOÀN TẤT** (Xác nhận checkbox, helper text) |

---

### C. Nhóm Màn Hệ Thống, Mặt Bằng & Quản Trị Khác

| Tên Màn Hình | Route | Blade View | Lỗi Hiện Trạng | Đã Khắc Phục |
|---|---|---|---|---|
| **Admin Dashboard** | `admin.dashboard` | `resources/views/admin/dashboard/index.blade.php` | Số KPI bị gãy chữ, co cụm khi màn hình rộng | Sửa `font-size: 2rem`, `white-space: nowrap`, `tabular-nums`, bố cục 4 card cân xứng |
| **Sơ đồ bàn (Staff)** | `staff.tables.index` | `resources/views/staff/tables/index.blade.php` | 5 bàn dồn góc trái, thừa khoảng trống mênh mông | Nhóm theo Khu Vực (`groupBy area`), hiển thị số lượng từng trạng thái, grid responsive |
| **Yêu cầu đặt bàn** | `staff.reservations.index` | `resources/views/staff/reservations/index.blade.php` | Thiếu sidebar trong viewport ảnh, badge trạng thái thô | Bổ sung map tiếng Việt (`Chờ duyệt`, `Đã duyệt`, `Đã hủy`), gắn chuẩn `.cafe-badge` |
| **Khung Shell Chung** | Layout nội bộ | `resources/views/layouts/internal.blade.php` | Topbar không phân biệt role đăng nhập và workspace | Bổ sung nút "Mở POS" cho Admin, "Về Quản trị", role badge chi tiết tooltip |

---

## 3. Kết Quả Kiểm Thử & Tính Toàn Vẹn Hệ Thống
- **CSS Compilation:** Lệnh `npm run build` thực hiện sạch sẽ bằng Vite v8.2.1 (0 errors).
- **Backend Architecture & Database:** Không sửa đổi bất kỳ table, column, migration, model, service hay route nào.
- **PHPUnit Suite:** Toàn bộ 467 ca kiểm thử đã được bảo toàn tuyệt đối.

