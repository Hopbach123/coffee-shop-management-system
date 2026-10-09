# CAFE-MENU-BE-001 — Category backend verification

## Task Status

**PARTIAL cho Category Management tổng thể.**

- **Backend Category: DONE trong phạm vi backend độc lập được engineer xác nhận.** PHP lint, routes và 14 Category tests đã PASS.
- **Category UI: BLOCKED** vì owner chưa cung cấp shared Admin layout; không triển khai Blade/layout trong lượt này.
- Full regression suite **chưa PASS**: 34/41 tests PASS, 7 tests Auth/Public thất bại vì thiếu Vite manifest. Chi tiết bên dưới; không sửa module ngoài scope.

## Task Information

- Task ID: CAFE-MENU-BE-001 / CAFE-MENU-FE-001.
- Date: 2026-10-09 (Asia/Bangkok).
- Prompt: yêu cầu Category trong tệp đính kèm, phản hồi tách backend độc lập khỏi UI, và yêu cầu tiếp tục kiểm chứng/commit/push.
- Scope hiện tại: kiểm chứng backend, chỉ sửa lỗi Category nếu có; UI giữ BLOCKED.
- Pre-existing worktree changes khi bắt đầu lượt kiểm chứng: Controller, hai FormRequests, tests, routes/admin.php và báo cáo từ lượt triển khai Category trước. Đã giữ nguyên.
- Lượt kiểm chứng này không sửa application/test source; chỉ cập nhật báo cáo và cài vendor từ lock theo quyền được cấp.

## Summary / Implementation Details

Controller mỏng dùng Eloquent; FormRequests chia sẻ validation; resource routes kế thừa group Admin hiện hữu. Có tìm tên, phân trang, tạo/cập nhật, đổi active/inactive và xóa mềm. Không tạo Service hoặc thay Models.

Lượt đầu chưa tìm thấy PHP/Composer trong PATH. Sau khi engineer cung cấp đường dẫn Laragon, đã chạy PHP 8.3.33, cài dependencies và kiểm chứng thành công backend. Các kết luận UNVERIFIED do thiếu runtime trong báo cáo ban đầu được thay bằng evidence thực tế của lượt này.

## Source / Context Reviewed

- AGENTS.md, architecture.md, requirements.md, plan.md, frontend.md.
- database-design.md: Category dictionary, FK/status/soft-delete conventions.
- Bốn docs Auth, hướng dẫn chạy dự án, báo cáo migration và audit nghiệp vụ; báo cáo Role Authorization; reports/README.md.
- Auth Controller, LoginRequest, CheckRole, bootstrap/app.php, routes web/admin/staff, User.
- Category/Product Base và Custom Models; migrations users/categories/products và migration sửa FK Product.
- Layouts Auth/Public, login view, components/style/JS hiện hữu.
- Category/Auth/Role tests, tests/TestCase.php, phpunit.xml, composer.json/lock, package.json, .github/workflows/tests.yml.

## Dependency Check

| Dependency | Kết quả |
| --- | --- |
| Authentication | Login/logout/web session hiện hữu, không sửa. |
| Authorization | CheckRole hiện hữu, không sửa logic role. |
| Admin middleware | Cả 7 routes có web, auth:web, role:admin theo route:list và tests. |
| Model | Category kế thừa SoftDeletes, hasMany Product; Product belongsTo Category với withTrashed. Không sửa. |
| Admin layout | Chưa có resources/views/layouts/admin.blade.php; UI BLOCKED. |
| plan.md | Backend dựa trên Auth/Role và model foundation đã có. Category tests xác minh trực tiếp bảo vệ các routes và persistence. Frontend còn chờ layout. |

## Git

