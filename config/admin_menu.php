<?php

return [
    // System routes cannot be created by ordinary requests.
    'protected_routes' => ['admin.home', 'admin.dashboard.index', 'admin.menus.index'],
    'routes' => [
        'admin.categories.index', 'admin.products.index', 'admin.product-variants.index',
        'admin.toppings.index', 'admin.promotions.index', 'admin.ingredients.index',
        'admin.areas.index', 'admin.tables.index', 'admin.customers.index',
        'admin.users.index', 'admin.reservations.index',
    ],
    'icons' => ['grid', 'layers', 'coffee', 'box', 'plus', 'gift', 'tag', 'chart', 'table', 'users', 'bag', 'calendar', 'receipt'],
    'active_routes' => [
        'admin.categories.index' => ['admin.categories.create', 'admin.categories.edit'],
        'admin.products.index' => ['admin.products.create', 'admin.products.edit'],
        'admin.product-variants.index' => ['admin.product-variants.create', 'admin.product-variants.edit'],
        'admin.toppings.index' => ['admin.toppings.create', 'admin.toppings.edit'],
        'admin.promotions.index' => ['admin.promotions.create', 'admin.promotions.edit'],
        'admin.ingredients.index' => ['admin.ingredients.create', 'admin.ingredients.edit'],
        'admin.areas.index' => ['admin.areas.create', 'admin.areas.edit'],
        'admin.tables.index' => ['admin.tables.create', 'admin.tables.edit'],
        'admin.customers.index' => ['admin.customers.create', 'admin.customers.edit'],
        'admin.users.index' => ['admin.users.create', 'admin.users.edit'],
        'admin.reservations.index' => ['admin.reservations.create', 'admin.reservations.show'],
        'admin.menus.index' => ['admin.menus.create', 'admin.menus.edit'],
    ],
];
