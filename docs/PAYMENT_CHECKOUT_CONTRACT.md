# Payment checkout contract

## Finance completion — user approval 18/11/2026

Các xác nhận trong chat ngày 18/11 bổ sung Finance API có operation key/payload hash bền vững, tham chiếu chuyển khoản duy nhất cho receipt mới, nhập tiền khách đưa và tính tiền thừa. D06 được duyệt theo Payment paid/paid_at, kỳ ngày Việt Nam, cash/bank, số đơn và danh sách chứng từ. requirements §15 ghi phạm vi hiện hành.

- `PaymentService::confirmCash(orderId, operationKey, receivedAmount, actor)` nhận cash tối thiểu bằng tổng Order, trả SettlementResult cùng tiền thừa. `confirmBankTransfer(orderId, operationKey, bankReference, verifiedAmount, actor)` yêu cầu mã tham chiếu và đúng tổng tiền. Các API chỉ dùng sau khi nhân viên thực nhận và caller Sales đã kiểm điều kiện nguồn đơn. `settlementForOrder(orderId)` đọc Payment thật, paidInFull chỉ true khi có đúng một paid đủ tổng và paid_at hợp lệ.
- Khóa actor → Order → items → Payment, xác minh actor active và giữ transaction khi tái tính tổng qua primitive OrderService. Replay cùng nội dung trả receipt cũ trước khi xét Order status hiện tại. Khác actor/order/method/amount/reference bị chặn. Operation key và payload lưu SHA-256, giới hạn key 200 ký tự. Bank reference trim và kiểm trùng không phân biệt chữ hoa/thường. Mã tham chiếu trên form checkout cũ vẫn tùy chọn, khi có thì phải duy nhất.
- API Finance mới giữ trách nhiệm ghi receipt/consume promotion usage. Sales điều phối preparing/completion. Ba operation POS/web/COD hiện hữu vẫn giữ preconditions và orchestration đã kiểm thử.
- Payment.amount là phần tiền áp vào Order. received_amount là cash khách đưa, change_amount là chênh lệch. Legacy form bỏ trống cash receipt nghĩa nhận đúng tổng. Report cộng amount. Metadata mới nullable cho lịch sử.
- Migration `2026_11_18_000001_add_finance_receipt_keys_to_payments_table` thêm hai operation hashes, bank_reference_hash unique nullable và received_amount/change_amount DECIMAL(15,2). Không backfill reference cũ vì lịch sử có thể trùng; writer kiểm tham chiếu lịch sử trước thu mới. Rollback làm mất metadata mới, cần bảo toàn dữ liệu trước khi chạy trên môi trường đã phát sinh receipt.

Các đoạn baseline lịch sử dưới đây giữ mốc thời gian của chúng. Trạng thái source/test mới nằm trong báo cáo CAFE-FINANCE-COMPLETE-001.

## Approval delta — CAFE-CUSTOMER-POS-COMPLETE-001, 27/10/2026

User đã duyệt: POS key hash unique + payload hash trên Order; final server quote recheck; receipt failure giữ cart/key và kiểm tra trước retry, không thu hai lần; chặn item/topping mutation và cancel sau paid; completed chỉ khi paid + ít nhất một served + mọi món chưa hủy served; trả occupied khi completion và không còn pending dine-in khác, không ghi đè maintenance.

Phí giao web cố định 20000.00 VND, lưu delivery_fee riêng, cộng vào total; không phải tax. Web mapped takeaway theo user, xác định giao hàng qua request relation. Không giảm phí giao trong phép tính discount trên subtotal món.

Operations runtime được tách: `recordPosPrepaymentAndStartPreparing` chỉ gọi trong outer POS creation transaction; `confirmCustomerBankTransfer` kiểm tra request accepted/bank intent, xác nhận tiền và chuẩn bị; `recordCustomerCodReceipt` chỉ trong delivery confirmation transaction sau khách nhận; `recordPaid` giữ compatibility cho Order cũ đã served. Controller lấy nguồn từ liên kết database, không nhận cờ skip completion/allow unserved từ client.

COD dispatch chưa paid. Xác nhận khách nhận và thu đủ mới served/paid/completed cùng transaction. Bank transfer đã paid không tạo Payment thứ hai khi giao; final served hoàn tất Order. Không consume lại khi thu COD/giao món. Các approval này thay gate P01/P02/P04–P07 tương ứng bên dưới; MySQL concurrency vẫn cần kiểm tra riêng.

