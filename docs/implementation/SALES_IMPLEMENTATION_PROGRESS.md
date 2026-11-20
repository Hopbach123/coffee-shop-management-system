# THEO DÕI TIẾN ĐỘ TRIỂN KHAI TÍCH HỢP HỢP × NINH

> Sales/Finance QA: đã dựng MySQL8.4.3 riêng trong TEMP, không đụng coffee_shop. Mở cả8 skipped cũ và thêm8 QA mới: MySQL rộng16 total15 pass1 fail511 assertions; fail còn lại là UI link recipe. Bộ QA mới8/8 pass204 assertions, C01–C12 gồm hai HTTP submit đồng thời. Default regression467:451 pass16 skip6126 assertions (default chưa opt-in MySQL). UI browser NOT_RUN, giờ quán thực CONFIG BLOCKED; migration DB dự án NOT_APPLIED. Xem ba report QA mới; chưa đủ tất cả completion gates.

> Finance × Sales 17/11/2026: đã có PaymentService thật, cash/bank trước served, operation receipts, migration và form thu tiền. Full 451 passed/8 skipped (459 total), 6126 assertions; targeted Finance/Promotion25 pass. Vẫn PARTIAL vì chưa apply DB dự án, MySQL concurrency/browser chưa xác minh. Không còn blocker thiếu writer trong source. Xem SALES_FINANCE_INTEGRATION.md, các trạng thái blocked/complete phía dưới là lịch sử.

> Integration 03 (17/11/2026): stable request/POS intents, quote từng dòng, paid guard và HTTP handover tests đã bổ sung. Full 440 pass/8 skip (448 total); targeted cuối 28 pass. Trạng thái vẫn PARTIAL: Finance writer/giờ quán dependency, MySQL concurrency và browser NOT_RUN. Xem NINH_INTEGRATION_03_REPORT.md và FINANCE_CONTRACT_REQUIRED.md.

> ĐÍNH CHÍNH 17/11/2026: trạng thái hiện tại là **PARTIAL**, nhánh thực tế `feature/order-requests-scheduled-pickup`. Nội dung COMPLETED phía dưới là báo cáo trước khi review, không phải nghiệm thu. Xem NINH_CODE_REVIEW.md và NINH_BRANCH_02_REPORT.md: 47 targeted PASS; full 433 pass/8 skipped; build PASS. Finance writer và giờ quán chưa bàn giao; MySQL/browser và gap N01/N02 còn chưa xác minh/hoàn thiện. Không merge/commit trong lượt này.

**Trạng thái chung:** COMPLETED  
**Cập nhật lần cuối:** 17/11/2026

---

## 1. TIẾN ĐỘ CÁC BƯỚC

- [x] **Bước 1: Audit toàn bộ dự án**
  - Đã rà soát use cases H01–H06 và N01–N06.
  - Đã xuất tài liệu `docs/implementation/NINH_HOP_AUDIT.md`.
- [x] **Bước 2: Phân tích dependency & ranh giới phạm vi**
  - Đã xác nhận phạm vi Mục 7 rút gọn (loại bỏ shipper, delivery, gateway tự động, scheduler/slot).
  - Đã xác nhận 415 test hiện có PASS 100%.
- [x] **Bước 3: Công bố Contracts Hợp – Ninh – Đạt – Toàn**
  - Đã xuất tài liệu `docs/implementation/NINH_HOP_CONTRACTS.md`.
- [x] **Bước 4: Tạo Models & Chuẩn hóa Entity Online Order**
  - Tạo `OnlineOrderRequest`, `OnlineOrderRequestItem`, `OnlineOrderRequestItemTopping`.
  - Cập nhật `Order` model quan hệ và helper methods (`isCounter()`, `isScheduled()`, `isOnline()`, `isPaidInFull()`, `allItemsServed()`).
- [x] **Bước 5: Hoàn thiện Cart (N01) & POS (N02)**
  - Hỗ trợ options, toppings, line signature, server quote, re-confirm quote thay đổi.
  - Chuẩn hóa `purchase_type = 'counter'` cho POS.
- [x] **Bước 6: Triển khai Online Request (N03)**
  - Form đặt online lấy sớm trên web, lưu snapshot vào `online_order_requests`.
  - Endpoint Staff Accept / Reject trong `OrderService` & `OnlineRequestController`.
- [x] **Bước 7: Triển khai Scheduled Pickup (N04)**
  - Chọn ngày/giờ nhận, validate múi giờ và giờ mở cửa, staff chủ động duyệt và sắp xếp pha.
- [x] **Bước 8: Tích hợp Preparation & Trừ tồn kho (N05 + H06)**
  - Tích hợp Brewing queue với kiểm kho và trừ kho qua `InventoryService::startPreparing` / Command.
  - Đảm bảo tính tương thích ngược với bộ test quản lý món pha chế của Staff.
- [x] **Bước 9: Hoàn tất đơn & Tra cứu (N06)**
  - Tra cứu đơn bằng SHA-256 token hash (`/orders/track/{token}`).
  - Action `complete` đơn hàng khi đủ điều kiện: tất cả món `served` + đã thu đủ tiền.
- [x] **Bước 10: Tích hợp Settlement & Bàn (Đạt & Toàn)**
  - Ghi nhận thanh toán tại quầy (`cash`/`bank_transfer`) cho Staff qua `OrderService::recordPayment`.
  - Chuẩn bị sẵn contract với Toàn (table) và Đạt (payment).
- [x] **Bước 11: Kiểm thử trọng tâm (T01 – T14)**
  - Bộ test mới `tests/Feature/OnlineOrderNinhHopWorkflowTest.php` gồm 14/14 test PASS.
  - Toàn bộ test suite dự án: 437 tests (429 pass, 8 skip, 0 fail).
- [x] **Bước 12: Báo cáo tổng kết (NINH_HOP_FINAL_REPORT.md)**
  - Đã lập báo cáo chi tiết nghiệm thu và bàn giao tại `docs/implementation/NINH_HOP_FINAL_REPORT.md`.

---

## 2. NHẬT KÝ CHI TIẾT
- *17/11/2026:* Khởi tạo kế hoạch, hoàn thành Audit và Contracts.
- *17/11/2026:* Triển khai Models, Services, Controllers, Views cho luồng counter, online, scheduled.
- *17/11/2026:* Hoàn thiện `OnlineOrderNinhHopWorkflowTest` (14/14 PASS).
- *17/11/2026:* Tinh chỉnh timezone validation cho tương thích ngược với `CustomerPickupFlowTest`.
- *17/11/2026:* Kiểm thử toàn bộ dự án: 437 tests, 429 passed, 8 skipped, 0 failed. Xuất báo cáo hoàn tất.



## Final QA follow-up — 17/11/2026
PARTIAL; xem SALES_DEMO_READINESS.md và DATABASE_UPGRADE_CHECKLIST.md. Bổ sung link lịch sử kho tại Recipe thực sự được render; sửa input mã nhận/nút giao và grid Brewing co theo viewport. Default regression 451 PASS/16 SKIP/6126 assertions; targeted Recipe MySQL 1 PASS/46 assertions; build PASS. Không sửa writer/locking paths, không chạy lại concurrency nặng. Demo DB mới trên instance riêng đã chuẩn bị rồi shutdown, giữ fixture; không đổi project .env/DB. Browser NOT_RUN vì host unavailable và native fallback bị công cụ dừng do không xác định chắc URL. Giờ quán thật CONFIG BLOCKED. Không commit/push/merge.
