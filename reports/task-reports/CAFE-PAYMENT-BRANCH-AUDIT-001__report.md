# Rà soát Payment và dev theo phân công

## Task Status

SUCCESS cho rà soát và kiểm chứng code trên nhánh Payment. Toàn bộ roadmap phân công còn PARTIAL. Việc tích hợp với dev và triển khai database đang sử dụng cần task riêng.

## Task ID

CAFE-PAYMENT-BRANCH-AUDIT-001

Ngày kiểm tra 16/11/2026. Task hiện tại cho phép commit phần code đã hoàn thiện.

## Summary

Không tìm thấy file có tên chính xác coffeshop.md trong repository, Downloads và thư mục Đồ án đã tìm. Đối chiếu bản phân công tại D:/nam 4 HK1/MNM/Đồ án/coffee-shop-team-work-assignment.md, đặc biệt mục 7. Bản Downloads khác nội dung nên dùng bản Đồ án theo yêu cầu và lịch sử task.

Đã fetch origin thành công. Trước commit, HEAD Payment và origin/feature/payment-management cùng ở 3afb0c14. dev và origin/dev cùng ở fb7e77fd. dev có 19 commit mà HEAD Payment chưa có. Toàn bộ tính năng Payment/Customer mới đang nằm trong worktree chưa commit, do đó số commit phía Payment bằng 0 trước lần lưu này.

Task hiện tại bảo toàn toàn bộ worktree có sẵn. Chỉ bổ sung báo cáo và lưu code đã kiểm chứng. Các thay đổi runtime là công việc đã thực hiện ở các task trước trong chat, gồm Payment, Public Cart và Customer/POS integration.

## Implementation Details

### Đối chiếu phân công hiệu lực

| Owner / gói | Nhánh Payment có cả worktree | dev sau fetch | Kết luận |
| --- | --- | --- | --- |
| Hợp H01–H03 Catalog/nguyên liệu | Có CRUD/menu baseline | Có thêm Catalog fixes, UI và đơn vị nguyên liệu | dev có cập nhật chưa tích hợp |
| Hợp H04 Recipe | Có model và contract recipe dùng variant | Có ProductRecipeController, màn công thức, tests | dev có module quản trị đầy đủ hơn |
| Hợp H05 Nhập/điều chỉnh/lịch sử | Chưa có toàn bộ workflow mới của dev | Có import, adjustment/waste/stocktake, history và tests | Phần này đã hiện diện trên dev |
| Hợp H06 Consume | Có pending → preparing và ledger | Có thêm kiểm tra công thức trống, khóa Order, duplicate ledger và inventory completion | Phải giữ cải tiến dev khi tích hợp |
| Ninh N01 Cart/POS | Có Customer cart riêng và POS trả trước qua Service | Có public CartController cho lấy tại quán/hẹn giờ và POS baseline | Có hai thiết kế cần hợp nhất |
| Ninh N02 Order | Có item/topping/lifecycle/cancel và khóa sửa sau paid | Có Order/lifecycle cùng các cập nhật giao diện/quyền | Hai nhánh cùng sửa OrderService/routes/views/tests |
| Ninh N03 Request | Guest gửi request, Staff accept đúng một Order | Có online_order_requests schema, inbox chỉ đọc; public pickup lưu PickupRequest | Payment có workflow giao hàng mới, schema khác dev |
| Ninh N05–N06 Giao/tracking/completion | Có dispatch khi ready, xác nhận giao, tracking session/token, completed sau served + paid | Có brewing/pickup/online inbox theo luồng khác | Chưa xác minh hành vi kết hợp hai nhánh |
| Đạt D01–D02 Promotion | Có PromotionService, quản trị và tests; Payment gọi quota/consume usage | PromotionService baseline cũng tồn tại | Lần này chỉ mở rộng công thức tổng có delivery_fee |
| Đạt D03 Cash/bank POS | Có PaymentService và POS atomic Order/items/paid/preparing | Không có PaymentService/PaymentController tương ứng trong tree dev | Đã triển khai ở Payment, chưa có trên dev |
| Đạt D04 Web transfer/COD | Có operation xác nhận chuyển khoản và COD khi giao thành công | Chưa có PaymentService tương ứng | Đã triển khai ở Payment, cần tích hợp nguồn request dev |
| Đạt D06 Báo cáo tiền | Có RevenueStatisticsService tổng Payment paid | dev cũng có service này và dashboard | Chưa thể kết luận hoàn thành mọi metric D06 khi metric còn chưa duyệt |
| Toàn T01–T06 | Có auth/customer/table/reservation/layout baseline | Có thêm canOperateSales và cập nhật UI/đặt bàn public | Giữ owner review khi hợp nhất |

