# Kiểm tra và chạy Payment sau merge dev

## Task Status

SUCCESS cho sửa lỗi tích hợp được phát hiện và kiểm tra bản chạy local. Finance contract của nhánh Sales mới còn là task bàn giao riêng.

## Task ID

CAFE-PAYMENT-POST-MERGE-001

Ngày 17/11/2026, theo yêu cầu kiểm tra/chạy code trên feature/payment-management sau merge.

## Summary

HEAD khi bắt đầu là d4aea442, merge dev vào feature/payment-management. Worktree ban đầu sạch, không còn conflict markers hoặc unmerged paths. Đã kiểm tra baseline trước khi sửa, tìm ra lỗi view/redirect pickup và các fixture/assertion bị trộn sau merge. Đã sửa trong phạm vi tích hợp hai luồng đã được user duyệt giữ riêng.

Ứng dụng chạy tại http://127.0.0.1:8000. Đã áp dụng ba migration Payment còn pending trên database local coffee_shop và kiểm tra giao diện thật qua trình duyệt.

## Files Created

- resources/views/public/cart/pickup-index.blade.php — khôi phục view giỏ đến lấy từ dev, tách khỏi delivery cart.
- resources/views/public/cart/_pickup-form.blade.php — giữ lối thêm món đến lấy trên chi tiết sản phẩm, dùng route/cart hiện có của dev.
- reports/task-reports/CAFE-PAYMENT-POST-MERGE-001__report.md.
- reports/task-reports/evidence/CAFE-PAYMENT-POST-MERGE-001/checkout-desktop.png.
- reports/task-reports/evidence/CAFE-PAYMENT-POST-MERGE-001/pickup-mobile.png.

## Files Modified

- app/Http/Controllers/Public/CartController.php — dùng pickup-index và redirect pickup.cart.index.
- resources/views/public/cart/checkout.blade.php — quay lại đúng giỏ pickup.
- resources/views/public/menu/show.blade.php — render form pickup bên cạnh luồng delivery hiện có.
- tests/Feature/AuthenticationTest.php — kiểm tra route thật và badge của delivery cart riêng.
- tests/Feature/CustomerPickupFlowTest.php — dùng production routes, kiểm UI pickup/delivery cùng tồn tại và session độc lập.
- tests/Feature/InternalWorkspaceFlowTest.php — bổ sung request schema vào database fixture để khớp dependencies Order đã merge.
- tests/Feature/PosOrderWorkflowTest.php — bổ sung recipe cho hai happy paths và test từ chối POS không có recipe.
- tests/Feature/StaffOrderCancellationTest.php — assert Order vẫn pending khi mọi request đều bị chặn.
- tests/Feature/StaffOrderItemManagementTest.php — assert item/topping không đổi khi guest/inactive bị chặn.

## Files Deleted

None.

## Implementation Details

### Gate và scope

- Đọc AGENTS, architecture, requirements, plan, frontend và source/tests/routes/migrations liên quan.
- Không có worktree changes sẵn khi bắt đầu task này.
- User đã duyệt tạm giữ delivery tại /cart, pickup tại /pickup/cart, tách thống nhất schema/phân loại Order thành task sau.
- Thay đổi runtime giới hạn CartController và public views phục hồi đường đi pickup. Không sửa Payment/Order/Inventory algorithm hoặc middleware để né tests.

### Lỗi thực tế

CartController từ dev truyền data shape của pickup gồm items có product_name/variant_name và total_cents. Sau merge, controller dùng public.cart.index của delivery, vốn cần id/name/availability và quote labels. Khi giỏ có món, view lỗi Undefined array key id. Khi giỏ trống, trang render được nhưng hiển thị nhầm luồng, nên chỉ kiểm HTTP giỏ trống chưa phát hiện lỗi.

Đã tách pickup-index theo view dev và chỉnh add/update/back checkout về pickup.cart.index. Form đến lấy giữ quantity 1–20 và session customer_cart hiện có. Delivery giữ topping/note và customer_order_cart. Không thay đổi các rule cũ về giá/quantity của pickup trong task này.

### Lỗi fixture/assertion

- Inactive authorization cases giữ assert expected của active mutation từ nhánh khác. Đã sửa expected thành dữ liệu trước thao tác, giữ nguyên HTTP 403 và thêm coverage no side effect.
- Hai POS happy paths không có recipe trong fixture, trong khi dev đã yêu cầu recipe trước preparing. Bổ sung recipe đúng trường hợp và thêm test missing recipe rollback; giữ nguyên validation của Inventory.
- Internal workspace fixtures thiếu customer_order_requests, trong khi Order controller hiện eager-load relation này. Bổ sung schema test.
- Navigation test còn giả định cart/reservation routes chưa tồn tại. Đã kiểm route production và badge của delivery cart, tách pickup session.

### Môi trường local

Đã chạy artisan migrate trên MySQL coffee_shop local. Ba migration đã có trong code được áp dụng thành batch 3 gồm POS checkout hashes, delivery_fee và ba bảng customer_order_requests/items/toppings. Migrations trước của dev vẫn ran. Không có migration mới được viết trong task.

Browser QA dùng Staff demo đã có và các session cart của tab kiểm thử. Chỉ thao tác draft và xem checkout, không submit đơn mới hoặc xác nhận thu tiền trên DB local. Login có cập nhật session/last_login_at theo ứng dụng. Payment/Inventory persistence được kiểm bằng database tests cô lập.

