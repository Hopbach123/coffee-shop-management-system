# Coffee Shop Management System - Kiến trúc dự án

## 1. Mục đích và phạm vi

Tài liệu này là chuẩn kiến trúc kỹ thuật chính thức của Coffee Shop Management System. Engineer và AI Agent phải đọc tài liệu này trước khi thực hiện thay đổi kỹ thuật.

Dự án là ứng dụng web Laravel monolith, ưu tiên convention của phiên bản Laravel đang sử dụng. Kiến trúc chính là **Laravel MVC + Selective Service Layer**, với Laravel Blade là frontend, Eloquent là ORM và MySQL là cơ sở dữ liệu mục tiêu.

Tài liệu chỉ quy định cách tổ chức và trách nhiệm kỹ thuật. Các quy tắc nghiệp vụ chi tiết phải đến từ requirements đã được phê duyệt; không được suy đoán từ tài liệu kiến trúc này.

## 2. Tổng quan kiến trúc

Các thành phần chính:

- PHP và Laravel cho ứng dụng phía server.
- Laravel Blade, HTML, CSS, Bootstrap và JavaScript cho giao diện.
- AJAX cho các tương tác cục bộ cần cập nhật mà không tải lại toàn trang.
- Eloquent Models cho ánh xạ và truy cập dữ liệu.
- MySQL là cơ sở dữ liệu mục tiêu.
- FormRequest cho validation ở request layer.
- Middleware cho authentication, authorization và cross-cutting request filtering.
- Service chỉ dành cho workflow hoặc business logic đủ phức tạp.

Laravel conventions luôn được ưu tiên hơn custom architecture. Dự án không phải React SPA, không có frontend tách riêng và không phải API-first application.

## 3. Request processing flow

Luồng đầy đủ cho nghiệp vụ phức tạp:

```text
Browser
  -> Route
  -> Middleware
  -> FormRequest
  -> Controller
  -> Service
  -> Eloquent Model(s)
  -> MySQL
  -> Blade View hoặc AJAX/JSON Response
```

Service là **tùy chọn**, không bắt buộc trong mọi request.

### 3.1 Simple CRUD flow

CRUD đơn giản có thể gọi Eloquent trực tiếp từ Controller:

```text
Route
  -> Middleware
  -> FormRequest
  -> Controller
  -> Eloquent Model
  -> Database
  -> Blade View hoặc Redirect
```

Cách này phù hợp với master data đơn giản như Category hoặc Area khi không có workflow liên miền, transaction phức tạp hay business rules đáng kể.

### 3.2 Complex business flow

Nghiệp vụ phức tạp phải được điều phối qua Service:

```text
Route
  -> Middleware
  -> FormRequest
  -> Thin Controller
  -> Service
  -> Một hoặc nhiều Eloquent Models
  -> Database
  -> Response
```

Các domain có khả năng cần Service gồm Order, Payment, Promotion, Reservation, Inventory và Dashboard. Việc một domain nằm trong danh sách này không có nghĩa phải tạo Service trước khi business logic tương ứng được triển khai.

## 4. Trách nhiệm của từng layer

### 4.1 Route

Route chịu trách nhiệm:

- Khai báo URL và HTTP method.
- Gắn middleware phù hợp.
- Ánh xạ request tới Controller action.
- Đặt route name theo Laravel conventions.

Route file không chứa validation, truy vấn dữ liệu hoặc business logic.

### 4.2 Middleware

Middleware chịu trách nhiệm:

- Kiểm tra authentication.
- Kiểm tra authorization hoặc quyền truy cập theo vai trò.
- Áp dụng request-level access control.
- Xử lý các concern dùng chung tại biên HTTP.

Ví dụ định hướng:

```text
/admin/* -> auth -> admin authorization -> Controller
/staff/* -> auth -> staff authorization -> Controller
```

Middleware không tính tổng đơn hàng, xử lý thanh toán, thay đổi tồn kho hoặc thực thi domain workflow.

### 4.3 FormRequest

FormRequest chịu trách nhiệm:

- Khai báo input validation rules.
- Thực hiện request-level authorization khi phù hợp.
- Chuẩn hóa việc cung cấp dữ liệu đã kiểm tra cho Controller.

Controller nên dùng `$request->validated()` khi làm việc với FormRequest. Không đặt validation array lớn trong các Controller method phức tạp.

### 4.4 Controller - Thin Controller policy

Controller phải mỏng và chỉ điều phối HTTP request/response:

- Nhận request sau Route và Middleware.
- Nhận input đã được FormRequest validation khi phù hợp.
- Gọi Eloquent trực tiếp cho CRUD đơn giản.
- Gọi Service cho workflow phức tạp.
- Trả về Blade View, redirect hoặc AJAX/JSON response.

Controller không được:

- Chứa business workflow dài.
- Chứa phép tính thanh toán phức tạp.
- Xử lý logic tiêu hao tồn kho.
- Chứa promotion engine.
- Chứa transaction lớn liên quan nhiều entity.
- Trở thành nơi chứa helper logic không liên quan.

