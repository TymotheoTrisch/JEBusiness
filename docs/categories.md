# Categorias

Este documento descreve a implementação do módulo de Categorias desenvolvido como parte da task #4 [PRODUCT].

---

## Visão Geral

O sistema de categorias foi implementado para organizar e categorizar produtos, permitindo uma melhor estruturação e recuperação de dados no catálogo.

---

## Modelo

O modelo `Category` (`src/Models/Category.php`) fornece operações CRUD:

```php
class Category {
    - findAll()              // Lista todas as categorias
    - findById($id)          // Localiza categoria por ID
    - create(array $data)    // Cria nova categoria
    - update(int $id, array $data) // Atualiza categoria
    - delete(int $id)        // Remove categoria
    - transferProductsToDefaultCategory($categoryId) // Move produtos ao deletar
}
```

**Campos da Tabela:**
- `id` (INT): Identificador único
- `name` (VARCHAR): Nome da categoria (obrigatório, único)
- `description` (VARCHAR): Descrição opcional
- `created_at` (TIMESTAMP): Data de criação automática

---

## Controlador API

`ApiCategoryController` (`src/Controllers/Api/ApiCategoryController.php`):

### Endpoints

- **GET `/api/categories`** - Lista todas as categorias
  * Retorna array com todas as categorias ordenadas por nome
  * Acesso público

- **GET `/api/categories/show/{id}`** - Retorna categoria específica
  * Retorna dados da categoria ou HTTP 404
  * Acesso público

- **GET `/api/categories/edit/{id}`** - Retorna dados para edição
  * Retorna dados da categoria para formulário de edição
  * HTTP 404 se categoria não existir
  * Requer autenticação e role ['admin', 'vendedor']

- **POST `/api/categories/create`** - Cria nova categoria
  * Requer autenticação e role ['admin', 'vendedor']
  * Retorna HTTP 201 com dados da categoria criada
  * Suporta JSON e form-data

- **PUT `/api/categories/update/{id}`** - Atualiza categoria existente
  * Requer autenticação e role ['admin', 'vendedor']
  * Retorna dados atualizados ou HTTP 404
  * Suporta JSON e form-data

- **DELETE `/api/categories/delete/{id}`** - Remove categoria
  * Requer autenticação e role ['admin', 'vendedor']
  * Move produtos associados para NULL antes de deletar
  * HTTP 404 se categoria não existir

### Validações Implementadas

- Nome da categoria é obrigatório
- Verifica duplicação de nomes (case-insensitive)
- Suporta tanto requisições JSON quanto form-data
- Validação automática do tipo de conteúdo (Accept header)

---

## Banco de Dados

### Migration

Criada migration `scripts/migrations/003_create_category.php`:

```sql
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Características:**
- Charset UTF-8 para suportar textos em português
- Timestamp automático de criação
- Primary key para otimizar queries

### Execução da Migration

```bash
php scripts/migration.php  # Executa todas as migrations, incluindo categorias
```

---

## Rotas

### Rotas de API

Arquivo: `src/Routes/Categories/CategoriesApiRoutes.php`

```php
GET  /api/categories              // Listar (público)
GET  /api/categories/show/{id}    // Detalhar (público)
GET  /api/categories/edit/{id}    // Dados para edição (autenticado)
POST /api/categories/create       // Criar (autenticado)
PUT  /api/categories/update/{id}  // Atualizar (autenticado)
DELETE /api/categories/delete/{id} // Deletar (autenticado)
```

**Proteção:**
- Operações públicas: GET `/api/categories` e GET `/api/categories/show/{id}`
- Operações autenticadas: todos os demais endpoints
- Requer role ['admin', 'vendedor'] para escrita

---

## Exemplos de Uso

### 1. Listar Categorias

```bash
curl http://localhost/api/categories
```

Retorna:
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Eletrônicos",
      "description": "Produtos eletrônicos em geral",
      "created_at": "2025-12-04 10:00:00"
    },
    {
      "id": 2,
      "name": "Livros",
      "description": "Livros e publicações",
      "created_at": "2025-12-04 10:05:00"
    }
  ]
}
```

### 2. Obter Categoria Específica

```bash
curl http://localhost/api/categories/show/1
```

Retorna:
```json
{
  "category": {
    "id": 1,
    "name": "Eletrônicos",
    "description": "Produtos eletrônicos em geral",
    "created_at": "2025-12-04 10:00:00"
  }
}
```

### 3. Criar Categoria

```bash
curl -X POST http://localhost/api/categories/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "name": "Eletrônicos",
    "description": "Produtos eletrônicos em geral"
  }'
```

Retorna HTTP 201:
```json
{
  "category": {
    "id": 1,
    "name": "Eletrônicos",
    "description": "Produtos eletrônicos em geral",
    "created_at": "2025-12-04 10:00:00"
  }
}
```

Erro se nome duplicado (HTTP 409):
```json
{
  "error": "Já existe uma categoria com esse nome"
}
```

### 4. Obter Dados para Edição

```bash
curl http://localhost/api/categories/edit/1 \
  -H "Authorization: Bearer <token>"
```

Retorna:
```json
{
  "category": {
    "id": 1,
    "name": "Eletrônicos",
    "description": "Produtos eletrônicos em geral",
    "created_at": "2025-12-04 10:00:00"
  }
}
```

### 5. Atualizar Categoria

```bash
curl -X PUT http://localhost/api/categories/update/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "name": "Eletrônicos e Informática",
    "description": "Produtos eletrônicos, informática e acessórios"
  }'
```

Retorna:
```json
{
  "category": {
    "id": 1,
    "name": "Eletrônicos e Informática",
    "description": "Produtos eletrônicos, informática e acessórios",
    "created_at": "2025-12-04 10:00:00"
  }
}
```

### 6. Deletar Categoria

```bash
curl -X DELETE http://localhost/api/categories/delete/1 \
  -H "Authorization: Bearer <token>"
```

Retorna HTTP 204 (No Content) ou:
```json
{
  "message": "Categoria deletada com sucesso"
}
```

Nota: Produtos associados à categoria terão `category_id` definido como NULL.

---

## Segurança

### Autenticação

- Operações de leitura (GET para index e show) são públicas
- Operações de escrita (POST, PUT, DELETE) requerem token JWT válido
- Token validado pelo middleware `ApiAuthMiddleware`

### Autorização

- Apenas usuários com roles 'admin' ou 'vendedor' podem gerenciar categorias
- Validação implementada via `Access::requireWebRoleJson()`

### Validações

- Dados obrigatórios verificados em todas as operações
- Tipos de dados validados
- Duplicação de nomes prevenida (case-insensitive)
- Suporte automático para JSON e form-data

---

## Integração com Produtos

As categorias são parte integral do módulo de produtos:

- Cada produto pode estar associado a uma categoria via `category_id`
- Quando um produto é listado, suas dados de categoria são inclusos automaticamente
- Ao deletar uma categoria, produtos não são deletados, apenas desassociados
- Endpoint GET `/api/products/edit/{id}` retorna lista completa de categorias para seleção

---

## Próximos Passos Sugeridos

- Implementar paginação para lista de categorias (com muitas categorias)
- Adicionar filtros de busca por nome
- Implementar hierarquia de categorias (subcategorias)
- Adicionar contagem de produtos por categoria
- Implementar ordenação customizável (nome, data, quantidade de produtos)
- Adicionar ícones ou imagens para categorias
