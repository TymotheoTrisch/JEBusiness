<?php
// API routes for Products (uses $router and $apiProductController)

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
