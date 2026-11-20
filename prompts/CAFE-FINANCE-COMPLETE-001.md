# Hoàn thiện use case Finance của Đạt

Bạn là Coding Agent của Coffee Shop Management System.

Task cha `CAFE-FINANCE-COMPLETE-001`, theo yêu cầu và các xác nhận ngày 18/11/2026. Branch thực tế cần kiểm tra bằng Git, dự kiến `feature/payment-management`.

## Mục tiêu và phạm vi

Hoàn thiện D01–D04 và D06 theo mục 7.4 của `D:\nam 4 HK1\MNM\Đồ án\coffee-shop-team-work-assignment.md`. D04 hiện hành là xác nhận chuyển khoản web và thu COD. Các quyết định nghiệp vụ trực tiếp trong chat và requirements hiện hành quyết định phạm vi thực hiện.

Đọc AGENTS.md, architecture.md, requirements.md, frontend.md, plan.md, database-design.md, hai checkout/customer contracts, FINANCE_CONTRACT_REQUIRED.md và source/tests trước khi sửa. Kiểm tra branch, worktree, toàn bộ chuỗi migrations và phpunit.xml. Bảo vệ thay đổi có sẵn. Chia các task dưới đây thành các phần 30–90 phút, review diff và báo cáo từng phần.

## Quyết định đã được user duyệt

- Ghi Payment paid sau khi người nội bộ active xác nhận tiền thực nhận. Cash/bank_transfer, tối đa một paid mỗi Order.
- POS thu trước rồi preparing. Web chuyển khoản phải thực nhận trước preparing. COD chuẩn bị trước, chỉ paid tại xác nhận khách nhận và trả đủ tiền.
- Paid và completed có điều kiện riêng. Completion cần paid và mọi món chưa hủy served, ít nhất một món served. Bàn chỉ trả khi completion hợp lệ.
- Đơn đã paid và giá/cấu hình đơn web được bảo vệ khỏi thay đổi sau chốt.
- Finance API có khóa thao tác bền vững/hash payload. Cùng key/payload/actor trả kết quả cũ, khác nội dung bị chặn. Mã chuyển khoản được cung cấp phải chống sử dụng lại cho khoản thu khác.
- Migration mới thêm metadata receipt và khóa/hash. Các chứng từ lịch sử được giữ nguyên.
- Cash cho nhập tiền khách đưa, lưu tiền thừa. Payment.amount là phần tiền áp vào Order. Bank xác minh đúng tổng tiền. Form checkout cũ vẫn cho mã tham chiếu tùy chọn theo contract, API confirmBankTransfer yêu cầu tham chiếu để phục vụ tích hợp Sales mới.
- Admin xem báo cáo theo ngày Việt Nam, dựa Payment paid và paid_at, tách cash/bank, số đơn đã thu và chứng từ. Trùng paid trên một Order phải có cảnh báo và dừng công bố tổng được xác nhận.

## Task CAFE-FINANCE-PROMO-001

Hoàn thiện đường áp mã thực tế tại trang Payment, reuse PromotionService. Cho active Admin/Staff thao tác theo quyền vận hành đã duyệt. Chỉ lấy code từ client. Bảo vệ paid Order và Order web đã chốt giá ở Service. Giữ snapshot, một mã/Order, quota và consume usage một lần tại paid.

Acceptance gồm HTTP apply có hiệu lực đúng, giả discount bị bỏ qua, guest/inactive bị chặn, paid/web Order bị từ chối, rollback và quota tests vẫn đạt. Kiểm tra UI bằng build và browser.

## Task CAFE-FINANCE-SETTLEMENT-001

Thêm public operations trong PaymentService với DTO thật

```php
confirmCash(int $orderId, string $operationKey, string $receivedAmount, User $actor): SettlementResult;
confirmBankTransfer(int $orderId, string $operationKey, string $bankReference, string $verifiedAmount, User $actor): SettlementResult;
settlementForOrder(int $orderId): SettlementSnapshot;
```

Giao diện Finance ghi tiền trong transaction, lock/recheck actor và Order, khóa các record liên quan theo thứ tự nhất quán. Dùng primitive xác minh tổng hiện có của OrderService. Phần Sales gọi tiếp primitive chuẩn bị/completion tương ứng. Thực hiện replay trước khi từ chối trạng thái Order đã hoàn tất để retry sau thành công vẫn trả đúng receipt cũ.

Migration mới dùng nullable unique operation_key_hash, payload hash, nullable unique bank_reference_hash, received_amount và change_amount. SHA-256 cho khóa thô. Lịch sử giữ null cho metadata mới. Writer kiểm cả tham chiếu lịch sử. Unique constraint bảo vệ tham chiếu mới trước race giữa hai Order. Rollback migration cần review metadata đã phát sinh.

Giữ tương thích các operations POS/web/COD hiện hữu. Tái sử dụng một primitive tạo paid receipt để cùng chống trùng tham chiếu. Form tiền mặt cho nhập received_amount, blank nghĩa nhận đúng tổng. Giữ precision decimal bằng integer cents. Recheck received_amount tại Service. Khóa POS payload cần bao gồm tiền khách đưa khi được cung cấp.

Acceptance gồm cash/change, bank exact amount, replay, key/actor/payload conflict, tham chiếu trùng và lịch sử, payment-code collision retry, rollback receipt/key/usage, settlement false cho unpaid/refunded/multiple-paid, không completion/consume từ operation ghi nhận độc lập. Chạy regression POS/web/COD/Order/Inventory/Promotion.

## Task CAFE-FINANCE-REPORT-001

Thêm FinancialReportService, Admin Controller/FormRequest và Blade tại `/admin/finance`. Thời gian lọc inclusive ngày Việt Nam, truy vấn UTC theo khoảng start inclusive/end exclusive. Mặc định ngày hiện tại tại Việt Nam. Chỉ active Admin được xem. Tổng paid theo phương thức, số Order đã thu, chứng từ phân trang và link lịch sử đúng Order. Báo paid thiếu paid_at rõ ràng. Kiểm trùng paid kể cả lần thu thứ hai nằm ngoài kỳ. Dữ liệu lịch sử phải giữ nguyên.

Acceptance gồm chính xác số lẻ, cash/bank totals, loại pending/failed/refunded, ranh giới UTC/ngày Việt Nam, empty, duplicate và quyền. Kiểm tra giao diện 1440/768/375, keyboard và build.

## Verification và giao nộp

Dùng PHP `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`. Xác minh SQLite :memory: hoặc database QA cô lập trước tests. Chạy targeted, toàn suite, route:list và git diff --check. Chạy npm.cmd run build khi sửa frontend. MySQL concurrency phải có database QA riêng và hai connection thực tế, ghi giới hạn nếu môi trường chưa đáp ứng.

Báo đủ mẫu AGENTS cho từng task cùng file changed, criteria PASS/FAIL/UNVERIFIED, evidence thực tế, hướng dẫn test và API bàn giao. Quyết định commit/merge thuộc engineer. Những phần còn chờ nghiệm thu cần được ghi chính xác trong báo cáo.
