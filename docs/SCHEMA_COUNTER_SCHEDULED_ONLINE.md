# Cấu trúc dữ liệu cho ba cách mua

Ngày 26/10/2026. Schema extension CAFE-DB-ONLINE-001, nhánh feature/schema-template-sync. Phạm vi là mục 7 rút gọn của bảng phân công, không phải các yêu cầu gateway/shipper/holds ở phần cũ.

## Bảng hiện có và phần thêm

| Nghiệp vụ / owner | Nguồn dữ liệu | Thay đổi lần này |
| --- | --- | --- |
| Auth/User/Customer — Toàn | users, sessions, customers | Không cần bảng mới; khách gửi request không cần user nội bộ |
| Bàn/đặt bàn — Toàn | areas, cafe_tables, reservations | Giữ nguyên; hẹn lấy đồ uống không dùng reservations |
| Catalog/menu — Hợp | categories, products, product_variants, toppings | Giữ nguyên; request lưu snapshot tên/giá, tham chiếu variant/topping |
| Công thức/kho — Hợp | ingredients, product_recipes, inventory_transactions | Giữ nguyên; tiêu hao lúc preparing, không reserve/holds |
| POS/Order — Ninh | orders, order_items, order_item_toppings | orders thêm purchase_type và online_request_id |
| Đặt online/hẹn — Ninh | online_order_requests, online_order_request_items, online_order_request_item_toppings | Ba bảng mới lưu request, liên hệ/giờ hẹn, món/topping, chống trùng và token hash |
| Thanh toán/khuyến mãi — Đạt | payments, promotions, order_promotions | Giữ nguyên; request thêm payment_preference và payment_timing; thực nhận vẫn ghi payments |
| Report/UI chung | Đọc chứng từ và admin_menus | Không thêm bảng báo cáo/template; UI phải triển khai sau theo dữ liệu mới |

purchase_type: counter (tại quầy), scheduled (hẹn giờ đến lấy), online (web lấy sớm). order_type vẫn dine_in/takeaway, không bị thay bằng kiểu mua. scheduled/online phải takeaway và không có bàn. Đơn cũ mặc định counter, online_request_id=NULL; giữ nguyên ID, tiền, trạng thái và liên kết lịch sử.

Request scheduled có pickup_at; online không có giờ hẹn cụ thể. payment_preference là cash/bank_transfer; payment_timing là on_receipt/prepaid; prepaid chỉ bank_transfer. Phải phân biệt ý định của khách với thực thu: request không có trạng thái paid, payments là nguồn tiền chuẩn.

Quan hệ: Customer (optional) → Request → RequestItem → RequestItemTopping. Variant/Topping tham chiếu catalog. Request liên kết tối đa một Order qua FK UNIQUE orders.online_request_id, không có FK ngược. accepted_by là FK User nullable trước duyệt; orders.user_id vẫn bắt buộc, được Staff/Admin accept gán trong workflow sau.

Các index/precision/CHECK đầy đủ nằm trong database-design.md. Bảng request không xóa mềm/cascade lịch sử. Lặp variant trong request được phép vì ghi chú/topping có thể khác; chỉ cấm cùng topping xuất hiện hai lần trên cùng dòng.

## Phần schema bảo vệ và phần Service phải làm

Schema: FK, UNIQUE mã/submission/token/request–Order, enum, lượng dương/giá không âm, scheduled cần giờ, accepted cần người/thời điểm, rejected cần lý do, prepaid cần bank, online không gắn bàn. Token chỉ lưu hash hex 64, không lưu token thật.

Service sau: quyền Admin/Staff, giờ nhận tương lai/giờ mở, số điện thoại, giỏ không rỗng, active/not deleted, server price/reconfirm giá đổi, request/Order cùng purchase_type và accepted, accept nguyên tử, một paid/Order, tổng tiền, ready/served/completed và thiếu tồn. Không thể kết luận chức năng đã chạy chỉ vì migrate thành công.

Giữ token/submission key bí mật và khó đoán do ứng dụng sinh; unique hash không tự chứng minh chống đoán nếu dùng token yếu. Request và snapshot phải được tạo trong transaction, không cho khách tự sửa các trường accepted hoặc giá server.

## Migration và cách áp dụng

Thứ tự: 2026_10_26_000001 requests → 000002 items → 000003 toppings → 000004 orders. MySQL là đích dự án, dùng CHECK enforced; kiểm thử thật trên MySQL 8.4. Không hỗ trợ chạy bốn migration mới này trên SQLite, tương tự phần CHECK MySQL baseline. Các feature tests SQLite cũ dùng fixture riêng không bị thay đổi.

Lần triển khai này chỉ tạo migration và kiểm thử database tạm; database coffee_shop chưa áp dụng, bốn migrations vẫn Pending. Sau review/backup DB local đúng target, dùng PowerShell tại project:

```powershell
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
& $php artisan migrate:status
& $php artisan migrate
```

Không migrate:fresh, không import lại SQL v1 chỉ để áp schema. Đơn cũ sẽ nhận default counter khi ALTER; không có UPDATE tiền hoặc xóa bản ghi. Nếu DB đã có nghiệp vụ online tự tạo ngoài migrations, cần audit/mapping trước, không tự gộp vào request mới.

down chỉ dùng khi bảng request/items/toppings còn rỗng và không Order online; có lịch sử sẽ báo Refusing rollback. Không xóa dữ liệu để né guard. Backup/forward fix khi có nghiệp vụ thật; MySQL DDL không hoàn toàn transactional và rollback một batch có thể đã lùi vài migrations trước khi gặp guard.

## Kiểm thử riêng MySQL

```powershell
$env:ONLINE_SCHEMA_MYSQL_TEST = '1'
& $php artisan test --filter=OnlinePickupSchemaMySqlTest
Remove-Item Env:ONLINE_SCHEMA_MYSQL_TEST
```

Test dùng cấu hình kết nối MySQL của project, tạo database online_schema_test_<random>, chạy migrations, test và chỉ drop database do chính test tạo. Cần quyền CREATE/DROP database trên MySQL test/local. Không trỏ credential test vào production. Mặc định test opt-in bị skip, không giả kết quả MySQL bằng SQLite.

## Bàn giao tiếp

Ninh tạo workflow request/accept và UI online/hẹn; Đạt hoàn thiện Payment confirmation; Hợp công bố catalog/stock và fixture; Toàn quyền/Customer/shared template. Chưa tạo Models/Base generated, routes hoặc viết lại Service ngoài task schema. SQL v2/verifier mới thuộc task sau; giữ SQL v1 nguyên trạng.
