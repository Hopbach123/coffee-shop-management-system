# Branch 01 logic — N01–N03

## Task Status
PARTIAL

## Task ID
NINH-INTEGRATION-01

## Summary
Không có nhánh 1 riêng: dùng changes hiện có trên `feature/order-requests-scheduled-pickup` theo owner. Cart/Models/accept/reject/POS có sẵn từ lượt trước, không triển khai lại. Report này dựa source/test, không xác nhận lịch sử merge.

## Files Created
Report này. Models OnlineOrderRequest/Item/Topping và OnlineOrderNinhHopWorkflowTest là untracked có sẵn trước lượt tiếp tục.

## Files Modified
Lượt này chạm CartController, OrderService và fixture workflow; sửa topping snapshot quantity và không ghi duplicate legacy cho request mới. Các file integration khác có thay đổi sẵn, xem git diff và review.

## Files Deleted
None.

## Implementation Details
Giá/availability lấy Catalog; accept khóa request, trả Order đã có nếu accepted; chỉ takeaway; không gọi Inventory tại accept. Chặn category inactive khi accept. Không thêm Model Order thứ hai, không thêm migration.

## Tests / Checks Executed
Targeted và suite cùng lượt nhánh 2, xem report nhánh 2. Test accept lặp tuần tự có; concurrent accept MySQL chưa kiểm chứng.

## Test Results
47 targeted PASS; toàn suite 433 pass/8 skip. Đây không chứng minh mọi AC N01–N03 đầy đủ.

## Acceptance Criteria
PASS: code có cart/options/server quote/POS/accept-reject, accept replay một Order và accept không consume theo targeted tests.
UNVERIFIED: đồng thời MySQL, browser ba loại mua.
FAIL/Remaining: khóa submit retry ổn định; quote xác nhận từng dòng và thay đổi availability; rà addItem sau paid.

## Risks
CartController vẫn điều phối nhiều persistence trong controller từ nền có sẵn; cần chia task refactor tối thiểu riêng nếu tiếp tục tách workflow, không rewrite trong lượt này.

## Blockers
Finance writer chưa có; Toàn chưa cung cấp giờ quán. Không chặn rà Cart/accept độc lập.

## Assumptions
Phạm vi mục 7; không gateway/slot/holds.

## Out-of-Scope Findings
None ngoài các dependency đã nêu.

## Unverified Items
MySQL/browser/remaining trong review.

## Engineer Review Required
Đối chiếu phần code có sẵn và phần sửa mới; không dùng report cũ làm acceptance.

## Recommended Next Task
Hoàn thiện remaining N01/N02; không tự chuyển nhánh hay merge.
