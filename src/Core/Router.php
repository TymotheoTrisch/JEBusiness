<?php
namespace Core;

class Router {
    private $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $uri, string $method): bool
    {
        $method = strtoupper($method);
        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) continue;
            $path = $r['path'];
            // If path looks like a regex (starts with #) or contains a capturing group, use preg_match
            if (strpos($path, '#') === 0 || strpos($path, '(') !== false) {
                if (preg_match($path, $uri, $m)) {
                    $params = array_slice($m, 1);
                    call_user_func_array($r['handler'], $params);
                    return true;
                }
            } else {
                if ($path === $uri) {
                    call_user_func($r['handler']);
                    return true;
                }
            }
        }
        return false;
    }
}