- Base: dev tại ae4b3820, đã fetch/pull trước lượt triển khai.
- Feature: feature/category-management.
- Branch Category không tồn tại local/remote sau fetch ở lượt đầu; đã tạo đúng tên trực tiếp từ dev mới nhất.
- Commit được yêu cầu: `feat: implement category management`.
- Chỉ stage sáu file liệt kê bên dưới; commit/push sau review theo yêu cầu hiện tại. Hash và kết quả push được xác nhận trong thông báo bàn giao sau lệnh Git.
- Không merge vào dev/main, không force push/reset/discard.

## Files Created

- app/Http/Controllers/Admin/CategoryController.php
- app/Http/Requests/Admin/StoreCategoryRequest.php
- app/Http/Requests/Admin/UpdateCategoryRequest.php
- tests/Feature/CategoryManagementTest.php
- reports/task-reports/CAFE-MENU-BE-001__report.md

## Files Modified

- routes/admin.php: đăng ký resource routes Category và PATCH toggle-status bên trong group Admin đã có. Đây là shared source file duy nhất cần sửa.
- Trong riêng lượt kiểm chứng: chỉ cập nhật báo cáo này; không cần sửa lỗi Category.

## Files Deleted

None.

## Routes

Đã xác minh bằng `php artisan route:list --path=admin/categories -v`: **7 routes**, đều có web + auth:web + role:admin.

| Method | URI | Name |
| --- | --- | --- |
| GET/HEAD | /admin/categories | admin.categories.index |
| GET/HEAD | /admin/categories/create | admin.categories.create |
| POST | /admin/categories | admin.categories.store |
| GET/HEAD | /admin/categories/{category}/edit | admin.categories.edit |
| PUT/PATCH | /admin/categories/{category} | admin.categories.update |
| PATCH | /admin/categories/{category}/toggle-status | admin.categories.toggle-status |
| DELETE | /admin/categories/{category} | admin.categories.destroy |

Dùng routes/admin.php theo architecture/convention hiện tại; chưa có module loader/catalog.php, không refactor route tổng.

## Category Features

| Chức năng | Kết quả |
| --- | --- |
| List query | PASS: sort display_order ASC, id ASC; loại soft-deleted, vẫn giữ inactive. Chưa có UI list. |
| Search | PASS: query parameter search, LIKE trên name, không tìm description, không AJAX. |
| Pagination | PASS: 15/trang, trang 2 đúng dữ liệu, giữ search parameter. |
| Create | PASS: validated fields, Eloquent persistence, redirect list, flash success; tên trùng hợp lệ. |
| Update | PASS: field whitelist, validation chung, bảo toàn image/system fields, redirect/flash. |
| Active/Inactive | PASS cả hai chiều qua PATCH; không đổi deleted_at. |
| Soft Delete | PASS: row còn trong DB, query mặc định loại record, Product còn nguyên và relationship vẫn đọc được Category đã xóa. |
| Image | Không upload/thumbnail; giữ schema và ảnh hiện hữu, bỏ qua image client gửi. |
| Authorization/CSRF | PASS: Guest redirect login, Staff 403 trên mọi endpoint; mutations yêu cầu CSRF; GET toggle bị từ chối. |

## Validation

- name: required, string, max:255; không unique.
- description: nullable, string.
- display_order: nếu gửi thì required integer trong khoảng signed INT; nếu thiếu khi create dùng DB default 0, thiếu khi update giữ giá trị cũ. Không áp đặt rule không âm.
- status: required, active/inactive.
- Chỉ dùng validated fields; không nhận id, created_at, deleted_at, image cho persistence.
- FormRequest dùng lỗi tiếng Việt và cơ chế old input của Laravel. status được gán riêng từ validated data để giữ nguyên fillable của model.
- UI hiển thị lỗi/flash/old input/confirmation/empty state chưa được triển khai.

## Environment / Dependency Installation

