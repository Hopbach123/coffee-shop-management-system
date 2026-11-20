# Sales × Finance — triển khai 17/11/2026

> QA follow-up cùng ngày: migration receipt và C01–C12 đã chạy MySQL8.4.3 tạm, bộ mới8/8 PASS204 assertions; rộng hơn16 test MySQL:15 PASS/1 FAIL511 assertions (link “Lịch sử kho” ở recipe, không phải receipt). Đã sửa lock lifecycle/table và current settlement read có regression MySQL; Reservation harness Windows chuyển pipe sang file. Browser vẫn NOT_RUN, DB dự án chưa migrate. Chi tiết PAYMENT_MYSQL_VERIFICATION.md / SALES_CONCURRENCY_REPORT.md / SALES_UI_QA_REPORT.md; các dòng NOT_RUN MySQL phía dưới là lịch sử trước QA.

## QA completion gates — bản cập nhật hiện hành
| Gate | Status | Evidence |
| --- | --- | --- |
| 1 Database | PASS MySQL8.4.3 test | Fresh/upgrade/status/generated unique và legacy checks |
| 2 Concurrency | PASS bộ C01–C12 mới | Hai PHP/PDO process, C12 HTTP kernels, 8 tests204 assertions |
| 3 Functional | PASS Service-level MySQL | Counter/online/scheduled paid+consume+ready+served+complete, không auto pha khi paid |
| 4 UI | NOT_RUN / BLOCKED automation | Browser access host unavailable; chưa có 375/768/1440 evidence |
| 5 Deployment | NOT_APPLIED | Không chạy migration coffee_shop; chờ review/backup/duyệt riêng |

Kết luận tổng vẫn PARTIAL. Bộ MySQL mở rộng không hoàn toàn xanh vì link recipe missing; không báo SUCCESS toàn dự án. Runtime chỉ sửa lock lifecycleOrder (table trước Order cho dine-in) và locking settlement read; không sửa migration/Inventory/Payment writer rule để test xanh. Tests mới: SalesFinanceMySqlTest + sales-finance-worker; harness Reservation chuyển file output; old schema test so từng cột legacy thay so thêm metadata derived mới, paid label cập nhật đúng UI, assertion recipe link giữ FAIL. No source deleted/dependency installed. Full mặc định 467 total451 pass16skip6126 assertions; 16skip là8 cũ +8 MySQL QA mới chưa bật opt-in trong full default. MySQL riêng đã mở cả16:15 pass1fail511 assertions. Không cộng hai suite thành số tests duy nhất giả vì có overlap.

## Task Status
PARTIAL — writer đã nối thật trong source/HTTP tests; chưa nghiệm thu MySQL concurrency, browser và chưa áp migration vào DB dự án.

## Task ID
SALES-FINANCE-RECEIPT-001

## Summary / Git
Giữ nhánh `feature/order-requests-scheduled-pickup`, baseline HEAD fb7e77fd. Owner cho phép sửa Finance đã bàn giao. PaymentService chưa có trong workspace lúc bắt đầu, nên đọc snapshot commit e1e16fc2 và tích hợp chọn lọc vào tên PaymentService; không copy context OrderService/InventoryService/CustomerOrderRequest/COD của nhánh Đạt. Worktree staged/unstaged trước task được giữ, không commit/push/merge/reset hoặc switch branch.

## Files Created
- app/Services/PaymentService.php
- app/Http/Requests/Payment/RecordPaymentRequest.php
- database/migrations/2026_11_17_000001_add_payment_receipt_contract.php
- tests/Feature/PaymentWorkflowTest.php
- Report này.

## Files Modified — lượt Finance này
OrderService (delegate writer + Finance settlement checks); Staff/OrderController (FormRequest, payment schema/intent, canCancel); Payment custom model (decimal casts); PromotionService (chặn thay tổng sau paid/refunded); staff/orders/show (form/receipt); requirements.md/database-design.md (extension owner duyệt); SALES_IMPLEMENTATION_PROGRESS.md (trạng thái). File khác trong status là thay đổi có sẵn, không nhận là task này làm.

## Files Deleted
None.

## Implementation Details / API thật
```php
PaymentService::recordPaid(Order $order, array $attributes, User $actor): Payment
PaymentService::settlementForOrder(Order $order): array
OrderService::recordPayment(...) // chỉ delegate, không ghi Payment
```
Attributes: payment_method cash/bank_transfer; operation_key 16–64 ký tự ASCII alphanumeric/_/-; received_amount chuỗi decimal tối đa 13 chữ số nguyên/2 số lẻ; received_confirmation; transaction_code bắt buộc cho bank. Tổng lấy từ Order server đã chốt, không từ browser. Không gọi legacy refreshTotalsForPayment; không lấy giá catalog mới để đổi snapshot Order.