N04 hẹn giờ/slot, D05 refund/gateway, retention PII và xử lý giao thất bại giữ theo roadmap/phê duyệt riêng.

### Workflow đã có trên Payment

- POS review tổng server, xác nhận nhận tiền, một transaction lưu Order/item/topping/paid và chuyển preparing qua InventoryService. Idempotency key hash/payload hash chống lặp.
- Web guest gửi tên/SĐT/địa chỉ, món/topping/note, lựa chọn chuyển khoản hoặc COD. Ba bảng customer_order_request* giữ snapshot, phí giao 20000.00 và link Order duy nhất.
- Guest gửi chưa tạo Order/Payment/consume. Staff/Admin active tiếp nhận bằng tài khoản thật mới tạo Order.
- Web chuyển khoản phải xác nhận thực nhận trước paid/preparing. COD được preparing khi chưa paid, chỉ ghi paid trong thao tác xác nhận giao và thu đủ tiền.
- Giao web được user duyệt ánh xạ takeaway, địa chỉ/fulfillment giữ qua request relation. Mọi món ready mới dispatch, xác nhận nhận hàng mới served.
- Paid khóa sửa/hủy. Hoàn tất khi paid, có món served và mọi món chưa hủy đều served; chỉ trả bàn đúng điều kiện.
- Tracking đòi session capability khó đoán và hạn truy cập 7 ngày. Submit giới hạn 5/phút/session và 20/phút/IP.
- Có ba migration mới. Lần audit này không chạy migration trên database ứng dụng.

### Ranh giới tích hợp cần review

dev có các bảng online_order_requests/items/toppings và orders.purchase_type/online_request_id. Payment dùng customer_order_requests/items/toppings với link order_id từ request. Hai schema có thể cùng tồn tại về tên nhưng biểu diễn hai nguồn dữ liệu riêng. Cần quyết định canonical request và kế hoạch chuyển dữ liệu trước tích hợp.

dev có CartController, customer_cart và public cart/checkout/confirmation. Payment có CustomerCartController/CustomerOrderController, customer_order_cart và checkout/tracking giao tận nơi. Cả hai cùng dùng các route/view cart, cần chọn routing và compatibility có chủ đích.

requirements trên dev có extension pickup-only ngày 26/10, còn Payment có các phê duyệt giao hàng/COD/phí ngày 27/10 và các quyết định trực tiếp sau đó. Cần giữ quyết định mới đã duyệt khi giải quyết tài liệu xung đột.

InventoryService trên dev có bảo vệ bổ sung và import/adjustment/history. Tránh thay cả file bằng bản Payment cũ. Cùng lưu ý OrderService, routes/web.php, routes/staff.php, public navbar/cart và Staff Order/POS views.

Danh sách các file cùng thay đổi được so theo HEAD Payment với dev và worktree Payment. Chồng lấn file là rủi ro tích hợp, chưa phải kết quả một lần merge thực tế.

## Tests / Checks Executed

- git status, branch -avv, fetch origin --prune, rev-list/log/tree/diff giữa HEAD và dev.
- Đọc phân công mục 7, AGENTS, architecture, requirements, plan, frontend, contracts và source/tests liên quan.
- Xác minh phpunit.xml dùng testing, SQLite :memory:, session/cache array. Các MySQL concurrency tests chỉ bật khi có database QA riêng.
- Chạy PHP artisan test --compact bằng PHP 8.3.30. Sandbox không đọc được vendor/autoload.php ở lần đầu, đã chạy lại ngoài sandbox theo approval và nhận kết quả đầy đủ.
- npm.cmd run build.
- artisan route:list --path=customer-orders và --path=staff/orders.
- git diff --check.

