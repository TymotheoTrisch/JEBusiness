<?php
namespace Controllers\Web;

class WebViewController
{
    public function dashboard()
    {
        header('Content-Type: text/html; charset=utf-8');
        $path = __DIR__ . '/../../../public/views/home.html';
        $html = file_get_contents($path);

        // CSRF meta
        $csrf = \Helpers\Csrf::generate();
        $meta = '<meta name="csrf-token" content="' . htmlspecialchars($csrf, ENT_QUOTES) . '">';
        $html = str_replace('{{csrf_meta}}', $meta, $html);

        // Determine current user and role
        $user = \Middlewares\AuthMiddleware::check();
        $roleName = 'guest';
        $isAdmin = false;
        $isVendedor = false;
        if ($user && isset($user['role_id'])) {
            $roleModel = new \Models\Role();
            $roleRow = $roleModel->findById($user['role_id']);
            if ($roleRow && isset($roleRow['name'])) {
                $roleName = $roleRow['name'];
            } else {
                // fallback mapping
                $roleName = ($user['role_id'] == 99) ? 'admin' : 'cliente';
            }
            $isAdmin = ($roleName === 'admin');
            $isVendedor = ($roleName === 'vendedor');
        }

        // Build links visible to this user
        $links = [];
        $links[] = '<li><a href="/home">Home</a></li>';
        if ($isAdmin) {
            $links[] = '<li><a href="/users">Usuários</a></li>';
        }
        if ($isAdmin || $isVendedor) {
            $links[] = '<li><a href="/products">Produtos</a></li>';
            $links[] = '<li><a href="/categories">Categorias</a></li>';
            $links[] = '<li><a href="/stock-movements">Movimentações de Estoque</a></li>';
        }

        // Always provide logout link
        $links[] = '<li><a href="/logout">Sair</a></li>';

        $linksHtml = implode("\n", $links);

        $html = str_replace('{{links}}', $linksHtml, $html);
        $html = str_replace('{{role}}', htmlspecialchars($roleName, ENT_QUOTES), $html);

        echo $html;
    }
}
