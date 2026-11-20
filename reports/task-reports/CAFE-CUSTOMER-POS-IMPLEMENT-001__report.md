# Task Report

## Task Status

PARTIAL — đã triển khai và kiểm chứng giai đoạn Public Cart có UI thật. Chưa triển khai gửi yêu cầu giao hàng, Staff tiếp nhận, tracking hoặc POS trả trước. Full suite còn hai failure đã quan sát ngay ở baseline trước thay đổi.

## Task ID

CAFE-CUSTOMER-POS-IMPLEMENT-001

## Task Information

- Date: 2026-10-27.
- Prompt revision: prompt trực tiếp trong chat, bắt đầu bằng giai đoạn Public Cart; không có prompt file mới.
- Branch thực tế: `feature/payment-management`; giữ nguyên theo yêu cầu không tự checkout.
- Workspace: `C:\Users\Dat\Documents\GitHub\coffee-shop-management-system`.
- Phạm vi lần thực thi này: mục 4 và các yêu cầu kiểm chứng/report tương ứng; dừng để engineer nghiệm thu giai đoạn.

## Summary

Khách không cần đăng nhập có thể mở chi tiết món, chọn variant, số lượng, topping và ghi chú, thêm vào giỏ, sửa số lượng hoặc xóa dòng. Giỏ dùng session `customer_order_cart`, tách khỏi `pos_cart`. Giá và tên đọc từ Catalog trên server; tiền được tính bằng integer cents, có chặn overflow theo schema. Không tạo Order, Payment hoặc biến động kho.

Trang cart và form chọn món dùng layout public hiện có, thông báo rõ giỏ chưa gửi đến quán. Đã sửa kích thước ảnh trên detail ở mobile vì kiểm tra thực tế phát hiện tràn ngang.

## Source / Context Reviewed

- AGENTS.md, architecture.md, requirements.md, database-design.md, frontend.md, plan.md, Markdown trong docs và hai checkout/customer contracts hiện có.
- BangPhanCongMNM.docx trong thư mục Đồ án; tài liệu phân công Markdown đang mở trong Downloads được đối chiếu thêm.
- Public MenuController, routes, menu/layout/components/styles; các Service Order/Inventory/Payment/Promotion, Staff/POS workflow, FormRequests, Models, migrations và tests liên quan.
- phpunit.xml: `APP_ENV=testing`, SQLite `:memory:`, session array, cache array; test cart cũng assert đúng driver/database trước khi tạo fixture.
- reports/README.md để lưu report theo đúng convention.

## Files Created

- app/Http/Controllers/Public/CustomerCartController.php
- app/Http/Requests/Cart/StoreCustomerCartItemRequest.php
- app/Http/Requests/Cart/UpdateCustomerCartItemRequest.php
- app/Services/CustomerCartService.php
- resources/css/public/cart.css
- resources/views/public/cart/_add-form.blade.php
- resources/views/public/cart/_notices.blade.php
- resources/views/public/cart/index.blade.php
- tests/Feature/PublicCustomerCartTest.php
- reports/task-reports/CAFE-CUSTOMER-POS-IMPLEMENT-001__report.md

## Files Modified

Chỉ các file sau được task cart thay đổi từ baseline sạch của chúng:

- app/Http/Controllers/Public/MenuController.php — cung cấp lựa chọn variant/topping và giá từ server cho detail.
- routes/web.php — bốn route public cho xem/thêm/sửa quantity/xóa cart.
- resources/views/components/public/navbar.blade.php — liên kết Giỏ hàng.
- resources/views/public/menu/show.blade.php — notices và form chọn món.
- resources/css/app.css — import cart styles.
- resources/css/public/menu.css — giới hạn ảnh detail vừa chiều ngang mobile.
- tests/Feature/PublicMenuTest.php — bổ sung toppings vào schema fixture cho detail.

### Pre-existing worktree changes được bảo vệ

Đã lưu và đối chiếu SHA256 trước/sau: **13/13 file không đổi bởi task cart**:

