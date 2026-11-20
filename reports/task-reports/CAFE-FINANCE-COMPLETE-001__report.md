# Hoàn thiện Finance của Đạt

## Task Status

PARTIAL — đã triển khai các phần code Finance thuộc phạm vi hiện hành và các kiểm tra tự động đã đạt. Nghiệm thu trực quan sau đăng nhập còn UNVERIFIED.

## Task ID

CAFE-FINANCE-COMPLETE-001. Các task con CAFE-FINANCE-PROMO-001, CAFE-FINANCE-SETTLEMENT-001, CAFE-FINANCE-REPORT-001. Ngày 18/11/2026.

## Summary

Đọc bảng phân công mục 7.4, AGENTS, architecture, requirements, plan, frontend, schema/contract và source/tests. Branch feature/payment-management, HEAD khởi đầu 914e6398, worktree ban đầu sạch. User duyệt bộ chỉ số D06 cùng schema Finance key/reference và cash/change trong chat. Cập nhật requirements §15, database-design §25, plan §12.3 và contract theo những xác nhận này. Prompt thực thi có trong prompts/CAFE-FINANCE-COMPLETE-001.md.

## Files Created

- app/Data/Payments/SettlementResult.php
- app/Data/Payments/SettlementSnapshot.php
- app/Http/Controllers/Admin/FinancialReportController.php
- app/Http/Requests/Payment/ApplyOrderPromotionRequest.php
- app/Http/Requests/Payment/FinancialReportRequest.php
- app/Services/FinancialReportService.php
- app/Support/DecimalMoney.php
- database/migrations/2026_11_18_000001_add_finance_receipt_keys_to_payments_table.php
- docs/FINANCE_MANUAL_TEST.md
- prompts/CAFE-FINANCE-COMPLETE-001.md
- reports/task-reports/CAFE-FINANCE-COMPLETE-001__report.md
- reports/task-reports/CAFE-FINANCE-PROMO-001__report.md
- reports/task-reports/CAFE-FINANCE-REPORT-001__report.md
- reports/task-reports/CAFE-FINANCE-SETTLEMENT-001__report.md
- resources/views/admin/finance/index.blade.php
- tests/Feature/FinanceMySqlTest.php
- tests/Feature/FinancialReportTest.php
- tests/Support/finance_mysql_worker.php

## Files Modified

- app/Http/Controllers/Staff/PaymentController.php
- app/Http/Requests/Payment/RecordPaymentRequest.php
- app/Http/Requests/Pos/CheckoutPosRequest.php
- app/Models/Payment.php
- app/Services/PaymentService.php
- app/Services/PosCheckoutService.php
- app/Services/PromotionService.php
- config/admin_menu.php
- database-design.md
- docs/PAYMENT_CHECKOUT_CONTRACT.md
- plan.md
- requirements.md
- resources/views/admin/dashboard/index.blade.php
- resources/views/staff/home.blade.php
- resources/views/staff/payments/show.blade.php
- routes/admin.php
- routes/staff.php
- tests/Feature/CustomerOrderWorkflowTest.php
- tests/Feature/PaymentWorkflowTest.php
- tests/Feature/PosOrderWorkflowTest.php
- tests/Feature/PromotionServiceTest.php

## Files Deleted

None.

## Implementation Details

### Use case và ownership

| Use case | Kết quả source hiện tại | Owner |
| --- | --- | --- |
| D01 quản lý/quote khuyến mãi | Giữ CRUD/eligibility/calculation đã có, regression đạt | Đạt |
| D02 áp mã và quota | Thêm form/route áp mã thực tế; Service chặn paid/web quoted Order; giữ snapshot/consume một lần | Đạt |
| D03 thu tại quán | Cash/bank hiện hữu; thêm tiền khách đưa/tiền thừa, metadata receipt và bảo vệ tham chiếu dùng lại | Đạt, phối hợp POS của Ninh |
| D04 chuyển khoản web/COD | Giữ hai operations tiền/giao đã hoạt động, kiểm lại regressions và shared receipt writer | Đạt, phối hợp CustomerOrderService của Ninh |
| D06 báo cáo tiền | Admin paid/paid_at theo ngày Việt Nam, cash/bank, số Order, chứng từ, duplicate/undated warnings | Đạt |
| Finance API bàn giao | confirmCash, confirmBankTransfer, settlementForOrder và hai DTO thật | Đạt |

### Finance writer

