# Dữ liệu mẫu SQL toàn hệ thống

## Task Status
SUCCESS trong phạm vi khôi phục hướng triển khai sai, tạo SQL và hướng dẫn import; không import vào coffee_shop hiện có.

## Task ID
SEED-DATA-SQL — 13/10/2026.

## Summary
Thay bộ PHP Seeder bằng file database/seed_data.sql có dữ liệu liên kết cho 18 bảng nghiệp vụ. Bổ sung SQL kiểm chứng và hướng dẫn import HeidiSQL/MySQL terminal. Giữ nguyên Ingredient feature và DatabaseSeeder mặc định.

## Files Created
- database/seed_data.sql
- database/verify_seed_data.sql
- reports/task-reports/seed-data-sql__report.md

## Files Modified
- docs/HUONG_DAN_SEED_DATA_TOAN_HE_THONG.md: thay nội dung db:seed bằng import SQL.

## Files Deleted
- database/seeders/SystemDemoSeeder.php
- tests/Feature/SystemDemoSeederTest.php
- reports/task-reports/system-demo-seed__report.md

Các file xóa là file untracked do hướng triển khai trước của agent tạo, được user yêu cầu restore. Không git reset/restore/discard source Ingredient. Scripts tạm tạo/kiểm tra SQL đã xóa sau khi chạy.

## Implementation Details
Đọc AGENTS/architecture/requirements/plan, migrations thật và fixtures trước khi đổi. File SQL chỉ chứa INSERT/SET/transaction/SELECT, schema vẫn do migration quản lý. ID tự tăng, liên kết FK bằng LAST_INSERT_ID và biến session. Không hardcode DB target; người import chọn đúng DB local. Không disable FK/unique/check, không DDL, truncate, replace hoặc update bản ghi cũ.

Có tiền tố DEMO-V1, guard trên order completion marker và email demo. Rerun bỏ qua trọn bộ, không nhân đôi hoặc ghi đè các bản ghi demo đã sửa. Password bcrypt hash của credential fixture development công khai (DemoCoffee123!), không dùng credentials của người dùng thật. Import phải cùng connection và stop-on-error; lỗi cần ROLLBACK trước COMMIT.

Fixtures gồm 3 users, 4 customers, 3 areas, 3 categories, 3 toppings, 18 ingredients, 6 promotions, 5 cafe_tables, 4 products, 7 variants, 6 reservations, 12 recipes, 7 orders, 7 items, 4 payments, 1 order promotion, 29 inventory logs và 1 item topping. Time fields tương đối theo NOW() ở lần import đầu.

Pre-existing Ingredient changes: routes/admin.php, internal layout, controller/requests/views/tests/report giữ nguyên. Không commit/push/chuyển branch.

## Tests / Checks Executed
- MySQL Laragon database tạm coffee_sql_verify_<random>: đầy đủ migrations thật, import SQL, rerun và giữ unrelated user/demo edit.
- verify_seed_data.sql: 7 checks trên dữ liệu vừa import.
- Hash::check cho tài khoản Admin và Staff mới trong SQL.
- php artisan test: 125 tests / 1758 assertions PASS sau khi xóa tests của PHP Seeder cũ.
- git diff --check/status và kiểm tra không còn references SystemDemoSeeder/COFFEE_DEMO_PASSWORD ở source/docs.
- Build không áp dụng: chỉ SQL/Markdown và gỡ PHP Seeder/tests; không thay frontend trong task này.

## Test Results
MySQL import/rerun/hash/existing-user PASS. Cả 7 invalid_count bằng 0: order totals, item/topping snapshots, paid payment, ledger chain, stock cuối, consume theo recipe, promotion used_count. Counts đúng bộ 18 bảng, cộng một unrelated user được giữ. Database tạm đã drop đúng target; coffee_shop không thay đổi.

## Acceptance Criteria
- PASS: artifact .sql import trực tiếp, không cần PHP Seeder.
- PASS: 18 bảng có dữ liệu đúng migration/MySQL constraints.
- PASS: FK thực tế, totals/payments/ledger/công thức nhất quán.
- PASS: giữ existing user và rerun không nhân đôi/ghi đè dữ liệu đã test.
- PASS: gỡ file hướng triển khai sai, giữ source Ingredient.
- PASS: hướng dẫn Laragon/HeidiSQL/CMD/PowerShell phân biệt rõ.

## Risks
Interactive import bỏ qua lỗi có thể commit một phần; phải stop-on-error và ROLLBACK. SQL không có production environment guard; chỉ import vào development/test. Demo password là public fixture, không dùng production. Rerun không tự phục hồi rows bị xóa. Không chạy concurrent import. Quota/thời gian expire tự nhiên sau lần import đầu. Không bao phủ hết tình huống biên/refund/concurrency.

## Blockers
None.

## Assumptions
User yêu cầu tạo file và cách nạp, chưa yêu cầu thực thi import vào DB có dữ liệu thật của nhóm. Không reset coffee_shop. File chạy bằng một connection chọn DB đích có đầy đủ migrations.

## Out-of-Scope Findings
Validation unit trong Ingredient chưa chuẩn hóa danh mục; SQL mẫu không thay đổi requirement này. Không sửa trong task SQL.

## Unverified Items
HeidiSQL UI thao tác bằng tay, import vào coffee_shop hiện tại, remote CI, browser/engineer acceptance. SQL đã kiểm chứng bằng PDO trên MySQL thật.

## Engineer Review Required
Review SQL và docs; xác nhận DB local trước import, chạy verify_seed_data.sql sau import. Không migrate:fresh hoặc dùng --force bỏ qua lỗi.

## Recommended Next Task
Import bộ mẫu vào database development đã chọn và nghiệm thu nghiệp vụ theo module. Không tự triển khai task kế tiếp.

## Cập nhật: import vào database dự án theo yêu cầu tiếp theo

Đã xác nhận target APP_ENV=local, mysql 127.0.0.1, database coffee_shop và import database/seed_data.sql thành công. Trước import có 3 users, 0 ingredients, 0 orders; sau import có 6 users, 18 ingredients, 7 orders. Cả 7 invalid_count từ verify_seed_data.sql bằng 0; hash mật khẩu Admin/Staff demo khớp. Không reset/xóa dữ liệu cũ. Script chạy SQL tạm đã được xóa. Các ghi chú “chưa import coffee_shop” ở phần báo cáo ban đầu là trạng thái trước yêu cầu này, được thay bằng evidence cập nhật tại đây.
