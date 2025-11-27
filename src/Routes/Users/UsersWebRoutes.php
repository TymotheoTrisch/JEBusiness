<?php
// Web routes for Users (uses $router and $webUserController)

$router->add('GET', '/users', function () use ($webUserController) {
    $user = \Middlewares\AuthMiddleware::check();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    if ($user['role_id'] != 99) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }
    $webUserController->index();
});
