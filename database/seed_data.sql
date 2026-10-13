-- Coffee Shop: DEMO-V1 linked test data, MySQL 8.4.
-- Run migrations first; select the intended DEVELOPMENT database before import.
-- Data only: no DROP/TRUNCATE/CREATE/ALTER, no disabled FK checks.
-- Public fixture login password: DemoCoffee123! (never use in production).
-- Execute the entire file in ONE session, STOP ON ERROR and ROLLBACK on failure.
-- See docs/HUONG_DAN_SEED_DATA_TOAN_HE_THONG.md.
SET NAMES utf8mb4;
SET @demo_now = NOW();
START TRANSACTION;
SET @demo_existing = EXISTS(SELECT 1 FROM orders WHERE order_code = 'DEMO-V1-CANCELLED');
SET @demo_account_collision = EXISTS(SELECT 1 FROM users WHERE email IN ('admin-demo@example.test', 'staff-demo@example.test', 'inactive-demo@example.test'));
SET @demo_new = NOT @demo_existing AND NOT @demo_account_collision;

-- users: 3 rows
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `avatar`, `role`, `status`, `last_login_at`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Quản trị', 'admin-demo@example.test', '$2y$12$wivN.bhjbDzbBtG0/25m4e6SyCO7.iVG17ZtqcI652S.gri8wF1RG', NULL, NULL, 'admin', 'active', NULL, NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_users_1 = LAST_INSERT_ID();
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `avatar`, `role`, `status`, `last_login_at`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Nhân viên', 'staff-demo@example.test', '$2y$12$SvTWyeg1kXHhFJMkxPkFwOuoD3y/8ZTxFdvycK.zq7jQUpfAnu3Py', NULL, NULL, 'staff', 'active', NULL, NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_users_2 = LAST_INSERT_ID();
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `avatar`, `role`, `status`, `last_login_at`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Tài khoản khóa', 'inactive-demo@example.test', '$2y$12$uvf3YoYXB5FR6Uw1ymHtbe6zTWHwz53G72w3hbv7el0427zRqiUE.', NULL, NULL, 'staff', 'inactive', NULL, NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_users_3 = LAST_INSERT_ID();

