# Điều chỉnh, hao hụt và kiểm kê nguyên liệu

Nhánh `feature/inventory-adjustments`, baseline `dev e6e926e7` (PR #33). Không cần migration hoặc seed mới. Không sửa tồn bằng CRUD.

## Thao tác

1. Đăng nhập Admin đang hoạt động → Nguyên liệu → **Điều chỉnh / kiểm kê** trên nguyên liệu đang dùng. Có thể mở từ Lịch sử kho.
2. Chọn nghiệp vụ, nhập số lượng theo đơn vị hiện tại và lý do (bắt buộc, tối đa 1000 ký tự).
3. Kiểm tra rồi xác nhận ghi phiếu. Trang chuyển đến lịch sử với tồn trước/sau, lượng biến động, người thực hiện và lý do.

| Nghiệp vụ | Dữ liệu nhập | Ví dụ tồn trước 10 g |
| --- | --- | --- |
| Điều chỉnh tăng | Lượng tăng, lớn hơn 0 | Nhập 2 → tồn 12 |
| Điều chỉnh giảm | Lượng giảm, lớn hơn 0 | Nhập 2 → tồn 8 |
| Hao hụt / hư hỏng | Lượng mất, lớn hơn 0 | Nhập 2 → tồn 8, loại waste |
| Kiểm kê | Tổng tồn thực tế, được bằng 0 | Nhập 2 → tồn 2, lượng điều chỉnh 8 |

Số có tối đa 9 chữ số nguyên và 3 chữ số thập phân. Tồn sau phải từ 0 đến 999999999.999; không tự quy đổi kg/g hoặc l/ml. Phiếu kiểm kê khớp tồn hiện tại không tạo log bằng 0; hệ thống báo không cần điều chỉnh. Đây là điều chỉnh sau kiểm kê một nguyên liệu, chưa phải module biên bản kiểm kê nhiều dòng hoặc lưu phiên kiểm kê không chênh lệch.

Admin ghi phiếu là người xác nhận; chưa có quy trình Staff đề xuất rồi người khác duyệt. Phiếu đã ghi không sửa/xóa. Nếu ghi sai, lập phiếu điều chỉnh mới có lý do đối chiếu phiếu cũ; không sửa chứng từ lịch sử.

## An toàn khi thao tác

- Mỗi lần mở phiếu có token riêng. Gửi lại cùng token và dữ liệu chỉ trả chứng từ cũ, không thay tồn lần hai. Đổi loại/lượng/lý do trên token đã ghi bị từ chối. Dùng **Mở phiếu mới** cho nghiệp vụ độc lập.
- Kiểm kê giữ mốc tồn và lịch sử lúc mở phiếu. Nếu có biến động xen vào, kể cả tồn tăng rồi giảm về đúng số cũ, phải mở phiếu mới và kiểm kê lại. Không ghi đè tồn mới từ số kiểm kê cũ.
- Tăng/giảm/hao hụt áp dụng trên tồn mới nhất dưới khóa; hao hụt quá tồn bị từ chối. Kho và ledger cùng thành công hoặc cùng rollback. Không dùng float để tính số lượng.
- Nguyên liệu ngừng dùng/đã xóa không ghi mới; đơn vị đổi làm phiếu cũ mất hiệu lực. Client không được quyết định người ghi, loại ledger hoặc tồn trước/sau.

## Kịch bản nghiệm thu

Trên dữ liệu development có thể bỏ: tăng 2.125, giảm 1.125, waste 1, kiểm kê về 0; đối chiếu từng trước/sau và lý do. Gửi lại phiếu, thử đổi payload sau ghi; thử 0 ở waste, số âm, quá 3 thập phân, quá tồn, thiếu lý do, Staff/guest, token nguyên liệu khác. Mở hai phiếu kiểm kê, ghi phiếu thứ nhất rồi ghi phiếu thứ hai phải bị từ chối. Kiểm tra các nút bằng Tab/Enter và hiển thị 375/768/1440.

## Kiểm chứng tự động

`php artisan test --compact --filter=StockAdjustmentWorkflowTest` dùng SQLite `:memory:`. Full suite dùng cấu hình phpunit.xml, không reset DB dự án.

Test MySQL tùy chọn (CMD, PHP phải có trong PATH):

```bat
set INVENTORY_IMPORT_MYSQL_TEST=1
php artisan test --compact --filter=InventoryImportConcurrencyMySqlTest
set INVENTORY_IMPORT_MYSQL_TEST=
```

Harness dùng cấu hình kết nối MySQL hiện có, tạo database ngẫu nhiên `inventory_import_test_<16hex>`, chỉ migrate ba bảng fixtures và drop chính database đó sau test. Không chạy migration/seed trên coffee_shop. Hai test gồm nhập kho trước đây và adjustment mới; hai worker tranh tồn, gửi lặp và kiểm kê lỗi thời. Tài khoản MySQL cần quyền tạo/drop DB test.

Kết quả ngày 06/11/2026: workflow 4 tests / 117 assertions; MySQL 2 tests / 50 assertions; full suite 415 tests, 408 passed, 7 skipped, 5801 assertions; build và diff check đạt. Browser mới tới login, chưa nghiệm thu responsive/keyboard. Các test opt-in không bật bị skip trong suite mặc định; MySQL nhập/điều chỉnh đã chạy riêng.
