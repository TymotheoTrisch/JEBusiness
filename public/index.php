<?php

// autoload
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	require __DIR__ . '/../vendor/autoload.php';
} else {
	// Simple autoloader for src/ classes (PSR-4-ish)
	spl_autoload_register(function ($class) {
		$base = __DIR__ . '/../src/';
		$prefix = '';
		$class = ltrim($class, '\\');
		$path = str_replace('\\', '/', $class);
		$file = $base . $path . '.php';
		if (file_exists($file)) require $file;
	});
}

// session cookie params
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
// Use host-only cookies by omitting the 'domain' option. This avoids problems
// with hosts that include ports (e.g. localhost:8080) and keeps the cookie
// scope limited to the request host.
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'secure' => $secure,
	'httponly' => true,
	'samesite' => 'Lax',
]);

// Make sure session starts at the very beginning
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
	if (!isset($_SESSION['_csrf_token'])) {
		$_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
	}
}

// Debug session info
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	error_log('Session ID: ' . session_id());
	error_log('Session Data: ' . print_r($_SESSION, true));
	error_log('Cookie Data: ' . print_r($_COOKIE, true));
}

// basic routing
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

use Controllers\Api\ApiAuthController;
use Controllers\Api\ApiUserController;
use Controllers\Api\ApiProductController;
use Controllers\Api\ApiCategoryController;
use Controllers\Web\WebUserController;
use Controllers\Web\WebViewController;
use Controllers\Web\WebProductController;
use Controllers\Web\WebCategoryController;
use Middlewares\AuthMiddleware;
use Middlewares\ApiAuthMiddleware;

$auth = new ApiAuthController();
$apiUserController = new ApiUserController();
$apiProductController = new ApiProductController();
$apiCategoryController = new ApiCategoryController();
$webUserController = new WebUserController();
$views = new WebViewController();
$webProductController = new WebProductController();
$webCategoryController = new WebCategoryController();

// Login routes
if ($uri === '/login') {
	if ($method === 'GET') {
		$auth->showLogin();
		exit;
	}
	if ($method === 'POST') {
		$auth->login();
		exit;
	}
}

// Logout route
if ($uri === '/logout') {
	$auth->logout();
	exit;
}

// Home / Dashboard route
if ($uri === '/home' || $uri === '/' || $uri === '' || $uri === '/index.php') {
	$user = AuthMiddleware::check();
	if (!$user) {
		header('Location: /login');
		exit;
	}
	$views->dashboard();
	exit;
}

// Rotas web (sessão, CSRF)
if (strpos($uri, '/users') === 0) {
	$user = AuthMiddleware::check();
	if (!$user) {
		header('Location: /login');
		exit;
	}
	if ($user['role_id'] != 99) {
		http_response_code(403);
		echo 'Acesso negado.';
		exit;
	}
	// CRUD via modais na mesma rota
	if ($uri === '/users' && $method === 'GET') {
		// Serve apenas HTML para o frontend
		$webUserController->index();
		exit;
	}
	// As demais rotas web podem ser ajustadas para servir HTML ou redirecionar
	// Se necessário, implemente formulários web ou redirecione para /users
}

// Rotas web para Produtos
if (strpos($uri, '/products') === 0) {
	$user = AuthMiddleware::check();
	if (!$user) {
		header('Location: /login');
		exit;
	}
	// Lista pública para usuários autenticados
	if ($uri === '/products' && $method === 'GET') {
		$webProductController->index();
		exit;
	}
	// Ações de gerenciamento exigem admin
	if ($user['role_id'] != 99) {
		http_response_code(403);
		echo 'Acesso negado.';
		exit;
	}
	if ($uri === '/products' && $method === 'POST') {
		$apiProductController->store();
		exit;
	}
	if (preg_match('#^/products/update/(\d+)$#', $uri, $m) && $method === 'PUT') {
		$apiProductController->update($m[1]);
		exit;
	}
	if (preg_match('#^/products/delete/(\d+)$#', $uri, $m) && $method === 'DELETE') {
		$apiProductController->delete($m[1]);
		exit;
	}
}