-- customers: 4 rows
INSERT INTO `customers` (`name`, `phone`, `email`, `birthday`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Khách 1', NULL, 'customer1@example.test', NULL, 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_customers_1 = LAST_INSERT_ID();
INSERT INTO `customers` (`name`, `phone`, `email`, `birthday`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Khách 2', NULL, 'customer2@example.test', NULL, 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_customers_2 = LAST_INSERT_ID();
INSERT INTO `customers` (`name`, `phone`, `email`, `birthday`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Khách 3', NULL, 'customer3@example.test', NULL, 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_customers_3 = LAST_INSERT_ID();
INSERT INTO `customers` (`name`, `phone`, `email`, `birthday`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Khách 4', NULL, 'customer4@example.test', NULL, 'inactive', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_customers_4 = LAST_INSERT_ID();

-- areas: 3 rows
INSERT INTO `areas` (`name`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Tầng trệt', 'Khu vực dữ liệu mẫu', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_areas_1 = LAST_INSERT_ID();
INSERT INTO `areas` (`name`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Sân vườn', NULL, 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_areas_2 = LAST_INSERT_ID();
INSERT INTO `areas` (`name`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Khu ngừng dùng', NULL, 'inactive', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_areas_3 = LAST_INSERT_ID();

-- categories: 3 rows
INSERT INTO `categories` (`name`, `description`, `image`, `display_order`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Cà phê', NULL, NULL, '1', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_categories_1 = LAST_INSERT_ID();
INSERT INTO `categories` (`name`, `description`, `image`, `display_order`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Trà', NULL, NULL, '2', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_categories_2 = LAST_INSERT_ID();
INSERT INTO `categories` (`name`, `description`, `image`, `display_order`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Danh mục ẩn', NULL, NULL, '3', 'inactive', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_categories_3 = LAST_INSERT_ID();

-- toppings: 3 rows
INSERT INTO `toppings` (`name`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Kem sữa', '5000.00', 'available', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_toppings_1 = LAST_INSERT_ID();
INSERT INTO `toppings` (`name`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Trân châu', '7000.00', 'available', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_toppings_2 = LAST_INSERT_ID();
INSERT INTO `toppings` (`name`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Topping tạm hết', '3000.00', 'unavailable', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_toppings_3 = LAST_INSERT_ID();

-- ingredients: 18 rows
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Cà phê hạt', 'g', '9860.000', '2000.000', '250.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_1 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Sữa tươi', 'ml', '19300.000', '3000.000', '30.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_2 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Đường', 'g', '4950.000', '1000.000', '20.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_3 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Trà đen', 'g', '1000.000', '1500.000', '200.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_4 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Sữa đặc', 'ml', '3000.000', '500.000', '45.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_5 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Bột cacao', 'g', '2000.000', '300.000', '150.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_6 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Matcha', 'g', '1000.000', '200.000', '400.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_7 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Syrup vanilla', 'ml', '1000.000', '200.000', '100.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_8 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Syrup caramel', 'ml', '1000.000', '200.000', '100.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_9 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Đào', 'g', '2000.000', '300.000', '90.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_10 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Vải', 'g', '2000.000', '300.000', '100.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_11 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Chanh', 'g', '2000.000', '300.000', '40.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_12 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Cam', 'g', '2000.000', '300.000', '60.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_13 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Mật ong', 'ml', '1000.000', '200.000', '120.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_14 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Kem tươi', 'ml', '1000.000', '200.000', '100.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_15 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Quế', 'g', '500.000', '100.000', '200.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_16 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Ly giấy', 'cái', '100.000', '20.000', '1500.00', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_17 = LAST_INSERT_ID();
INSERT INTO `ingredients` (`name`, `unit`, `current_stock`, `minimum_stock`, `cost_price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT '[DEMO] Nguyên liệu hết', 'g', '0.000', '100.000', '50.00', 'inactive', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_ingredients_18 = LAST_INSERT_ID();

-- promotions: 6 rows
INSERT INTO `promotions` (`code`, `name`, `description`, `discount_type`, `discount_value`, `max_discount`, `minimum_order`, `start_at`, `end_at`, `usage_limit`, `used_count`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-10', '[DEMO] Giảm 10%', NULL, 'percentage', '10.00', '20000.00', '30000.00', DATE_ADD(@demo_now, INTERVAL -2592000 SECOND), DATE_ADD(@demo_now, INTERVAL 2592000 SECOND), '100', '1', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_promotions_1 = LAST_INSERT_ID();
INSERT INTO `promotions` (`code`, `name`, `description`, `discount_type`, `discount_value`, `max_discount`, `minimum_order`, `start_at`, `end_at`, `usage_limit`, `used_count`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-FIXED', '[DEMO] FIXED', NULL, 'fixed', '5000.00', NULL, '30000.00', DATE_ADD(@demo_now, INTERVAL -2592000 SECOND), DATE_ADD(@demo_now, INTERVAL 2592000 SECOND), '100', '0', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_promotions_2 = LAST_INSERT_ID();
INSERT INTO `promotions` (`code`, `name`, `description`, `discount_type`, `discount_value`, `max_discount`, `minimum_order`, `start_at`, `end_at`, `usage_limit`, `used_count`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-EXPIRED', '[DEMO] EXPIRED', NULL, 'fixed', '5000.00', NULL, '30000.00', DATE_ADD(@demo_now, INTERVAL -2592000 SECOND), DATE_ADD(@demo_now, INTERVAL -86400 SECOND), '100', '0', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_promotions_3 = LAST_INSERT_ID();
INSERT INTO `promotions` (`code`, `name`, `description`, `discount_type`, `discount_value`, `max_discount`, `minimum_order`, `start_at`, `end_at`, `usage_limit`, `used_count`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-FUTURE', '[DEMO] FUTURE', NULL, 'fixed', '5000.00', NULL, '30000.00', DATE_ADD(@demo_now, INTERVAL 432000 SECOND), DATE_ADD(@demo_now, INTERVAL 2592000 SECOND), '100', '0', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_promotions_4 = LAST_INSERT_ID();
INSERT INTO `promotions` (`code`, `name`, `description`, `discount_type`, `discount_value`, `max_discount`, `minimum_order`, `start_at`, `end_at`, `usage_limit`, `used_count`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-INACTIVE', '[DEMO] INACTIVE', NULL, 'fixed', '5000.00', NULL, '30000.00', DATE_ADD(@demo_now, INTERVAL -2592000 SECOND), DATE_ADD(@demo_now, INTERVAL 2592000 SECOND), '100', '0', 'inactive', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_promotions_5 = LAST_INSERT_ID();
INSERT INTO `promotions` (`code`, `name`, `description`, `discount_type`, `discount_value`, `max_discount`, `minimum_order`, `start_at`, `end_at`, `usage_limit`, `used_count`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-LIMIT', '[DEMO] LIMIT', NULL, 'fixed', '5000.00', NULL, '30000.00', DATE_ADD(@demo_now, INTERVAL -2592000 SECOND), DATE_ADD(@demo_now, INTERVAL 2592000 SECOND), '0', '0', 'active', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_promotions_6 = LAST_INSERT_ID();

-- cafe_tables: 5 rows
INSERT INTO `cafe_tables` (`area_id`, `table_code`, `table_name`, `capacity`, `status`, `qr_code`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_areas_1, 'DEMO-V1-T1', '[DEMO] Bàn 1', '4', 'available', NULL, NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_cafe_tables_1 = LAST_INSERT_ID();
INSERT INTO `cafe_tables` (`area_id`, `table_code`, `table_name`, `capacity`, `status`, `qr_code`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_areas_1, 'DEMO-V1-T2', '[DEMO] Bàn 2', '4', 'occupied', NULL, NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_cafe_tables_2 = LAST_INSERT_ID();
INSERT INTO `cafe_tables` (`area_id`, `table_code`, `table_name`, `capacity`, `status`, `qr_code`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_areas_1, 'DEMO-V1-T3', '[DEMO] Bàn 3', '4', 'maintenance', NULL, NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_cafe_tables_3 = LAST_INSERT_ID();
INSERT INTO `cafe_tables` (`area_id`, `table_code`, `table_name`, `capacity`, `status`, `qr_code`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_areas_2, 'DEMO-V1-T4', '[DEMO] Bàn 4', '4', 'available', NULL, NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_cafe_tables_4 = LAST_INSERT_ID();
INSERT INTO `cafe_tables` (`area_id`, `table_code`, `table_name`, `capacity`, `status`, `qr_code`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_areas_2, 'DEMO-V1-T5', '[DEMO] Bàn 5', '4', 'available', NULL, NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_cafe_tables_5 = LAST_INSERT_ID();

-- products: 4 rows
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `image`, `status`, `is_featured`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_categories_1, '[DEMO] Latte', 'demo-v1-product-1', 'Sản phẩm mẫu để kiểm thử', NULL, 'available', '1', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_products_1 = LAST_INSERT_ID();
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `image`, `status`, `is_featured`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_categories_1, '[DEMO] Cà phê sữa', 'demo-v1-product-2', 'Sản phẩm mẫu để kiểm thử', NULL, 'available', '0', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_products_2 = LAST_INSERT_ID();
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `image`, `status`, `is_featured`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_categories_2, '[DEMO] Trà sữa', 'demo-v1-product-3', 'Sản phẩm mẫu để kiểm thử', NULL, 'available', '0', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_products_3 = LAST_INSERT_ID();
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `image`, `status`, `is_featured`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_categories_2, '[DEMO] Trà tạm hết', 'demo-v1-unavailable', NULL, NULL, 'unavailable', '0', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_products_4 = LAST_INSERT_ID();

-- product_variants: 7 rows
INSERT INTO `product_variants` (`product_id`, `name`, `sku`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_products_1, 'Nhỏ', 'DEMO-V1-SKU-0-0', '30000.00', 'available', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_variants_1 = LAST_INSERT_ID();
INSERT INTO `product_variants` (`product_id`, `name`, `sku`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_products_1, 'Lớn', 'DEMO-V1-SKU-0-1', '40000.00', 'available', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_variants_2 = LAST_INSERT_ID();
INSERT INTO `product_variants` (`product_id`, `name`, `sku`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_products_2, 'Nhỏ', 'DEMO-V1-SKU-1-0', '30000.00', 'available', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_variants_3 = LAST_INSERT_ID();
INSERT INTO `product_variants` (`product_id`, `name`, `sku`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_products_2, 'Lớn', 'DEMO-V1-SKU-1-1', '40000.00', 'available', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_variants_4 = LAST_INSERT_ID();
INSERT INTO `product_variants` (`product_id`, `name`, `sku`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_products_3, 'Nhỏ', 'DEMO-V1-SKU-2-0', '30000.00', 'available', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_variants_5 = LAST_INSERT_ID();
INSERT INTO `product_variants` (`product_id`, `name`, `sku`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_products_3, 'Lớn', 'DEMO-V1-SKU-2-1', '40000.00', 'available', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_variants_6 = LAST_INSERT_ID();
INSERT INTO `product_variants` (`product_id`, `name`, `sku`, `price`, `status`, `deleted_at`, `created_at`, `updated_at`) SELECT @demo_products_4, 'Tiêu chuẩn', 'DEMO-V1-OFF', '25000.00', 'unavailable', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_variants_7 = LAST_INSERT_ID();

-- reservations: 6 rows
INSERT INTO `reservations` (`customer_id`, `table_id`, `customer_name`, `customer_phone`, `guest_count`, `reservation_start_at`, `reservation_end_at`, `note`, `status`, `created_by`, `created_at`, `updated_at`) SELECT @demo_customers_1, NULL, '[DEMO] Khách đặt bàn', '0000000000', '2', DATE_ADD(@demo_now, INTERVAL 86400 SECOND), DATE_ADD(@demo_now, INTERVAL 93600 SECOND), 'DEMO-V1 pending', 'pending', NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_reservations_1 = LAST_INSERT_ID();
INSERT INTO `reservations` (`customer_id`, `table_id`, `customer_name`, `customer_phone`, `guest_count`, `reservation_start_at`, `reservation_end_at`, `note`, `status`, `created_by`, `created_at`, `updated_at`) SELECT @demo_customers_1, @demo_cafe_tables_4, '[DEMO] Khách đặt bàn', '0000000000', '2', DATE_ADD(@demo_now, INTERVAL 172800 SECOND), DATE_ADD(@demo_now, INTERVAL 180000 SECOND), 'DEMO-V1 confirmed', 'confirmed', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_reservations_2 = LAST_INSERT_ID();
INSERT INTO `reservations` (`customer_id`, `table_id`, `customer_name`, `customer_phone`, `guest_count`, `reservation_start_at`, `reservation_end_at`, `note`, `status`, `created_by`, `created_at`, `updated_at`) SELECT @demo_customers_1, @demo_cafe_tables_4, '[DEMO] Khách đặt bàn', '0000000000', '2', DATE_ADD(@demo_now, INTERVAL -1800 SECOND), DATE_ADD(@demo_now, INTERVAL 5400 SECOND), 'DEMO-V1 arrived', 'arrived', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_reservations_3 = LAST_INSERT_ID();
INSERT INTO `reservations` (`customer_id`, `table_id`, `customer_name`, `customer_phone`, `guest_count`, `reservation_start_at`, `reservation_end_at`, `note`, `status`, `created_by`, `created_at`, `updated_at`) SELECT @demo_customers_1, @demo_cafe_tables_4, '[DEMO] Khách đặt bàn', '0000000000', '2', DATE_ADD(@demo_now, INTERVAL -259200 SECOND), DATE_ADD(@demo_now, INTERVAL -252000 SECOND), 'DEMO-V1 completed', 'completed', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_reservations_4 = LAST_INSERT_ID();
INSERT INTO `reservations` (`customer_id`, `table_id`, `customer_name`, `customer_phone`, `guest_count`, `reservation_start_at`, `reservation_end_at`, `note`, `status`, `created_by`, `created_at`, `updated_at`) SELECT @demo_customers_1, @demo_cafe_tables_4, '[DEMO] Khách đặt bàn', '0000000000', '2', DATE_ADD(@demo_now, INTERVAL -345600 SECOND), DATE_ADD(@demo_now, INTERVAL -338400 SECOND), 'DEMO-V1 cancelled', 'cancelled', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_reservations_5 = LAST_INSERT_ID();
INSERT INTO `reservations` (`customer_id`, `table_id`, `customer_name`, `customer_phone`, `guest_count`, `reservation_start_at`, `reservation_end_at`, `note`, `status`, `created_by`, `created_at`, `updated_at`) SELECT @demo_customers_1, @demo_cafe_tables_4, '[DEMO] Khách đặt bàn', '0000000000', '2', DATE_ADD(@demo_now, INTERVAL -432000 SECOND), DATE_ADD(@demo_now, INTERVAL -424800 SECOND), 'DEMO-V1 no_show', 'no_show', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_reservations_6 = LAST_INSERT_ID();

-- product_recipes: 12 rows
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_1, @demo_ingredients_1, '20.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_1 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_1, @demo_ingredients_2, '100.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_2 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_2, @demo_ingredients_1, '30.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_3 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_2, @demo_ingredients_2, '150.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_4 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_3, @demo_ingredients_1, '20.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_5 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_3, @demo_ingredients_2, '100.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_6 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_4, @demo_ingredients_1, '30.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_7 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_4, @demo_ingredients_2, '150.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_8 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_5, @demo_ingredients_4, '20.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_9 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_5, @demo_ingredients_2, '100.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_10 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_6, @demo_ingredients_4, '30.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_11 = LAST_INSERT_ID();
INSERT INTO `product_recipes` (`variant_id`, `ingredient_id`, `quantity`, `created_at`, `updated_at`) SELECT @demo_product_variants_6, @demo_ingredients_2, '150.000', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_product_recipes_12 = LAST_INSERT_ID();

-- orders: 7 rows
INSERT INTO `orders` (`order_code`, `table_id`, `customer_id`, `user_id`, `reservation_id`, `order_type`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `note`, `ordered_at`, `completed_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-PAID-CASH', @demo_cafe_tables_4, @demo_customers_1, @demo_users_2, @demo_reservations_4, 'dine_in', 'completed', '70000.00', '7000.00', '0.00', '63000.00', 'Dữ liệu mẫu PAID-CASH', DATE_ADD(@demo_now, INTERVAL -259200 SECOND), DATE_ADD(@demo_now, INTERVAL -257400 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_orders_1 = LAST_INSERT_ID();
INSERT INTO `orders` (`order_code`, `table_id`, `customer_id`, `user_id`, `reservation_id`, `order_type`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `note`, `ordered_at`, `completed_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-PAID-BANK', NULL, @demo_customers_2, @demo_users_2, NULL, 'takeaway', 'completed', '60000.00', '0.00', '0.00', '60000.00', 'Dữ liệu mẫu PAID-BANK', DATE_ADD(@demo_now, INTERVAL -172800 SECOND), DATE_ADD(@demo_now, INTERVAL -171000 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_orders_2 = LAST_INSERT_ID();
INSERT INTO `orders` (`order_code`, `table_id`, `customer_id`, `user_id`, `reservation_id`, `order_type`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `note`, `ordered_at`, `completed_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-PENDING', NULL, @demo_customers_3, @demo_users_2, NULL, 'takeaway', 'pending', '30000.00', '0.00', '0.00', '30000.00', 'Dữ liệu mẫu PENDING', DATE_ADD(@demo_now, INTERVAL 0 SECOND), NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_orders_3 = LAST_INSERT_ID();
INSERT INTO `orders` (`order_code`, `table_id`, `customer_id`, `user_id`, `reservation_id`, `order_type`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `note`, `ordered_at`, `completed_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-PREPARING', @demo_cafe_tables_2, @demo_customers_4, @demo_users_2, NULL, 'dine_in', 'pending', '30000.00', '0.00', '0.00', '30000.00', 'Dữ liệu mẫu PREPARING', DATE_ADD(@demo_now, INTERVAL 0 SECOND), NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_orders_4 = LAST_INSERT_ID();
INSERT INTO `orders` (`order_code`, `table_id`, `customer_id`, `user_id`, `reservation_id`, `order_type`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `note`, `ordered_at`, `completed_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-READY', NULL, @demo_customers_1, @demo_users_2, NULL, 'takeaway', 'pending', '30000.00', '0.00', '0.00', '30000.00', 'Dữ liệu mẫu READY', DATE_ADD(@demo_now, INTERVAL 0 SECOND), NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_orders_5 = LAST_INSERT_ID();
INSERT INTO `orders` (`order_code`, `table_id`, `customer_id`, `user_id`, `reservation_id`, `order_type`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `note`, `ordered_at`, `completed_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-SERVED', NULL, @demo_customers_2, @demo_users_2, NULL, 'takeaway', 'pending', '30000.00', '0.00', '0.00', '30000.00', 'Dữ liệu mẫu SERVED', DATE_ADD(@demo_now, INTERVAL 0 SECOND), NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_orders_6 = LAST_INSERT_ID();
INSERT INTO `orders` (`order_code`, `table_id`, `customer_id`, `user_id`, `reservation_id`, `order_type`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `note`, `ordered_at`, `completed_at`, `created_at`, `updated_at`) SELECT 'DEMO-V1-CANCELLED', NULL, @demo_customers_3, @demo_users_2, NULL, 'takeaway', 'cancelled', '0.00', '0.00', '0.00', '0.00', 'Dữ liệu mẫu CANCELLED', DATE_ADD(@demo_now, INTERVAL 0 SECOND), NULL, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_orders_7 = LAST_INSERT_ID();

-- order_items: 7 rows
INSERT INTO `order_items` (`order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `quantity`, `unit_price`, `topping_amount`, `subtotal`, `note`, `status`, `created_at`, `updated_at`) SELECT @demo_orders_1, @demo_products_1, @demo_product_variants_1, '[DEMO] Latte', 'Nhỏ', '2', '30000.00', '10000.00', '70000.00', NULL, 'served', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_items_1 = LAST_INSERT_ID();
INSERT INTO `order_items` (`order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `quantity`, `unit_price`, `topping_amount`, `subtotal`, `note`, `status`, `created_at`, `updated_at`) SELECT @demo_orders_2, @demo_products_1, @demo_product_variants_1, '[DEMO] Latte', 'Nhỏ', '2', '30000.00', '0.00', '60000.00', NULL, 'served', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_items_2 = LAST_INSERT_ID();
INSERT INTO `order_items` (`order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `quantity`, `unit_price`, `topping_amount`, `subtotal`, `note`, `status`, `created_at`, `updated_at`) SELECT @demo_orders_3, @demo_products_1, @demo_product_variants_1, '[DEMO] Latte', 'Nhỏ', '1', '30000.00', '0.00', '30000.00', NULL, 'pending', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_items_3 = LAST_INSERT_ID();
INSERT INTO `order_items` (`order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `quantity`, `unit_price`, `topping_amount`, `subtotal`, `note`, `status`, `created_at`, `updated_at`) SELECT @demo_orders_4, @demo_products_1, @demo_product_variants_1, '[DEMO] Latte', 'Nhỏ', '1', '30000.00', '0.00', '30000.00', NULL, 'preparing', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_items_4 = LAST_INSERT_ID();
INSERT INTO `order_items` (`order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `quantity`, `unit_price`, `topping_amount`, `subtotal`, `note`, `status`, `created_at`, `updated_at`) SELECT @demo_orders_5, @demo_products_1, @demo_product_variants_1, '[DEMO] Latte', 'Nhỏ', '1', '30000.00', '0.00', '30000.00', NULL, 'ready', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_items_5 = LAST_INSERT_ID();
INSERT INTO `order_items` (`order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `quantity`, `unit_price`, `topping_amount`, `subtotal`, `note`, `status`, `created_at`, `updated_at`) SELECT @demo_orders_6, @demo_products_1, @demo_product_variants_1, '[DEMO] Latte', 'Nhỏ', '1', '30000.00', '0.00', '30000.00', NULL, 'served', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_items_6 = LAST_INSERT_ID();
INSERT INTO `order_items` (`order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `quantity`, `unit_price`, `topping_amount`, `subtotal`, `note`, `status`, `created_at`, `updated_at`) SELECT @demo_orders_7, @demo_products_1, @demo_product_variants_1, '[DEMO] Latte', 'Nhỏ', '1', '30000.00', '0.00', '30000.00', NULL, 'cancelled', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_items_7 = LAST_INSERT_ID();

-- payments: 4 rows
INSERT INTO `payments` (`order_id`, `payment_code`, `payment_method`, `amount`, `transaction_code`, `status`, `paid_at`, `created_by`, `created_at`, `updated_at`) SELECT @demo_orders_1, 'DEMO-V1-PAY-0', 'cash', '63000.00', NULL, 'paid', DATE_ADD(@demo_now, INTERVAL -257400 SECOND), @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_payments_1 = LAST_INSERT_ID();
INSERT INTO `payments` (`order_id`, `payment_code`, `payment_method`, `amount`, `transaction_code`, `status`, `paid_at`, `created_by`, `created_at`, `updated_at`) SELECT @demo_orders_2, 'DEMO-V1-FAILED', 'bank_transfer', '60000.00', NULL, 'failed', NULL, @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_payments_2 = LAST_INSERT_ID();
INSERT INTO `payments` (`order_id`, `payment_code`, `payment_method`, `amount`, `transaction_code`, `status`, `paid_at`, `created_by`, `created_at`, `updated_at`) SELECT @demo_orders_2, 'DEMO-V1-PAY-1', 'bank_transfer', '60000.00', NULL, 'paid', DATE_ADD(@demo_now, INTERVAL -171000 SECOND), @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_payments_3 = LAST_INSERT_ID();
INSERT INTO `payments` (`order_id`, `payment_code`, `payment_method`, `amount`, `transaction_code`, `status`, `paid_at`, `created_by`, `created_at`, `updated_at`) SELECT @demo_orders_6, 'DEMO-V1-PAY-5', 'bank_transfer', '30000.00', NULL, 'pending', NULL, @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_payments_4 = LAST_INSERT_ID();

-- order_promotions: 1 rows
INSERT INTO `order_promotions` (`order_id`, `promotion_id`, `promotion_code`, `discount_amount`, `created_at`, `updated_at`) SELECT @demo_orders_1, @demo_promotions_1, 'DEMO-V1-10', '7000.00', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_promotions_1 = LAST_INSERT_ID();

-- inventory_transactions: 29 rows
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_1, 'import', '10000.000', '0.000', '10000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_1 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_2, 'import', '20000.000', '0.000', '20000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_2 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_3, 'import', '5000.000', '0.000', '5000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_3 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_4, 'import', '1000.000', '0.000', '1000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_4 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_5, 'import', '3000.000', '0.000', '3000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_5 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_6, 'import', '2000.000', '0.000', '2000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_6 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_7, 'import', '1000.000', '0.000', '1000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_7 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_8, 'import', '1000.000', '0.000', '1000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_8 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_9, 'import', '1000.000', '0.000', '1000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_9 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_10, 'import', '2000.000', '0.000', '2000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_10 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_11, 'import', '2000.000', '0.000', '2000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_11 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_12, 'import', '2000.000', '0.000', '2000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_12 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_13, 'import', '2000.000', '0.000', '2000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_13 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_14, 'import', '1000.000', '0.000', '1000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_14 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_15, 'import', '1000.000', '0.000', '1000.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_15 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_16, 'import', '500.000', '0.000', '500.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_16 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_17, 'import', '100.000', '0.000', '100.000', NULL, NULL, 'DEMO-V1 import', @demo_users_1, DATE_ADD(@demo_now, INTERVAL -864000 SECOND), DATE_ADD(@demo_now, INTERVAL -864000 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_17 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_1, 'consume', '40.000', '10000.000', '9960.000', 'order_item', @demo_order_items_1, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL -259200 SECOND), DATE_ADD(@demo_now, INTERVAL -259200 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_18 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_2, 'consume', '200.000', '20000.000', '19800.000', 'order_item', @demo_order_items_1, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL -259200 SECOND), DATE_ADD(@demo_now, INTERVAL -259200 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_19 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_1, 'consume', '40.000', '9960.000', '9920.000', 'order_item', @demo_order_items_2, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL -172800 SECOND), DATE_ADD(@demo_now, INTERVAL -172800 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_20 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_2, 'consume', '200.000', '19800.000', '19600.000', 'order_item', @demo_order_items_2, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL -172800 SECOND), DATE_ADD(@demo_now, INTERVAL -172800 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_21 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_1, 'consume', '20.000', '9920.000', '9900.000', 'order_item', @demo_order_items_4, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_22 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_2, 'consume', '100.000', '19600.000', '19500.000', 'order_item', @demo_order_items_4, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_23 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_1, 'consume', '20.000', '9900.000', '9880.000', 'order_item', @demo_order_items_5, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_24 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_2, 'consume', '100.000', '19500.000', '19400.000', 'order_item', @demo_order_items_5, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_25 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_1, 'consume', '20.000', '9880.000', '9860.000', 'order_item', @demo_order_items_6, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_26 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_2, 'consume', '100.000', '19400.000', '19300.000', 'order_item', @demo_order_items_6, 'DEMO-V1 consume', @demo_users_2, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_27 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_3, 'waste', '100.000', '5000.000', '4900.000', NULL, NULL, 'DEMO-V1 waste', @demo_users_1, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_28 = LAST_INSERT_ID();
INSERT INTO `inventory_transactions` (`ingredient_id`, `type`, `quantity`, `before_quantity`, `after_quantity`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) SELECT @demo_ingredients_3, 'adjustment', '50.000', '4900.000', '4950.000', NULL, NULL, 'DEMO-V1 adjustment', @demo_users_1, DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_inventory_transactions_29 = LAST_INSERT_ID();

-- order_item_toppings: 1 rows
INSERT INTO `order_item_toppings` (`order_item_id`, `topping_id`, `topping_name`, `price`, `quantity`, `created_at`, `updated_at`) SELECT @demo_order_items_1, @demo_toppings_1, '[DEMO] Kem sữa', '5000.00', '2', DATE_ADD(@demo_now, INTERVAL 0 SECOND), DATE_ADD(@demo_now, INTERVAL 0 SECOND) WHERE @demo_new;
SET @demo_order_item_toppings_1 = LAST_INSERT_ID();

COMMIT;
SELECT DATABASE() AS target_database, CASE WHEN @demo_existing THEN 'SKIPPED: DEMO-V1 already imported; existing data preserved' WHEN @demo_account_collision THEN 'SKIPPED: demo emails exist but dataset marker missing; inspect before importing' ELSE 'IMPORTED: DEMO-V1, 18 business tables' END AS import_result;
