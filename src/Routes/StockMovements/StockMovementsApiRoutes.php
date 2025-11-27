<?php
// API routes for Stock Movements

$router->add('GET', '/api/stock-movements', function () use ($apiStockMovementsController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiStockMovementsController->index();
});

$router->add('GET', '#^/api/stock-movements/edit/(\d+)$#', function ($id) use ($apiStockMovementsController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiStockMovementsController->edit($id);
});

$router->add('GET', '#^/api/stock-movements/show/(\d+)$#', function ($id) use ($apiStockMovementsController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiStockMovementsController->show($id);
});

$router->add('POST', '/api/stock-movements/create', function () use ($apiStockMovementsController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiStockMovementsController->store();
});

$router->add('PUT', '#^/api/stock-movements/update/(\d+)$#', function ($id) use ($apiStockMovementsController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiStockMovementsController->update($id);
});

$router->add('DELETE', '#^/api/stock-movements/delete/(\d+)$#', function ($id) use ($apiStockMovementsController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiStockMovementsController->delete($id);
});
