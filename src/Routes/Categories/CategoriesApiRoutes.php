<?php
// API routes for Categories (uses $router and $apiCategoryController)

$router->add('GET', '/api/categories', function () use ($apiCategoryController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiCategoryController->index();
});

$router->add('GET', '#^/api/categories/edit/(\d+)$#', function ($id) use ($apiCategoryController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiCategoryController->edit($id);
});

$router->add('GET', '#^/api/categories/show/(\d+)$#', function ($id) use ($apiCategoryController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiCategoryController->show($id);
});

$router->add('POST', '/api/categories/create', function () use ($apiCategoryController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiCategoryController->store();
});

$router->add('PUT', '#^/api/categories/update/(\d+)$#', function ($id) use ($apiCategoryController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiCategoryController->update($id);
});

$router->add('DELETE', '#^/api/categories/delete/(\d+)$#', function ($id) use ($apiCategoryController) {
    \Helpers\Access::requireWebRoleJson(['admin', 'vendedor']);
    $apiCategoryController->delete($id);
});
