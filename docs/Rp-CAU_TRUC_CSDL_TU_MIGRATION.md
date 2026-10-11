# BÁO CÁO CẤU TRÚC CƠ SỞ DỮ LIỆU TỪ MIGRATION

## Coffee Shop Management System

- **Ngày rà soát:** 07/10/2026
- **Phạm vi:** Các file trong `database/migrations/` hiện có trong repository.
- **Mục đích:** Tổng hợp cấu trúc bảng, cột, khóa, ràng buộc và quan hệ được khai báo bởi migration.
- **Cơ sở dữ liệu mục tiêu theo tài liệu dự án:** MySQL.

> Báo cáo này mô tả schema được định nghĩa trong source migration; không khẳng định các migration đã được áp dụng vào bất kỳ database cụ thể nào. Không kết nối hoặc thay đổi database trong quá trình lập báo cáo.

## 1. Nguồn và phạm vi kiểm tra

Đã rà soát các migration trong [`database/migrations/`](../database/migrations/) và đối chiếu các quy tắc thiết kế tại [`database-design.md`](../database-design.md). Migration là nguồn khai báo schema của ứng dụng; nếu nội dung migration khác tài liệu thiết kế thì báo cáo này ghi nhận schema theo migration và nêu khác biệt riêng.

Repository hiện có **25 file migration**:

