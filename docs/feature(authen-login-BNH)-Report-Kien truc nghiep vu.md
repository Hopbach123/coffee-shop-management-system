# Báo cáo cấu trúc và logic nghiệp vụ Login, Authentication, Authorization

Ngày ghi nhận: **09/10/2026**. Nội dung đối chiếu với source hiện tại, ghi lại công việc trên hai nhánh; không phải yêu cầu triển khai thêm module.

## 1. Phạm vi và trạng thái hai nhánh

| Nhánh | Trách nhiệm | Trạng thái ghi nhận |
| --- | --- | --- |
| `feature/auth-login` | Xác thực email/password, login/logout, validation, session, trạng thái tài khoản và last_login_at | Đã commit; được merge vào `dev` qua PR #3, merge commit `194cb0cf` |
| `feature/role-authorization` | Kiểm tra vai trò, bảo vệ nhóm routes Admin/Staff, xử lý truy cập không có quyền | Source và tests đã triển khai; các thay đổi role còn chưa commit tại thời điểm báo cáo |

Nhánh hiện tại là `feature/role-authorization`, đã chứa code auth từ nhánh trước. Không cần checkout lại nhánh auth để đọc logic đã kế thừa. Các kiểm tra local đã PASS; không suy ra GitHub CI hoặc nghiệm thu browser cũng đã PASS.

Authentication xác định **người dùng đã đăng nhập là ai**. Authorization xác định **người dùng đó có được truy cập chức năng hay không**.

## 2. Cấu trúc code và trách nhiệm

Kiến trúc áp dụng: Laravel MVC, Eloquent, FormRequest, middleware và Laravel Auth/session. Nghiệp vụ này chưa cần Service hoặc Repository riêng.

| File | Trách nhiệm |
| --- | --- |
| `routes/web.php` | Routes login/logout/public; nạp routes Admin/Staff trong các nhóm được bảo vệ |
| `routes/admin.php` | Nơi khai báo routes nghiệp vụ Admin trong tương lai; hiện trống |
| `routes/staff.php` | Nơi khai báo routes nghiệp vụ vận hành Staff trong tương lai; hiện trống |
| `app/Http/Requests/Auth/LoginRequest.php` | Validation dữ liệu login và thông báo lỗi tiếng Việt |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Render login, phối hợp Auth/session, cập nhật lần đăng nhập và logout |
| `app/Http/Middleware/CheckRole.php` | So sánh role người dùng với danh sách role cho phép |
| `bootstrap/app.php` | Đăng ký alias `role`, redirect người dùng đã đăng nhập khỏi trang guest về `/` |
| `app/Models/User.php` | Authenticatable User, soft delete, ẩn password, cast password hashed và last_login_at datetime; được tái sử dụng |
| `config/auth.php` | Guard web sử dụng session và Eloquent provider User |
| `config/session.php` | Cấu hình lưu session theo môi trường |
| `database/migrations/0001_01_01_000000_create_users_table.php` | Schema users và sessions đã có sẵn; không thay đổi trong hai task auth/role |
| `resources/views/layouts/auth.blade.php` | Document shell của trang auth |
| `resources/views/auth/login.blade.php` | Form email/password, CSRF, old email, lỗi validation/authentication |
| `resources/views/components/public/brand.blade.php` | Branding được tách từ navbar để dùng chung |
| `resources/views/components/public/button.blade.php` | Button sẵn có được tái sử dụng |
| `resources/views/components/public/navbar.blade.php` | Link login cho guest; form POST logout cho người đã đăng nhập |
| `resources/css/auth/login.css` | Styles form/auth dựa trên Coffee Shop tokens |
| `resources/css/app.css` | Import CSS auth cùng theme/components sẵn có |
| `tests/Feature/AuthenticationTest.php` | Tests login/logout, validation, status, timestamp, session và CSRF |
| `tests/Feature/RoleAuthorizationTest.php` | Tests guest, Admin/Staff, 403, giữ phiên và đa role |
| `.github/workflows/tests.yml` | Build frontend trước khi chạy tests để runner có Vite manifest |

Không đưa truy vấn database vào Blade; không đặt phân quyền trong JavaScript hoặc chỉ dựa vào việc ẩn menu.

## 3. Routes và middleware

| Method | URI / nhóm | Name | Middleware / xử lý |
| --- | --- | --- | --- |
| GET/HEAD | `/` | Chưa đặt tên | web; trang public |
| GET/HEAD | `/login` | login | web, guest:web → create() |
| POST | `/login` | login.store | web, guest:web → store() |
| POST | `/logout` | logout | web, auth:web → destroy() |
| Routes nạp từ admin.php | Prefix `/admin` | Prefix admin. | web, auth:web, role:admin |
| Routes nạp từ staff.php | Prefix `/staff` | Prefix staff. | web, auth:web, role:admin,staff |