## Tests / Checks Executed

- Git status/log/diff, conflict-marker scan và unmerged-path check.
- Xác minh phpunit.xml dùng SQLite :memory:, testing, cache/session array.
- Full suite baseline trước sửa.
- Chạy nhóm Authentication/CustomerPickup/InternalWorkspace/StaffOrderCancellation/StaffOrderItem/POS sau sửa.
- Chạy full suite sau sửa.
- npm.cmd run build trước và sau sửa UI.
- artisan migrate:status, migrate --no-interaction, route:list --json.
- git diff --check và review diff.
- Browser thật tại local, responsive 1440/768/375.

## Test Results

| Kiểm tra | Kết quả |
| --- | --- |
| Baseline d4aea442 | 478 tests, 458 pass, 8 fail, 2 error, 10 skip; 6563 assertions |
| Nhóm targeted cuối | 73 pass, 684 assertions |
| Full suite cuối | 479 tests, 469 pass, 0 fail/error, 10 skip; 6614 assertions |
| Frontend build | PASS |
| Routes | 155 routes, không có named route trùng |
| Migrations local | Cả ba migration Payment ran trong batch 3 |
| Conflict/diff whitespace | Không có conflict markers/unmerged paths; diff --check PASS |

Các test skipped cần MySQL QA riêng, gồm CustomerPos concurrency, DashboardRevenue, OnlinePickupSchema, InventoryImportConcurrency và ReservationConcurrency. Lần này chưa chạy các test MySQL opt-in, SQLite không chứng minh locking/concurrency MySQL.

Build có warning optional fontaine và hai đường dẫn ảnh chưa resolve tại build time, giống baseline trước sửa. Không thêm package hoặc sửa assets ngoài phạm vi.

### Browser QA

- Menu detail hiển thị chọn variant/topping/note cho delivery và form đến lấy riêng.
- Pickup thêm 2 Latte nhỏ, tổng 60000, giỏ render đúng. Lỗi render khi có món đã được kiểm lại thực tế.
- Delivery thêm 1 Latte nhỏ + 1 kem sữa, tổng món 35000, phí giao 20000, tổng checkout 55000. Session pickup vẫn độc lập.
- Login Staff demo thành công. POS thêm Latte lớn vào draft, tổng 40000; chưa ghi paid khi chỉ thêm draft.
- Orders list, Order Detail của DEMO-V1-SERVED, lịch sử Payment và inbox giao web mở được.
- Pickup cart và delivery checkout kiểm tại 1440/768/375, document scrollWidth không vượt innerWidth. Viewport đã reset sau kiểm tra.
- Đã giữ tab POS làm trang mở cho người dùng.

## Acceptance Criteria

| Tiêu chí | Kết quả | Evidence |
| --- | --- | --- |
| Chạy đúng nhánh Payment sau merge | PASS | Git HEAD d4aea442 và server local |
| Phát hiện và sửa lỗi pickup/delivery bị trộn | PASS | Production-route test và browser giỏ có món |
| Giữ hai cart riêng theo phê duyệt | PASS | Session isolation test và UI hai luồng |
| Bảo vệ rule Admin/Staff/recipe và no side effect | PASS | Targeted/full suite, missing-recipe rollback test |
| UI build và các trang chính hoạt động | PASS | Build, route list, browser |
| Responsive các trang vừa sửa | PASS | 1440/768/375, screenshots |
| Regression đầy đủ trong môi trường mặc định | PASS | 469 passed, 10 opt-in skipped |
| Concurrency MySQL của bản tích hợp | UNVERIFIED | Cần QA database riêng |

## Risks

- Local database đã được nâng schema bởi ba migrations có sẵn. Rollback cần review dữ liệu phát sinh và ràng buộc delivery_fee.
- Hai schema request còn tồn tại riêng theo user approval. Thống nhất nguồn Order/canonical request cần task khác.
- Runtime receipt/completion concurrency MySQL còn chưa xác minh trong lượt này.
- Tính năng Finance contract mới của Ninh chưa được coi là hoàn thành chỉ từ việc regression bản hiện tại pass.

## Blockers

None cho bản local và sửa tích hợp trong scope.

## Assumptions

Yêu cầu kiểm tra/chạy code sau merge cho phép sửa các lỗi tích hợp trực tiếp đã tái hiện. Giữ phê duyệt hai luồng delivery/pickup riêng trước đó.

## Out-of-Scope Findings

- Pickup controller/view cũ từ dev còn float/round hoặc làm tròn giá hiển thị. Task này giữ nguyên logic cũ và chỉ sửa view/route wiring.
- Finance contract đích còn cần operation key Finance, bank reference uniqueness, settlement API và thống nhất lock/ownership theo report bàn giao.
- Placeholder/cảnh báo asset ở build cần task UI/asset riêng nếu muốn xử lý.

## Unverified Items

MySQL concurrency opt-in, nhận tiền thật ngoài đời, browser submit cash/bank/COD trên DB local, refund/giao thất bại và Finance API của nhánh Sales mới.

## Engineer Review Required

Review các file và diff sửa tích hợp, migrations đã áp dụng, và test evidence. Các sửa mới của task đang ở worktree để engineer review/commit.

## Recommended Next Task

Nghiệm thu các sửa tích hợp và chạy MySQL QA riêng. Sau đó chốt/triển khai Finance contract với Ninh theo gói bàn giao. Không tự triển khai task kế tiếp.
