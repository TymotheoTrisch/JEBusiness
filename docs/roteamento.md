# Roteamento da Aplicação

Este documento descreve o mecanismo de roteamento atualmente usado no projeto `JEBusiness`, o mapa de rotas existente, como os arquivos de rota estão organizados e instruções passo-a-passo para adicionar novos recursos e rotas (web e API).

> Visão geral rápida

- O ponto de entrada é `public/index.php`.
- Foi adicionado um `Router` simples em `src/Core/Router.php` que faz dispatch de rotas registradas por método HTTP e por padrão suporta caminhos exatos e padrões via regex.
- As rotas estão organizadas por recurso em `src/Routes/` (ex.: `src/Routes/Products/ProductsWebRoutes.php` e `src/Routes/Products/ProductsApiRoutes.php`).
- `public/index.php` carrega `src/Routes/bootstrap.php`, que inclui os arquivos de rota existentes e chama `$router->dispatch($uri, $method)`.
- Para requisições que começam com `/api/` o `index.php` faz uma checagem precoce de token via `ApiAuthMiddleware::check()` (retornando 401 JSON caso esteja ausente/inválido). Rotas ainda fazem checagens de permissão mais granulares.

**Índice**

- [Como funciona o Router (visão técnica)](#como-funciona-o-router-visão-técnica)
- [Organização de arquivos de rota](#organização-de-arquivos-de-rota)
- [Mapa de Rotas (atual)](#mapa-de-rotas-atual)
- [Como adicionar um novo recurso / rota](#como-adicionar-um-novo-recurso--rota)
- [Boas práticas e cuidados](#boas-práticas-e-cuidados)
- [Testes e depuração](#testes-e-depuração)


## Como funciona o Router (visão técnica)

- Arquivo do Router: `src/Core/Router.php`.
- API mínima:
  - `add(string $method, string $path, callable $handler)` — registra uma rota.
  - `dispatch(string $uri, string $method): bool` — tenta casar e executar a rota; retorna `true` se a rota foi tratada.
- O Router suporta dois estilos de `path`:
  - Caminho exato (ex.: `/products`) — compara igualdade simples.
  - Padrão via regex (ex.: `#^/products/update/(\d+)$#`) — usa `preg_match` e passa os grupos capturados como parâmetros para o handler.
- Handlers são `callable` (normalmente closures) que executam controladores. As closures podem usar variáveis do escopo de `public/index.php` quando os arquivos de rota são incluídos (ex.: `$apiProductController`).


## Organização de arquivos de rota

Estrutura adotada atualmente (por recurso):

```
src/Routes/
  Products/
    ProductsWebRoutes.php
    ProductsApiRoutes.php
  Users/
    UsersWebRoutes.php
    UsersApiRoutes.php
  Categories/
    CategoriesWebRoutes.php
    CategoriesApiRoutes.php
  StockMovements/
    StockMovementsWebRoutes.php
    StockMovementsApiRoutes.php
  bootstrap.php
```

- `bootstrap.php` inclui de forma segura os arquivos de rota por recurso (só inclui se existir).
- Cada arquivo de rota registra suas rotas via `$router->add(...)`.
- Por enquanto os arquivos usam as instâncias de controllers já criadas em `public/index.php` (migração incremental). Em uma evolução futura, é recomendável usar um container/DI.


## Mapa de Rotas (atual)

Observação: algumas rotas de autenticação e página inicial ainda estão definidas diretamente em `public/index.php` (login, logout, home).

- Autenticação / páginas principais
  - GET `/login` — Mostrar formulário de login (via `ApiAuthController::showLogin()`)
  - POST `/login` — Submeter login (`ApiAuthController::login()`)
  - GET `/logout` — Logout (`ApiAuthController::logout()`)
  - GET `/home`, `/`, `/index.php` — Dashboard (requer sessão)

- Products (Web)
  - GET `/products` — Lista e interface web (requer sessão e papéis `admin` ou `vendedor`)

- Products (API)
  - GET `/api/products` — Listar produtos
  - GET `/api/products/edit/{id}` — Editar (obter dados do produto)
  - GET `/api/products/show/{id}` — Mostrar detalhes
  - POST `/api/products/create` — Criar produto
  - PUT `/api/products/update/{id}` — Atualizar produto
  - DELETE `/api/products/delete/{id}` — Deletar produto

- Users (Web)
  - GET `/users` — Interface de gerenciamento de usuários (requer `role_id == 99`)

- Users (API)
  - GET `/api/users` — Listar usuários (apenas admin)
  - POST `/api/users/create` — Criar usuário (apenas admin)
  - GET `/api/users/edit/{id}` — Obter usuário (apenas admin)
  - PUT `/api/users/update/{id}` — Atualizar usuário (apenas admin)
  - DELETE `/api/users/delete/{id}` — Remover usuário (apenas admin)

- Categories (Web)
  - GET `/categories` — Lista de categorias (requer sessão e papéis `admin` ou `vendedor`)

- Categories (API)
  - GET `/api/categories`
  - GET `/api/categories/edit/{id}`
  - GET `/api/categories/show/{id}`
  - POST `/api/categories/create`
  - PUT `/api/categories/update/{id}`
  - DELETE `/api/categories/delete/{id}`

- Stock Movements (Web)
  - GET `/stock-movements` — Interface de movimentações (requer sessão e papéis `admin` ou `vendedor`)

- Stock Movements (API)
  - GET `/api/stock-movements`
  - GET `/api/stock-movements/edit/{id}`
  - GET `/api/stock-movements/show/{id}`
  - POST `/api/stock-movements/create`
  - PUT `/api/stock-movements/update/{id}`
  - DELETE `/api/stock-movements/delete/{id}`


## Como adicionar um novo recurso / rota

1. Criar pasta do recurso em `src/Routes/YourResource`.
2. Criar dois arquivos (opcional):
   - `YourResourceWebRoutes.php` — rotas que servem HTML/pages/views.
   - `YourResourceApiRoutes.php` — endpoints JSON/REST.
3. No `public/index.php` certifique-se de ter instanciado os controllers necessários e que `$router` existe.
   - Exemplo mínimo dentro do arquivo de rota:

```php
$router->add('GET', '/yourresource', function() use ($webYourResourceController) {
    $user = \Middlewares\AuthMiddleware::check();
    if (!$user) { header('Location: /login'); exit; }
    \Helpers\Access::requireWebRole(['admin', 'vendedor']);
    $webYourResourceController->index();
});
```

4. Para rotas com parâmetros use regex (começando com `#`) e capture grupos:

```php
$router->add('PUT', '#^/yourresource/update/(\d+)$#', function($id) use ($apiYourResourceController) {
    \Helpers\Access::requireWebRoleJson(['admin']);
    $apiYourResourceController->update($id);
});
```

5. Atualize `src/Routes/bootstrap.php` apenas se quiser incluir manualmente novos arquivos. O `bootstrap.php` atual já tenta incluir os arquivos por recurso (se existirem).

6. Teste localmente a rota chamando a URL e validando o comportamento e os cabeçalhos (JSON para `/api/*`, redirecionamento/HTML para rotas web).


## Boas práticas e cuidados

- Evite imprimir (echo/print/var_dump) antes de usar `header()` ou `setcookie()` — isso causa o erro "headers already sent".
- Centralize checagem de token para `/api/*` no início do `index.php` (já implementado). Mantenha checagens granulares de autorização nas rotas/controllers.
- Prefira usar `POST` para criação, `PUT` para atualizações e `DELETE` para remoção nas APIs.
- Use nomes de rota consistentes: `/{resource}` para listagem, `/api/{resource}` para API, `/api/{resource}/show/{id}` ou `GET /api/{resource}/{id}` (padrão REST) — atualmente usamos a forma `show/edit` por compatibilidade com o front existente.
- Considere migrar para um router maduro (FastRoute) quando desejar melhor performance e mais recursos (named routes, geração de URIs, grupo de middleware, etc.).


## Testes e depuração

- Para verificar se uma rota está sendo atendida pelo Router, adicione temporariamente um `error_log` dentro do handler.
- Teste endpoints API com `curl` ou Postman, lembrando de enviar o token quando necessário.

Exemplo curl:

```bash
curl -i -X GET 'http://localhost:8080/api/products' -H 'Authorization: Bearer <TOKEN>'
```

- Para encontrar saídas antes de headers, rode comandos Git grep:

```bash
grep -R "var_dump\|print_r\|echo\|print\(" src | grep -v "Tests\|vendor"
```


## Perguntas frequentes

- P: Por que temos arquivos separados `Web` e `Api`?
  - R: Organização: separa rotas que retornam HTML (páginas, redirecionamentos) das que retornam JSON/REST. Facilita aplicar diferentes checagens e middlewares.

- P: As closures de rota tem acesso a variáveis em `index.php`. Isso é seguro?
  - R: Funciona para migração rápida. Idealmente você deve adotar um container/DI e passar explicitamente dependências às rotas ou usar controllers com métodos estáticos/instanciados centralmente.