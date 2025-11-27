<?php
// Web routes for Products (uses $router, $webProductController and $apiProductController)

$router->add('GET', '/products', function () use ($webProductController) {
    $user = \Middlewares\AuthMiddleware::check();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    \Helpers\Access::requireWebRole(['admin', 'vendedor']);
    $webProductController->index();
});

// Web forms could be added here (e.g. /products/new) — currently the app
// uses AJAX and API endpoints for create/update/delete.