- app/Services/OrderService.php
- database-design.md
- plan.md
- requirements.md
- resources/views/staff/orders/show.blade.php
- routes/staff.php
- app/Http/Controllers/Staff/PaymentController.php
- app/Http/Requests/Payment/RecordPaymentRequest.php
- app/Services/PaymentService.php
- docs/CUSTOMER_ORDER_CONTRACT.md
- docs/PAYMENT_CHECKOUT_CONTRACT.md
- resources/views/staff/payments/show.blade.php
- tests/Feature/PaymentWorkflowTest.php

Các file trên vẫn xuất hiện dirty/untracked trong Git; không tính chúng là implementation của cart. Không checkout, stash, commit, push hoặc merge.

## Files Deleted

None. Fixture SQLite, PHP setup và file baseline lint tạm phục vụ QA đã được dọn sau kiểm tra; không phải source dự án.

## Implementation Details

- `CustomerCartService` quản lý draft/quote, đọc Catalog theo batch, kiểm tra Category active và Product/Variant/Topping available, gồm cả soft-delete.
- Session chỉ lưu ID, quantity, note; mỗi lần thêm dùng UUID riêng, không gộp mất cấu hình. Cả cấu hình giống nhau cũng được giữ riêng và UI nói rõ hành vi này.
- Topping quantity là số phần cho cả dòng món: line subtotal = variant price × quantity món + tổng(price topping × quantity topping).
- HTTP nhận qua FormRequest; Controller chỉ điều phối validation/service/session/redirect. Client price, total, actor, status, customer_id không được dùng.
- PATCH/DELETE chỉ truy cập UUID tồn tại trong chính session hiện tại. CSRF/method spoofing theo web middleware và Blade convention hiện có.
- GET cart tính quote theo giá hiện tại, không thay draft hay Catalog. Dòng hết bán được giữ để khách thấy lỗi và xóa; không hiển thị tổng hợp lệ khi có dòng không thể quote.
- Integer cents và kiểm tra trước phép nhân/cộng bảo vệ precision DECIMAL(12,2)/DECIMAL(15,2), quantity theo signed INT. Note tối đa 500 ký tự theo schema; không thêm hạn mức số dòng, TTL hoặc rate business mới.
- Không sửa .env, migrations, seed_data.sql, dependency manifests/lockfiles, Base Models, vendor hoặc node_modules. Build outputs theo Vite hiện tại, không thêm artifact generated vào Git.

## Tests / Checks Executed

1. Full suite baseline trước source change: `php artisan test --compact`.
2. Targeted bản cuối: `php artisan test --filter='PublicCustomerCartTest|PublicMenuTest' --compact`.
3. Full suite bản cuối: `php artisan test --compact`.
4. `php artisan route:list --path=cart --except-vendor`.
5. `npm.cmd run build` sau các thay đổi frontend, gồm lần cuối sau sửa tràn ảnh mobile.
6. Pint `--test` trên bảy PHP source/test file liên quan; kiểm tra routes/web.php riêng và đối chiếu bản HEAD trong file tạm.
7. `git diff --check`, review tracked diff và toàn bộ source file mới; đối chiếu SHA256 13 file pre-existing.
8. Browser QA bằng skill computer-use, HTTP thật và CSRF form trên server tạm 127.0.0.1:8001 với database SQLite fixture riêng. Không thay database quán; đã dừng server và dọn fixture.
9. Desktop 1440, tablet 768, mobile 375: chọn món/topping/note, thêm hai cấu hình, sửa quantity bằng keyboard Tab/Enter, xóa từng dòng và món cuối; navigation mobile và horizontal overflow.

PHP tests phải chạy ngoài filesystem sandbox vì PHP trong sandbox báo không đọc được bootstrap vendor/autoload.php. Không sửa dependency/ACL/bootstrap để né lỗi môi trường.

## Test Results