Task: CAFE-PAYMENT-DOC-001. Ngày: 26/10/2026.
Owner: Đạt, theo BangPhanCongMNM.docx. Branch: feature/payment-management.

## Cập nhật có thẩm quyền — 27/10/2026

Task thay đổi: `CAFE-CUSTOMER-POS-WORKFLOW-002`, giai đoạn DOC. **POS trả trước đã duyệt. User chọn “cả 2” cho web giao đến địa chỉ: chuyển khoản trước hoặc COD. COD là ngoại lệ cho web, cho chuẩn bị trước khi paid. Completion/trả bàn và thao tác ghi nhận giao/thu vẫn còn gate; chưa sửa source runtime.** Nội dung từ mục “Baseline lịch sử” xuống cuối lưu bằng chứng contract cũ, không phải rule chung áp dụng cho các operation mới.

### 1. POS tại quán — luồng đã duyệt

Chọn món/topping/số lượng → review tổng server → nhân viên nhận tiền → xác nhận đã nhận đủ tiền → lưu Order + Item/topping + Payment paid → chuyển các món pending → preparing qua InventoryService.

- Không yêu cầu served trước paid cho operation POS này. Điều kiện served-before-paid ngày 26/10 đã bị thay thế **trong phạm vi POS trả trước tại quán**.
- Order lưu trước Payment trong transaction do payments.order_id FK, không phải thao tác tạo Order riêng trước khi nhân viên nhận tiền.
- Một outer transaction bao trùm actor/idempotency, availability/bàn, Order/items/toppings, tổng tiền server, promotion, paid và preparing. Một lỗi rollback toàn bộ database; không có paid hoặc consume một phần.
- Actor là Admin/Staff active theo requirements; created_by của Payment/ledger là actor thật, orders.user_id là creator thật; không ghi đè creator bằng người trả tiền.
- Cash/bank_transfer là ghi nhận thủ công sau xác nhận đã nhận đủ tiền. Không suy ra đã nhận tiền từ method/reference; transaction_code bank_transfer vẫn optional, tối đa 100 ký tự theo schema.
- Không split/partial, không Payment zero, không paid trùng; tiền DECIMAL và snapshot server, không float hoặc giá client.
- Inventory chỉ qua pending → preparing; không thêm phép trừ stock trong Controller hoặc PaymentService. Transaction này không tự completed Order hoặc giải phóng bàn.
- Clear POS cart sau commit, redirect Order Detail. Trước khi xác nhận nhận đủ tiền không tạo Order/paid/consume từ checkout POS này.

### 1.1 Web giao đến địa chỉ — hai nhánh đã duyệt

- **Chuyển khoản trước:** khách chọn phương thức khi gửi request; Staff tiếp nhận bằng actor thật và xác minh tiền thực nhận, rồi mới ghi paid/chuyển preparing theo workflow atomic cho Order đã liên kết. Không tạo Order thứ hai. Không coi mã giao dịch, ảnh biên lai hoặc thao tác guest là xác nhận đã nhận tiền.
- **COD:** Staff tiếp nhận Order được phép chuẩn bị/giao trước khi thu. Payment chưa paid trong thời gian này; chỉ ghi paid sau khi người nội bộ có quyền xác nhận đã thu đủ tiền khi khách nhận món. Không gọi workflow POS trả trước hoặc tạo paid giả để mở Inventory. Mọi consume vẫn chỉ khi item pending → preparing qua InventoryService.
- Đây là lựa chọn **thời điểm/cách thu**, không tự thêm enum cod vào payments.payment_method vốn chỉ cash/bank_transfer. Contract cần lưu payment intent đã chọn trong request và ánh xạ method thực nhận khi thu; chi tiết cột/validation còn migration review. Không tạo pending/failed Payment mới chỉ vì khách chọn COD; giữ baseline paid-only recording và history cũ.
- Không tự hoàn tất Order lúc gửi COD/accept/preparing. Sự kiện giao thành công, người xác nhận thu, thao tác kết thúc và xử lý giao thất bại còn cần contract cụ thể; không tự thêm shipper role hoặc gateway.

### 2. Ba chiều trạng thái và compatibility