Hai dòng nhóm Admin/Staff là cấu hình cho routes được khai báo bên trong, **không tự tạo endpoint GET /admin hoặc GET /staff**. Hiện mở các URL đó có thể trả 404 do chưa có trang nghiệp vụ.

## 4. Logic đăng nhập

```text
Guest → GET /login → form email/password
      → POST /login + CSRF token
      → LoginRequest kiểm tra input
      → Laravel Auth kiểm tra credentials + status active
          ├─ Thất bại → về form, lỗi chung, giữ email
          └─ Thành công → regenerate session ID
                         → cập nhật users.last_login_at
                         → intended URL hoặc /
```

### Validation

- Email: required, string, email.
- Password: required, string.
- Validation kiểm tra hình dạng dữ liệu; không dùng validation rule để kiểm tra mật khẩu đúng/sai.
- Blade hiển thị lỗi gần field với aria-invalid/aria-describedby; email dùng autocomplete=email, password dùng autocomplete=current-password.

### Authentication

Controller gọi `Auth::guard('web')->attempt([...$request->validated(), 'status' => 'active'])`.

- Email/password đúng và tài khoản active: được đăng nhập.
- Sai credentials hoặc inactive: từ chối với cùng thông báo chung, không tiết lộ nguyên nhân chi tiết của tài khoản.
- Soft-deleted User không được lấy bởi Eloquent provider do User có SoftDeletes.
- User model có cast password=hashed; Laravel kiểm tra mật khẩu với hash, không lưu plaintext.
- Không triển khai remember-me hoặc Customer authentication.

### Khi thành công / thất bại

Sau thành công, session ID được đổi để tránh giữ ID phiên guest, rồi last_login_at được cập nhật. Controller dùng forceFill cho timestamp hệ thống thay vì cho phép client gửi giá trị đó.

Thất bại không cập nhật last_login_at. Auth failure chỉ flash email; validation dùng cơ chế Laravel loại password khỏi input được flash. Form password không có value để refill.

Redirect dùng `redirect()->intended('/')`: tiếp tục URL được auth middleware ghi nhận trước đó; fallback là trang chủ đang tồn tại. Sau redirect, authorization vẫn được kiểm tra trên route đích. Login không tự quyết định quyền dựa trên role và không tạo Dashboard giả.

## 5. Session và lưu trữ

Laravel quản lý session và authentication bằng web guard, không có cơ chế session tự viết.

- Với cấu hình local SESSION_DRIVER=database, session được lưu trong bảng sessions của database đang kết nối; trình duyệt giữ cookie nhận diện phiên.
- User account nằm trong bảng users và tồn tại độc lập với session.
- Tắt server/terminal không xóa tài khoản. Session còn phụ thuộc cookie, thời hạn và cấu hình session.
- Tests auth/role dùng array session và SQLite :memory:, không chứng minh đầy đủ lifecycle database-session trên browser/MySQL.

Inactive bị chặn tại login. Chưa thêm cơ chế thu hồi realtime một phiên đã đăng nhập nếu tài khoản bị chuyển sang inactive trong khi phiên còn tồn tại; việc đó cần yêu cầu account lifecycle riêng.

## 6. Logic đăng xuất

```text
Người đã đăng nhập → POST /logout + CSRF
                   → Auth::guard('web')->logout()
                   → session()->invalidate()
                   → session()->regenerateToken()
                   → redirect route login
```

Logout bỏ trạng thái authentication, hủy dữ liệu phiên cũ và tạo CSRF token mới cho phiên tiếp theo. Laravel có thể tạo phiên guest mới; không hiểu logout là sẽ không bao giờ có bất kỳ session nào nữa.

Logout **không xóa User** hoặc mật khẩu trong database. Guest sau logout vào route được bảo vệ sẽ phải đăng nhập lại. GET /logout không được hỗ trợ.

## 7. Quy tắc phân quyền đã được xác nhận

Engineer xác nhận Admin có toàn bộ quyền vận hành Staff cộng thêm quyền quản trị. Quy tắc đã cập nhật trong requirements.md.

| Người truy cập | Khu vực Admin | Khu vực Staff | Public |
| --- | --- | --- | --- |
| Guest | Redirect Login | Redirect Login | Cho phép |
| Staff | 403 Forbidden | Cho phép | Cho phép |
| Admin | Cho phép | Cho phép | Cho phép |

