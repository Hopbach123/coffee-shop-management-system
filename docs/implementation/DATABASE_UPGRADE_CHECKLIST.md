# Checklist nâng schema receipt — REVIEW ONLY

Trạng thái: chưa chạy trên DB `coffee_shop`. Migration cần review: `2026_11_17_000001_add_payment_receipt_contract.php`. Demo fresh đã migrate trên instance TEMP riêng; không thay thế kiểm tra upgrade bản sao dữ liệu thật.

1. Xác minh đúng host/port/database/version/datadir bằng kết nối chỉ đọc; không in password/token. Ghi số Order/Payment, tổng tiền theo trạng thái và lịch sử kho trước nâng cấp. Xác nhận maintenance window và ngăn writer cũ chạy song song.
2. Backup nhất quán bằng công cụ MySQL sẵn có, truyền thông tin xác thực qua cơ chế bảo mật phù hợp, không đặt mật khẩu trên command line hoặc report. Ghi checksum, version/schema và thử restore vào DB mới có tên riêng. Không reset DB nguồn.
3. Review migration và `migrate:status` chỉ sau khi xác minh connection. Các trường receipt mới nullable cho dữ liệu cũ; không tự suy diễn tiền khách đưa/tiền thừa từ chứng từ lịch sử. Generated paid_order_key và unique bank reference phải phù hợp MySQL đích.
4. Preflight trên bản sao: tìm nhiều Payment paid cùng Order; tìm bank reference trùng sau chuẩn hóa trim/ASCII uppercase đúng PaymentService/migration. Có trùng thì dừng, engineer đối soát; không xóa chứng từ, tự sửa mã hoặc chọn một receipt để lách unique.
5. Chạy migration trên bản sao bằng connection override riêng. Đối chiếu từng cột cũ/ID/FK, counts và tổng tiền với snapshot trước; kiểm nullable metadata, generated column/index và bank reference backfill. MySQL DDL không được giả định rollback nguyên tử.
6. Chạy Payment/Sales tests trên môi trường test riêng: cash/change, bank exact amount/reference, replay operation key, duplicate paid, hai nhân viên, cancel/serve/complete tranh payment; kiểm payment không tự consume hoặc completed. Không dùng fixture paid như bằng chứng giao dịch thật.
7. Review UI receipt/schema readiness, giờ quán chính thức, quyền, validation và ba kênh mua. Thu đủ + served mới completed. Ghi rõ PASS/FAIL/NOT_RUN và artifact evidence.
8. Chỉ sau engineer phê duyệt backup/restore/evidence mới áp dụng lên DB dự án trong maintenance window. Task final QA này không cấp kết quả migration thành công trên DB dự án.
9. Postflight: migration status, index/constraint, counts/số tiền/ledger, giữ nguyên lịch sử cũ; smoke thu tiền bằng dữ liệu được engineer cho phép. Giám sát duplicate/reference lỗi và đối soát trước mở lại writer.
10. Khi lỗi: ngừng writer, giữ log/backup/audit. Receipt đã phát sinh có guard rollback; không cưỡng ép drop cột hoặc xóa Payment. Ưu tiên forward fix có review, chỉ restore toàn bộ backup khi engineer đánh giá tác động các giao dịch sau backup. Không dùng migrate:fresh.

Owner phối hợp: Đạt xác nhận chứng từ/quy tắc Finance; Ninh xác nhận lifecycle Sales; Hợp xác nhận stock không bị ghi lại; Toàn xác nhận access/config giờ mở. Không coi checklist là phê duyệt production.
