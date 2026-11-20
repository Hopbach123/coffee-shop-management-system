# Review gói Finance Đạt → Ninh — 17/11/2026

## Task Status
PARTIAL cho khả năng tích hợp; đã hoàn tất kiểm tra tĩnh trọng tâm Payment. Không chép source từ ZIP vào project.

## Task ID
FINANCE-HANDOFF-REVIEW-001

## Summary
Nguồn: `FINANCE_HANDOFF_DAT_TO_NINH_2026-11-17.zip` trong Downloads. Manifest chỉ tới commit `e1e16fc2cd12fa026d7d0eed47808b06c9ad2534`, feature/payment-management. Đã xác minh **35/35 file** SHA256/Git blob khớp ZIP manifest và blob tại commit local thật. Điều này xác minh bản xuất, không chứng minh runtime đã đúng hoặc đã merge vào Sales.
Nhánh hiện tại vẫn feature/order-requests-scheduled-pickup, HEAD fb7e77fd; PaymentService chưa có trong app/Services hiện tại. Staged/unstaged changes của người dùng giữ nguyên.

## Findings theo mức ưu tiên
### High — vòng chặn served/paid khi tích hợp trực tiếp
`SOURCE_SNAPSHOT/app/Services/PaymentService.php::recordPaid` yêu cầu mọi món chưa hủy phải served rồi mới thu. `app/Services/OrderService.php::transitionItemStatus` của Sales kiểm paid trước served. Nối hai API nguyên trạng tạo vòng chặn, không phải luồng có thể demo. Đạt cần operation ghi nhận thực thu cho Order hợp lệ trước handover, không bắt buộc served; không nới guard giao món của Sales để né lỗi.

### High — side effects vượt ownership và sai thời điểm pha đơn hẹn
Payment::recordPaid gọi `OrderService::completePaidOrderIfAllServed`; operation prepaid gọi transitionItemStatus(preparing) cho mọi món. Finance ghi tiền đang đồng thời điều khiển lifecycle/consume. Đơn hẹn cần Staff chủ động pha theo lịch, nên không gọi operation prepaid này để thu tiền rồi tự pha. Cần writer chỉ ghi settlement; Sales giữ quyền chuyển trạng thái.

### High — dependency không tương thích nhánh hiện tại
PaymentService gọi `OrderService::refreshTotalsForPayment` và `completePaidOrderIfAllServed`; nhánh Sales hiện không có hai method. PaymentController còn load `Order::customerRequest`, dùng CustomerOrderRequest/payment_option; Sales dùng OnlineOrderRequest/onlineOrderRequest, payment_preference/payment_timing. Copy PaymentService/Controller nguyên trạng không đủ để chạy. Không ghi đè OrderService/InventoryService hoặc tạo schema request thứ hai chỉ để phù hợp snapshot.

### High — contract idempotency Finance còn thiếu
Payment có Order lock và kiểm paid lần hai: giúp ngăn ghi hai lần theo đường code hiện có nhưng replay bị từ chối thay vì trả receipt cũ; không có operation key/payload conflict bền vững. `transaction_code` là index thường, chưa chống dùng cùng bank reference cho hai Order; reference còn nullable cho bank. Đây là gap so contract cần bàn giao, không suy ra toàn bộ Finance hiện có vô dụng.

### Medium — tiền thực nhận/read settlement và thứ tự khóa
Writer lấy amount từ tổng server và yêu cầu received_confirmation; chưa có receivedAmount/verifiedAmount/tiền thừa hoặc public settlement snapshot. Signature khác tên đề xuất không tự nó là lỗi; thiếu semantics mới là blocker. Finance khóa Order trước rồi mới đọc actor, Sales khóa actor trước Order: cần thống nhất thứ tự khóa và kiểm lại trạng thái actor khi chốt. Chưa chứng minh deadlock cụ thể; đây là rủi ro tích hợp cần test concurrency.

### Medium — snapshot chứa phạm vi khác
ZIP có COD/delivery/customer_order_requests và Inventory snapshot cũ. Phạm vi Sales hiện theo mục 7 pickup-only; không tự áp dụng scope COD từ tài liệu trong ZIP. Gói README cũng cảnh báo Inventory mới trên dev đã có cải tiến hơn snapshot này.

## Phần tốt có thể tái sử dụng
Writer Eloquent thật trong transaction; server amount, actor/paid_at/payment_code; Order lock và paid check; Promotion primitives hiện có cùng tên trong repo; Payment decimal cast/schema; tests PaymentWorkflowTest làm nguồn testcase. Chưa chạy bộ tests snapshot trên nhánh Sales, không chuyển kết quả lịch sử thành PASS hiện tại.

## Files Created
Report này.
## Files Modified / Deleted
None trong lượt review. Không chỉnh runtime/source/ZIP/DB; không commit/push/merge.

## Tests / Checks Executed
Đọc ZIP inventory/README/REPORT/manifest/PaymentService/PaymentController/payment migration; đối chiếu caller/method/schema hiện tại. SHA256 và Git blob 35/35 khớp; commit nguồn tồn tại local. Git status/branch/HEAD đã đọc. Không giải nén đè workspace, không chạy code trong ZIP.
## Test Results
Integrity PASS. Static integration findings ở trên. Application test/build/browser/MySQL NOT_RUN trong lượt review.

## Acceptance Criteria
PASS: nhận diện code Finance thật, commit/hash và các gap tích hợp chính.
FAIL: nối nguyên trạng vào Sales để chạy paid-before-served.
UNVERIFIED: runtime/concurrency/CI snapshot, mọi test của gói trên nhánh hiện tại.

## Risks / Blockers
Các finding High còn là blocker tích hợp. Không đồng nhất tên signature thiếu với bắt buộc tạo framework/DTO; thống nhất API tối thiểu đáp ứng semantics với Đạt trước.
## Assumptions
Phạm vi mục 7 Sales hiện tại vẫn hiệu lực; tài liệu gói là bằng chứng bàn giao, không phải quyền ghi đè code hoặc đổi nghiệp vụ.
## Out-of-Scope Findings
Không audit toàn bộ CustomerOrderService/COD/Promotion/Inventory snapshot; các module đó không được coi đã review đầy đủ từ task này.
## Unverified Items
Runtime trên nhánh Ninh, MySQL race, browser, received amount/bank reference và contract hoàn thiện.
## Engineer Review Required / Recommended Next Task
Gửi Đạt findings: writer trước served, không auto preparing/completed, operation key/replay/conflict, bank reference, read settlement và lock order. Sau khi chốt, tích hợp có chọn lọc Payment module/adapter Sales và tests trên nhánh hiện tại. Không copy toàn bộ context snapshot hoặc migration cũ. Report này không tự mở task implementation.