## Test Results

- Full suite PASS, 432 tests gồm 428 passed, 4 skipped, 6048 assertions, 0 failed. Thời gian test 130871 ms.
- Bốn test skip thuộc CustomerPosMySqlConcurrencyTest (2), DashboardRevenueMySqlTest (1), ReservationConcurrencyTest (1), cần MySQL QA riêng. Lần audit này chưa chạy MySQL concurrency.
- Build PASS. Route list PASS, 8 route customer-orders và 14 route staff/orders.
- Diff whitespace check PASS.
- dev được inspect bằng Git, chưa chạy bộ test riêng trên dev trong lần audit này.
- Browser QA từ task triển khai trước đã được thực hiện theo lịch sử chat. Lần audit này chưa chạy lại browser/responsive và chưa coi kết quả cũ là một lượt test mới.

## Acceptance Criteria

| Tiêu chí | Kết quả | Bằng chứng |
| --- | --- | --- |
| Xác định file phân công và phiên bản dùng | PASS | Bản Đồ án, mục 7; ghi rõ khác tên user gọi |
| Xác định Payment/dev/remote hiện tại | PASS | Fetch và Git refs |
| Liệt kê phần đã có và phần còn thiếu theo owner | PASS | Bảng đối chiếu và inspect source |
| Kiểm chứng code chưa commit trước khi lưu | PASS | Full tests/build/routes/diff |
| Giữ nguyên work và dữ liệu có sẵn | PASS | Không checkout/reset/stash/discard, không migrate DB local |
| Khẳng định nhánh Payment tương thích dev sau merge | UNVERIFIED | Cần task tích hợp riêng |
| Khẳng định mọi gói trong phân công đã hoàn thành | UNVERIFIED | Roadmap và metric/retention còn cần duyệt |

## Risks

- Branch Payment đang dựa trên base cũ, chưa có 19 commit dev. Test xanh trên Payment chưa xác minh code kết hợp.
- Ba migration cần review khi triển khai và đối chiếu schema online sẵn trên dev. MySQL DDL có giới hạn rollback, không tự rollback sau khi có dữ liệu tiền/đơn mới.
- Tracking phụ thuộc session còn tồn tại, 7 ngày là hạn tối đa của capability. Mất session có thể mất quyền xem sớm hơn.
- Xử lý giao thất bại, refund và retention thông tin liên hệ cần rule riêng.
- Phần code lưu gồm thay đổi nhiều task trước và tích hợp nhiều owner. Engineer cần review theo file inventory bên dưới.

## Blockers

None cho audit và commit. Tích hợp dev còn cần thống nhất canonical request/schema/routes và review Inventory/Order mới.

## Assumptions

Dùng coffee-shop-team-work-assignment.md trong Đồ án làm file phân công mà user gọi coffeshop.md. Không tự coi bản Downloads là bản mới nhất.

## Out-of-Scope Findings

dev có workflow pickup và schema online khác với giao hàng hiện có trên Payment. Không sửa hoặc merge hai thiết kế trong task audit này.

## Unverified Items

MySQL concurrency lần này, test trên dev, test sau tích hợp, deployment/migrations trên DB ứng dụng và browser QA mới.

## Engineer Review Required

Review commit runtime và các migration. Tách task tích hợp dev có backup/test database và giải quyết canonical request, routes, snapshot/completion, phí giao, cùng các cải tiến Inventory của Hợp. User đã cấp quyền commit trong yêu cầu hiện tại.

## Recommended Next Task

CAFE-PAYMENT-DEV-INTEGRATION-001 để hợp nhất hai thiết kế Customer/POS/Order và kiểm thử trên base dev. Không tự triển khai.

## Files Deleted

None.

## Files Created / Files Modified

Danh sách bên dưới được chụp từ worktree trước commit, gồm code task trước đang có sẵn. Task audit chỉ tạo report này. Đường dẫn tương đối với repository nêu trên.

### Files Created

