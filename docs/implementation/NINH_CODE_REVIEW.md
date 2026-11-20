# Ninh N01–N06 — source review 17/11/2026

> Cập nhật Integration 03: các gap stable intent, quote từng dòng và addItem sau paid ở bảng lịch sử dưới đây đã được sửa; xem NINH_INTEGRATION_03_REPORT.md. Full mới 448 total: 440 passed, 8 skipped. Finance/MySQL/browser vẫn chưa nghiệm thu; không dùng bảng cũ để suy ra các gap đã sửa còn nguyên.

Nhánh thực tế: `feature/order-requests-scheduled-pickup`, baseline `fb7e77fd`.
Owner cho phép dùng 16 file modified và các file untracked có sẵn làm nền. Không có hai nhánh đã merge. Review này thay thế kết luận COMPLETED trong report cũ khi mâu thuẫn.

| UC | Existing | Missing / Issues | Priority |
| --- | --- | --- | --- |
| N01 | Session cart có line signature, size/topping/note, quote server | Submit chưa có khóa retry ổn định từ checkout; xác nhận giá chỉ so tổng, còn cần kiểm snapshot từng dòng và availability bị bỏ âm thầm | High |
| N02 | POS/Order/Items, snapshots, nested ownership | Cần tiếp tục rà addItem sau paid và quy trình bàn với Toàn; không tự coi toàn bộ POS nghiệm thu | High |
| N03 | Request Models, accept/reject transactional; accept lặp trả Order cũ | MySQL concurrent accept chưa chạy; không có primitive Finance ghi tiền | High |
| N04 | Checkout có datetime-local | Đã sửa parse giờ Việt Nam/lưu UTC, revalidate khi accept, đọc config; thiếu giá trị giờ quán từ Toàn | High |
| N05 | InventoryService/startPreparing và ledger có sẵn | Đã thêm ready UI và điểm vào giao; served kiểm paid/mã nhận; concurrent MySQL chưa chạy lại | High |
| N06 | Random token/hash tracking, complete guards | Đã chặn paid cancel, tracking no-store/referrer và token format; Finance write bị chặn đúng ownership, browser chưa chạy | High |

## Findings đã sửa trong lượt tiếp tục
- Bỏ hardcode 07–22 và bỏ parse datetime-local theo UTC mặc định. `ScheduledPickupService` dùng Asia/Ho_Chi_Minh, persist UTC, kiểm giờ cấu hình và kiểm lại khi accept.
- Bổ sung `config/shop.php` làm điểm cấu hình bàn giao, không giả định đã có config của Toàn. Hai giá trị môi trường không có fallback. Chưa cấu hình thì scheduled bị từ chối rõ ràng; online vẫn dùng được.
- Giữ nguyên InventoryService: Sales chỉ gọi startPreparing như trước. UI preparing có Pha xong, ready liên kết chi tiết giao; giờ hẹn trên queue/detail/tracking theo giờ Việt Nam.
- Served yêu cầu thu đủ; đơn online/scheduled kiểm request_code khách cung cấp. Request_code là mã đối chiếu thao tác, không thay token bảo mật. Thao tác sai thứ tự/lặp bị từ chối.
- Sales recordPayment không còn ghi bảng payments; endpoint giữ để trả lỗi dependency rõ ràng. Form thu tiền đổi thành thông báo. Không tạo Service Finance thay Đạt.
- Chặn cancel đã paid; chặn các chỉnh sửa qua lockedPendingItem sau paid. addItem là điểm còn cần rà bổ sung (không che giấu hạn chế).
- Snapshot topping từ checkout nhân số phần để khớp tổng server và quy ước quantity theo dòng của OrderService; request mới không tạo thêm bản legacy trùng.
- Tracking format 64 hex, no-store/private, no-referrer, noindex; chưa cấu hình/access-log redaction ở web server.

## Bằng chứng / giới hạn
47 targeted tests, 399 assertions PASS. Full suite 441: 433 pass, 8 skipped, 5986 assertions; exit 0. npm build exit 0; Pint dirty chạy và sửa format các file integration đang dirty; git diff --check đạt. Không thay dependency/schema, không migrate/seed/commit/push/merge. Browser và MySQL integration/concurrency lần này NOT_RUN.

## Dependency
Đạt: contract ghi nhận thu cash/bank thực và read settlement dùng chung dưới khóa Order. Hiện Sales chỉ đọc dữ liệu paid có thật, không tự cấp paid. Toàn: giá trị giờ mở/đóng và xác nhận chính sách giờ quán; config hỗ trợ một khoảng mở trong ngày, không giả định qua đêm. Cần xác nhận trước khi nhận scheduled thực tế.

## Remaining
Các vấn đề N01/N02 ở bảng trên và xác minh MySQL/browser vẫn còn. Không gọi toàn bộ nhánh DONE. Chi tiết kiểm thử/skip/demo: NINH_BRANCH_02_REPORT.md.
