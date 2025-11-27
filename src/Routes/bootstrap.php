<?php
// Bootstrap all route files. Assumes $router and controllers are available
// in the including scope (`public/index.php`).

$base = __DIR__;

// Products
if (file_exists($base . '/Products/ProductsWebRoutes.php')) {
    require $base . '/Products/ProductsWebRoutes.php';
}
if (file_exists($base . '/Products/ProductsApiRoutes.php')) {
    require $base . '/Products/ProductsApiRoutes.php';
}

// Users
if (file_exists($base . '/Users/UsersWebRoutes.php')) {
    require $base . '/Users/UsersWebRoutes.php';
}
if (file_exists($base . '/Users/UsersApiRoutes.php')) {
    require $base . '/Users/UsersApiRoutes.php';
}

// Categories
if (file_exists($base . '/Categories/CategoriesWebRoutes.php')) {
    require $base . '/Categories/CategoriesWebRoutes.php';
}
if (file_exists($base . '/Categories/CategoriesApiRoutes.php')) {
    require $base . '/Categories/CategoriesApiRoutes.php';
}

// Stock movements
if (file_exists($base . '/StockMovements/StockMovementsWebRoutes.php')) {
    require $base . '/StockMovements/StockMovementsWebRoutes.php';
}
if (file_exists($base . '/StockMovements/StockMovementsApiRoutes.php')) {
    require $base . '/StockMovements/StockMovementsApiRoutes.php';
}
