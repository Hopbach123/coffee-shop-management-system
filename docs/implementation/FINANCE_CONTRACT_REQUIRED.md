# Finance contract cần bàn giao — Đạt → Ninh

17/11/2026, `feature/order-requests-scheduled-pickup`. **BLOCKED: chưa có implementation writer thật.** Tài liệu đề xuất giao diện để owner review, không tạo interface/PaymentService giả.

## API cần có
```php
// Tên/signature cuối cùng do Đạt xác nhận, không phải code đã tồn tại.
confirmCash(int $orderId, string $operationKey, string $receivedAmount, User $actor): SettlementResult;
confirmBankTransfer(int $orderId, string $operationKey, string $bankReference, string $verifiedAmount, User $actor): SettlementResult;
settlementForOrder(int $orderId): SettlementSnapshot;
```

- `SettlementResult`: payment ID/reference, order ID, confirmed amount, method, paid timestamp, replay flag.
- `SettlementSnapshot`: order ID, expected/confirmed amount, `paidInFull`, payment references. Tiền là chuỗi decimal 2 chữ số, không float/scientific notation; VND theo baseline, không cần thêm currency column.
- Cash: xác nhận thực thu/tiền thừa theo rule owner; bank: nhân viên đã xác minh thực nhận, không dựa ảnh hoặc nút khách bấm.
- Một paid/Order trong MVP. Operation key bền vững: retry cùng payload trả cùng kết quả; cùng key khác payload trả validation/conflict; hai thu ngân không thu hai lần. Bank reference không tái sử dụng cho khoản thu khác.
- Khóa cùng Order trước khi ghi payment/read settlement cho phục vụ/completion; phối hợp thứ tự khóa user → Order → items → payment/stock hiện có. Không gọi Order command cấp cao vòng ngược. Chốt thứ tự toàn hệ thống với Hợp/Toàn trước nghiệm thu concurrency.
- Lỗi cần phân biệt: actor không hợp lệ, Order cancelled/completed, amount không đúng, đã thu, key conflict, bank reference trùng, lỗi DB/retry. Không sửa/xóa Payment cũ, không đánh unpaid giả để hủy.

## Hiện trạng caller Sales
`OrderService::recordPayment` giữ fail-closed, không tạo Payment. `OrderController::storePayment` vẫn kiểm quyền/validation rồi nhận lỗi dependency. UI không còn form ghi tiền giả.
`Order::isPaidInFull` đọc paid thật, so integer cents; `complete` khóa và tải payment rows trước kiểm đủ tiền + mọi món served. Đây là read adapter tạm, chưa phải xác nhận Finance writer được tích hợp.

## Acceptance cần chạy khi bàn giao
Cash/bank thật trên test DB; paid/unpaid/failed/refunded; duplicate/concurrent thu; key/reference conflict; không sửa món sau paid; ready chưa complete; paid chưa served; served thiếu tiền; served+paid complete. Fixture Payment chỉ ở tests, không insert vào DB dự án để demo.

## Không thuộc phạm vi
Gateway, COD, split/partial payment, auto-refund, shipper và kế toán lợi nhuận.