| Thao tác / nguồn | Payment | Order | Item / bàn | Trạng thái quyết định |
| --- | --- | --- | --- | --- |
| POS trả trước commit thành công | Một paid | Không tự completed; còn pending để pha chế | Món preparing; không trả bàn sớm | ĐÃ DUYỆT 27/10 |
| Chuẩn bị/phục vụ tiếp | Không thu hoặc consume usage lần hai | Completion trigger cần chốt | preparing → ready → served theo workflow hiện có | Lifecycle đã có; completion mới CHỜ DUYỆT |
| Request web vừa gửi | Không suy ra đã paid | Chưa có Order theo phương án A | Chưa preparing/consume/đổi bàn | ĐÃ DUYỆT phạm vi submission |
| Web chuyển khoản trước | Chỉ paid sau xác nhận thực nhận | Tạo bằng actor thật; không auto-completed | Chỉ preparing sau paid | ĐÃ DUYỆT phương thức/thời điểm 27/10; integration còn gate |
| Web COD | Chưa paid khi chuẩn bị/giao; paid sau xác nhận thu đủ lúc nhận | Tạo bằng actor thật; không auto-completed lúc accept | Cho preparing trước paid qua InventoryService | ĐÃ DUYỆT ngoại lệ COD 27/10; thao tác giao/thu còn gate |
| Order đã tồn tại, dùng trang Payment cũ | Source hiện recordPaid đòi served, paid rồi completed/trả bàn | Hành vi hiện hữu, chưa refactor | Không được gọi operation cũ cho POS trả trước | Compatibility giữ/bỏ luồng này cần review riêng |

Không dùng cờ HTTP như allow_unserved hoặc skip_completion để khách chọn bỏ rule. Tách public operation của Service theo workflow, với preconditions cụ thể và actor nội bộ; tên/signature chỉ chốt khi implementation task sẵn sàng. Không đơn giản xóa check served khỏi recordPaid cũ, không để paid-before-preparing và auto-completed cùng xảy ra cho một checkout.

Operation ghi paid cho POS không tự complete/release/consume stock; outer orchestrator gọi OrderService/Inventory qua public contracts. Public workflow cho Order đã tồn tại cần quyết định riêng trước khi đổi behavior của endpoint đó. Không tạo hai Payment cho một Order hoặc dùng việc đã paid để gọi lại side effects.

Promotion trong transaction **POS/web trả trước**: khóa Order và xác minh totals → gọi lockPromotionForPayment trước paid → lưu paid → consumeUsageAfterPaid đúng một lần → chuyển preparing qua workflow Order/Inventory → commit. Nếu promotion/Inventory/storage lỗi thì rollback mọi phần. COD chuẩn bị và thu tiền là hai thời điểm riêng: usage chỉ sau paid, không được tăng lúc accept/preparing. Quota có thể thay đổi trước lúc thu COD; cần review contract owner nếu áp Promotion, không tự bỏ check hoặc rollback bằng việc xóa ledger pha chế đã commit. Không sửa nội bộ PromotionService hoặc thuật toán Inventory.

### 3. Decisions cần engineer duyệt trước phần phụ thuộc

