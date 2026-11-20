# Sales/Finance concurrency — 17/11/2026

## Task Status
PASS cho bộ command/HTTP concurrency mới trên MySQL test. PARTIAL cho nghiệm thu toàn hệ thống/UI.

## Task ID
SALES-FINANCE-QA-CONCURRENCY-001

## Cách kiểm chứng
Hai PHP process với PDO connections độc lập; cùng start barrier, parent giữ Order lock khi case dùng cùng Order để hai command thực sự phải chờ. Case khác Order dùng hai process đồng thời rồi tranh index/nguyên liệu. Test không gọi tuần tự để giả concurrency. Worker nhận cấu hình qua stdin, chỉ chấp nhận DB prefix sales_finance_test_<16hex> và host127.0.0.1; không log mật khẩu hoặc SQL payload. Có timeouts và cleanup chỉ process/file/DB do suite tạo.

| Case | Kết quả kỳ vọng / assertions | Evidence |
| --- | --- | --- |
| C01 Cash hai Staff | Một success, một reject, một receipt | PASS |
| C02 Bank hai Staff/cùng Order | Một success, một reject, một receipt | PASS |
| C03 Cùng key/payload | Cả hai trả cùng Payment ID | PASS |
| C04 Cùng key/khác payload | Chỉ một khoản thu | PASS |
| C05 Cùng ref/hai Order | Một success, unique/ref check từ chối lần còn lại | PASS |
| C06 Pay/cancel | Không có cancelled Order đã nhận paid; một phía phải reject | PASS |
| C07 Pay/served | Có paid thật; served chỉ qua settlement guard; ready còn lại nếu serve đi trước là hợp lệ | PASS |
| C08 Pay/complete | Không complete thiếu paid; có thể giữ pending nếu complete đến trước | PASS |
| C09 Promotion/pay | Receipt amount khớp tổng Order cuối; promo phải chạy trước paid hoặc bị reject sau paid | PASS |
| C10 Hai Order/tồn cuối | Một prepare, một reject, tồn0, một ledger, không âm | PASS |
| C11 Accept hai Staff | Cùng một Order ID, unique request, không consume thêm | PASS |
| C12 Hai POST/intent | Hai HTTP kernel độc lập, cùng frozen session intent; cả hai302 cùng request ID | PASS |

C12 dùng route test-only qua middleware web/CartController, session stores độc lập mang cùng intent và Catalog thật; không tạo route QA trong runtime/routes. HTTP chạy trong hai tiến trình riêng gọi HttpKernel, không phải hai call tuần tự, cũng không phải browser network test. CSRF được bỏ qua theo môi trường testing của Laravel; không suy ra CSRF production đã QA bằng browser.

## Lock review và sửa có bằng chứng
- Payment: actor → Order → items → receipt/promotion. Sales item/preparation: actor → Order → item → recipe/ingredients (Inventory). Import/adjust: actor → ingredient. Promotion apply: Order → promotion, không acquire actor/table sau đó.
- Inversion cụ thể trước sửa: ReservationCancellationService khóa area → table → reservation → linked Order; OrderService complete/cancel khóa Order → table. Hai command cùng reservation/order có thể đợi ngược nhau. Không đổi rule Reservation/Inventory.
- Sửa tối thiểu Sales lifecycle: `lockLifecycleOrder` khóa area/table trước Order cho dine_in, kiểm lại table_id/order_type và area sau khi đợi. Payment/prepare không acquire table nên không tạo ngược nhánh này.
- Read snapshot: lifecycle đọc snapshot bàn trước khi chờ; settlement plain SELECT dưới REPEATABLE READ có thể cũ. `settlementForOrder` dùng locking read khi đã trong transaction; cancel paid check cũng current locking read.
- Regression MySQL `C08-current-read`: chủ động tạo read snapshot cũ, đợi process khác commit paid, rồi complete phải thành công. `TABLE-ORDER-lock-regression`: dine-in complete chạy cùng Reservation cancel linked Order, complete success/cancel reject, bàn được release đúng logic baseline.
- Những ca này nằm trong lượt **8 tests / 204 assertions PASS** tại sales-mysql-final.json. Không tuyên bố lock graph toàn project hoàn toàn không thể deadlock. DB transaction retry3 giữ nguyên; không instrument số deadlock/retry nội bộ nên chỉ xác nhận không có DB error không xử lý ở lượt đạt.

## Test harness Windows
Lượt mở 8 skipped cũ phát hiện ReservationConcurrencyTest trả error ở case identical (~50s lock wait). Harness dùng anonymous stdout pipes và stream_get_contents trong lúc parent đang giữ table lock; Windows nonblocking pipes không đáng tin, có thể chặn parent tới khi worker timeout. Đổi **test harness** sang temp output/error files như suite Inventory hiện có; không nới thời hạn/rule Reservation để lấy PASS. Worker stderr chỉ class/code/file/line, không in SQL/credentials. Kết quả rerun cuối ở sales-mysql-all.json; xem phần bổ sung cuối report nếu còn failure.

## Files Created / Modified / Deleted
Mới tests/Feature/SalesFinanceMySqlTest.php, tests/Support/sales-finance-worker.php; reports QA. Sửa OrderService lockLifecycleOrder/cancel/complete; PaymentService transactional settlement read. Sửa OnlinePickupSchemaMySqlTest chỉ nhãn paid và so các cột legacy sau additive schema (không xóa assertion link recipe); ReservationConcurrencyTest và worker chỉ transport/diagnostics. Files Deleted: None.

## Risks / Blockers / Unverified
UI browser NOT_RUN; link Lịch sử kho thiếu trên trang recipe CRUD là finding ngoài module Sales, test cũ vẫn giữ để báo FAIL. Không claim baseline đã fail trước task khi chưa chạy baseline độc lập. Seed hoặc migration DB dự án không chạy. Need engineer review lock ordering/table ownership với Toàn; retry metrics chưa đo.

## Recommended Next Task
Review/khắc phục riêng UI recipe link, browser QA trên demo cô lập; không tự deploy/merge. Gate concurrency source/services đạt bộ mới, không thay nghiệm thu toàn project.

## Kết quả rerun cuối
`sales-mysql-all.json`: **16 tests, 15 passed, 1 failed, 511 assertions**, ~84.8s. Bao gồm 8 test mới và cả 8 test trước đây skipped đã được mở trên các DB cô lập. ReservationConcurrencyTest PASS sau sửa transport; Inventory3 và Dashboard1 PASS; OnlinePickupSchema2 PASS, 1 FAIL vì thiếu link recipe. Không có skipped trong lượt MySQL này. Không gọi cả suite MySQL PASS.


## Final QA follow-up — 17/11/2026
Không chạy lại bộ concurrency nặng: lượt này không sửa PaymentService/OrderService/InventoryService hoặc locking paths. Kết quả8/8 PASS/204 assertions trước đó là evidence lịch sử, không phải lần chạy mới. Lỗi Recipe của broad MySQL run15/16 đã sửa UI link và chạy riêng test liên quan:1 PASS/46 assertions (storage/logs/recipe-final-qa.json). Không tuyên bố broad16/16 được chạy lại. Default regression mới451 PASS/16 SKIP;16 SKIP không là concurrency PASS trong default run. Browser concurrency/manual actions chưa chạy.