| Check | Kết quả thực tế |
| --- | --- |
| Full baseline | 393 tests: 389 pass, 2 fail, 2 skip; 5487 assertions |
| Targeted cuối | 27 pass; 436 assertions |
| Full cuối | 405 tests: 401 pass, 2 fail, 2 skip; 5712 assertions |
| Cart routes | Đúng 4 route GET/POST/PATCH/DELETE, không yêu cầu login |
| Build | PASS; cảnh báo optional fontaine và poster video không resolve lúc build |
| Pint 7 source/test PHP | PASS |
| Pint routes/web.php | FAIL; cùng các fixer fully_qualified_strict_types, blank_line_before_statement, ordered_imports trên bản HEAD. Không mass-format home route ngoài scope |
| Diff whitespace | PASS |
| Bảo vệ work có sẵn | 13/13 hash giữ nguyên |
| Browser | PASS cho các thao tác/viewport đã mô tả |

Hai failure ở baseline và full cuối giống nhau:

- RoleAuthorizationTest::test_admin_can_use_both_interfaces, line 106: expected 200, actual 403.
- UserManagementIntegrationTest::test_edit_role_and_email_are_used_by_authentication_and_authorization, line 80: expected 200, actual 403.

Không sửa middleware/role/module khác để làm suite xanh. Không gọi full suite PASS.

Browser fixture xác nhận: món 30.000,10 × 2 + topping 5.000,25 × 1 = 65.000,45. Sửa món về quantity 1 còn 35.000,35 và topping vẫn quantity 1. Thêm cấu hình variant 25.000,00 + topping 7.000,30 × 2 tạo dòng riêng 39.000,60; tổng 74.000,95. Xóa dòng sau còn 35.000,35, xóa tiếp về giỏ trống. Mobile detail trước sửa scrollWidth 404/viewport 375; sau sửa 360/375, tablet 753/768.

Ảnh QA dùng dữ liệu giả riêng, không phải dữ liệu khách của quán:

- Desktop: C:/Users/Dat/.codex/visualizations/2026/10/19/01a152d1-5ca2-7ac3-8ca5-0c2814bd2239/public-cart-desktop.png
- Mobile cart: C:/Users/Dat/.codex/visualizations/2026/10/19/01a152d1-5ca2-7ac3-8ca5-0c2814bd2239/public-cart-mobile.png
- Mobile form: C:/Users/Dat/.codex/visualizations/2026/10/19/01a152d1-5ca2-7ac3-8ca5-0c2814bd2239/public-cart-form-mobile.png

## Acceptance Criteria

| Criterion của giai đoạn Public Cart | Status / Evidence |
| --- | --- |
| Guest dùng được UI thật, không tài khoản | PASS — guest HTTP tests + browser |
| Session customer_order_cart tách POS, hai session độc lập | PASS — isolation/ownership tests |
| Thêm variant/quantity/topping/note, sửa quantity, xóa dòng | PASS — HTTP tests + browser |
| Khác topping/note không gộp sai, topping không nhân theo số ly | PASS — math/configuration tests + browser |
| Recheck active/available/soft-delete và không tin giá client | PASS — Catalog/tampering tests |
| Quantity sai/overflow bị chặn; không float/round tiền mới | PASS — boundary tests + source review |
| Không Order/Payment/consume/Catalog mutation | PASS — query/side-effect tests trên DB test |
| Public nav, cart totals, empty/error/out-of-stock states | PASS — render/stale-catalog tests + browser |
| Giữ dữ liệu lỗi phù hợp, note escape, CSRF forms | PASS — validation/escape tests, Blade và browser submit |
| Desktop/tablet/mobile, keyboard | PASS — browser 1440/768/375, Tab/Enter |
| Targeted tests/build | PASS |
| Toàn bộ suite xanh | FAIL — hai lỗi quyền Admin được chứng minh có ở baseline |
| Bảo vệ work có sẵn, không tự commit/push | PASS — hash/diff/status |
| Toàn bộ Customer delivery và POS trả trước | UNVERIFIED / chưa triển khai — ngoài giai đoạn đang nghiệm thu |

## Risks