### 4.5 Service - Selective Service Layer policy

Service chỉ được tạo khi business logic đủ phức tạp để cần một layer riêng. Service có thể:

- Đóng gói business workflow.
- Điều phối nhiều Models.
- Thực hiện thao tác cần database transaction.
- Thực thi business rules đã được phê duyệt.
- Cung cấp business operation có thể tái sử dụng.
- Giữ Controller nhỏ và tập trung vào HTTP.

Các Service dự kiến trong tương lai có thể gồm `AuthService`, `OrderService`, `PaymentService`, `PromotionService`, `ReservationService`, `InventoryService` và `DashboardService`.

Không tạo một Service cho mỗi Model, không lặp lại Eloquent CRUD đơn giản và không dùng Service như nơi chứa utility tùy ý.

Ví dụ:

```text
Category CRUD: CategoryController -> Category Model
Order checkout: PaymentController -> PaymentService -> Order + Payment + CafeTable
```

### 4.6 Eloquent Model

Model chịu trách nhiệm:

- Đại diện cho database entity.
- Định nghĩa relationships.
- Định nghĩa casts.
- Quản lý `fillable` hoặc `guarded` theo quyết định bảo mật của module.
- Định nghĩa query scopes.
- Định nghĩa accessors và mutators khi phù hợp.

Model nên tránh workflow lớn xuyên nhiều domain. Business operation phối hợp nhiều entity thuộc về Service.

### 4.7 Blade View

Blade chịu trách nhiệm render UI, hiển thị dữ liệu đã được chuẩn bị và tái sử dụng layouts/components. View không chứa truy vấn dữ liệu hoặc business logic phức tạp.

## 5. Trách nhiệm thư mục

| Đường dẫn | Trách nhiệm |
| --- | --- |
| `app/Enums/` | PHP Enums cho các giá trị domain cố định đã được requirements phê duyệt. |
| `app/Http/Controllers/` | Controllers theo khu vực Auth, Admin, Staff và Public. |
| `app/Http/Middleware/` | Authentication, authorization và request filtering dùng chung. |
| `app/Http/Requests/` | FormRequest validation và request-level authorization theo module. |
| `app/Models/` | Custom Eloquent Models và hành vi được duy trì thủ công. |
| `app/Models/Base/` | Generated Base Models có thể được regenerate; không chứa custom business logic. |
| `app/Services/` | Service cho business workflow phức tạp; không tạo class khi chưa có logic cần thiết. |
| `resources/views/` | Blade layouts, components và pages cho Auth, Admin, Staff, Public. |
| `resources/js/` | JavaScript và AJAX source theo khu vực chức năng. |
| `resources/css/` | CSS source theo khu vực giao diện. |
| `routes/` | Route definitions theo phạm vi public/auth/admin/staff. |
| `database/migrations/` | Lịch sử thay đổi schema và source of truth của database schema. |

## 6. Tổ chức routes

- `routes/web.php`: public routes, authentication routes và nạp các route file bổ sung.
- `routes/admin.php`: admin routes; future routes dùng URL prefix `/admin`, name prefix `admin.` và middleware phù hợp.
- `routes/staff.php`: staff routes; future routes dùng URL prefix `/staff`, name prefix `staff.` và middleware phù hợp.

Hiện tại `web.php` nạp `admin.php` và `staff.php`. Route group, prefix và middleware cụ thể chỉ được thêm trong task triển khai tương ứng. Route files không chứa business logic.

## 7. Frontend architecture

Frontend chính sử dụng Laravel Blade và nằm trong:

- `resources/views/`
- `resources/js/`
- `resources/css/`

Các nhóm view chính:

- `resources/views/layouts/`
- `resources/views/components/`
- `resources/views/auth/`
- `resources/views/admin/`
- `resources/views/staff/`
- `resources/views/public/`

AJAX có thể được dùng cho các tương tác chọn lọc như thêm/xóa order item, cập nhật số lượng, thay đổi topping hoặc áp dụng promotion. Endpoint AJAX vẫn phải đi qua Route, Middleware, FormRequest, Controller và Service khi cần; response thường là JSON.

Không chuyển dự án thành SPA riêng và không đưa business logic vào JavaScript hoặc Blade.

## 8. Database migration policy

**SRS v1.2 và `database-design.md` Frozen là nguồn thẩm quyền của logical target schema; Laravel Migrations là SOURCE OF TRUTH của physical schema đã được triển khai. Migration phải được audit và sửa bằng task được phê duyệt khi lệch thiết kế Frozen, không được dùng schema hiện tại để ghi đè yêu cầu đã duyệt.**

Workflow chuẩn:

```text
ERD / approved database design
  -> Laravel Migration
  -> php artisan migrate
  -> MySQL Schema
```

