# Hướng dẫn chạy dự án và tạo tài khoản local

Áp dụng cho Coffee Shop Management System trên Windows với Laragon. Nhánh `feature/auth-login` có login/logout cho Admin và Staff, chưa có Register hoặc Dashboard. Đăng nhập thành công mặc định chuyển về trang chủ `/`.

## 1. Bật MySQL

Mở ứng dụng **Laragon**, bấm **Start All** và kiểm tra MySQL đang chạy. MySQL là dịch vụ cơ sở dữ liệu; terminal là nơi nhập lệnh chạy Laravel và build giao diện.

Cấu hình local hiện tại dùng MySQL tại `127.0.0.1:3306`, database `coffee_shop`. Nếu máy dùng cấu hình khác, kiểm tra `.env` của máy đó. Không chia sẻ mật khẩu hoặc commit `.env`.

## 2. Mở terminal và vào thư mục dự án

Trong VS Code chọn **Terminal → New Terminal**. Các bước chính dưới đây dùng **PowerShell**, nhận biết bằng dấu nhắc bắt đầu bằng `PS`.

Chỉ copy nội dung trong khối lệnh; không copy dấu nhắc `PS ...>` hoặc `>` từ log.

```powershell
cd F:\HocTap\Nam4\MNM\DeTaiMonHoc\coffee-shop-management-system
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
```

Biến `$php` chỉ tồn tại trong terminal vừa khai báo. Khi mở terminal mới, khai báo lại. Nếu Laragon dùng phiên bản PHP khác, thay đường dẫn bằng đường dẫn `php.exe` thực tế; project yêu cầu PHP 8.3 trở lên.

## 3. Kiểm tra và chạy migrations

```powershell
& $php artisan migrate:status
```

- Nếu tất cả dòng là **Ran**, database đã áp dụng các migrations; tiếp tục bước build.
- Nếu có **Pending**, chạy lệnh bên dưới để áp dụng migrations còn thiếu.
- Nếu báo `Unknown database 'coffee_shop'` trên lần thiết lập local đầu tiên, chạy lệnh bên dưới. Khi Laravel hỏi tạo database, chọn `yes` nếu đây đúng là database local bạn muốn tạo.

```powershell
& $php artisan migrate
```

Sau đó kiểm tra lại `migrate:status`. Nếu lỗi kết nối, kiểm tra MySQL trong Laragon và cấu hình kết nối trong `.env`.

Không dùng `migrate:fresh` để khắc phục lỗi thông thường: lệnh này xóa toàn bộ bảng trong database đang kết nối.

## 4. Build giao diện

```powershell
npm.cmd run build
```

Chờ thông báo `built`. Dùng `npm.cmd` giúp tránh lỗi PowerShell chặn `npm.ps1` do Execution Policy.

Cảnh báo thiếu optional package `fontaine` cho font fallback không đồng nghĩa build thất bại. Nếu cuối log vẫn báo build thành công thì có thể tiếp tục; không cần tự cài package để chạy login.

Các bước này áp dụng cho checkout hiện tại đã có `vendor/` và `node_modules/`. Nếu thiếu dependencies, cần thực hiện quy trình cài đặt dự án đã được team phê duyệt trước; không dùng lệnh cập nhật dependency tùy ý.

## 5. Chạy website

```powershell
& $php artisan serve --host=127.0.0.1 --port=8000
```

Chờ thông báo server đang chạy. Giữ terminal này mở rồi truy cập:

- Trang chủ: <http://localhost:8000>
- Login: <http://localhost:8000/login>

Nếu cổng 8000 đang được dùng, kiểm tra terminal/server đã chạy trước đó. Có thể chọn cổng khác, ví dụ `--port=8001`, rồi mở đúng địa chỉ tương ứng.

Nhấn **Ctrl+C** tại terminal server để dừng website.

## 6. Tạo tài khoản test local

Hệ thống chưa có Register. Để thử login, dùng tài khoản nội bộ có sẵn hoặc tạo fixture local bằng Tinker. Không áp dụng tài khoản/mật khẩu mẫu cho môi trường production.

Mở **terminal PowerShell thứ hai**, giữ terminal server chạy. Nhập:

```powershell
cd F:\HocTap\Nam4\MNM\DeTaiMonHoc\coffee-shop-management-system
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
& $php artisan tinker
```

Khi Tinker hiện dấu `>`, thực hiện từng bước và chờ bước trước hoàn tất.

### 6.1 Nhập mật khẩu

Copy nguyên dòng:

```php
$password = \Laravel\Prompts\password('Nhap mat khau test local'); null;
```

Khi hiện dấu **`❯`**, chỉ gõ mật khẩu muốn dùng rồi nhấn Enter. Nội dung nhập được ẩn. Chờ dấu **`>`** của Tinker xuất hiện lại mới dán lệnh tiếp theo.

Chuỗi `'Nhap mat khau test local'` là nhãn câu hỏi, không phải mật khẩu. Không dán lệnh PHP vào ô `❯`: nếu làm vậy, đoạn code sẽ trở thành mật khẩu.

### 6.2 Tạo User

Copy **toàn bộ dòng dưới đây và dán một lần** vào dấu `>`:

```php
\App\Models\User::forceCreate(['name' => 'Tai khoan test local', 'email' => 'auth-local@example.test', 'password' => $password, 'role' => 'staff', 'status' => 'active']);
```

Nếu hiện đối tượng `App\Models\User` có `id`, tài khoản đã được tạo. Model tự hash mật khẩu trước khi lưu.

- Tài khoản Staff: dùng `'role' => 'staff'`.
- Nếu cần fixture Admin riêng: thay email bằng `admin-local@example.test` và role bằng `admin` trước khi chạy. Không tạo lại cùng email.
- `status` phải là `active` để đăng nhập.

