# ĐẶC TẢ HỢP ĐỒNG NGHIỆP VỤ (CONTRACTS) HỢP × NINH

**Ngày ban hành:** 17/11/2026  
**Chủ thể:** Tích hợp giữa module Catalog/Inventory (Hợp), Sales/Fulfillment (Ninh), Thanh toán (Đạt) và Bàn/Quyền (Toàn).

---

## 1. CONTRACT E1: CATALOG → CART / POS (HỢP → NINH)

### 1.1 Mục đích
Cung cấp thông tin sản phẩm, biến thể, topping, giá chuẩn và tình trạng sẵn sàng phục vụ cho Cart của khách và POS của nhân viên.

### 1.2 Dữ liệu và Interface
- **Entity tham chiếu:** `ProductVariant` (`id`, `product_id`, `name`, `price`, `status`), `Topping` (`id`, `name`, `price`, `status`).
- **Caller:** `CartController`, `PosController`, `OrderService`.
- **Owner:** Hợp (Catalog).
- **Quy tắc giá & Options:**
  - Client không được tự quyết định giá hoặc gửi tổng tiền lên để server lưu.
  - Mọi dòng giỏ hàng phải được server truy vấn lại từ database theo `variant_id` và `topping_id`.
  - Giá được chuẩn hóa theo VND 2 chữ số thập phân (`DECIMAL(12,2)` / `cents`).
  - Biến thể hoặc sản phẩm bị `unavailable` hoặc `trashed` sẽ bị server từ chối ngay lập tức.
  - **Line Signature:** Khóa nhận diện dòng món trong giỏ: `hash(variant_id + sorted(toppings) + note)`. Cùng một món nếu khác topping hoặc ghi chú sẽ được tách thành các dòng khác nhau.
  - Khi giá tại thời điểm duyệt/thanh toán có sự thay đổi so với báo giá ban đầu của khách, server bắt buộc yêu cầu xác nhận lại hoặc gửi lại request.

---

## 2. CONTRACT E2: INVENTORY → PREPARATION (HỢP → NINH)

### 2.1 Mục đích
Đảm bảo kiểm tra tồn kho và tiêu hao nguyên liệu chính xác, nguyên tử, chống trừ lặp khi món bắt đầu được pha chế.

### 2.2 Dữ liệu và Interface
- **Method:** `InventoryService::startPreparing(OrderItem $orderItem, User $actor): void`
- **Caller:** `OrderService::transitionItemStatus($order, $item, 'preparing', $actor)`.
- **Owner:** Hợp (Inventory).
- **Quy tắc nghiệp vụ:**
  - Nhận vào: `OrderItem` (có `variant_id`, `quantity`) và `User $actor` (nhân viên thao tác).
  - Kiểm tra trạng thái: Đơn hàng cha phải ở trạng thái `pending`. Món phải ở trạng thái `pending`.
  - Lấy công thức: Truy vấn `ProductRecipe` theo `variant_id`. Nếu biến thể chưa được cấu hình công thức hoặc định lượng = 0, ném `ValidationException` và giữ món ở trạng thái `pending` (không cho pha, báo Admin cấu hình).
  - Kiểm tra tồn khả dụng: Khóa dòng (`lockForUpdate`) toàn bộ nguyên liệu liên quan trong bảng `ingredients`. Tính `required = recipe_quantity * item_quantity`. Nếu bất kỳ nguyên liệu nào không đủ tồn hoặc đã bị ngừng sử dụng, ném `ValidationException` (không trừ kho, thông báo rõ nguyên liệu thiếu).
  - **Tiêu hao nguyên tử (Transaction & Idempotency):**
    - Trừ `current_stock` của nguyên liệu.
    - Ghi nhận `InventoryTransaction` với `type = 'consume'`, `reference_type = 'order_item'`, `reference_id = $orderItem->id`.
    - Kiểm tra chống trùng: Nếu đã tồn tại bản ghi `consume` cho `order_item` này, từ chối ngay lập tức.
    - Cập nhật trạng thái `OrderItem` sang `preparing`.
  - Không giữ kho (hold/reserve) lúc khách gửi request hoặc lúc nhân viên accept đơn.
  - Sau khi đã chuyển sang `preparing` (đã trừ kho), nếu đơn hàng bị hủy hoặc làm hỏng thì **không tự động hoàn lại nguyên liệu**.

---

## 3. CONTRACT E3: FINANCE → ORDER (ĐẠT → NINH)

### 3.1 Mục đích
Xác định trạng thái thanh toán đáng tin cậy của đơn hàng trước khi cho phép đóng đơn (`completed`).

### 3.2 Dữ liệu và Interface
- **Entity tham chiếu:** `Payment` (`order_id`, `payment_method`, `amount`, `status`, `paid_at`, `created_by`).
- **Adapter / Query Port:**
  - `Order::isPaidInFull(): bool`
  - Đơn được coi là đã thanh toán đủ khi:
    $$\sum \text{amount}(\text{payments where status} = 'paid') \ge \text{order.total\_amount}$$
- **Quy tắc bảo vệ:**
  - Module Sales không tự ý tạo bản ghi `payments` giả lập.
  - Khi nhân viên thu tiền trực tiếp (tiền mặt / chuyển khoản đã đối soát), bản ghi `Payment` được tạo với `status = 'paid'`, gắn với nhân viên thu tiền (`created_by`).
  - Không bao giờ cho phép đóng đơn (`completed`) chỉ vì khách bấm nút "Đã thanh toán" hoặc mới tạo đơn.
  - **Điều kiện đóng đơn (`completed`):**
    1. Tất cả các món trong đơn hàng phải có trạng thái `served`.
    2. Đơn hàng đã thu đủ tiền (`isPaidInFull() === true`).

---

## 4. CONTRACT E4: RESERVATION / TABLE → ORDER (TOÀN → NINH)

### 4.1 Mục đích
Quản lý trạng thái bàn ăn khi tạo đơn `dine_in` và giải phóng bàn khi đơn hoàn tất hoặc bị hủy.

### 4.2 Dữ liệu và Interface
- **Entity tham chiếu:** `CafeTable` (`id`, `area_id`, `table_code`, `status`), `Area` (`id`, `status`).
- **Caller:** `PosController`, `OrderService`.
- **Owner:** Toàn (Table Operations).
- **Quy tắc:**
  - Khi tạo đơn `dine_in`, bắt buộc chọn `table_id`.
  - Khóa theo đúng thứ tự bảo vệ: `Area` -> `CafeTable`.
  - Bàn phải ở trạng thái `available` và khu vực phải `active`.
  - Không được có đơn hàng `pending` khác đang chiếm giữ bàn đó.
  - Khi tạo đơn thành công, chuyển bàn sang `occupied`.
  - Khi đơn hàng hoàn tất (`completed`) hoặc bị hủy (`cancelled`), nếu không còn đơn pending nào khác trên bàn, chuyển bàn về lại `available`.
  - Đơn hàng `online` và `scheduled` bắt buộc là `takeaway` và `table_id = NULL` (không được gán bàn).