- Không dùng việc tạo hoặc sửa schema thủ công bằng phpMyAdmin làm workflow phát triển chính.
- Schema change thông thường phải được đưa vào migration mới.
- Không sửa migration đã được chia sẻ/chạy trên môi trường khác nếu việc đó phá vỡ lịch sử; tạo migration tiếp theo khi phù hợp.
- Migration history phải được version control bằng Git.

## 9. Generated Base Model strategy

Model generation trong tương lai phải giữ code sinh tự động tách biệt với code tùy chỉnh:

```text
Laravel Migrations
  -> MySQL Schema
  -> Model Generator
  -> app/Models/Base/
  -> Custom Models trong app/Models/
```

Ví dụ cấu trúc:

```text
app/Models/
  Base/
    Product.php
    Order.php
  Product.php
  Order.php
```

Generated Base Models:

- Có thể được regenerate.
- Không chứa business logic được duy trì thủ công.
- Không được sửa thủ công trừ khi generator configuration yêu cầu rõ ràng.

Custom Models:

- Có thể extend generated Base Models khi generator được chọn hỗ trợ an toàn.
- Chứa project-specific scopes, behavior, accessors/mutators và manual relationship adjustments.
- Không được ghi đè khi regenerate Base Models.

Migration vẫn là source of truth của physical schema đã triển khai và phải khớp `database-design.md` Frozen; model generator không thay thế migration. Việc cài đặt hoặc cấu hình generator phải thuộc một task riêng được phê duyệt.

## 10. Repository Pattern policy

**Repository Pattern KHÔNG được sử dụng trong kiến trúc hiện tại.**

Không tự ý đưa vào flow sau:

```text
Controller -> Service -> Repository -> Model
```

Với quy mô hiện tại, Eloquent cung cấp abstraction truy cập dữ liệu phù hợp. Flow được ưu tiên là:

```text
Simple CRUD: Controller -> Model
Complex domain: Controller -> Service -> Model(s)
```

Repository chỉ có thể được xem xét nếu có architecture decision rõ ràng trong tương lai.

## 11. Enum policy

`app/Enums/` dùng để chuẩn hóa giá trị domain cố định, tránh magic strings lặp lại và cải thiện type safety. Các ví dụ dự kiến gồm `UserRole`, `UserStatus`, `TableStatus`, `OrderStatus`, `OrderType`, `PaymentStatus`, `PaymentMethod` và `ReservationStatus`.

Không tạo Enum hoặc tự đặt giá trị trước khi requirements tương ứng được phê duyệt.

## 12. Transaction policy

Operation phức tạp thay đổi nhiều bảng liên quan phải dùng database transaction khi tính nhất quán yêu cầu các thay đổi cùng thành công hoặc cùng thất bại.

Ví dụ, checkout trong tương lai có thể phối hợp việc tạo payment, cập nhật order, cập nhật cafe table và có thể cập nhật inventory. Ranh giới transaction và thứ tự thao tác phải được quyết định trong task nghiệp vụ dựa trên requirements đã duyệt.

## 13. Error handling policy

- Validation errors được xử lý qua FormRequest và cơ chế response chuẩn của Laravel.
- Authorization failure trả HTTP response phù hợp.
- Business rule violation phải được báo rõ ràng mà không làm lộ thông tin nhạy cảm.
- Unexpected errors không được hiển thị stack trace hoặc secret trong production.
- Application logs được dùng để điều tra unexpected failures.
- Không tự tạo error infrastructure riêng nếu Laravel đã cung cấp cơ chế phù hợp.

## 14. Naming conventions

Tuân theo Laravel và PSR conventions:

| Thành phần | Quy ước | Ví dụ |
| --- | --- | --- |
| Model | singular PascalCase | `Product`, `Order`, `CafeTable` |
| Controller | PascalCase + `Controller` | `ProductController`, `OrderController` |
| Service | PascalCase + `Service` | `OrderService`, `PaymentService` |
| FormRequest | hành động + entity + `Request` | `StoreProductRequest`, `UpdateProductRequest`, `CheckoutRequest` |
| Database table | plural snake_case | `products`, `orders`, `cafe_tables` |
| Route name | dot notation theo phạm vi | `admin.products.index`, `staff.orders.show` |
| Blade view | dot notation tương ứng thư mục | `admin.products.index` |

Ưu tiên resource routes và resource controller conventions khi phù hợp. Tên phải thể hiện đúng trách nhiệm, không dùng tên chung chung như `Helper`, `Manager` hoặc `CommonService` để chứa logic không liên quan.

## 15. Nguyên tắc ra quyết định

Trước khi thêm một layer hoặc abstraction mới, kiểm tra theo thứ tự:

1. Laravel convention hiện tại đã giải quyết nhu cầu chưa?
2. Đây là CRUD đơn giản hay workflow phức tạp?
3. Logic có liên quan nhiều Models, transaction hoặc business rules không?
4. Thay đổi có được requirements và task hiện tại cho phép không?

Không thêm abstraction vì mục đích dự phòng. Giữ thay đổi nhỏ, có thể kiểm thử và nằm đúng phạm vi task.
