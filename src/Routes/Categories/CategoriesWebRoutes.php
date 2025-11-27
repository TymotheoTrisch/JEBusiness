<?php
// Web routes for Categories (uses $router and $webCategoryController)

$router->add('GET', '/categories', function () use ($webCategoryController) {
    $user = \Middlewares\AuthMiddleware::check();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    \Helpers\Access::requireWebRole(['admin', 'vendedor']);
    $webCategoryController->index();
});
