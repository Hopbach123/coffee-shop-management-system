# Import seed_data.sql vào MySQL Laragon

## Trạng thái nạp trên máy hiện tại

**Đã import `database/seed_data.sql` vào database dự án `coffee_shop` trên MySQL Laragon theo yêu cầu của bạn.** Không cần import lại để bắt đầu test.

| Kiểm tra | Kết quả |
| --- | --- |
| Database đích | MySQL local, 127.0.0.1, coffee_shop |
| Import | IMPORTED: DEMO-V1, 18 business tables |
| Dữ liệu trước import | 3 users, 0 ingredients, 0 orders |
| Dữ liệu sau import | 6 users (3 cũ + 3 demo), 18 ingredients, 7 orders |
| Kiểm chứng SQL | Cả 7 invalid_count bằng 0 |
| Tài khoản demo Admin/Staff | Active, password hash khớp DemoCoffee123! |

Dữ liệu/tài khoản cũ được giữ nguyên. Các bước import bên dưới dành cho thành viên khác hoặc database mới; kiểm chứng trên đây là kết quả ngay sau lần import, không phải trạng thái được kiểm tra liên tục.

## File và luồng sử dụng

- `database/seed_data.sql`: dữ liệu mẫu cho 18 bảng nghiệp vụ.
- `database/verify_seed_data.sql`: SELECT kiểm tra tổng tiền, payment, topping, recipe/consume, stock ledger và lượt dùng promotion.

**Migration tạo bảng → import SQL vào database dự án → Laravel đọc dữ liệu để test.** Không dùng PHP Seeder/db:seed cho bộ này. DatabaseSeeder mặc định không đổi. SQL không tạo/sửa/xóa schema, không DROP/TRUNCATE hoặc tắt FK checks.

## Chuẩn bị

Bật MySQL trong Laragon. `.env` cần trỏ đúng DB local: DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=coffee_shop (hoặc tên DB của bạn). Không commit `.env`.

Kiểm tra đủ migration bằng **PowerShell** tại project:

```powershell
cd F:\HocTap\Nam4\MNM\DeTaiMonHoc\coffee-shop-management-system
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
& $php artisan config:clear
& $php artisan migrate:status
```

Nếu còn pending, review đúng target rồi chạy `& $php artisan migrate`. Đổi đường dẫn PHP theo máy bạn. **Không migrate:fresh trên database chứa dữ liệu cần giữ.**

## Cách 1: HeidiSQL

1. Mở công cụ Database trong Laragon, kết nối MySQL local.
2. Chọn `coffee_shop`; mở Query và chạy `SELECT DATABASE();` để xác nhận.
3. Load SQL file → chọn `database/seed_data.sql`, encoding UTF-8.
4. Bật chế độ **dừng khi gặp lỗi** rồi chạy **toàn bộ file** trong cùng connection, không chỉ chạy một đoạn bôi chọn.
5. Kết quả cuối là `IMPORTED: DEMO-V1, 18 business tables`, hoặc `SKIPPED` nếu đã có dữ liệu mẫu.
6. Refresh bảng để xem dữ liệu. Mở/chạy `database/verify_seed_data.sql`; mọi `invalid_count` phải bằng **0** ngay sau import.

Nếu có lỗi, dừng và chạy `ROLLBACK;` trong **cùng connection**. Không tiếp tục tới COMMIT. Công cụ bỏ qua lỗi rồi tiếp tục có thể commit bộ dữ liệu thiếu.

## Cách 2: terminal MySQL

Mở Laragon Terminal, đăng nhập database. DB password nhập khi được hỏi:

```text
mysql --default-character-set=utf8mb4 -u root -p coffee_shop
```

Tại prompt `mysql>`:

```sql
SELECT DATABASE();
source F:/HocTap/Nam4/MNM/DeTaiMonHoc/coffee-shop-management-system/database/seed_data.sql
source F:/HocTap/Nam4/MNM/DeTaiMonHoc/coffee-shop-management-system/database/verify_seed_data.sql
```

Nếu có lỗi, dừng và `ROLLBACK;`. Không dùng --force để bỏ qua lỗi.

Để batch import tự dừng khi lỗi, dùng **CMD/Laragon Terminal**, không phải PowerShell:

```bat
mysql --default-character-set=utf8mb4 -u root -p coffee_shop < database\seed_data.sql
```

Chạy từ thư mục project. Nếu mysql không ở PATH, dùng đường dẫn đầy đủ `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe`. Batch gặp lỗi sẽ đóng kết nối và rollback transaction chưa commit.

