# Đối chiếu phân công và phần Finance của Đạt

Rà soát 20/11/2026 trên `feature/payment-management`, baseline `c94d728d`. Worktree sạch trước lượt audit.

## Phân công nào đang áp dụng

Word `D:\nam 4 HK1\MNM\Đồ án\BangPhanCongMNM.docx` ngày 08/10 giao Đạt Order, Item, Topping, POS và Payment; AJAX là điểm cộng. Markdown `D:\nam 4 HK1\MNM\Đồ án\coffee-shop-team-work-assignment.md` ghi rõ mục 7 là phạm vi hiện hành: Đạt sở hữu Promotion/Payment/thu COD/báo cáo tiền, Ninh nhận Cart/POS/Order/Customer requests. Requirements §§14–15 bổ sung các quyết định được duyệt sau bản phân công.

Không xóa/viết lại code Đạt đã làm cho POS/Order vì đổi owner. Word được dùng để tra lịch sử. D04 hiện hành là xác nhận tiền web/COD, không phải gateway sandbox ở roadmap cũ. D05 refund, hold/release mã theo lịch hẹn, partial payment và PDF chưa được duyệt trong release này.

## Đối chiếu từng use case

| Use case hiện hành | Source | Kết quả kiểm source |
| --- | --- | --- |
| D01 quản lý mã | PromotionController, Store/UpdatePromotionRequest, admin/promotions, PromotionManagementTest | Có CRUD, validation/normalize, search/pagination, status và soft-delete giữ history |
| D01 quote | PromotionService.previewDiscount/calculateDiscount, PromotionServiceTest | Có fixed/percentage, thời gian, minimum/cap/quota; preview không tiêu lượt |
| D02 áp mã/usage | applyToOrder/lockPromotionForPayment/consumeUsageAfterPaid, PaymentController.applyPromotion | Một snapshot/Order, khóa quota và usage sau paid đúng một lần; chưa có hold engine vì chưa duyệt |
| D03 cash | PaymentService.confirmCash, paid writers, POS/Payment forms | Có tiền nhận/tiền thừa, actor và receipt; form cũ bỏ trống tiền nhận nghĩa trả đúng tổng |
| D03 bank | confirmBankTransfer/createPaidPayment, migration unique bank_reference_hash | Xác minh thủ công, API exact amount/reference bắt buộc, form hiện hữu giữ mã tùy chọn theo rule |
| D03 chống lặp | Payment key/payload hash, POS key, PosCheckoutService | Có replay/conflict, một paid và rollback, không chỉ dựa mã receipt |
| D04 web trả trước | confirmCustomerBankTransfer, CustomerOrderService.accept | Tiếp nhận một Order, xác nhận tiền rồi preparing qua Inventory, atomic |
| D04 COD | recordCustomerCodReceipt, CustomerOrderService.confirmDelivery | Chuẩn bị khi chưa paid; chỉ thu khi xác nhận khách nhận và trả đủ, atomic với served/completion |
| D06 | FinancialReportService/Controller, admin/finance, FinancialReportTest | Có ngày Việt Nam theo paid_at, tổng cash/bank/số đơn/chứng từ, cảnh báo duplicate/thiếu paid_at theo metric duyệt |
| API Ninh yêu cầu | confirmCash/confirmBankTransfer/settlementForOrder, app/Data/Payments DTOs | Writer thật đã có trên branch này; chưa xác minh caller ở branch Sales khác |

OrderService vẫn có create, item CRUD, topping CRUD, cancel, lifecycle và completion. PosController dùng PosCheckoutService; Inventory chỉ consume tại pending → preparing. Đơn paid chưa tự completed khi còn preparing/ready. Các phần này hiện thuộc owner Ninh theo Markdown mới. AJAX Cart/Checkout trong Word là điểm cộng, chưa phải deliverable của lượt Finance này.

## Sửa tiếp trong lượt này

Thông báo QueryException ở PaymentController còn chỉ hướng dẫn kiểm tra/thử lại. Đã bổ sung yêu cầu kiểm tra lịch sử đơn và giao dịch thực tế nếu đã nhận tiền, không thu tiền lần hai. Regression inject lỗi Payment/Order/Table/Promotion kiểm rollback và cả hướng dẫn này. Không thêm migration hoặc đổi workflow tiền.

## Bằng chứng thủ công đã quan sát và giới hạn

- Order #28: tổng 90.000đ, tiền thiếu bị chặn, cash 100.000đ/thừa 10.000đ, paid → preparing → ready → served/completed.
- POS-01: tự nhập 2 ly Cà phê sữa + 1 kem sữa, note `QA thủ công ít đá`, giỏ đúng 85.000đ, topping không nhân số ly.
- POS-03 Order #29 `ORD-20261120092119-N0PS5V7Z`: nhận 85.000đ, thừa 0; receipt `PAY-Q96IPQNADBPRAJOYF0YI6CYY`.
- POS-06 Order #30 `ORD-20261120092341-8A19JABG`: Latte 40.000đ, reference `QA-MANUAL-20261120-0925`, paid bank và preparing.
- POS-07 giỏ mới dùng lại reference bị từ chối và giữ cart.
- POS-10 Admin ngưng đúng Latte lớn variant #2 sau review, checkout cũ bị chặn.
- POS-11 Admin đổi variant #2 từ 40.000đ sang 41.000đ sau review, checkout cũ bị chặn và yêu cầu đối soát. Đã khôi phục 40.000đ/có sẵn, thấy quote POS lại 40.000đ.
- POS-08/09/12 thủ công chưa hoàn tất. Đã chọn Bàn 1 và thử bấm maintenance ở Admin, nhưng Computer Use buộc dừng vì không xác minh URL. Kết quả thao tác bàn cuối chưa được xác nhận.

Các quan sát UI không chứng minh trực tiếp số row rollback/ledger; feature tests kiểm riêng điều đó. Không ghi cả 12 ca thủ công PASS từ kết quả test tự động.

## Phần chưa đủ để nghiệm thu toàn bộ

1. Hoàn tất ba ca thủ công còn lại, kiểm trạng thái bàn hiện tại trước thao tác tiếp.
2. Ninh review/integrate API Finance trên branch nhận. Tài liệu `FINANCE_CONTRACT_REQUIRED.md` và zip ngày 17/11 là snapshot lịch sử trước khi writer được bổ sung, không còn là bằng chứng branch này thiếu API.
3. Phối hợp Ninh/Toàn sửa chữ tổng tiền quá nhạt trên Order detail, giữ đúng owner màn Sales/UI chung.
4. Chưa chạy lại MySQL concurrency trong lượt audit. Các tests opt-in/skipped không được tính PASS.

Source đã có các chức năng cốt lõi D01–D04/D06 theo phạm vi hiện hành. Nghiệm thu UI và tích hợp branch nhận vẫn PARTIAL. Không bổ sung gateway/refund chỉ để làm đủ các dòng roadmap cũ.

## Kiểm chứng trong lượt audit

Targeted Finance/Promotion/POS/Customer 83/83 PASS, 927 assertions. Full suite 503 tests với 492 PASS, 11 skipped, 6822 assertions, không fail/error. PHP lint, route:list Finance/Order và git diff --check đạt. Đây là kết quả tự động; các giới hạn thủ công bên trên vẫn giữ nguyên.