Thoát Tinker:

```php
unset($password);
exit
```

Đăng nhập tại `/login` bằng email đã tạo và mật khẩu vừa gõ ở bước 6.1.

### 6.3 Đặt lại mật khẩu nếu nhập nhầm

Mở lại Tinker. Nhập mật khẩu mới bằng cùng cách ở bước 6.1, rồi copy một dòng:

```php
\App\Models\User::where('email', 'auth-local@example.test')->firstOrFail()->forceFill(['password' => $password])->save();
```

Kết quả `true` xác nhận đã lưu. Sau đó `unset($password);` và `exit`.

Lệnh này cập nhật tài khoản có sẵn; không tạo User thứ hai. Nếu báo không tìm thấy User, kiểm tra email và database đang kết nối.

### 6.4 Các lỗi thường gặp khi tạo tài khoản

| Lỗi | Nguyên nhân và cách xử lý |
| --- | --- |
| `unexpected T_DOUBLE_ARROW` | Các dòng `'email' => ...` bị chạy riêng ngoài mảng. Nhấn Ctrl+C để hủy lệnh chưa hoàn tất, rồi dán toàn bộ lệnh tạo User trên một dòng |
| Email bị trùng / `Duplicate entry` | Tài khoản đã tồn tại. Dùng bước đặt lại mật khẩu thay vì tạo lại |
| Login thất bại dù `save()` trả `true` | `save()` chỉ xác nhận lưu; có thể đã nhập code hoặc mật khẩu khác vào ô `❯`. Đặt lại mật khẩu đúng quy trình |
| `$password` chưa được định nghĩa | Đã thoát hoặc mở phiên Tinker mới. Thực hiện bước nhập mật khẩu trong chính phiên Tinker hiện tại |

Không chạy `php artisan db:seed` với seeder mặc định hiện tại: UserFactory còn dùng các cột không có trong schema và thiếu role bắt buộc. Hướng dẫn này tạo fixture local trực tiếp qua custom User model, không thay đổi schema.

## 7. Kiểm tra chức năng thủ công

| Thao tác | Kết quả mong đợi |
| --- | --- |
| Mở `/login` khi chưa đăng nhập | Form có email, mật khẩu và nút đăng nhập |
| Bỏ trống email hoặc mật khẩu | Có thông báo yêu cầu nhập |
| Email sai định dạng | Bị chặn bởi trình duyệt hoặc backend validation |
| Nhập sai mật khẩu | Hiện lỗi đăng nhập; giữ email, không refill password |
| Đăng nhập User active | Chuyển về URL đã yêu cầu trước đó hoặc `/`; navbar có Đăng xuất |
| Kiểm tra `last_login_at` sau thành công | Có thời điểm đăng nhập mới |
| Đăng nhập thất bại | Không cập nhật `last_login_at` |
| Bấm Đăng xuất | Chuyển về `/login`, kết thúc phiên đăng nhập |
| User inactive đăng nhập | Bị từ chối |

Có thể kiểm tra timestamp trong Tinker:

```php
\App\Models\User::where('email', 'auth-local@example.test')->firstOrFail()->last_login_at;
```

Để thử inactive, đăng xuất trước, rồi thay đổi **chỉ tài khoản fixture local** trong Tinker:

```php
\App\Models\User::where('email', 'auth-local@example.test')->update(['status' => 'inactive']);
```

Thử login lại. Sau khi kiểm tra, khôi phục fixture:

```php
\App\Models\User::where('email', 'auth-local@example.test')->update(['status' => 'active']);
```

Admin và Staff dùng cùng form login. Nhánh auth này chưa cung cấp dashboard hoặc route phân quyền; không dùng URL `/admin/dashboard` chưa tồn tại để đánh giá login.

## 8. Chạy tests tự động

Trong terminal PowerShell khác đã khai báo `$php`:

```powershell
# Chỉ kiểm tra auth
& $php artisan test --filter=AuthenticationTest

# Toàn bộ tests
& $php artisan test
```

Tại thời điểm hướng dẫn, suite đã được kiểm tra đạt **17 tests / 112 assertions**. Khi chạy lại, đọc kết quả thực tế. Auth tests xác minh SQLite `:memory:` trước khi tạo schema/fixtures, không dùng dữ liệu MySQL local.

## 9. Kiểm tra responsive và phát triển giao diện

Trong trình duyệt nhấn **F12**, bật Device Toolbar và kiểm tra các chiều rộng **375px, 768px, 1440px**. Kiểm tra form/nút không bị cắt, không tràn ngang, lỗi dễ đọc, Tab có focus rõ và assets không báo lỗi. Visual QA chưa được agent xác minh hoàn tất; đây là bước kiểm tra thủ công cần thực hiện.

Nếu đang sửa CSS/JS, có thể chạy Vite tại terminal riêng để cập nhật trực tiếp:

```powershell
npm.cmd run dev
```

Giữ cả Laravel server và Vite chạy. Chỉ để xem bản đã build thì không cần chạy Vite dev. Nhấn Ctrl+C để dừng Vite khi kết thúc.

## 10. Nếu terminal là CMD

CMD có dấu nhắc dạng `F:\...>` thay vì `PS F:\...>`. Không dùng `$php = ...` hoặc `& $php` trong CMD. Chạy trực tiếp:

```cmd
cd /d F:\HocTap\Nam4\MNM\DeTaiMonHoc\coffee-shop-management-system
"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan migrate:status
npm.cmd run build
"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan serve --host=127.0.0.1 --port=8000
```

Để mở Tinker trong terminal CMD thứ hai:

```cmd
"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan tinker
```

Các lệnh PHP bên trong Tinker giống bước 6, bất kể bạn mở Tinker từ CMD hay PowerShell.
