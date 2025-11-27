<?php
// Web routes for Stock Movements

$router->add('GET', '/stock-movements', function () use ($webStockMovementsController) {
    $user = \Middlewares\AuthMiddleware::check();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    \Helpers\Access::requireWebRole(['admin', 'vendedor']);
    $webStockMovementsController->index();
});
