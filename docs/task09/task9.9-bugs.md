# Task9.9 — Final bug / dependency report

Nghiệm thu toàn module: **NOT READY FOR REVIEW**. Đây là bugs/risks đã kiểm chứng hoặc dependency đã xác định; không thay thế owner decision. Không sửa backend ngoài ownership. Không có UI source bug mới cần sửa trong lượt task9.9; các UI fixes task9.8 đã qua regression.

## B99-01 — HIGH / full acceptance FAIL: repeated item request không có idempotency
- Owner: Đạt (Order/POS), phối hợp Toàn cho UI contract.
- Source: TableOrderContext::save → OrderService::addItem; endpoint POST staff/tables/{table}/orders/{order}/items.
- Repro: active Admin, Area active, bàn occupied, một pending dine_in; gửi cùng {variant_id,quantity:1,note} hai lần.
- Actual trên SQLite :memory: hai HTTP201, saved_item_id1 và2, hai order_items, total24.70 với unit_price12.35.
- Expected cho nghiệm thu chống replay: cần contract phân biệt một hành động bị gửi lại với hai hành động thêm món chủ ý. Hiện không có idempotency/request identity được duyệt, nên backend không phân biệt; không kết luận tất cả hai POST đều là lỗi cộng món.
- Impact: timeout/multi-tab/multi-client có thể lưu lại món nếu client khác retry cùng hành động. UI một trang chặn double-click và khóa uncertain request đã được duyệt; không bảo vệ cross-client.
- Evidence: test_final_diagnostics_capture_replay_and_reservation_release_without_accepting_them; storage/logs/task99-diagnostics.json.
- Fix: NONE. Đã báo HIGH-RISK trong baseline task9.5; task9.9 tái hiện thực tế. Owner cần approve idempotency scope/lifecycle/replay result trước backend/UI integration task riêng.
- Retest: diagnostic chạy thành công để ghi nhận sự thật; nhóm D02 vẫn FAIL, không coi PHPUnit xanh là idempotency PASS.

## B99-02 — HIGH / release acceptance FAIL: hủy Order bỏ qua lịch hiện tại
- Owner: Đạt + Toàn (Order/Reservation/release policy).
- Source: OrderService::cancel nhánh không còn pending dine_in → available.
- Repro: bàn occupied, một Order pending với tất cả items pending; thêm confirmed Reservation start<now<end vào fixture; gọi PATCH staff/orders/{order}/cancel.
- Actual: Order cancelled, persisted cafe_tables.status=available, confirmed Reservation vẫn tồn tại. Reader task9.7 hiển thị reserved, không tự sửa persisted.
- Expected trước final release acceptance: owner phải xác nhận/thi hành Reservation/cleanup conditions trước giải phóng; policy chưa đủ, không được kết luận available là khả dụng nhận khách.
- Impact: writer khác dựa persisted available có thể coi bàn là trống; display bảo thủ chỉ giảm nhầm lẫn UI, không bảo vệ toàn backend.
- Evidence: cùng diagnostic test, storage/logs/task99-diagnostics.json: live_reservations1, display_status reserved.
- Fix: NONE; không sửa Order/Reservation Service. Contract và writer correction thuộc task owner riêng. D03 FAIL; final release BLOCKED.

## B99-03 — P0 dependency BLOCKED: Payment/official receipt chưa có backend
- Owner: Đạt. Không tìm thấy Payment processing/result/idempotency/official receipt controller/service/routes trong inventory hiện tại.
- Supported: đọc records tham chiếu, tạm tính/proforma không ghi dữ liệu.
- Không kiểm chứng cash/bank_transfer payment success/pending/failed processing, double-checkout hoặc paid→completion/release bằng mock.
- Next: owner cung cấp accepted Payment/Receipt/release contract và tests; không giả lập success để đóng task.

## B99-04 — P1 known limitation: shared sidebar keyboard focus
- Source resources/js/internal.js/shared internal layout: Escape/backdrop có sẵn, nhưng thiếu containment/return-focus/inert cho mobile overlay. Task9.8 đã báo, task9.9 giữ nguyên source.
- Module cart là native inline disclosure, keyboard Enter/summary focus đã PASS; không đánh đồng với sidebar modal accessibility.
- Owner: shared frontend/layout owner; cần task nhỏ riêng, screen-reader/mobile keyboard review. Không refactor shell toàn ứng dụng trong nhiệm vụ nghiệm thu.

## Bug fix summary
| ID | Code fix trong task9.9 | Retest / state |
| --- | --- | --- |
| B99-01 | None — backend scope/contract | Reproduced, HIGH-RISK, D02 FAIL |
| B99-02 | None — backend scope/policy | Reproduced, HIGH-RISK, D03 FAIL |
| B99-03 | None — missing backend | BLOCKED |
| B99-04 | None — pre-existing shared shell limitation | Source reviewed, chưa accessibility acceptance |

Lỗi import DB thiếu trong bản test mới đã sửa trước kiểm chứng cuối; đây là lỗi harness, không phải bug ứng dụng. Không thay expected business behavior để làm suite xanh. Không có thay đổi UI/backend trong task9.9.