- PHP: **8.3.33 CLI**, từ `D:\Applications\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe`.
- Composer: **2.10.2**, trong `D:\Applications\laragon\bin\composer`.
- `composer validate --no-check-publish`: PASS.
- `composer install --no-interaction --prefer-dist`: PASS, **114 installs, 0 updates, 0 removals**, gồm require-dev; chạy package discovery thành công.
- Lần install sandbox đầu bị chặn mạng/quyền ghi; đã dừng rồi chạy lại với quyền được duyệt.
- `composer check-platform-reqs`: PASS; Laravel khóa tại v13.25.0.
- composer.json và composer.lock không thay đổi; không dùng composer update/setup, không cài frontend packages.
- PHP ban đầu chưa nạp extension. Đã tạo php.ini tạm tại `%TEMP%\coffee-category-php-verification\php.ini` và đặt PHPRC/PATH chỉ trong tiến trình kiểm chứng. Bật extension có sẵn: openssl, curl, mbstring, fileinfo, pdo_sqlite, sqlite3, zip; không sửa php.ini của Laragon.
- Repo không có .env/APP_KEY. Tests HTTP lần đầu lỗi MissingAppKeyException; đã cấp APP_KEY ngẫu nhiên chỉ qua environment tiến trình test, không in/lưu key vào source hoặc .env.
- Tests dùng SQLite :memory: theo phpunit.xml, có assertions từ chối persistent DB trước khi tạo schema fixtures.

## Tests / Checks Executed

| Check | Kết quả |
| --- | --- |
| PHP lint CategoryController | PASS |
| PHP lint StoreCategoryRequest | PASS |
| PHP lint UpdateCategoryRequest | PASS |
| PHP lint CategoryManagementTest | PASS |
| PHP lint routes/admin.php | PASS |
| route:list --path=admin/categories -v | PASS, 7 routes với đủ middleware |
| artisan test --filter=CategoryManagementTest | **PASS: 14 tests, 262 assertions** |
| artisan test | **FAIL: 34/41 tests PASS, 7 failures, 414 assertions** |
| git status/diff/diff --check và review file mới | PASS trong kiểm tra phạm vi/whitespace; không coi đây là runtime test |

## Test Results / Failure Analysis

1. Category run đầu: 3 tests PASS, 11 errors do thiếu APP_KEY; đây là cấu hình môi trường, không phải lỗi Category. Sau khi cấp key tạm: 14/14 PASS; không sửa source/tests để làm xanh.
2. Full suite: 7 failures đều là **ViteManifestNotFoundException**, thiếu `public/build/manifest.json` khi render Auth/Public. Chạy lại với output được tóm tắt để xác nhận cả 7 cùng nguyên nhân; kết quả giữ nguyên.
3. node_modules và Vite manifest chưa có. CI hiện hữu đã có npm ci + npm run build trước tests. Không cài frontend dependencies hoặc sửa Auth/Public/test assertions vì yêu cầu hiện tại chỉ cho cài Composer và sửa lỗi thuộc Category.

Các tests full-suite thất bại ngoài Category:

- AuthenticationTest::test_guest_can_view_login_and_public_home
- AuthenticationTest::test_email_must_be_valid
- AuthenticationTest::test_wrong_credentials_fail_without_updating_last_login
- AuthenticationTest::test_active_admin_can_login
- AuthenticationTest::test_active_staff_can_login
- ExampleTest::test_the_application_returns_a_successful_response
- RoleAuthorizationTest::test_role_checks_do_not_protect_public_home

Hai Category list tests mock view factory, kiểm chứng query/paginator mà không render Blade. Tests không dùng RefreshDatabase/migrate:fresh; tạo users/categories/products từ ba migrations thật trên DB trong bộ nhớ, không chạy migration sửa FK dành riêng MySQL.

## Acceptance Criteria