- **18 bảng nghiệp vụ** được tạo bởi migration dự án.
- **7 bảng hỗ trợ/framework** được tạo bởi các migration Laravel mặc định: `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.
- Bảng theo dõi `migrations` do Laravel quản lý khi chạy migration; không được tạo bởi một file migration riêng trong repository nên không tính vào 25 file trên hay 18 bảng nghiệp vụ.

Migration `0001_01_01_000000_create_users_table.php` tạo bảng nghiệp vụ `users` cùng hai bảng hỗ trợ `password_reset_tokens` và `sessions`.

## 2. Danh sách migration và thứ tự hiện có

Thứ tự dưới đây là thứ tự tên file/timestamp mà Laravel dùng để chạy migration:

| Thứ tự | File migration | Tác động chính |
| ---: | --- | --- |
| 1 | `0001_01_01_000000_create_users_table.php` | Tạo `users`, `password_reset_tokens`, `sessions` |
| 2 | `0001_01_01_000001_create_cache_table.php` | Tạo `cache`, `cache_locks` |
| 3 | `0001_01_01_000002_create_jobs_table.php` | Tạo `jobs`, `job_batches`, `failed_jobs` |
| 4 | `2026_08_14_000001_create_customers_table.php` | Tạo `customers` |
| 5 | `2026_08_14_000002_create_areas_table.php` | Tạo `areas` |
| 6 | `2026_08_14_000003_create_categories_table.php` | Tạo `categories` |
| 7 | `2026_08_14_000004_create_toppings_table.php` | Tạo `toppings` |
| 8 | `2026_08_14_000005_create_ingredients_table.php` | Tạo `ingredients` |
| 9 | `2026_08_14_000006_create_cafe_tables_table.php` | Tạo `cafe_tables` |
| 10 | `2026_08_14_000007_create_products_table.php` | Tạo `products` |
| 11 | `2026_08_14_000008_create_promotions_table.php` | Tạo `promotions` |
| 12 | `2026_08_16_000009_add_frozen_checks_to_toppings_table.php` | Thêm CHECK cho giá topping |
| 13 | `2026_08_16_000010_add_frozen_checks_to_ingredients_table.php` | Thêm CHECK cho tồn kho và giá vốn |
| 14 | `2026_08_16_000011_add_frozen_checks_to_promotions_table.php` | Thêm CHECK cho giá trị, lượt dùng và thời gian promotion |
| 15 | `2026_08_16_000012_align_cafe_tables_constraints_with_frozen_design.php` | Đặt FK `cafe_tables.area_id` thành RESTRICT/NO ACTION; CHECK sức chứa |
| 16 | `2026_08_16_000013_align_products_foreign_key_with_frozen_design.php` | Đặt FK `products.category_id` thành RESTRICT/NO ACTION |
| 17 | `2026_08_16_000014_create_product_variants_table.php` | Tạo `product_variants` |
| 18 | `2026_08_16_000015_create_reservations_table.php` | Tạo `reservations` |
| 19 | `2026_08_16_000016_create_orders_table.php` | Tạo `orders` |
| 20 | `2026_08_16_000017_create_order_items_table.php` | Tạo `order_items` |
| 21 | `2026_08_16_000018_create_order_item_toppings_table.php` | Tạo `order_item_toppings` |
| 22 | `2026_08_16_000019_create_payments_table.php` | Tạo `payments` |
| 23 | `2026_08_16_000020_create_order_promotions_table.php` | Tạo `order_promotions` |
| 24 | `2026_08_16_000021_create_product_recipes_table.php` | Tạo `product_recipes` |
| 25 | `2026_08_16_000022_create_inventory_transactions_table.php` | Tạo `inventory_transactions` |

Các migration 9–13 trong chuỗi timestamp trên là migration bổ sung ràng buộc/điều chỉnh FK, không tạo thêm bảng nghiệp vụ.

## 3. Quy ước cấu trúc chung

- Mỗi bảng nghiệp vụ có khóa chính đơn `id`, khai báo bằng `$table->id()` (BIGINT UNSIGNED, tự tăng).
- Các bảng dùng `$table->timestamps()` có `created_at` và `updated_at`.
- Mười bảng master có `deleted_at` qua `$table->softDeletes()`: `users`, `customers`, `areas`, `categories`, `toppings`, `ingredients`, `promotions`, `cafe_tables`, `products`, `product_variants`.
- Tám bảng còn lại không có `deleted_at`; chúng là lịch sử giao dịch hoặc cấu hình liên quan: `reservations`, `product_recipes`, `orders`, `order_items`, `payments`, `order_promotions`, `inventory_transactions`, `order_item_toppings`.
- Tiền dùng kiểu `DECIMAL`; lượng nguyên liệu/công thức dùng `DECIMAL(12,3)`.
- Các tập giá trị `ENUM` giới hạn những giá trị được ghi vào cột, nhưng không tự thực thi chuyển trạng thái nghiệp vụ.
- Các FK nghiệp vụ có quy tắc xóa RESTRICT và cập nhật NO ACTION. Hai FK tạo ban đầu theo Laravel convention (`cafe_tables.area_id`, `products.category_id`) được migration sau điều chỉnh khai báo rõ quy tắc này.
- Migration chứa raw SQL `ALTER TABLE` cho CHECK/FK và hướng đến MySQL; đây là một lý do không thể coi việc chạy trên mọi database engine là đã được xác minh.

## 4. Từ điển dữ liệu các bảng nghiệp vụ

Ký hiệu:

- `NN`: NOT NULL; `NULL`: cho phép NULL.
- `PK`: khóa chính; `FK`: khóa ngoại; `UQ`: unique.
- Nếu không ghi `NULL` hoặc default, cột bắt buộc và không khai báo default trong migration.
- Tất cả bảng dưới đây có `id BIGINT UNSIGNED AUTO_INCREMENT PK`.
- `created_at` và `updated_at` là nullable TIMESTAMP do `$table->timestamps()` tạo ra, trừ khi có ghi chú khác.

### 4.1 Người dùng và dữ liệu nền

#### `users` — tài khoản nội bộ

- Cột: `name VARCHAR(255) NN`; `email VARCHAR(255) NN UQ`; `password VARCHAR(255) NN`; `phone VARCHAR(20) NULL`; `avatar VARCHAR(255) NULL`; `role ENUM('admin','staff') NN`; `status ENUM('active','inactive') NN DEFAULT 'active'`; `last_login_at DATETIME NULL`; `deleted_at TIMESTAMP NULL`; timestamps.
- Quan hệ FK: không có FK nghiệp vụ đi ra từ bảng này; các bảng giao dịch tham chiếu `users.id`.
- Bảng hỗ trợ đăng nhập/session `sessions.user_id` không khai báo FK tới `users`.

#### `customers` — thông tin liên hệ khách hàng

- Cột: `name VARCHAR(255) NULL`; `phone VARCHAR(20) NULL UQ`; `email VARCHAR(255) NULL`; `birthday DATE NULL`; `status ENUM('active','inactive') NN DEFAULT 'active'`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: không có.

#### `areas` — khu vực quán

- Cột: `name VARCHAR(100) NN`; `description TEXT NULL`; `status ENUM('active','inactive') NN DEFAULT 'active'`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: không có.

### 4.2 Menu, bàn và khuyến mãi

#### `categories` — danh mục thực đơn

- Cột: `name VARCHAR(255) NN`; `description TEXT NULL`; `image VARCHAR(255) NULL`; `display_order INT NN DEFAULT 0`; `status ENUM('active','inactive') NN DEFAULT 'active'`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: không có.

#### `toppings` — topping dùng chung

- Cột: `name VARCHAR(255) NN`; `price DECIMAL(12,2) NN DEFAULT 0`; `status ENUM('available','unavailable') NN DEFAULT 'available'`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: không có.

#### `ingredients` — nguyên liệu và lượng tồn hiện tại

- Cột: `name VARCHAR(255) NN`; `unit VARCHAR(50) NN`; `current_stock DECIMAL(12,3) NN DEFAULT 0`; `minimum_stock DECIMAL(12,3) NN DEFAULT 0`; `cost_price DECIMAL(12,2) NN DEFAULT 0`; `status ENUM('active','inactive') NN DEFAULT 'active'`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: không có.

#### `promotions` — cấu hình khuyến mãi

- Cột: `code VARCHAR(50) NN UQ`; `name VARCHAR(255) NN`; `description TEXT NULL`; `discount_type ENUM('percentage','fixed') NN`; `discount_value DECIMAL(12,2) NN`; `max_discount DECIMAL(12,2) NULL`; `minimum_order DECIMAL(12,2) NN DEFAULT 0`; `start_at DATETIME NN`; `end_at DATETIME NN`; `usage_limit INT NULL`; `used_count INT NN DEFAULT 0`; `status ENUM('active','inactive') NN DEFAULT 'active'`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: không có.

#### `cafe_tables` — bàn vật lý

- Cột: `area_id BIGINT UNSIGNED NN FK`; `table_code VARCHAR(20) NN UQ`; `table_name VARCHAR(100) NULL`; `capacity INT NN DEFAULT 4`; `status ENUM('available','occupied','maintenance') NN DEFAULT 'available'`; `qr_code VARCHAR(255) NULL`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: `area_id → areas.id`, RESTRICT khi xóa / NO ACTION khi cập nhật.
- Index: FK `area_id` (tạo bởi Laravel khi dùng `constrained`).

#### `products` — sản phẩm thực đơn

- Cột: `category_id BIGINT UNSIGNED NN FK`; `name VARCHAR(255) NN`; `slug VARCHAR(255) NN UQ`; `description TEXT NULL`; `image VARCHAR(255) NULL`; `status ENUM('available','unavailable') NN DEFAULT 'available'`; `is_featured BOOLEAN NN DEFAULT false`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: `category_id → categories.id`, RESTRICT khi xóa / NO ACTION khi cập nhật.
- Index: FK `category_id`.
- Không có cột giá ở bảng này; giá bán nằm trong `product_variants`.

#### `product_variants` — biến thể bán được

- Cột: `product_id BIGINT UNSIGNED NN FK`; `name VARCHAR(50) NN`; `sku VARCHAR(50) NULL UQ`; `price DECIMAL(12,2) NN`; `status ENUM('available','unavailable') NN DEFAULT 'available'`; `deleted_at TIMESTAMP NULL`; timestamps.
- FK: `product_id → products.id`, RESTRICT / NO ACTION.
- Unique: `sku`; cặp `(product_id, name)`.

### 4.3 Đặt bàn và công thức

#### `reservations` — đặt bàn

- Cột: `customer_id BIGINT UNSIGNED NULL FK`; `table_id BIGINT UNSIGNED NULL FK`; `customer_name VARCHAR(255) NN`; `customer_phone VARCHAR(20) NN`; `guest_count INT NN`; `reservation_start_at DATETIME NN`; `reservation_end_at DATETIME NN`; `note TEXT NULL`; `status ENUM('pending','confirmed','arrived','completed','cancelled','no_show') NN DEFAULT 'pending'`; `created_by BIGINT UNSIGNED NULL FK`; timestamps.
- FK: `customer_id → customers.id`; `table_id → cafe_tables.id`; `created_by → users.id`; cả ba RESTRICT / NO ACTION.
- Index: `(table_id, status, reservation_start_at, reservation_end_at)`; `customer_id`; `created_by`; `(status, reservation_start_at)`.
- Không có soft delete.

#### `product_recipes` — định lượng nguyên liệu theo biến thể

- Cột: `variant_id BIGINT UNSIGNED NN FK`; `ingredient_id BIGINT UNSIGNED NN FK`; `quantity DECIMAL(12,3) NN`; timestamps.
- FK: `variant_id → product_variants.id`; `ingredient_id → ingredients.id`; cả hai RESTRICT / NO ACTION.
- Unique: `(variant_id, ingredient_id)`.
- Index bổ sung: `ingredient_id`.
- Không có soft delete.

### 4.4 Đơn hàng và thanh toán

#### `orders` — đơn hàng

- Cột: `order_code VARCHAR(50) NN UQ`; `table_id BIGINT UNSIGNED NULL FK`; `customer_id BIGINT UNSIGNED NULL FK`; `user_id BIGINT UNSIGNED NN FK`; `reservation_id BIGINT UNSIGNED NULL FK`; `order_type ENUM('dine_in','takeaway') NN DEFAULT 'dine_in'`; `status ENUM('pending','completed','cancelled') NN DEFAULT 'pending'`; `subtotal DECIMAL(15,2) NN DEFAULT 0`; `discount_amount DECIMAL(15,2) NN DEFAULT 0`; `tax_amount DECIMAL(15,2) NN DEFAULT 0`; `total_amount DECIMAL(15,2) NN DEFAULT 0`; `note TEXT NULL`; `ordered_at DATETIME NN`; `completed_at DATETIME NULL`; timestamps.
- FK: `table_id → cafe_tables.id`; `customer_id → customers.id`; `user_id → users.id`; `reservation_id → reservations.id`; tất cả RESTRICT / NO ACTION.
- Index bổ sung: `(table_id, status, ordered_at)`; `(status, ordered_at)`; `customer_id`; `user_id`; `reservation_id`.
- CHECK: các khoản tiền không âm; `discount_amount <= subtotal`; `total_amount = subtotal - discount_amount + tax_amount`.
- Không có soft delete.

#### `order_items` — chi tiết món trong đơn

- Cột: `order_id BIGINT UNSIGNED NN FK`; `product_id BIGINT UNSIGNED NN FK`; `variant_id BIGINT UNSIGNED NN FK`; `product_name VARCHAR(255) NN`; `variant_name VARCHAR(50) NULL`; `quantity INT NN`; `unit_price DECIMAL(12,2) NN`; `topping_amount DECIMAL(12,2) NN DEFAULT 0`; `subtotal DECIMAL(15,2) NN`; `note VARCHAR(500) NULL`; `status ENUM('pending','preparing','ready','served','cancelled') NN DEFAULT 'pending'`; timestamps.
- FK: `order_id → orders.id`; `product_id → products.id`; `variant_id → product_variants.id`; tất cả RESTRICT / NO ACTION.
- Index bổ sung: `order_id`; `product_id`; `variant_id`; `(status, created_at)`.
- CHECK: `quantity > 0`; `unit_price`, `topping_amount`, `subtotal` không âm.
- Không có soft delete.

#### `order_item_toppings` — topping đã bán theo order item

- Cột: `order_item_id BIGINT UNSIGNED NN FK`; `topping_id BIGINT UNSIGNED NN FK`; `topping_name VARCHAR(255) NN`; `price DECIMAL(12,2) NN`; `quantity INT NN DEFAULT 1`; timestamps.
- FK: `order_item_id → order_items.id`; `topping_id → toppings.id`; cả hai RESTRICT / NO ACTION.
- Unique: `(order_item_id, topping_id)`.
- Index bổ sung: `topping_id`.
- CHECK: `price >= 0`; `quantity > 0`.
- Không có soft delete.

#### `payments` — lần thử thanh toán

- Cột: `order_id BIGINT UNSIGNED NN FK`; `payment_code VARCHAR(50) NN UQ`; `payment_method ENUM('cash','bank_transfer') NN`; `amount DECIMAL(15,2) NN`; `transaction_code VARCHAR(100) NULL`; `status ENUM('pending','paid','failed','refunded') NN DEFAULT 'pending'`; `paid_at DATETIME NULL`; `created_by BIGINT UNSIGNED NN FK`; timestamps.
- FK: `order_id → orders.id`; `created_by → users.id`; cả hai RESTRICT / NO ACTION.
- Index bổ sung: `(order_id, status)`; `created_by`; `transaction_code`.
- CHECK: `amount > 0`.
- Không có soft delete.

#### `order_promotions` — promotion đã áp dụng

- Cột: `order_id BIGINT UNSIGNED NN FK UQ`; `promotion_id BIGINT UNSIGNED NN FK`; `promotion_code VARCHAR(50) NN`; `discount_amount DECIMAL(15,2) NN`; timestamps.
- FK: `order_id → orders.id`; `promotion_id → promotions.id`; cả hai RESTRICT / NO ACTION.
- Unique trên `order_id` đảm bảo tối đa một dòng áp dụng promotion cho một order.
- Index bổ sung: `promotion_id`.
- CHECK: `discount_amount >= 0`.
- Không có soft delete.

### 4.5 Lịch sử tồn kho

#### `inventory_transactions` — biến động nguyên liệu

- Cột: `ingredient_id BIGINT UNSIGNED NN FK`; `type ENUM('import','consume','adjustment','waste') NN`; `quantity DECIMAL(12,3) NN`; `before_quantity DECIMAL(12,3) NN`; `after_quantity DECIMAL(12,3) NN`; `reference_type VARCHAR(50) NULL`; `reference_id BIGINT UNSIGNED NULL`; `note TEXT NULL`; `created_by BIGINT UNSIGNED NN FK`; timestamps.
- FK: `ingredient_id → ingredients.id`; `created_by → users.id`; cả hai RESTRICT / NO ACTION.
- Index bổ sung: `(ingredient_id, created_at)`; `created_by`; `(reference_type, reference_id)`.
- CHECK: `quantity > 0`; `before_quantity >= 0`; `after_quantity >= 0`.
- `reference_type`/`reference_id` là cặp polymorphic, không có FK vật lý tới bảng tham chiếu.
- Không có soft delete.

## 5. Quan hệ khóa ngoại

Migration nghiệp vụ khai báo **23 FK**, nối các bảng như sau:

| Bảng nguồn | Cột nguồn | Bảng đích |
| --- | --- | --- |
| `cafe_tables` | `area_id` | `areas.id` |
| `products` | `category_id` | `categories.id` |
| `product_variants` | `product_id` | `products.id` |
| `reservations` | `customer_id`, `table_id`, `created_by` | `customers.id`, `cafe_tables.id`, `users.id` |
| `product_recipes` | `variant_id`, `ingredient_id` | `product_variants.id`, `ingredients.id` |
| `orders` | `table_id`, `customer_id`, `user_id`, `reservation_id` | `cafe_tables.id`, `customers.id`, `users.id`, `reservations.id` |
| `order_items` | `order_id`, `product_id`, `variant_id` | `orders.id`, `products.id`, `product_variants.id` |
| `payments` | `order_id`, `created_by` | `orders.id`, `users.id` |
| `order_promotions` | `order_id`, `promotion_id` | `orders.id`, `promotions.id` |
| `inventory_transactions` | `ingredient_id`, `created_by` | `ingredients.id`, `users.id` |
| `order_item_toppings` | `order_item_id`, `topping_id` | `order_items.id`, `toppings.id` |

Các FK nullable là `reservations.customer_id`, `reservations.table_id`, `reservations.created_by`, `orders.table_id`, `orders.customer_id`, `orders.reservation_id`. Các FK còn lại NOT NULL.

## 6. Unique constraints và index chính

### Unique constraints

Migration có các unique key nghiệp vụ sau:

1. `users.email`
2. `customers.phone` (cột nullable)
3. `cafe_tables.table_code`
4. `products.slug`
5. `promotions.code`
6. `product_variants.sku` (cột nullable)
7. `product_variants(product_id, name)`
8. `product_recipes(variant_id, ingredient_id)`
9. `orders.order_code`
10. `payments.payment_code`
11. `order_promotions.order_id`
12. `order_item_toppings(order_item_id, topping_id)`

### Index không unique

Các migration tạo index phục vụ FK/truy vấn hoạt động gồm:

- `reservations`: `(table_id, status, reservation_start_at, reservation_end_at)`, `customer_id`, `created_by`, `(status, reservation_start_at)`.
- `orders`: `(table_id, status, ordered_at)`, `(status, ordered_at)`, `customer_id`, `user_id`, `reservation_id`.
- `order_items`: `order_id`, `product_id`, `variant_id`, `(status, created_at)`.
- `payments`: `(order_id, status)`, `created_by`, `transaction_code`.
- `product_recipes`: `ingredient_id`.
- `order_promotions`: `promotion_id`.
- `inventory_transactions`: `(ingredient_id, created_at)`, `created_by`, `(reference_type, reference_id)`.
- `order_item_toppings`: `topping_id`.
- `cafe_tables.area_id` và `products.category_id` được index như FK.

Unique index không được tính lặp lại trong danh sách index không unique.

## 7. CHECK constraints theo migration

Các CHECK sau được khai báo bằng `DB::statement` trong migration:

| Bảng | Điều kiện |
| --- | --- |
| `toppings` | `price >= 0` |
| `ingredients` | `current_stock >= 0`; `minimum_stock >= 0`; `cost_price >= 0` |
| `promotions` | `discount_value >= 0`; `max_discount IS NULL OR max_discount >= 0`; `minimum_order >= 0`; `usage_limit IS NULL OR usage_limit >= 0`; `used_count >= 0`; `start_at < end_at` |
| `cafe_tables` | `capacity > 0` |
| `product_variants` | `price >= 0` |
| `reservations` | `guest_count > 0`; `reservation_start_at < reservation_end_at` |
| `product_recipes` | `quantity > 0` |
| `orders` | Các amount không âm; `discount_amount <= subtotal`; `total_amount = subtotal - discount_amount + tax_amount` |
| `order_items` | `quantity > 0`; `unit_price >= 0`; `topping_amount >= 0`; `subtotal >= 0` |
| `order_item_toppings` | `price >= 0`; `quantity > 0` |
| `payments` | `amount > 0` |
| `inventory_transactions` | `quantity > 0`; `before_quantity >= 0`; `after_quantity >= 0` |

Các quy tắc phụ thuộc nhiều hàng hoặc chuyển trạng thái — ví dụ tránh trùng lịch bàn, chỉ một thanh toán `paid` trên order, giới hạn lượt promotion và không cho tồn kho âm sau phép trừ — không được một CHECK đơn hàng biểu diễn đầy đủ; chúng cần được thực thi trong logic ứng dụng/transaction.

## 8. Bảng hỗ trợ do Laravel tạo

Các bảng này không thuộc 18 bảng nghiệp vụ:

- `password_reset_tokens`: `email` là PK; lưu token và thời điểm tạo.
- `sessions`: `id` là PK; có `user_id` nullable, IP, user agent, payload, `last_activity`. Migration không tạo FK cho `user_id`.
- `cache`: `key` là PK; `value`, `expiration`.
- `cache_locks`: `key` là PK; `owner`, `expiration`.
- `jobs`: hàng đợi job với `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`.
- `job_batches`: metadata batch job.
- `failed_jobs`: job lỗi, `uuid` unique, thông tin connection/queue/payload/exception và `failed_at`.

Chi tiết các bảng này thuộc migration framework trong repository; chúng không được tính trong database design nghiệp vụ 18 bảng.

## 9. Đối chiếu với `database-design.md`

### Điểm nhất quán

- Danh sách 18 bảng nghiệp vụ, các nhóm cột, ENUM, precision DECIMAL, PK/FK và soft delete trong migration phù hợp với data dictionary đã đóng băng.
- Tổng hợp migration cho thấy 23 FK nghiệp vụ và 12 unique constraint như matrix trong tài liệu thiết kế.
- Các migration ngày 16/08 tạo các bảng nghiệp vụ từ `product_variants` đến `inventory_transactions`; các bảng này hiện diện trong source.

### Khác biệt và lưu ý

1. **Danh sách theo timestamp không trùng canonical order:** migration tạo `cafe_tables`, `products`, `promotions` theo thứ tự đó, trong khi canonical order tại `database-design.md` xếp `promotions` trước `cafe_tables`/`products`. `product_recipes` cũng được timestamp sau `orders`, dù canonical order xếp recipe trước order. Đây là khác biệt về thứ tự file, không phải thiếu bảng; các FK được tham chiếu đã có trước khi migration tạo bảng phụ thuộc.
2. **Mục “Existing migration mismatch register” trong thiết kế cần được đọc theo thời điểm báo cáo của mục đó:** mục này ghi rằng mới có 9 bảng business migration và các migration bảng 10–18 chưa tồn tại. Trạng thái source hiện được rà soát trong báo cáo này đã khác: repository có đủ migration tạo 18 bảng nghiệp vụ. Các migrations điều chỉnh FK và CHECK cũng đã hiện diện.
3. **Hai FK ban đầu dùng Laravel convention:** migration tạo `cafe_tables.area_id` và `products.category_id` ban đầu dùng `constrained()`; migration `2026_08_16_000012` và `...000013` sau đó thay bằng FK khai báo rõ `ON DELETE RESTRICT ON UPDATE NO ACTION`. Muốn đánh giá schema cuối cùng phải xét cả chuỗi migration, không chỉ file tạo bảng ban đầu.
4. **Không xác nhận schema vật lý:** báo cáo không chạy migration, không đọc database đang kết nối và không dùng `migrate:status`; vì vậy không kết luận các migration đã được áp dụng thành công vào môi trường local/production.

## 10. Kết luận

Theo source hiện tại, dự án định nghĩa **18 bảng nghiệp vụ** cùng **7 bảng hỗ trợ Laravel** trong 25 file migration. Schema nghiệp vụ bao gồm dữ liệu người dùng, khách hàng, khu vực/bàn, menu, reservation, order, payment, promotion và inventory. Quan hệ lịch sử được bảo vệ bằng FK RESTRICT/NO ACTION, các snapshot bán hàng được lưu trong `order_items`, `order_item_toppings` và `order_promotions`, còn một số quy tắc xuyên nhiều hàng thuộc trách nhiệm của application workflow.

Đây là báo cáo tĩnh theo mã migration, không phải xác nhận trạng thái database runtime. Trước khi dùng làm biên bản nghiệm thu một môi trường, cần đối chiếu riêng với database đã triển khai và trạng thái migration của đúng môi trường đó.