| ID | Quyết định / bằng chứng | ĐỀ XUẤT reviewable | Gate |
| --- | --- | --- | --- |
| P01 | Paid trước phục vụ nên không thể auto-complete tại Payment | Chỉ completed khi có paid và ít nhất một món served, mọi món chưa hủy đều served; trigger trong transition cuối cùng, completed_at server; không lặp usage/paid | Completion task BLOCKED |
| P02 | Dine-in paid chưa đồng nghĩa khách đã được phục vụ hoặc rời bàn | Giữ occupied khi đang preparing/ready; khi completed theo P01 chỉ occupied → available nếu không còn pending dine-in khác; không ghi đè maintenance, takeaway không đổi bàn | Dine-in release task BLOCKED |
| P03 | User chọn “cả 2”: chuyển khoản trước hoặc COD cho web giao tận nơi | Hai nhánh ở mục 1.1 là rule mới đã duyệt. Còn thiết kế payment intent, người/thao tác xác nhận giao/thu, completion và ngoại lệ giao thất bại | ĐÃ DUYỆT phương thức và ngoại lệ COD; chi tiết integration còn gate |
| P04 | DB rollback không hoàn tiền thật đã nhận | Trước thu có preflight giá/availability/stock để giảm lỗi nhưng vẫn recheck dưới lock; khi lưu lỗi giữ cart/key, kiểm tra lại kết quả, không thu lại; nếu thiếu stock không thể tiếp tục thì chuyển người phụ trách xử lý thủ công, không tự ghi refund/failed hay tự đổi món | Xác nhận quy trình ngoại lệ trước production/runtime task |
| P05 | Paid Order mới vẫn pending; các endpoint cũ có thể thêm món vào pending Order | Đề xuất khóa add/update/remove item/topping/cancel làm đổi đơn đã paid; lifecycle hợp lệ vẫn được đi tiếp; sửa sau thu/refund để scope khác | Amount/history guard task BLOCKED |
| P06 | Chỉ kiểm tra paid theo order_id không chống được tạo hai Order từ cùng POS cart | Duyệt persistence idempotency tối thiểu trước migration; cùng key/payload/actor trả kết quả cũ, payload/actor sai bị chặn | POS idempotency/backend task BLOCKED |
| P07 | Price có thể đổi sau review hoặc sau khi tiền đã nhận | Quote server gắn draft/version; nếu lệch tại final recheck không silently tăng tiền, không ghi paid sai; đi theo P04 để đối soát. Không coi preflight là giữ giá/giữ stock | Quote/receipt UX cần acceptance |

P01/P02/P04–P07 là **đề xuất, chưa được user duyệt**. P03 đã duyệt cả chuyển khoản trước và COD; COD thay rule trả trước riêng cho web. Không tự tích hợp gateway hoặc coi ảnh biên lai/mã giao dịch do khách nhập là đã nhận tiền. Với giao hàng, mapping served/completed, giao thất bại và phí/vùng giao cần task thiết kế riêng. Không tự mở refund, không hủy paid Order để né xử lý tiền đã nhận. Đơn miễn phí/total 0 vẫn bị chặn theo contract đã duyệt; xử lý free order cần task riêng.

### 4. Idempotency proposal — chưa tạo migration

Đề xuất A tối thiểu cho POS: thêm migration tiếp theo cho `orders.pos_checkout_key_hash CHAR(64) NULL UNIQUE` và `orders.pos_checkout_payload_hash CHAR(64) NULL`; null cho Order cũ/non-POS. Server-issued random key gắn draft/actor, hash canonical cart + quote + loại đơn/bàn + method; không đưa raw secret hoặc contact vào log. orders.user_id hiện có là actor scope. Cần review precise canonical payload và retry lifetime trước code.

Đề xuất B thay thế: bảng receipt/checkout-attempt riêng có key unique, payload hash, actor và order link để lưu kết quả/idempotency. Chi phí và retention khác; không tự chọn hoặc tạo cả hai. Cả hai đều là schema proposal, chưa được duyệt bởi việc user chọn ba bảng Customer request.

Unique constraint + transaction/locking phải chống double submit qua nhiều workers; session lock đơn thuần và unique payment_code không đủ. Retry cùng key sau timeout phải tìm kết quả đã commit và xác minh actor; không sinh key mới hoặc Order mới khi chưa biết kết quả cũ. Key/mapping sống đủ để xử lý browser back/retry; lưu kết quả không được phá history. Nếu session write sau commit thất bại, vẫn có thể truy hồi đúng actor bằng key đã cấp và được lưu trước submit.

### 5. Evidence nguồn và file dự kiến sửa ở các task sau

- `app/Services/PaymentService.php:23` recordPaid; dòng 46–47 kiểm tra served; dòng 78 completed; releaseTable được gọi ngay sau đó.
- `app/Http/Controllers/Staff/PaymentController.php:25` canPay vẫn chỉ served. `app/Http/Requests/Payment/RecordPaymentRequest.php` có confirmation, phương thức và optional reference.
- `app/Http/Controllers/Staff/PosController.php:64` checkout hiện createWithItems và clear cart, chưa nhận tiền/preparing. OrderService.createWithItems chỉ truyền variant/quantity; POS mới phải giữ note/topping qua workflow public phù hợp.
- `app/Services/OrderService.php:366` transitionItemStatus gọi InventoryService.startPreparing; `InventoryService.php:16` sở hữu transaction stock/ledger. Không copy logic.
- `tests/Feature/PaymentWorkflowTest.php` test served-before-paid/auto-complete là evidence baseline source, không phải bằng chứng POS mới đã chạy. `PosOrderWorkflowTest` hiện kiểm tra tạo Order pending, cần tests riêng cho prepaid theo contract được chốt.
- Các file runtime dự kiến: POS/Payment Controller, FormRequest tương ứng, Order/Payment Service + orchestration khi cần, POS/order/payment views, targeted tests; migration chỉ sau P06 được duyệt. Guard paid mutation thuộc OrderService và tests theo P05, không sửa Inventory/Promotion nội bộ.