- Full regression còn hai lỗi authorization đã có trước; cần owner xử lý riêng trước khi tuyên bố suite xanh.
- Branch hiện vẫn payment-management và có work Payment chưa commit. Engineer cần review/stage đúng file; không git add toàn bộ nếu chưa muốn gộp Payment.
- Quote cart theo giá hiện tại, chưa chốt snapshot gửi yêu cầu. Việc giữ giá, chấp nhận thay đổi giá và persistence thuộc giai đoạn sau.
- Chưa có business limit số dòng/rate/TTL/retention được duyệt; cần chốt trước public release, không tự áp dụng proposal.
- Build có hai warning đã nêu, chưa thêm package hoặc sửa asset ngoài scope.

## Blockers

None đối với giai đoạn cart. Các giai đoạn schema/submit/delivery/POS cần review thiết kế tương ứng trước khi code: fulfillment mapping, trạng thái giao/completion, phí/vùng giao, xử lý giao thất bại, chi tiết chống trùng và dữ liệu liên hệ.

## Assumptions

- Mỗi lần thêm là một dòng riêng; không yêu cầu tự gộp cấu hình giống hệt. UI thể hiện rõ.
- Cart là draft; giá được cập nhật từ server khi xem/sửa, chưa phải cam kết snapshot Order.
- Không áp dụng rule pickup-only/no-COD trong tài liệu phân công mới để ghi đè prompt hiện tại đã duyệt delivery và COD. Không triển khai phần delivery trong giai đoạn này.

## Out-of-Scope Findings

- Hai failure authorization và style của home route đã có ở baseline; không sửa.
- Tài liệu phân công Markdown mới chuyển ownership Cart/Order/POS và đề xuất phạm vi khác prompt delivery/COD. Team cần đồng bộ owner/contract ở task sau; không phát minh service ports hoặc đổi kiến trúc trong cart.
- Payment/workflow contracts và thay đổi Payment có sẵn không được task này cập nhật; cart không gọi các operation đó.

## Unverified Items

- MySQL concurrency: chưa kiểm tra, SQLite sequential tests không chứng minh concurrency. Cart chưa có persistence Order.
- Gửi request, chống trùng persistence, Staff accept, delivery, tracking, POS paid-before-preparing và regression theo contract mới: chưa triển khai trong giai đoạn này.
- Chưa thực hiện manual test trên database quán của engineer; browser QA dùng fixture SQLite cô lập.

## Engineer Review Required

Review source/diff và nghiệm thu Public Cart trước task kế tiếp. Không coi report này là phê duyệt toàn bộ Customer/POS.

### Manual test trên dự án local

1. Chạy app local hiện có tại http://localhost:8000; nếu cần build lại, đứng tại thư mục dự án rồi chạy `npm.cmd run build`. Không cần migrate cho cart.
2. Mở http://localhost:8000/menu không đăng nhập, chọn một món đang bán để mở detail.
3. Chọn kích cỡ, số lượng 2, một topping quantity 1 và ghi chú; bấm Thêm vào giỏ hàng.
4. Tại http://localhost:8000/cart, kiểm tra tiền món × 2 cộng topping × 1. Sửa quantity món về 1 và Cập nhật: topping vẫn quantity 1.
5. Quay lại món, thêm với topping/ghi chú khác: phải có dòng riêng. Xóa từng dòng, rồi xóa món cuối: hiển thị giỏ trống.
6. Dùng cửa sổ ẩn danh để kiểm tra giỏ khác độc lập. Giỏ POS Staff phải giữ nguyên.
7. Sau khi thêm, Admin đổi đúng Product/Variant/Topping sang unavailable hoặc Category inactive; reload cart phải báo cần kiểm tra, cho xóa dòng và không trình bày tổng hợp lệ.
8. Kiểm tra ở 1440/768/375 và dùng Tab/Enter cho form. Giai đoạn này chưa có nút gửi yêu cầu/thu tiền/giao hàng.

Commit message đề xuất sau review: `feat(cart): add public guest cart with variant and topping options`.

## Recommended Next Task

Review và duyệt chi tiết schema/model public request cho tên/phone/address/payment intent, mapping delivery và các quyết định còn thiếu; sau đó triển khai gửi yêu cầu trong task riêng. Không tự triển khai.