Settlement gồm order_id, payment_status, total_amount, paid_amount, remaining_amount, payment_method, payment_reference, payment_id, paid_at. Tiền string decimal, phép tính integer cents. Dùng dưới Order lock khi quyết định served/complete. Order::isPaidInFull vẫn là helper đọc exact cho UI; Sales command gọi read Finance thật.

| Issue | Root cause | Files changed | Fix | Test | Status |
| --- | --- | --- | --- | --- | --- |
| Paid/served vòng chặn | Writer cũ đòi served | PaymentService | Cho thu pending với item pending/preparing/ready/served | Cash/bank trước served, HTTP ready→paid→served | PASS SQLite |
| Thu tiền tự pha/complete | Finance gọi Sales commands | PaymentService | Không phụ thuộc OrderService/InventoryService, không sửa lifecycle | Item/Order còn pending sau paid | PASS SQLite |
| Mismatch schema request | customerRequest/payment_option legacy | Writer/controller caller | Không dùng relationship legacy; nhận Order hiện hành | Ba purchase_type cash/bank | PASS SQLite |
| Tiền thừa sai/nghĩa amount | Thiếu received/change columns | Migration/PaymentService/Model/UI | amount áp cho đơn; received/change riêng | 50−35.50=14.50 | PASS SQLite |
| Bank thiếu kiểm chứng/ref | Chỉ checkbox/regular index | Request/writer/migration | Staff thực nhận, amount exact, bank key UNIQUE | Wrong amount/reference reused reject | PASS SQLite |
| Thu lặp/key conflict | Không key Finance | Migration/writer | operation_key UNIQUE + payload_hash + Order lock | Replay trả ID cũ, key khác payload reject, second cashier reject | PASS replay; concurrency NOT_RUN |
| Hai paid/Order | Chỉ code exists | Generated paid_order_key UNIQUE | CASE paid THEN order_id ELSE NULL, DB unique | Duplicate insert rejected | PASS SQLite, MySQL NOT_RUN |
| Đổi tổng sau paid | Promo có thể sửa tổng | PromotionService | Reject paid/refunded trước áp promo | Existing promotion regression | PASS targeted; race chưa xác minh |

### Cash/bank/idempotency
Khóa actor → Order → items → payment/promotion trong transaction (retry deadlock tối đa 3). Bank key chuẩn hóa trim/ASCII uppercase rồi SHA256; phạm vi mã đầy đủ giao dịch của một quán, nhập bank/account/reference nếu ngân hàng không cấp mã toàn cục. Unique giữ lịch sử kể cả refunded. Cash phải đủ tổng, change=received−total; bank phải bằng tổng, change0. Không nhận split/partial.
Payload hash bind Order/actor/method/received/reference. Cùng key/cùng payload trả receipt cũ, kể cả Order sau đó completed; key đổi payload trả validation conflict. Actor khác/new key không tạo paid lần hai. UNIQUE operation/bank/paid_order_key là bảo vệ DB; catch collision trả replay đúng hoặc lỗi rõ. Không dựa disable button.

### Promotion
Tận dụng primitives lockPromotionForPayment/consumeUsageAfterPaid trong cùng transaction; không viết rule giảm giá thứ hai. Nếu deployment đầy đủ có order_promotions thì gọi primitive; fixture thiếu bảng không giả promotion. Không tự thay tổng sau paid.

### Migration / lịch sử
Chỉ thêm migration mới, giữ Payment cũ/amount/status. Preflight duplicate paid và bank refs trước DDL; backfill canonical bank key, metadata cash cũ để NULL vì không biết tiền nhận. Rollback từ chối khi có operation receipt mới. SQLite memory đã thử up/down/up với legacy row và giữ nguyên chứng từ; MySQL DDL/index/upgrade/concurrency NOT_RUN. Không chạy migrate/migrate:fresh trên DB dự án. DDL MySQL không atomic; cần backup + bản sao DB test trước upgrade; migration sẽ dừng nếu lịch sử có duplicate cần đối soát.

### Routes/UI
Giữ POST staff.orders.payments.store → Staff/OrderController → FormRequest → OrderService delegate → PaymentService duy nhất. Không tạo controller/writer thứ hai cho cùng action. Order Detail có cash/bank, received amount, bank reference, checkbox xác minh thực nhận, key ổn định theo session/Order, receipt code/method/actor/paid_at/received/change sau thành công. Lỗi Laravel validation/catch QueryException không báo thành công. Khi thiếu migration, UI không bật form và writer fail rõ. Dùng template và token sẵn, không đổi CSS shared. Không có preview tiền thừa bằng JS; tiền thừa xác nhận hiển thị từ receipt backend, tránh một nguồn tính tiền UI thứ hai.