Quyền truy cập không bỏ qua business validation, trạng thái đơn, tồn kho hoặc yêu cầu ghi nhận đúng người thực hiện. Phân quyền không triển khai các nghiệp vụ đó trong nhánh này.

### CheckRole

Middleware lấy User từ web guard. Nếu không có User, ném AuthenticationException để Laravel xử lý. Nếu role không nằm trong danh sách tham số, abort 403; nếu đúng, chuyển request đến handler tiếp theo.

So sánh dùng `in_array(..., true)`; danh sách rỗng không cho phép truy cập. Alias role trỏ đến CheckRole trong bootstrap/app.php.

- `role:admin`: chỉ Admin.
- `role:admin,staff`: Admin hoặc Staff; dùng cho khu vực Staff đã duyệt.
- Middleware cũng hiểu `role:staff` khi được chỉ định, nhưng cấu hình khu vực Staff hiện dùng admin,staff để bảo đảm quyền Admin đã được xác nhận.

## 8. Xử lý không có quyền

- Chưa đăng nhập: web redirect Login; request JSON nhận 401.
- Đã đăng nhập nhưng sai role: 403, không chuyển về Login và không logout.
- Laravel default 403 response được giữ nguyên; không tạo custom error UI hoặc internal navigation chưa cần thiết.
- Prefix URL, CSS hoặc việc ẩn menu không thay thế middleware server-side.

## 9. Kiểm thử đã thực hiện

Kết quả local được quan sát trong lượt triển khai gần nhất: **27 tests PASS / 162 assertions**; gồm 15 auth tests, 10 role tests và 2 tests nền có sẵn. Pint trên PHP files liên quan và git diff --check PASS.

| Nhóm | Nội dung kiểm tra |
| --- | --- |
| Login | Render login/public home, required/email format/password, credentials sai hoặc không tồn tại |
| Account | Admin/Staff active thành công; inactive và soft-deleted bị chặn |
| Timestamp | Thành công cập nhật last_login_at; thất bại không cập nhật |
| Session/logout | Đổi ID sau login; invalidate phiên và đổi CSRF sau logout; guest bị chặn |
| CSRF | POST login/logout thiếu token bị từ chối trong test bật kiểm tra middleware thật |
| Role | Admin vào Admin và Staff; Staff vào Staff; Staff vào Admin nhận 403 |
| Failure/session | Sai role vẫn giữ trạng thái đăng nhập và dữ liệu phiên kiểm tra; vẫn logout được |
| HTTP | Guest JSON 401; sai role JSON 403; không có role tham số thì từ chối |

Tests tạo schema từ users migration thật trên SQLite :memory: sau khi xác minh connection an toàn. Routes `/role-test/*` chỉ được đăng ký trong test application; không tồn tại khi chạy website bình thường.

Chạy PowerShell trong thư mục project:

```powershell
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
& $php artisan test --filter=AuthenticationTest
& $php artisan test --filter=RoleAuthorizationTest
& $php artisan test
```

Auth UI build trước đây đã PASS. Workflow CI đã thêm Node.js 24, npm ci và npm run build trước php artisan test để tránh lỗi thiếu public/build/manifest.json trên runner mới. Kết quả local không thay thế việc xem kết quả GitHub CI của commit/PR thực tế.

## 10. Phần chưa triển khai và giới hạn

- Register, User CRUD, thay đổi role/status bằng UI.
- Trang nội bộ Admin/Staff, Dashboard, POS và các nghiệp vụ Order/Payment/Inventory.
- Bảng roles/permissions hoặc permission package: không cần và không thêm.
- Browser QA đầy đủ ở 375/768/1440px và kiểm tra MySQL database-session trực tiếp chưa được agent xác minh.
- Role changes hiện chưa commit/push; GitHub chưa thể kiểm tra chúng từ working tree local.
- Không đổi migrations/schema/dependencies; không tự triển khai công việc tiếp theo.

## 11. Tài liệu liên quan

- [Hướng dẫn chạy dự án và tạo tài khoản local](HUONG_DAN_CHAY_DU_AN_VA_TAO_TAI_KHOAN.md).
- [Task report auth](../reports/task-reports/feature-auth-login__report.md): báo cáo tại thời điểm hoàn tất lượt triển khai auth ban đầu.
- [Task report role](../reports/task-reports/feature-role-authorization__report.md): cấu hình role hiện tại và evidence kiểm tra.

Tài liệu này tổng hợp công việc hai nhánh trên source hiện tại; không tự thay đổi trạng thái roadmap hoặc thay thế engineer review/acceptance.
