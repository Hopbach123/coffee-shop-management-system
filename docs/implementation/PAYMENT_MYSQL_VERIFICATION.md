# Payment MySQL verification — 17/11/2026

## Task Status
PASS cho migration/receipt trên instance MySQL test cô lập. Không phải phê duyệt deployment.

## Task ID
SALES-FINANCE-QA-DB-001

## Target đã xác minh
- PHP Laragon 8.3.30, extension pdo_mysql có sẵn.
- Connection dự án cấu hình 127.0.0.1:3306 / coffee_shop, probe read-only thất bại code2002. Không chạy migration/query nghiệp vụ trên DB đó.
- Khởi tạo instance **MySQL 8.4.3** mới bằng binary Laragon, `--no-defaults`, data directory `C:\Users\Admin\AppData\Local\Temp\coffee_sales_qa_mysql_5_r40q40\data`, bind127.0.0.1, cổng **52479** riêng, mysqlx OFF. Tài khoản root chỉ của instance dùng một lần, không lấy credential thật ra report.
- Test tạo DB mới `sales_finance_test_<16hex>` bằng CREATE DATABASE (không IF NOT EXISTS), xác nhận tên và SHOW TABLES rỗng. Chỉ drop DB do test vừa tạo. Không xóa DB tồn tại trước, không sửa .env hoặc config DB project.
- Chạy migrate và migrate:status qua connection sales_qa chỉ tới DB mới. Schema/version/index thật được kiểm tra trong MySQL, không suy từ SQLite.

## Kết quả
SalesFinanceMySqlTest có fresh migration tất cả dependency; test upgrade tháo riêng receipt extension trên DB test rồi tạo legacy receipt, chạy up và so mọi cột cũ, received/operation legacy NULL; backfill hash reference khớp. SHOW COLUMNS xác nhận paid_order_key là VIRTUAL GENERATED. Insert paid thứ hai/Order bị UNIQUE từ chối; hai pending/NULL cùng Order được phép. Duplicate legacy paid chặn upgrade trước DDL; số liệu lịch sử không bị tự sửa. Tiền DECIMAL(15,2), lưu/read string; cash/bank/receipt được kiểm tra qua Service thật.

Migration file **không sửa trong task QA**. Không sửa migration cũ. Rollback có populated operation vẫn bị guard từ bộ PaymentWorkflowTest. MySQL DDL không atomic: vẫn phải backup + preflight trên bản sao trước khi dùng DB thật.

## Suite / evidence
- `storage/logs/sales-mysql-final.json`: **8/8 PASS, 204 assertions** (includes targeted C01–C12, upgrade, 3 flows, current-read và lock regression).
- `storage/logs/sales-concurrency-evidence.jsonl`: outcome từng case, DB ngẫu nhiên, receipt/order/ledger counts; chỉ fixture test.
- `storage/logs/sales-mysql-all.json`: lượt nhóm MySQL cuối, xem SALES_CONCURRENCY_REPORT.md. Không trộn lỗi UI recipe cũ thành lỗi migration receipt.

## Giới hạn
Phạm vi bank identity hiện là định danh giao dịch đầy đủ của một quán; không tự thêm nhiều tài khoản. Legacy refs NULL/blank giữ nguyên, không bịa mã; reference mới bắt buộc. Không chứng minh các hệ MySQL/MariaDB/version khác đều tương thích. Chưa upgrade coffee_shop, chưa backup DB dự án trong task.

## Re-run an toàn
Chuẩn bị instance disposable localhost (không dùng port/DB dự án), chỉ định port rõ. Ví dụ PowerShell sau khi xác minh instance:
```powershell
$env:SALES_FINANCE_MYSQL_TEST = '1'
$env:SALES_FINANCE_TEST_PORT = '<port-instance-test>'
$env:SALES_FINANCE_TEST_USER = '<user-test>'
# Secret qua môi trường local, không ghi vào report.
php artisan test --filter=SalesFinanceMySqlTest
```
Suite tự tạo/drop DB ngẫu nhiên riêng, cần quyền CREATE/DROP trên instance test. Không dùng migrate:fresh. Migrate:status tự gọi bên trong setUp sau xác minh target; không chạy default connection dự án.

## Files / checks
Mới: SalesFinanceMySqlTest, tests/Support/sales-finance-worker.php và report. Sửa tối thiểu OrderService/PaymentService theo lock findings và test harness được ghi ở báo cáo concurrency. Runtime/gói Finance khác giữ nguyên.

## Risks / Engineer Review Required
PASS ở test instance không là quyết định nâng DB dự án. Gate deployment vẫn NOT_APPLIED, cần engineer review/backup/phê duyệt riêng. Browser gate chưa đạt; UI recipe finding giữ riêng. Không commit/push/merge.

## Cleanup
Đã đối chiếu @@datadir với thư mục TEMP vừa tạo rồi shutdown đúng instance52479. `storage/logs/mysql-qa-environment.json` ghi version/port/shutdown và project_database_migrated=false. Không để MySQL test chạy nền; giữ datadir fixture để review, không xóa dữ liệu quán. Các DB riêng do suites tạo đã cleanup theo ownership.

## Acceptance Criteria / Blockers / Assumptions / Unverified Items
PASS: fresh/upgrade receipt, index/generated/NULL/legacy và migration status trên DB mới. MySQL8.4.3 là môi trường kiểm chứng; không giả định MariaDB. UI và deployment chưa nghiệm thu; không blocker receipt DB test mới. Bank legacy duplicate và operator identity vẫn cần preflight mỗi môi trường nâng cấp thực.


## Final QA/demo follow-up — 17/11/2026
MySQL8.4.3 riêng port55659/datadir TEMP mới dùng cho targeted Recipe test (PASS/46 assertions) và demo fresh database coffee_sales_demo_b7a7a76791d66d3e. Import SQL mẫu hiện có; giờ08:00–20:00 là fixture, không là giờ quán chính thức. Không chạy lại8 case Finance nặng, không đổi writer/payment migration trong lượt này. Instance và PHP demo đã shutdown, giữ datadir; evidence storage/logs/sales-demo-environment.json. Environment/log của lượt QA cũ được giữ nguyên. Không migrate/seed coffee_shop hoặc sửa .env. Review upgrade theo DATABASE_UPGRADE_CHECKLIST.md; fresh demo không chứng minh upgrade DB vận hành thật. Browser NOT_RUN; xem SALES_DEMO_READINESS.md.
