<?php
namespace Helpers;

class Access
{
    // Retorna o nome da role do usuário autenticado pela sessão, ou null
    public static function sessionRoleName(): ?string
    {
        $user = \Middlewares\AuthMiddleware::check();
        if (!$user || !isset($user['role_id'])) return null;
        $roleModel = new \Models\Role();
        $roleRow = $roleModel->findById($user['role_id']);
        return $roleRow['name'] ?? null;
    }

    public static function hasRole(array $roles): bool
    {
        $name = self::sessionRoleName();
        if (!$name) return false;
        // Comparação case-insensitive para evitar diferenças de capitalização
        $nameLower = strtolower($name);
        $rolesLower = array_map('strtolower', $roles);
        return in_array($nameLower, $rolesLower, true);
    }

    // Para rotas web: se não autenticado, redireciona ao login; se não autorizado, mostra texto 403
    public static function requireWebRole(array $roles, string $msg = 'Acesso negado')
    {
        $user = \Middlewares\AuthMiddleware::check();
        if (!$user) {
            header('Location: /login');
            exit;
        }
        if (!self::hasRole($roles)) {
            http_response_code(403);
            echo $msg;
            exit;
        }
    }

    // Para endpoints que esperam JSON (AJAX): retorna 403 JSON quando não autorizado
    public static function requireWebRoleJson(array $roles, string $msg = 'Acesso negado')
    {
        // assume sessão válida já verificada pelo chamador; apenas verifica role
        if (!self::hasRole($roles)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $msg]);
            exit;
        }
    }
}