## Tests / Checks Executed
- SQLite :memory: xác minh trong test, không DB dự án.
- PaymentWorkflowTest: cash receipt/change/replay; key conflict; second cashier; bank exact/duplicate; wrong amount; cancelled/inactive/unconfirmed; HTTP guest/paid-before-served/completion; DB UNIQUE; migration legacy/rollback guard; cả 3 purchase_type qua Finance delegate.
- Test fixtures là Finance receipts chỉ trong test, không insert production.
- `php artisan test --filter="PaymentWorkflowTest|Promotion"`: xem storage/logs/payment-promotion-final.json.
- Full: storage/logs/sales-finance-regression.json; lần cuối sau sửa import fixture, không đổi rule runtime để test xanh.
- Pint các PHP task thay đổi PASS; npm run build PASS (fontaine optional + 2 runtime images warnings có sẵn).
- git diff --check toàn worktree còn trailing whitespace có sẵn ở checkout/online-requests/show từ thay đổi UI trước task; không mass-format/reset file đó.

## Test Results
Targeted cuối: **25 PASS, 260 assertions**. Full cuối: **459 total = 451 passed + 8 skipped**, 6126 assertions, 0 fail/error, exit0 (~27.8s); không gộp skipped thành pass.
MySQL: NOT_RUN; 8 skipped cũ gồm Inventory concurrency3, OnlinePickupSchema3, Reservation1, Dashboard1. Chưa có dedicated DB sẵn và chưa bật opt-in; không dùng SQLite để tuyên bố tranh chấp Finance/Sales/MySQL đã đạt. Chưa bổ sung multi-process Payment runner riêng, concurrent-confirmation và Payment/Sales transition vẫn UNVERIFIED.
Browser/375/768/1440/keyboard: NOT_RUN trong task này; browser host ở lượt kiểm tra trước unavailable, chưa dựng test app/DB mới. Build không thay browser QA.

## Acceptance Criteria
PASS trong SQLite: cash/bank trước served, đủ/thiếu/sai amount, change, reference duplicate, replay/conflict, actor check, cancelled reject, paid không preparing/complete, paid+served complete qua HTTP, scheduled không tự pha, online/counter/scheduled thu thật bằng writer trong test. Existing suite có unpaid served reject, thiếu tồn/consume/replay.
UNVERIFIED: MySQL concurrent confirmations/transition, MySQL upgrade/indexes, real browser UI/responsive; toàn bộ demo trên DB Laragon chưa nâng schema. Không tuyên bố SUCCESS end-to-end.

## Risks
Generated UNIQUE cần MySQL engine/version phù hợp; legacy duplicate chặn upgrade; backup bắt buộc trước môi trường có dữ liệu. Bank identity một quán cần Staff nhập định danh đủ; đa account tự động chưa scope. Runtime direct insert của module khác phải dùng PaymentService, không được bỏ qua reference/key. Existing table/POS/Promotion khóa có thể cần kiểm deadlock cross-domain riêng. Payment code collision trả lỗi an toàn, không tự tạo chứng từ mơ hồ. Không thêm DB CHECK cho received/change ngoài writer; cột nullable phục vụ legacy, caller phải dùng service.

## Blockers / Demo limitations
Không còn blocker thiếu writer trong source, nhưng DB dự án chưa apply migration nên thu thực tế vẫn bị khóa. Cần review/test MySQL và engineer cho upgrade trước demo. Giờ quán của Toàn vẫn phải được cấu hình để scheduled submit/accept. Không dựng success giả bằng insert receipt thủ công.

## Assumptions
Mục 7 pickup-only; tiền VND decimal2; Staff xác minh thực nhận; không auto preparing/completed/refund/stock restore. Không nhập shipment/COD/customer_order_requests từ snapshot Đạt.

## Out-of-Scope Findings
Browser/môi trường và giờ quán dependency nêu trên; không sửa unrelated staged/unstaged UI changes.

## Unverified Items
MySQL concurrent payment, SQL upgrade thật, browser và production acceptance.

## Engineer Review Required / Recommended Next Task
Review writer/migration/bank identity và lock order với Đạt/Hợp; dedicated MySQL mới để chạy schema upgrade + multi-process confirmation/transition, rồi browser ba kích thước. Chỉ apply migration DB dự án sau review/backup theo chỉ dẫn mới. Không tự commit/push/merge.