Khóa actor rồi Order và các dòng liên quan, kiểm quyền active Admin/Staff, dùng primitive refreshTotalsForPayment của OrderService, xác minh amount bằng integer cents, khóa promotion trước paid và consume usage sau paid trong transaction. API Finance độc lập ghi receipt, caller Sales điều phối preparing/completion. Replay được kiểm trước Order status để retry sau hoàn tất vẫn trả receipt đã lưu. Key/hash payload gồm actor/Order/method/received amount/reference. Khác nội dung bị từ chối. Bank reference trim/uppercase cho hash, kiểm cả lịch sử. Shared createPaidPayment dùng cho operations cũ và API mới, tránh hai kiểu chống trùng tham chiếu.

Cash Payment.amount là tổng được áp cho Order, received_amount là tiền khách đưa, change_amount là chênh lệch. Mặc định blank tại form cũ nghĩa nhận đúng tổng. Bank API mới yêu cầu mã tham chiếu, form cũ giữ optional theo contract. Các giá trị amount/status/actor từ client tiếp tục bị kiểm soát ở Service.

Đã sửa nhận diện unique constraint dùng errorInfo của driver để phân biệt payment_code và bank_reference_hash. Truy vấn report chọn order_id rõ trước GROUP BY để đáp ứng MySQL ONLY_FULL_GROUP_BY. POS fingerprint chỉ bổ sung cash tender khi được cung cấp, giữ fingerprint cũ cho phiên/receipt không có field mới.

### Báo cáo D06

Khoảng ngày inclusive tại Asia/Ho_Chi_Minh đổi sang UTC start inclusive/end exclusive. Tổng từ Payment paid, phân phương thức, cộng integer cents. Query tìm duplicate paid kiểm cả khoản ngoài kỳ của cùng Order. Trang Admin có validation, phân trang và link lịch sử đúng Order. Dashboard có lối mở báo cáo, menu registry cho phép thêm mục này. GET chỉ đọc.

### Môi trường và dữ liệu

Migration mới nullable metadata đã chạy trên coffee_shop local, batch 4. Cả 5 chứng từ lịch sử còn nguyên về số lượng và metadata key vẫn null. Browser chỉ mở trang login. Các receipt thử nghiệm nằm trong SQLite :memory: hoặc MySQL QA `coffee_finance_qa_20261118_849ef2`. Database QA đã được dọn các bảng do test tạo, còn schema trống. Server Laravel đang chạy tại http://127.0.0.1:8000.

## Tests / Checks Executed

- Targeted Finance/Promotion/POS/web trên SQLite :memory: — 74/74 pass, 736 assertions.
- Full suite mặc định — 499 tests, 488 pass, 11 skip, 6754 assertions, 0 fail/error.
- FinanceMySqlTest opt-in — 1/1 pass, 60 assertions trên database QA riêng và hai process.
- npm.cmd run build — PASS. Có warning optional fontaine và hai đường dẫn ảnh chưa resolve ở build time.
- PHP lint cho file PHP/Blade đã đổi — PASS. git diff --check — PASS.
- route:list — 157 routes, 0 named route trùng. GET admin/finance và POST staff/orders/{order}/promotion đã được khai báo.
- Migration Finance local — batch 4 Ran. Đã kiểm tra 5 Payment trước/sau, metadata lịch sử giữ null.
- MySQL local read-only report năm 2026 — total 163000.00, cash 63000.00, bank 100000.00, 3 paid Order.

## Test Results

Các kết quả trên là lượt cuối quan sát thực tế. Lượt kiểm tra đầu quan sát 469 pass/10 skip. Các lỗi phát hiện trong task gồm nhận nhầm collision, GROUP BY không phù hợp strict MySQL và kiểm tra schema QA thiếu bộ lọc tên schema. Đã sửa và kiểm lại. Gate QA từ chối trước DDL khi chưa xác minh schema trống, sau đó giới hạn enumeration/cleanup đúng schema và xác minh SELECT DATABASE().

MySQL test đã dùng hai process cho cùng operation key, hai cashier trên cùng Order, hai Order dùng chung bank reference và tranh quota cuối. Kết quả một paid cho mỗi Order, bank reference dùng một lần, usage_limit 1 chỉ tăng một lần. Test report chạy trên strict MySQL. Các nhóm MySQL opt-in khác chưa được chạy trong lượt này.

## Acceptance Criteria