- app/Http/Controllers/Public/CustomerCartController.php
- app/Http/Controllers/Public/CustomerOrderController.php
- app/Http/Controllers/Staff/CustomerOrderRequestController.php
- app/Http/Controllers/Staff/PaymentController.php
- app/Http/Requests/Cart/StoreCustomerCartItemRequest.php
- app/Http/Requests/Cart/SubmitCustomerOrderRequest.php
- app/Http/Requests/Cart/UpdateCustomerCartItemRequest.php
- app/Http/Requests/Payment/ConfirmCustomerDeliveryRequest.php
- app/Http/Requests/Payment/RecordPaymentRequest.php
- app/Http/Requests/Pos/CheckoutPosRequest.php
- app/Http/Requests/Pos/StorePosCartItemRequest.php
- app/Models/CustomerOrderRequest.php
- app/Models/CustomerOrderRequestItem.php
- app/Models/CustomerOrderRequestItemTopping.php
- app/Services/CustomerCartService.php
- app/Services/CustomerOrderService.php
- app/Services/PaymentService.php
- app/Services/PosCheckoutService.php
- database/migrations/2026_10_27_000001_add_pos_checkout_keys_to_orders_table.php
- database/migrations/2026_10_27_000002_add_delivery_fee_to_orders_table.php
- database/migrations/2026_10_27_000003_create_customer_order_requests_tables.php
- docs/CUSTOMER_ORDER_CONTRACT.md
- docs/PAYMENT_CHECKOUT_CONTRACT.md
- reports/task-reports/CAFE-CUSTOMER-POS-IMPLEMENT-001__report.md
- reports/task-reports/CAFE-PAYMENT-BRANCH-AUDIT-001__report.md
- reports/task-reports/CAFE-TEAM-DOC-001__report.md
- resources/css/public/cart.css
- resources/views/components/public/money.blade.php
- resources/views/public/cart/_add-form.blade.php
- resources/views/public/cart/_notices.blade.php
- resources/views/public/cart/index.blade.php
- resources/views/public/orders/create.blade.php
- resources/views/public/orders/show.blade.php
- resources/views/staff/customer-orders/index.blade.php
- resources/views/staff/customer-orders/show.blade.php
- resources/views/staff/payments/show.blade.php
- tests/Feature/CustomerOrderWorkflowTest.php
- tests/Feature/CustomerPosMySqlConcurrencyTest.php
- tests/Feature/DeliveryFeeMigrationTest.php
- tests/Feature/PaymentWorkflowTest.php
- tests/Feature/PosCheckoutKeyMigrationTest.php
- tests/Feature/PublicCustomerCartTest.php
- tests/Support/customer_pos_mysql_worker.php

### Files Modified

- app/Http/Controllers/Public/MenuController.php
- app/Http/Controllers/Staff/OrderController.php
- app/Http/Controllers/Staff/PosController.php
- app/Http/Requests/Order/StoreOrderItemRequest.php
- app/Http/Requests/Order/StoreOrderItemToppingRequest.php
- app/Http/Requests/Order/StoreOrderRequest.php
- app/Http/Requests/Order/UpdateOrderItemQuantityRequest.php
- app/Http/Requests/Order/UpdateOrderItemStatusRequest.php
- app/Http/Requests/Order/UpdateOrderItemToppingQuantityRequest.php
- app/Models/Order.php
- app/Providers/AppServiceProvider.php
- app/Services/InventoryService.php
- app/Services/OrderService.php
- app/Services/PromotionService.php
- database-design.md
- plan.md
- requirements.md
- resources/css/app.css
- resources/css/internal/sneat.css
- resources/css/public/menu.css
- resources/views/components/public/navbar.blade.php
- resources/views/layouts/internal.blade.php
- resources/views/public/menu/show.blade.php
- resources/views/staff/home.blade.php
- resources/views/staff/orders/index.blade.php
- resources/views/staff/orders/show.blade.php
- routes/staff.php
- routes/web.php
- tests/Feature/PosOrderWorkflowTest.php
- tests/Feature/PublicMenuTest.php
- tests/Feature/StaffOrderCancellationTest.php
- tests/Feature/StaffOrderItemManagementTest.php
- tests/Feature/StaffOrderManagementTest.php