### 6. Acceptance matrix và manual test tương lai

1. Chưa xác nhận nhận tiền: không tạo Order/paid/consume; giữ draft.
2. Cash/bank_transfer sau xác nhận: một Order có đủ note/topping, đúng paid/amount/actor, mọi món preparing, stock/ledger đúng; Order chưa completed và bàn chưa trả sớm.
3. Hai submit/retry cùng key: cùng kết quả, không tạo Order/paid/consume/usage lần hai. Payload/actor khác không nhận được kết quả của người khác.
4. Một ingredient thiếu hoặc lỗi save Payment/Item/Order/ledger/link: outer transaction rollback toàn bộ DB; cart/key còn; UI không nói tiền thực đã được hoàn hoặc yêu cầu thu tiền lại.
5. Timeout không rõ commit: tra theo idempotency; chỉ retry đúng key sau kiểm tra. Không suy luận database chưa lưu từ việc browser chưa nhận response.
6. Lifecycle tiếp tục ready → served; completion/trả bàn chỉ theo P01/P02 khi được duyệt. Test paid mutation guards P05 qua direct endpoints, không chỉ kiểm tra nút.
7. Web guest chọn chuyển khoản trước hoặc COD; gửi request không paid/consume, Staff tiếp nhận không tự paid. Nhánh chuyển khoản cần thực nhận trước preparing; COD được preparing/giao trước, chỉ paid khi đã xác nhận thu đủ khi nhận hàng. Thu trên đúng Order liên kết, không tạo đơn/paid/consume trùng; không tự thêm gateway. Test xác nhận thu lặp, từ chối giao/thu thất bại và privacy theo contract được chốt.
8. Active Admin/Staff đúng quyền; guest/inactive bị chặn. Rollback inject bằng test events/database test, không SQL thủ công trên local.
9. Chạy targeted + Order/POS/Inventory/Payment/Promotion regression, full suite, route:list; build/keyboard/1440–768–375 khi thay frontend. Phân biệt SQLite sequential với MySQL concurrency test cô lập.

Manual steps này chưa thể thực hiện trên UI hiện tại vì DOC chưa đổi source. Khi báo test phải phân biệt kết quả contract cũ với kết quả prepaid mới; không xóa test cũ để né failure.

## Baseline lịch sử — CAFE-PAYMENT-DOC-001 ngày 26/10/2026

Phần còn lại lưu rule/implementation evidence trước change request; không áp served-before-paid hoặc auto-completion dưới đây cho POS trả trước đã duyệt ngày 27/10. Flow thu Order đã tồn tại và web phải theo quyết định compatibility mới, không mặc định từ phần lịch sử này.

### Quy tắc đã có trong tài liệu tại thời điểm baseline

- requirements.md §5.6–5.7, §7: Admin/Staff active vận hành; cash/bank_transfer;
  không split/partial; tối đa một paid/order; promotion usage chỉ tăng sau paid.
- database-design.md §17: mọi item không cancelled phải served trước thanh toán.
- Migration payments: amount DECIMAL(15,2) > 0; transaction_code nullable,
  tối đa 100 ký tự; payment_code unique; không unique order_id/transaction_code.
- Snapshot và lịch sử được giữ; completed Order không sửa theo nghiệp vụ bán hàng v1.
- Task hiện tại: completion, trả bàn và promotion usage atomic; chỉ trả occupied
  về available nếu không còn pending dine-in Order khác; không đổi maintenance.

### Quyết định được user duyệt trong chat ngày 26/10/2026 — lịch sử

User chọn “Duyệt toàn bộ phương án trên” cho checkout contract sau:

1. Chỉ thu khi Order pending, có ít nhất một món served, mọi món chưa hủy đều
   served, tổng chính thức > 0. Đơn rỗng, toàn cancelled hoặc total 0 bị chặn;
   không tạo Payment 0 và không tự đánh dấu completed cho đơn miễn phí.
