# Admin V5 — screen implementation and verification matrix

Reference: offline prototype ZIP; not Laravel screenshots. All routes below were inspected from route:list and live HTTP.

| ID / Screen | Route | Blade | Controller | Implementation | Functional evidence | Visual QA / Screenshot | Remaining |
| --- | --- | --- | --- | --- | --- | --- | --- |
| V5-01 Tổng quan | `admin.home` / `admin` | `admin/home.blade.php` | `DashboardController@home` | Existing 4 period KPIs/chart retained; V5 typography/card skin, no money wrapping | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-02 Danh mục | `admin.categories.index` / `admin/categories` | `admin/categories/index.blade.php` | `CategoryController@index` | Search/table/form foundation; remove unsupported category upload | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-03 Sản phẩm | `admin.products.index` / `admin/products` | `admin/products/index.blade.php` | `ProductController@index` | Real-image gallery + progressive table switch; reusable existing actions | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-04 Size & công thức | `admin.product-variants.index` / `admin/product-variants` | `admin/product-variants/index.blade.php` | `ProductVariantController@index` | Readable SKU/price/recipe action and variant-context callout | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-05 Topping | `admin.toppings.index` / `admin/toppings` | `admin/toppings/index.blade.php` | `ToppingController@index` | Compact price/status table, scoped toolbar and form style | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-06 Nguyên liệu & tồn kho | `admin.ingredients.index` / `admin/ingredients` | `admin/ingredients/index.blade.php` | `IngredientController@index` | Stock context, per-ingredient action disclosure; remove fake upload | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-07 Khu vực | `admin.areas.index` / `admin/areas` | `admin/areas/index.blade.php` | `AreaController@index` | Cards with actual metadata/actions and existing table filter link | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-08 Bàn | `admin.tables.index` / `admin/tables` | `admin/tables/index.blade.php` | `CafeTableController@index` | Group existing paginated records by area; status cards, unchanged transition payloads | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-09 Đặt bàn | `admin.reservations.index` / `admin/reservations` | `admin/reservations/index.blade.php` | `ReservationController@index` | Readable filter/table/actions; preserve current UTC and Admin/Staff differences | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-10 Khách hàng | `admin.customers.index` / `admin/customers` | `admin/customers/index.blade.php` | `CustomerController@index` | Data table/forms using real contact fields and existing actions | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-11 Khuyến mãi | `admin.promotions.index` / `admin/promotions` | `admin/promotions/index.blade.php` | `PromotionController@index` | Cards with real code/value/min/max/dates/usage/status/actions | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |
| V5-12 Tài khoản | `admin.users.index` / `admin/users` | `admin/users/index.blade.php` | `UserController@index` | Identity/role/status table and forms; no permission or session change | Focused suites + GET 200; existing auth/role guards | UNVERIFIED / None | Real browser widths, focus, zoom200%, empty/error/long content |

## Child forms and invariants

116 discovered GET child pages returned 200, including create/edit, recipe and stock/history forms. No mutation was performed in the demo. Existing PHP tests cover the underlying write workflows in isolated test databases. HTTP evidence is in storage/logs/admin-v5-live.txt.

Recipe requires a variant; stock operations require an ingredient. No standalone context-free links were invented. Table groups count only records on the current page. Product prices remain Variant data. Promotions, stock ledger, role rules and reservation timing remain backend-owned.

## Manual visual gate — NOT TESTED

- Widths: 375/390, 768, 1024, 1280, 1440; extra 320 and zoom200% overflow check.
- Check page/toolbar alignments, table scroll, money/SKU/date wrapping, card/action alignment.
- Keyboard: native gallery buttons, file input, row disclosures, sidebar drawer/collapse and return to Admin.
- Check actual empty/validation/error/disabled/success states; no prototype screenshot substitution.
- Capture actual before/after Laravel screenshots once browser access is restored.