## Tài khoản và mở UI

| Email | Role/status | Mật khẩu fixture công khai |
| --- | --- | --- |
| admin-demo@example.test | admin / active | DemoCoffee123! |
| staff-demo@example.test | staff / active | DemoCoffee123! |
| inactive-demo@example.test | staff / inactive | DemoCoffee123! (login phải bị từ chối) |

Đây là credential mẫu development công khai, không phải secret tài khoản thật; SQL lưu bcrypt hash trong password. Không dùng tài khoản này trên production. Tài khoản local cũ giữ nguyên.

```powershell
npm.cmd run build
& $php artisan serve --host=127.0.0.1 --port=8000
```

Mở http://127.0.0.1:8000/login. Admin vào Nguyên liệu/Catalog/Promotion. Seed cung cấp dữ liệu, không tự tạo UI/module chưa được code.

## Dữ liệu mẫu

| Bảng | Số bản ghi |
| --- | ---: |
| users | 3 |
| customers | 4 |
| areas | 3 |
| categories | 3 |
| toppings | 3 |
| ingredients | 18 |
| promotions | 6 |
| cafe_tables | 5 |
| products | 4 |
| product_variants | 7 |
| reservations | 6 |
| product_recipes | 12 |
| orders | 7 |
| order_items | 7 |
| payments | 4 |
| order_promotions | 1 |
| inventory_transactions | 29 |
| order_item_toppings | 1 |

- Nguyên liệu có g/ml/cái, tồn đủ/thấp/bằng 0 và active/inactive; đủ thử phân trang.
- Catalog có biến thể nhiều kích cỡ/giá và tạm hết; danh mục active/inactive.
- Bàn available/occupied/maintenance; Reservation đủ 6 trạng thái, pending chưa gán bàn.
- Orders dine_in/takeaway, completed/pending/cancelled; Items pending/preparing/ready/served/cancelled.
- Payment cash/bank_transfer, paid/pending/failed. Không tạo refunded khi chưa có workflow được duyệt.
- Promotion percentage/fixed, hợp lệ/hết hạn/chưa bắt đầu/inactive/hết quota.
- Kho có import/consume/waste/adjustment; consume tham chiếu order_item/công thức, tồn khớp log.

Ví dụ `DEMO-V1-PAID-CASH`: 2 Latte × 30.000 + 2 topping × 5.000 = 70.000; giảm 10% = 7.000; paid = 63.000, used_count=1. Pending chưa consume; preparing/ready/served đã consume. Cancelled có subtotal đơn=0, giữ snapshot item hủy.

## Import lại và giữ dữ liệu

- ID tự tăng và liên kết bằng biến session @demo_*; chạy toàn file trong cùng connection.
- Tên mẫu có [DEMO], mã/slug/SKU có DEMO-V1.
- Nếu đơn DEMO-V1-CANCELLED đã có thì bỏ qua toàn bộ INSERT, giữ cả dữ liệu bạn đã sửa để test.
- Nếu email demo đã tồn tại nhưng thiếu dấu hiệu bộ hoàn chỉnh thì bỏ qua và báo collision, không ghi đè.
- Không INSERT IGNORE/REPLACE/ON DUPLICATE UPDATE. Unique collision khác gây lỗi: dừng, ROLLBACK và review.
- Không reset/xóa/update dữ liệu cũ hoặc tự phục hồi rows demo đã xóa. Không import đồng thời.
- Date/time tính theo NOW() của MySQL ở lần import đầu; rerun không kéo dài promotion.
- Muốn bộ sạch mới, tạo một database development riêng, chạy migrations rồi import. Không reset DB nhóm đang dùng.
- Mẫu unit không thay thế validation đơn vị; bộ này không bao phủ mọi trường hợp biên/concurrency/refund.
- Chạy kiểm tra SQL ngay sau import; sau khi sửa giao dịch/tồn để test, kết quả có thể khác.

## Clone và kiểm chứng

Người khác clone sẽ có SQL, không có dữ liệu MySQL trên máy bạn. Họ chạy migrations vào DB riêng rồi import cùng file này.

Đã kiểm tra SQL trên MySQL Laragon database tạm có schema từ migrations thật và user khác sẵn: import, rerun, password hash, giữ user cũ PASS. Database tạm đã được xóa. Sau đó đã import vào `coffee_shop` của dự án và kiểm chứng thành công như mục Trạng thái nạp trên máy hiện tại. Các file PHP Seeder/test/báo cáo của cách trước đã gỡ; source Ingredient giữ nguyên. Chưa commit/push.