// Rotas web para Categorias
if (strpos($uri, '/categories') === 0) {
	$user = AuthMiddleware::check();
	if (!$user) {
		header('Location: /login');
		exit;
	}
	// Lista pública para usuários autenticados
	if ($uri === '/categories' && $method === 'GET') {
		$webCategoryController->index();
		exit;
	}
	// Ações de gerenciamento exigem admin
	if ($user['role_id'] != 99) {
		http_response_code(403);
		echo 'Acesso negado.';
		exit;
	}
	if ($uri === '/categories' && $method === 'POST') {
		$apiCategoryController->store();
		exit;
	}
	if (preg_match('#^/categories/update/(\d+)$#', $uri, $m) && $method === 'PUT') {
		$apiCategoryController->update($m[1]);
		exit;
	}
	if (preg_match('#^/categories/delete/(\d+)$#', $uri, $m) && $method === 'DELETE') {
		$apiCategoryController->delete($m[1]);
		exit;
	}
}

// Rotas API (JSON)
if (strpos($uri, '/api/') === 0) {
	$user = ApiAuthMiddleware::check();
	if (!$user) {
		http_response_code(401);
		header('Content-Type: application/json');
		echo json_encode(['error' => 'Token invalido ou ausente']);
		exit;
	}
	// Permissão: apenas admin pode acessar /api/users
	$isAdmin = ApiAuthMiddleware::checkRole(['admin']);
	if ($uri === '/api/users' && $method === 'GET') {
		if (!$isAdmin) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiUserController->index();
		exit;
	}
	if ($uri === '/api/users/create' && $method === 'POST') {
		if (!$isAdmin) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiUserController->store();
		exit;
	}
	if (preg_match('#^/api/users/edit/(\d+)$#', $uri, $m) && $method === 'GET') {
		if (!$isAdmin) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiUserController->edit($m[1]);
		exit;
	}
	if (preg_match('#^/api/users/update/(\d+)$#', $uri, $m) && $method === 'PUT') {
		if (!$isAdmin) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiUserController->update($m[1]);
		exit;
	}
	if (preg_match('#^/api/users/delete/(\d+)$#', $uri, $m) && $method === 'DELETE') {
		if (!$isAdmin) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiUserController->delete($m[1]);
		exit;
	}

	// API Produtos
	$isVendedor = ApiAuthMiddleware::checkRole(['vendedor']);
	if ($uri === '/api/products' && $method === 'GET') {
		$apiProductController->index();
		exit;
	}
	if (preg_match('#^/api/products/edit/(\d+)$#', $uri, $m) && $method === 'GET') {
		$apiProductController->edit($m[1]);
		exit;
	}
	if (preg_match('#^/api/products/show/(\d+)$#', $uri, $m) && $method === 'GET') {
		$apiProductController->show($m[1]);
		exit;
	}
	if ($uri === '/api/products/create' && $method === 'POST') {
		if (!$isAdmin && !$isVendedor) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiProductController->store();
		exit;
	}
	if (preg_match('#^/api/products/update/(\d+)$#', $uri, $m) && $method === 'PUT') {
		if (!$isAdmin && !$isVendedor) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiProductController->update($m[1]);
		exit;
	}
	if (preg_match('#^/api/products/delete/(\d+)$#', $uri, $m) && $method === 'DELETE') {
		if (!$isAdmin && !$isVendedor) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiProductController->delete($m[1]);
		exit;
	}

	// API Categorias
	if ($uri === '/api/categories' && $method === 'GET') {
		$apiCategoryController->index();
		exit;
	}
	if (preg_match('#^/api/categories/edit/(\d+)$#', $uri, $m) && $method === 'GET') {
		$apiCategoryController->edit($m[1]);
		exit;
	}
	if (preg_match('#^/api/categories/show/(\d+)$#', $uri, $m) && $method === 'GET') {
		$apiCategoryController->show($m[1]);
		exit;
	}
	if ($uri === '/api/categories/create' && $method === 'POST') {
		if (!$isAdmin && !$isVendedor) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiCategoryController->store();
		exit;
	}
	if (preg_match('#^/api/categories/update/(\d+)$#', $uri, $m) && $method === 'PUT') {
		if (!$isAdmin && !$isVendedor) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiCategoryController->update($m[1]);
		exit;
	}
	if (preg_match('#^/api/categories/delete/(\d+)$#', $uri, $m) && $method === 'DELETE') {
		if (!$isAdmin && !$isVendedor) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Acesso negado']);
			exit;
		}
		$apiCategoryController->delete($m[1]);
		exit;
	}
}

// fallback 404
http_response_code(404);
echo "Not Found - Index.php";
