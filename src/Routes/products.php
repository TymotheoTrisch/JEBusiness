<?php
// Routes for products (web + api). This file expects `$router` and controller
// variables (e.g. $webProductController, $apiProductController) to be
// available in the including scope (public/index.php).

// Web product list (requires session + role)
$router->add('GET', '/products', function () use ($webProductController) {
    $user = \Middlewares\AuthMiddleware::check();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    \Helpers\Access::requireWebRole(['admin', 'vendedor']);
    $webProductController->index();
});

// Web create (AJAX call to API controller)
$router->add('POST', '/products', function () use ($apiProductController) {
    \Helpers\Access::requireWebRole(['admin', 'vendedor']);
    $apiProductController->store();
});

$router->add('PUT', '#^/products/update/(\d+)$#', function ($id) use ($apiProductController) {
    \Helpers\Access::requireWebRole(['admin', 'vendedor']);
    $apiProductController->update($id);
});

$router->add('DELETE', '#^/products/delete/(\d+)$#', function ($id) use ($apiProductController) {
    \Helpers\Access::requireWebRole(['admin', 'vendedor']);
    $apiProductController->delete($id);
});

// API routes for products
$router->add('GET', '/api/products', function () use ($apiProductController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiProductController->index();
});

$router->add('GET', '#^/api/products/edit/(\d+)$#', function ($id) use ($apiProductController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiProductController->edit($id);
});

$router->add('GET', '#^/api/products/show/(\d+)$#', function ($id) use ($apiProductController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiProductController->show($id);
});

$router->add('POST', '/api/products/create', function () use ($apiProductController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiProductController->store();
});

$router->add('PUT', '#^/api/products/update/(\d+)$#', function ($id) use ($apiProductController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiProductController->update($id);
});

$router->add('DELETE', '#^/api/products/delete/(\d+)$#', function ($id) use ($apiProductController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiProductController->delete($id);
});
