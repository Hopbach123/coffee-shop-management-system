# Nhập kho nguyên liệu — feature/inventory-transactions

## Phạm vi và kế hoạch

Tiếp tục Service nhập kho đã có trên nhánh e629652d; tích hợp dev 1ba4b8fa (đã có công thức). Hợp nhất InventoryService để giữ consume khi pha. Phần nhập kho gồm backend → form → ledger → fixture/tests → build/report. Không mở rộng xuất/hao hụt/kiểm kê, không sửa schema hoặc chạy seed trên database đang dùng.

Các bước: (1) đọc baseline và xử lý conflict, (2) sửa active sidebar/report công thức, (3) kiểm số lượng và quyền, (4) form và mã phiếu chống lặp, (5) ghi tồn + ledger dưới khóa, (6) test rollback/retry/hồi quy và bàn giao. Không tự commit/push/merge vào dev.

## Cách sử dụng

1. Đăng nhập tài khoản Admin active.
2. Vào `/admin/ingredients`, chọn nguyên liệu đang dùng → **Nhập kho**.
3. Đọc tên, tồn hiện tại và đơn vị (g/kg/ml/l hoặc đơn vị đã cấu hình). Nhập số lượng theo chính đơn vị này; hệ thống không tự quy đổi.
4. Số lượng từ 0.001 đến 999999999.999, tối đa 3 chữ số thập phân. Tổng tồn sau nhập cũng không được vượt giới hạn trên. Ghi chú tùy chọn tối đa 1000 ký tự.
5. Bấm **Ghi nhận nhập kho**. Thành công chuyển sang lịch sử và hiển thị actor, thời điểm UTC, số lượng, tồn trước/sau và ghi chú.
6. Muốn nhập thêm thực sự: mở **Tạo phiếu nhập mới**. Gửi lại cùng phiếu/cùng dữ liệu chỉ ghi một lần; đổi số lượng/ghi chú của phiếu đã ghi bị từ chối.

Nguyên liệu inactive cần kích hoạt trước khi nhập. Nguyên liệu đã xóa không truy cập được. Staff không được nhập kho. Tồn hiện tại chỉ để tham khảo khi mở form; Service đọc lại tồn dưới khóa lúc ghi.

## Tính nhất quán

- Transaction khóa Admin rồi nguyên liệu, kiểm lại dữ liệu; cộng tồn bằng số nguyên phần nghìn và ghi ledger cùng thành công/thất bại.
- Mã thao tác ngẫu nhiên nằm trong token mã hóa, gắn với nguyên liệu, Admin và đơn vị khi mở form. Không tin các trường tồn/actor/type/đơn vị do client tự gửi.
- Ledger dùng `reference_type=stock_import`, `reference_id` là mã thao tác; kiểm lặp trong cùng khóa nguyên liệu. Không cần bảng mới. Mã có xác suất va chạm rất nhỏ; nếu va chạm với dữ liệu khác thì từ chối, không ghi đè.
- Chứng từ cũ không sửa/xóa qua UI. Không đổi giá vốn, đơn vị hoặc ngưỡng tồn trong thao tác nhập.
- Khóa chống lặp bảo vệ luồng recordImport có operation key. Các lời gọi nội bộ cũ không truyền key vẫn là các lần nhập độc lập; controller mới luôn bắt buộc token.

## Kiểm thử thủ công

Chỉ dùng dữ liệu thử do bạn chọn; không reset database.

| Kịch bản | Kết quả mong đợi |
| --- | --- |
| Tồn 1.250 kg, nhập 2.375 kg | Tồn 3.625, một ledger import có actor đúng |
| Gửi lại cùng phiếu và dữ liệu | Tồn giữ 3.625, vẫn một ledger |
| Đổi lượng/ghi chú trên phiếu cũ | Báo lỗi, mở phiếu mới để nhập tiếp |
| 0, âm, chữ, mảng, quá 3 thập phân | Validation, không thay tồn |
| Tổng tồn vượt giới hạn | Báo lỗi, không có ledger mới |
| Đổi đơn vị sau khi mở form chưa dùng | Từ chối phiếu cũ, mở lại form |
| Nguyên liệu inactive/đã xóa | Chặn nhập; không ghi kho |
| Staff/Guest gọi URL trực tiếp | 403/chuyển đăng nhập |
| Ledger lỗi khi ghi | Rollback tồn, retry cùng phiếu sau khi sửa lỗi chỉ ghi một lần |
| Xem lịch sử nhiều trang | Đúng nguyên liệu, mới nhất trước; chỉ đọc |

Test thông thường dùng SQLite :memory: qua phpunit.xml và tạo fixtures riêng trong test; không import database/seed_data.sql.

Kiểm thử MySQL opt-in đã có tại `InventoryImportConcurrencyMySqlTest`: tự tạo database tên ngẫu nhiên `inventory_import_test_<16 hex>`, chỉ chạy migrations users/ingredients/ledger ở database này và tự xóa database test sau khi chạy. Không chọn `coffee_shop`. Ca test giữ khóa nguyên liệu trong tiến trình chính, khởi chạy hai PHP workers rồi thả khóa để kiểm gửi trùng và hai phiếu độc lập của hai Admin. Workers nhận cấu hình qua stdin, không ghi secret ra file/log.

Chạy trên CMD khi MySQL Laragon đang bật:

```bat
set INVENTORY_IMPORT_MYSQL_TEST=1
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=InventoryImportConcurrencyMySqlTest
set INVENTORY_IMPORT_MYSQL_TEST=
```

Kết quả local đã quan sát: 1 test, 21 assertions PASS. Đây là kiểm chứng hai ca tranh chấp cụ thể; không thay thế kiểm thử tải lớn hoặc nghiệm thu UI.
