<?php

// Fixed application navigation. No AdminMenu records or database configuration are read here.
return [
    ['id' => 'overview', 'label' => 'Tổng quan', 'items' => [
        ['label' => 'Tổng quan cửa hàng', 'route' => 'admin.home', 'icon' => 'grid', 'active' => ['admin.home', 'admin.dashboard.*']],
    ]],
    ['id' => 'catalog', 'label' => 'Thực đơn & công thức', 'items' => [
        ['label' => 'Danh mục đồ uống', 'route' => 'admin.categories.index', 'icon' => 'layers', 'active' => ['admin.categories.*']],
        ['label' => 'Sản phẩm', 'route' => 'admin.products.index', 'icon' => 'coffee', 'active' => ['admin.products.*']],
        ['label' => 'Size & công thức', 'route' => 'admin.product-variants.index', 'icon' => 'box', 'active' => ['admin.product-variants.*', 'admin.product-recipes.*']],
        ['label' => 'Topping', 'route' => 'admin.toppings.index', 'icon' => 'plus', 'active' => ['admin.toppings.*']],
    ]],
    ['id' => 'inventory', 'label' => 'Nguyên liệu & kho', 'hint' => 'Chọn nguyên liệu để nhập kho, kiểm kê hoặc xem lịch sử.', 'items' => [
        ['label' => 'Nguyên liệu & tồn kho', 'route' => 'admin.ingredients.index', 'icon' => 'box', 'active' => ['admin.ingredients.*']],
    ]],
    ['id' => 'tables', 'label' => 'Không gian & đặt bàn', 'items' => [
        ['label' => 'Khu vực quán', 'route' => 'admin.areas.index', 'icon' => 'layers', 'active' => ['admin.areas.*']],
        ['label' => 'Quản lý bàn', 'route' => 'admin.tables.index', 'icon' => 'table', 'active' => ['admin.tables.*']],
        ['label' => 'Đặt bàn', 'route' => 'admin.reservations.index', 'icon' => 'calendar', 'active' => ['admin.reservations.*']],
    ]],
    ['id' => 'customers', 'label' => 'Khách hàng & ưu đãi', 'items' => [
        ['label' => 'Khách hàng', 'route' => 'admin.customers.index', 'icon' => 'users', 'active' => ['admin.customers.*']],
        ['label' => 'Khuyến mãi', 'route' => 'admin.promotions.index', 'icon' => 'gift', 'active' => ['admin.promotions.*']],
    ]],
    ['id' => 'system', 'label' => 'Tài khoản', 'items' => [
        ['label' => 'Nhân viên & tài khoản', 'route' => 'admin.users.index', 'icon' => 'users', 'active' => ['admin.users.*']],
    ]],
];