| Criterion | Status / evidence |
| --- | --- |
| Staff bị chặn trên mọi Category endpoint | PASS: feature test 403, dữ liệu/session không bị thay đổi trái phép |
| Guest bị chặn trên mọi Category endpoint | PASS: feature test redirect login |
| Admin create/update | PASS: mutation tests và redirect/flash |
| name required/max255, description string | PASS: validation tests create/update |
| display_order integer/range/default | PASS: invalid/boundary/default tests |
| status chỉ active/inactive | PASS: invalid status tests |
| Hai chiều toggle và phân biệt inactive/delete | PASS: state tests |
| Soft delete, giữ row và loại khỏi list | PASS: DB assertions và query test |
| Giữ nguyên Product | PASS: Product row/relationship assertions |
| Search name, sort, pagination giữ query | PASS: query/paginator tests |
| Whitelist, không thêm unique name, giữ image | PASS: create/update tests |
| CSRF và HTTP method | PASS: missing token 419, GET toggle 405 |
| Routes/namespace/PHP syntax | PASS: lint, route:list, runtime tests |
| Admin list/create/edit render Blade thành công | UNVERIFIED / UI BLOCKED: views và layout chưa có; không thuộc phạm vi kiểm chứng backend độc lập |
| Browser/responsive/accessibility | UNVERIFIED / UI BLOCKED |
| Full regression suite | FAIL: thiếu Vite build, 7 lỗi ngoài Category |
| Không đổi Auth/schema/dependencies versions/shared layout | PASS: source/diff review, lockfile không thay đổi |

## Database Safety

Không sửa/tạo migration, thêm column/constraint/enum, hard delete Category hoặc cascade Product. Không chạy migrate:fresh/db:wipe/migration trên development DB. Tests chỉ dùng SQLite :memory:; không truy cập dữ liệu MySQL development. Category migration khớp schema yêu cầu.

## Risks

- Các GET list/create/edit và trang đích sau redirect chưa render được vì thiếu Category Blade views. Không deploy như Category Management hoàn chỉnh trước khi có layout/UI.
- Category tests xác nhận backend, không chứng minh Admin UI HTTP 200 hoặc browser workflow.
- SQLite tests không thay thế kiểm chứng MySQL collation/constraints/FK correction.
- Full suite còn đỏ vì thiếu build assets; không tuyên bố regression toàn hệ thống PASS.
- Toggle đồng thời trên cùng record chưa có concurrency test.

## Blockers / Remaining Dependencies

- UI: owner cung cấp Admin layout, sau đó tiếp tục chính Category Blade task.
- Full suite: chuẩn bị frontend dependencies/build assets theo quy trình đã duyệt rồi chạy lại suite.
- Không còn blocker PHP/Composer/vendor cho backend khi dùng cấu hình phiên kiểm chứng mô tả ở trên.

## Assumptions

- Chỉ commit/push backend sau Category checks PASS theo yêu cầu mới; full-suite failures ngoài scope phải công bố, không âm thầm sửa.
- Page size 15 là lựa chọn presentation; status/display_order bám schema hiện hữu.
- Không coi báo cáo hoặc test pass là thay thế engineer acceptance hoặc quyền merge/deploy.

## Out-of-Scope Findings

- Bảy failures do Vite manifest của Auth/Public: environment/build dependency, không sửa.
- Audit nghiệp vụ cũ và Current Next Tasks trong plan.md chưa phản ánh toàn bộ source hiện tại; không sửa roadmap hoặc lịch sử audit.

## Unverified Items

Category UI/Blade/browser/responsive/accessibility; full suite sau khi có assets; MySQL; concurrency; remote CI; engineer acceptance.

## Engineer Review Required

Review sáu file của Category, evidence 14 tests/262 assertions và giới hạn UI/full suite. Để tái chạy, dùng PHP/Composer Laragon với extensions và APP_KEY tạm theo mục environment; không commit key. Chạy lint → route:list → Category tests → full suite. Full suite cần assets đã build.

## Scope Check

Không làm Product/Variant/Topping/Promotion/Public Menu/Order/Payment/Inventory/Reservation/Dashboard. Không sửa Auth/Authorization, Models, migrations, Admin layout hoặc shared frontend. Không dùng sub-agent.

## Recommended Next Task

Owner cung cấp Admin layout và hoàn tất dependency build để tiếp tục Category UI/full regression. Không tự triển khai task roadmap khác.
