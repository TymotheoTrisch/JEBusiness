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
use Controllers\Api\ApiStockMovementsController;
use Controllers\Web\WebUserController;
use Controllers\Web\WebViewController;
use Controllers\Web\WebProductController;
use Controllers\Web\WebCategoryController;
use Controllers\Web\WebStockMovementsController;
use Middlewares\AuthMiddleware;
use Middlewares\ApiAuthMiddleware;
use Helpers\Access;

$auth = new ApiAuthController();
$apiUserController = new ApiUserController();
$apiProductController = new ApiProductController();
$apiCategoryController = new ApiCategoryController();
$apiStockMovementsController = new ApiStockMovementsController();
$apiProductController = new ApiProductController();
$apiCategoryController = new ApiCategoryController();
$webUserController = new WebUserController();
$views = new WebViewController();
$webProductController = new WebProductController();
$webCategoryController = new WebCategoryController();
$webStockMovementsController = new WebStockMovementsController();

// NOTE: session-based access checks are handled by Helpers\Access
$webProductController = new WebProductController();
$webCategoryController = new WebCategoryController();

// Simple router: register route files and attempt dispatch. If a route
// is handled by the router it will exit here — otherwise legacy routing
// blocks below still act as fallback.
$router = new Core\Router();

// If request targets API paths, perform a token check early and return 401 JSON
// if missing/invalid. Route files can still perform finer-grained role checks.
if (strpos($uri, '/api/') === 0) {
	$apiUser = ApiAuthMiddleware::check();
	if (!$apiUser) {
		http_response_code(401);
		header('Content-Type: application/json');
		echo json_encode(['error' => 'Token invalido ou ausente']);
		exit;
	}
}

// Load organized route files (bootstrap will include per-resource route files)
require __DIR__ . '/../src/Routes/bootstrap.php';

if ($router->dispatch($uri, $method)) {
	exit;
}

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

// Resource routes are handled via `src/Routes/bootstrap.php` and per-resource
// route files under `src/Routes/*`. These provide Web and API routes for
// Products, Users, Categories and Stock Movements. If a route is matched by
// the router it has already been dispatched above.

// fallback 404
http_response_code(404);
echo "Not Found - Index.php";
