# BÁO CÁO AUDIT NGHIỆP VỤ HỢP × NINH (COFFEE SHOP MANAGEMENT SYSTEM)

**Thời điểm thực hiện:** 17/11/2026  
**Phạm vi áp dụng:** Phân công công việc nhóm ngày 26/10/2026 — Điều chỉnh phạm vi đơn giản theo **Mục 7**.  
**Baseline kiểm toán:** Branch `dev`, commit `fb7e77fd` (Merge PR #35).

---

## 1. BẢNG TỔNG HỢP USE CASE HỢP (H01–H06) VÀ NINH (N01–N06)

| UC | Trạng thái | Mã nguồn hiện có (File, Method, Route) | Phần còn thiếu / Cần bổ sung | Dependency | Priority | Hành động tiếp theo |
| :--- | :---: | :--- | :--- | :--- | :---: | :--- |
| **H01** (Category & Product CRUD) | **DONE_VERIFIED** | `CategoryController`, `ProductController`, Model `Category`, `Product`. Routes `admin.categories.*`, `admin.products.*`. Test: `CategoryManagementTest`, `ProductManagementTest`. | Không. | Foundation | P1 | Giữ nguyên, tái sử dụng. |
| **H02** (Variant, Topping, Menu) | **DONE_VERIFIED** | `ProductVariantController`, `ToppingController`, `MenuController`. Model `ProductVariant`, `Topping`. Public route `menu.index`, `menu.show`. Test: `ProductVariantManagementTest`, `PublicMenuTest`. | Không. | H01 | P1 | Giữ nguyên, kết nối vào Cart/POS. |
| **H03** (Nguyên liệu & Tồn kho) | **DONE_VERIFIED** | `IngredientController`, `InventoryOverviewController`. Model `Ingredient`. Ngưỡng tồn và filter trạng thái/cảnh báo. Test: `IngredientManagementTest`. | Không. | Foundation | P1 | Giữ nguyên. |
| **H04** (Công thức sản phẩm) | **DONE_VERIFIED** | `ProductRecipeController`. Model `ProductRecipe`. Định lượng `DECIMAL(12,3)`. Test: `ProductRecipeManagementTest`. | Không. | H02, H03 | P1 | Giữ nguyên, làm đầu vào cho consume H06. |
| **H05** (Nhập/Xuất/Kiểm kê) | **DONE_VERIFIED** | `StockImportController`, `StockAdjustmentController`. Model `InventoryTransaction`. Test: `StockImportWorkflowTest`, `StockAdjustmentWorkflowTest`. | Không. | H03, H04 | P2 | Giữ nguyên. |
| **H06** (Trừ kho khi bắt đầu pha) | **DONE_VERIFIED** | `InventoryService::startPreparing`. Model `InventoryTransaction` (`type=consume`). Idempotent qua `reference_type=order_item`, khóa chống âm kho, kiểm tra thiếu công thức. Test: `InventoryServiceTest`. | Không. | H03, H04, N02 | P0 | Tích hợp vào bước chuyển trạng thái `preparing` của Order. |
| **N01** (Giỏ hàng & Server Quote) | **PARTIAL** | `CartController.php` (Session `customer_cart`). Tính giá server-side, view `public/cart/*`. | Chưa lưu toppings, ghi chú theo dòng; chưa có `line_signature` phân tách dòng; chưa có cơ chế phát hiện giá thay đổi để yêu cầu re-confirm. | H02 | P1 | Nâng cấp `CartController` / `CartService` hỗ trợ options, toppings, line signature, quote validation. |
| **N02** (POS & Counter Order) | **PARTIAL** | `PosController.php`, `OrderService.php` (`createWithItems`, `addItem`, `cancel`). View `staff/home.blade.php`. Test: `PosOrderWorkflowTest`. | Thiếu thiết lập rõ `purchase_type = 'counter'`; thiếu snapshot đầy đủ topping từ POS vào Order; cơ chế chống submit lặp trên POS. | H02, Toàn (Table) | P1 | Cập nhật `OrderService::create` hỗ trợ `purchase_type`, chống submit lặp, hỗ trợ snapshot đầy đủ. |
| **N03** (Online Request & Staff Acceptance) | **PARTIAL** | Migrations `online_order_requests`, `online_order_request_items`, `online_order_request_item_toppings`. `OnlineRequestController` (`index`, `show`). | Chưa có Eloquent Models (`OnlineOrderRequest*`). Chưa có action `accept` và `reject` tại controller. Public checkout chưa lưu vào bảng này. Chưa có logic accept tạo đúng 1 Order nguyên tử. | H02, N02 | P0 | Tạo Models, hoàn thiện endpoint checkout công khai, viết `accept` & `reject` trong `OrderService` & controller. |
| **N04** (Scheduled Pickup) | **PARTIAL** | Migration có cột `pickup_at`, CHECK constraint yêu cầu scheduled phải có giờ nhận. | Form public chưa cho chọn ngày/giờ hẹn nhận đồ; validation timezone `Asia/Ho_Chi_Minh` và khung giờ mở cửa; hiển thị giờ nhận trên Staff inbox. | N03, Toàn (Giờ mở) | P1 | Bổ sung validation giờ mở quán, UI chọn giờ nhận tại checkout và hiển thị tại inbox. |
| **N05** (Hàng đợi pha chế & Giao tại quầy) | **PARTIAL** | `WorkspaceController::brewing`, `OrderController::updateItemStatus`. View `staff/brewing/index.blade.php`. | Giao diện brewing chưa có nút bấm chuyển trạng thái trực tiếp cho Staff thao tác; thiếu thao tác served kiểm tra điều kiện giao hàng. | H06, N02 | P1 | Bổ sung nút chuyển trạng thái `preparing` (gọi H06) -> `ready` -> `served` trên brewing view. |
| **N06** (Tracking, Hủy & Hoàn tất đơn) | **PARTIAL** | `OrderController::cancel` (hủy pending). View `staff/orders/show.blade.php`. | Chưa có trang công khai tra cứu đơn bằng token SHA-256 (`/orders/track/{token}`); chưa có action `complete` kiểm tra điều kiện (tất cả món đã served + thu đủ tiền). | N02, N03, Đạt (Payment) | P1 | Tạo route/view tracking; viết `OrderService::complete` với kiểm tra chặt chẽ điều kiện đóng đơn. |

---

## 2. PHẠM VI BỊ LOẠI TRỪ (OUT OF SCOPE THEO MỤC 7)

Căn cứ theo mục 7 của tài liệu phân công:
1. **Shipper, Delivery, COD:** Không triển khai vùng giao hàng, tính phí ship, phân công shipper, hay luồng COD.
2. **Gateway & Webhook tự động:** Chỉ hỗ trợ `cash` và `bank_transfer` xác nhận thủ công bởi nhân viên/thu ngân.
3. **Slot, Capacity, Tự động hủy (Expiry engine / Scheduler):** Nhân viên chủ động xem giờ nhận và tự điều phối pha chế, không dùng cron scheduler tự động hủy.
4. **Reserve / Release kho trước khi pha:** Không giữ kho lúc khách đặt/nhân viên duyệt. Kho chỉ được kiểm tra và tiêu hao thật sự khi nhân viên bấm "Bắt đầu pha chế" (`pending` -> `preparing`).
5. **Quy trình hoàn tiền tự động (Refund workflow):** Đơn đã thanh toán hoặc đã pha chế nếu hủy sẽ được xử lý thủ công tại quán.

---

## 3. KẾT LUẬN AUDIT VÀ THỨ TỰ TRIỂN KHAI

Hệ thống đã có nền tảng database, catalog và inventory rất tốt từ phía Hợp (H01–H06 đều đã hoạt động và pass test). Phía Ninh đã có khung POS cơ bản và view read-only của online-requests, nhưng luồng nghiệp vụ end-to-end (Cart -> Online/Scheduled Submit -> Staff Accept/Reject -> Brewing Transition -> Served -> Thu tiền -> Completed -> Tracking) cần được kết nối hoàn chỉnh.

