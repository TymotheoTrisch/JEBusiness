<?php
// API routes for Users (uses $router and $apiUserController)

$router->add('GET', '/api/users', function () use ($apiUserController) {
    // Only admin
    if (!\Middlewares\ApiAuthMiddleware::checkRole(['admin'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    $apiUserController->index();
});

$router->add('POST', '/api/users/create', function () use ($apiUserController) {
    if (!\Middlewares\ApiAuthMiddleware::checkRole(['admin'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    $apiUserController->store();
});

$router->add('GET', '#^/api/users/edit/(\d+)$#', function ($id) use ($apiUserController) {
    if (!\Middlewares\ApiAuthMiddleware::checkRole(['admin'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    $apiUserController->edit($id);
});

$router->add('PUT', '#^/api/users/update/(\d+)$#', function ($id) use ($apiUserController) {
    if (!\Middlewares\ApiAuthMiddleware::checkRole(['admin'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    $apiUserController->update($id);
});

$router->add('DELETE', '#^/api/users/delete/(\d+)$#', function ($id) use ($apiUserController) {
    if (!\Middlewares\ApiAuthMiddleware::checkRole(['admin'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    $apiUserController->delete($id);
});