| Criterion | Status | Evidence |
| --- | --- | --- |
| Prompt theo phân công hiện hành, chia task | PASS | prompts/CAFE-FINANCE-COMPLETE-001.md, requirements §15 |
| D01/D02 CRUD, áp mã, quota và immutable guards | PASS | PromotionManagement/PromotionService/PaymentWorkflow tests |
| D03 tiền mặt/chuyển khoản, cash change và lịch sử | PASS | PaymentWorkflow và POS tests |
| D04 web transfer/COD giữ điều kiện thu/chuẩn bị/giao | PASS | CustomerOrderWorkflow tests |
| Finance API replay/key/reference conflict và settlement | PASS | PaymentWorkflow, FinanceMySqlTest |
| Atomic rollback receipt/key/usage | PASS | Trigger failure test và regression |
| Race MySQL hai worker, bank reference và quota | PASS | FinanceMySqlTest, 60 assertions |
| D06 exact totals, timezone, auth, duplicate history | PASS | FinancialReportTest, MySQL QA và local read-only |
| Build, routes, lint, whitespace và regression | PASS | Kết quả checks trên |
| Visual 1440/768/375 và keyboard sau login | UNVERIFIED | Cần người dùng đăng nhập Admin để tiếp tục |
| Tích hợp Finance API với nhánh Sales khác | UNVERIFIED | Caller nhánh nhận chưa được đưa vào scope checkout này |

## Risks

- Cần chạy migration mới trước khi triển khai source receipt mới trên máy khác.
- Rollback migration làm mất metadata khóa/tiền nhận/tiền thừa, cần bảo toàn trước khi downgrade.
- Chứng từ lịch sử giữ bank_reference_hash null. Writer đối chiếu tham chiếu cũ, unique hash bảo vệ các tham chiếu được ghi mới.
- Các operations POS/web/COD hiện hữu tiếp tục được gọi bởi UI hiện tại. API Finance mới sẵn cho caller Sales, tích hợp vào nhánh khác của Ninh cần review source nhánh nhận.
- 11 tests opt-in bị skip trong full suite mặc định. Finance MySQL mới đã chạy riêng, các nhóm opt-in cũ còn theo environment của chúng.

## Blockers

Browser đã mở ứng dụng local và đến trang login. Bước đăng nhập Admin cần người dùng thực hiện theo skill Computer Use. Đã gửi yêu cầu hỗ trợ đăng nhập. Chưa kiểm tra trực quan các trang mới sau login ở 1440/768/375 hoặc keyboard, nên phần này UNVERIFIED. HTTP page render, authorization và build đã pass qua tests.

## Assumptions

"Toàn bộ use case" được hiểu là phần Đạt tại mục 7.4 của phân công mới. Đã thông báo cách hiểu này trước khi triển khai. Các tham số schema cụ thể và bộ chỉ số báo cáo được user duyệt trong lượt này. Hai luồng giao web và pickup vẫn theo quyết định tạm giữ riêng đã có.

## Out-of-Scope Findings

Gateway/refund/PDF và các metric ngoài paid report tiếp tục theo roadmap. Thông tin tài khoản ngân hàng/QR merchant chưa được cung cấp, luồng chuyển khoản hiện dùng xác nhận thực nhận thủ công theo contract. Các phần Order/Cart/POS từng do Đạt viết được ghi nhận là hỗ trợ tích hợp cho owner Ninh.

## Unverified Items

Browser đã mở ứng dụng local và đến trang login. Bước đăng nhập Admin cần người dùng thực hiện theo skill Computer Use. Đã gửi yêu cầu hỗ trợ đăng nhập. Chưa kiểm tra trực quan các trang mới sau login ở 1440/768/375 hoặc keyboard, nên phần này UNVERIFIED. HTTP page render, authorization và build đã pass qua tests.
Nhận tiền ngân hàng thực tế, chuyển Finance API sang nhánh Sales khác và các nhóm MySQL opt-in cũ còn cần nghiệm thu riêng.

## Engineer Review Required

Review migration/rollback, API/DTO và caller Sales, cash receipt defaults, report metric/timezone và các file trong diff. Source đang ở worktree để review. Sau khi đăng nhập Admin, thực hiện manual guide tại docs/FINANCE_MANUAL_TEST.md.

## Recommended Next Task

Hoàn tất nghiệm thu trực quan và bàn giao API Finance cho Ninh. Quyết định commit/merge thuộc engineer.

Commit message đề xuất `feat(finance): complete settlement receipts and paid reports`.