2. Cash và bank_transfer đều là ghi nhận thủ công sau khi nhân viên xác nhận đã
   nhận đủ tiền. Form bắt buộc xác nhận. Chuyển khoản không có gateway/webhook
   và không tự suy ra đã nhận tiền từ phương thức hoặc mã giao dịch.
3. transaction_code chuyển khoản không bắt buộc; trim, tối đa 100 ký tự.
4. Bản này tạo mới paid sau xác nhận; không có thao tác tạo pending/failed mới.
   Giữ và hiển thị các pending/failed cũ; validation/DB failure không tạo log failed.
5. Promotion đã áp dụng giữ snapshot; quota được kiểm tra lúc trả tiền qua
   PromotionService::lockPromotionForPayment(), sau paid gọi consumeUsageAfterPaid()
   trong cùng transaction. Không áp lại eligibility ngày hiệu lực/status tại Payment.
   Không thêm form áp mã mới; dùng promotion đã có trên Order.

### Ranh giới kỹ thuật của implementation cũ

POST lock Order, recheck actor/item/payment, tính lại totals bằng OrderService,
lock promotion qua public contract, tạo paid, consume usage, complete Order và
trả bàn trong một transaction. Lặp request bị từ chối, không lặp side effects.
GET checkout/history chỉ đọc. Không Inventory consume/refund/QR/AJAX/PDF.
Retry deadlock/collision có giới hạn; concurrency phải được kiểm tra trên MySQL
test riêng, không suy luận concurrency từ SQLite sequential tests.

### Findings khi readiness audit cũ

Schema/model Payment, Order/POS/lifecycle/Inventory và Promotion hooks đã có.
OrderService/OrderController hiện vẫn có một số kiểm tra staff-only mặc dù
requirements cho Admin vận hành. Payment endpoints dùng quyền Admin/Staff riêng;
không sửa middleware, POS hoặc các workflow cũ ngoài scope để né test.
Chưa có evidence acceptance CAFE-ORDER-TEST-001; regression là gate trước bàn giao.

### Các giai đoạn triển khai baseline cũ

DOC-001: contract này. BE-001: preconditions. BE-002: record paid + promotion hooks.
BE-003: completion. BE-004: table release. FE-001: checkout/history + Order link.
TEST-001: validation, precision, permissions, retry, rollback và regression.

### Test thủ công baseline cũ — không phải prepaid acceptance

1. Đăng nhập Staff active. Tạo Order có món và topping, đi hết lifecycle đến served.
2. Từ chi tiết đơn chọn Thanh toán / Lịch sử. Kiểm tra snapshot và tổng tiền.
3. Chọn tiền mặt, xác nhận đã nhận đủ tiền, submit. Phải có paid, Order completed,
   completed_at và đúng actor; bàn occupied được trả available nếu không còn đơn pending.
4. Refresh kết quả, gửi lại request cũ: không có paid thứ hai hoặc lặp lượt promotion.
5. Với đơn mới đã served, chọn chuyển khoản, nhập mã tham chiếu tùy chọn;
   chỉ tick xác nhận sau khi kiểm tra đã nhận tiền. Kết quả giống bước 3.
6. Không tick xác nhận: bị chặn, không tạo failed Payment. Đơn cancelled/completed,
   món pending/preparing/ready, không món served hoặc total 0 không thể thu tiền.
7. Mở /staff/orders/{id}/payments bằng Admin active để kiểm tra quyền Payment.
   Existing Order Detail/POS có staff-only checks là finding riêng, không sửa ở task này.
8. Kiểm tra đơn có promotion: snapshot giữ nguyên, used_count tăng đúng một lần.
   Hết quota thì toàn bộ workflow rollback.
9. Không inject lỗi bằng SQL trên dữ liệu demo. Rollback do Payment/Order/Table/
   Promotion thất bại được kiểm tra trong PaymentWorkflowTest bằng model events.

Browser QA dùng preview dữ liệu giả trong bộ nhớ, viewport 1440/768/375 và keyboard,
không chứng minh luồng nhận tiền thật hoặc MySQL concurrency. Test MySQL cần database
test cô lập và hai connection độc lập, không dùng database local có dữ liệu.
