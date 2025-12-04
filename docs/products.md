# Produtos

Este documento descreve a implementação do módulo de Produtos desenvolvido como parte da task #4 [PRODUCT].

---

## Visão Geral

O sistema de produtos foi implementado com gerenciamento completo de catálogo, incluindo associação a categorias, controle de estoque e metadados de imagens.

---

## Modelo

O modelo `Product` (`src/Models/Product.php`) gerencia produtos com associação a categorias:

```php
class Product {
    - getAll()              // Lista todos com dados de categoria
    - findById($id)         // Localiza produto com categoria associada
    - create(array $data)   // Cria novo produto
    - update(int $id, array $data) // Atualiza produto
    - delete(int $id)       // Remove produto
    - getCategory($product) // Obtém categoria do produto
}
```

**Campos da Tabela:**
- `id` (INT): Identificador único
- `name` (VARCHAR): Nome do produto (obrigatório, único)
- `description` (VARCHAR): Descrição opcional
- `price` (DECIMAL): Preço do produto (obrigatório, >= 0)
- `stock_qty` (INT): Quantidade em estoque (padrão: 0)
- `category_id` (INT): Referência à categoria (NULL = sem categoria)
- `image_path` (VARCHAR): Caminho para imagem principal
- `thumbnail_path` (VARCHAR): Caminho para miniatura
- `is_active` (TINYINT): Status ativo/inativo (padrão: 1)
- `created_at`, `updated_at` (TIMESTAMP): Timestamps automáticos

---

## Controlador API

`ApiProductController` (`src/Controllers/Api/ApiProductController.php`):

### Endpoints

- **GET `/api/products`** - Lista todos os produtos com dados de categoria
  * Retorna array com todos os produtos ordenados por nome
  * Inclui dados da categoria associada para cada produto
  * Acesso público

- **GET `/api/products/show/{id}`** - Retorna produto com categoria
  * Retorna dados completos do produto incluindo categoria
  * HTTP 404 se produto não existir
  * Acesso público

- **GET `/api/products/edit/{id}`** - Retorna dados para edição + lista de categorias
  * Retorna dados do produto e lista completa de categorias para select/combobox
  * HTTP 404 se produto não existir
  * Requer autenticação e role ['admin', 'vendedor']

- **POST `/api/products/create`** - Cria novo produto
  * Requer autenticação e role ['admin', 'vendedor']
  * Retorna HTTP 201 com dados do produto criado
  * Suporta JSON e form-data
  * Cria movimentação inicial de estoque automaticamente se stock_qty > 0

- **PUT `/api/products/update/{id}`** - Atualiza produto
  * Requer autenticação e role ['admin', 'vendedor']
  * Retorna dados atualizados ou HTTP 404
  * Suporta JSON e form-data

- **DELETE `/api/products/delete/{id}`** - Remove produto
  * Requer autenticação e role ['admin', 'vendedor']
  * Cascata: remove todas as movimentações de estoque associadas
  * HTTP 404 se produto não existir

### Validações Implementadas

1. **Validação de dados obrigatórios:**
   - Nome e preço são obrigatórios
   - Retorna HTTP 400 se faltarem

2. **Validação de unicidade:**
   - Nome única (case-insensitive)
   - Retorna HTTP 409 se nome duplicado

3. **Validação de categoria:**
   - Se category_id informado, valida se existe
   - Retorna HTTP 400 se categoria inválida

4. **Validação de valores numéricos:**
   - Preço deve ser numérico e >= 0
   - stock_qty deve ser numérico e >= 0
   - Retorna HTTP 400 se inválido

5. **Movimentação inicial automática:**
   - Ao criar produto com stock_qty > 0
   - Cria automaticamente movimentação de entrada tipo 'in'
   - Mantém rastreabilidade desde o início

6. **Suporte flexível de parâmetros:**
   - Suporta JSON e form-data
   - Detecção automática do tipo de conteúdo

---

## Banco de Dados

### Migration

Criada migration `scripts/migrations/004_create_produtcs.php`:

```sql
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock_qty` INT NOT NULL DEFAULT 0,
    `category_id` INT NULL,
    `image_path` VARCHAR(255) NULL,
    `thumbnail_path` VARCHAR(255) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
      FOREIGN KEY (category_id) REFERENCES categories(id)
      ON DELETE SET NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Características:**
- Charset UTF-8 para suportar textos em português
- Foreign key com `ON DELETE SET NULL` (produtos não são deletados ao deletar categoria)
- Timestamps automáticos de criação e atualização
- Preço com 2 casas decimais
- Status `is_active` para filtros futuros

### Execução da Migration

```bash
php scripts/migration.php  # Executa todas as migrations, incluindo produtos
```

---

## Relação com Categorias

Cada produto pode estar associado a uma categoria (FK `category_id`). Quando um produto é listado:

```json
{
    "id": 1,
    "name": "Notebook Dell",
    "description": "Notebook 15 polegadas",
    "price": 2500.00,
    "stock_qty": 15,
    "category_id": 2,
    "category": {
        "id": 2,
        "name": "Eletrônicos",
        "description": "Produtos eletrônicos em geral"
    },
    "image_path": "/assets/img/notebook-dell.jpg",
    "thumbnail_path": "/assets/img/notebook-dell-thumb.jpg",
    "is_active": 1,
    "created_at": "2025-12-04 10:00:00",
    "updated_at": "2025-12-04 10:00:00"
}
```

---

## Rotas

### Rotas de API

Arquivo: `src/Routes/Products/ProductsApiRoutes.php`

```php
GET  /api/products              // Listar (público)
GET  /api/products/show/{id}    // Detalhar (público)
GET  /api/products/edit/{id}    // Dados para edição (autenticado)
POST /api/products/create       // Criar (autenticado)
PUT  /api/products/update/{id}  // Atualizar (autenticado)
DELETE /api/products/delete/{id} // Deletar (autenticado)
```

**Proteção:**
- Operações públicas: GET `/api/products` e GET `/api/products/show/{id}`
- Operações autenticadas: todos os demais endpoints
- Requer role ['admin', 'vendedor'] para escrita

### Rotas Web

Arquivo: `src/Routes/Products/ProductsWebRoutes.php`

```php
GET /products  // Renderiza página HTML de produtos
```

**Proteção:**
- Requer autenticação
- Requer role ['admin', 'vendedor']

---

## Exemplos de Uso

### 1. Listar Produtos

```bash
curl http://localhost/api/products
```

Retorna:
```json
{
  "products": [
    {
      "id": 1,
      "name": "Notebook Dell",
      "price": 2500.00,
      "stock_qty": 100,
      "category_id": 1,
      "category": {
        "id": 1,
        "name": "Eletrônicos",
        "description": "Produtos eletrônicos em geral"
      },
      "is_active": 1
    },
    {
      "id": 2,
      "name": "Mouse Logitech",
      "price": 85.00,
      "stock_qty": 250,
      "category_id": 1,
      "category": {
        "id": 1,
        "name": "Eletrônicos",
        "description": "Produtos eletrônicos em geral"
      },
      "is_active": 1
    }
  ]
}
```

### 2. Obter Produto Específico

```bash
curl http://localhost/api/products/show/1
```

Retorna:
```json
{
  "product": {
    "id": 1,
    "name": "Notebook Dell",
    "description": "Notebook 15 polegadas",
    "price": 2500.00,
    "stock_qty": 100,
    "category_id": 1,
    "category_name": "Eletrônicos",
    "image_path": "/assets/img/notebook.jpg",
    "thumbnail_path": "/assets/img/notebook-thumb.jpg",
    "is_active": 1
  }
}
```

### 3. Criar Produto

```bash
curl -X POST http://localhost/api/products/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "name": "Notebook Dell",
    "description": "Notebook 15 polegadas",
    "price": 2500.00,
    "stock_qty": 100,
    "category_id": 1,
    "image_path": "/assets/img/notebook.jpg",
    "thumbnail_path": "/assets/img/notebook-thumb.jpg",
    "is_active": 1
  }'
```

Retorna HTTP 201:
```json
{
  "product": {
    "id": 1,
    "name": "Notebook Dell",
    "description": "Notebook 15 polegadas",
    "price": 2500.00,
    "stock_qty": 100,
    "category_id": 1,
    "category_name": "Eletrônicos",
    "image_path": "/assets/img/notebook.jpg",
    "thumbnail_path": "/assets/img/notebook-thumb.jpg",
    "is_active": 1
  }
}
```

**Nota:** Uma movimentação de entrada de 100 unidades é criada automaticamente com reason "Saldo inicial do produto Notebook Dell".

Erros possíveis:
- HTTP 400: Nome/preço obrigatórios ou valores inválidos
- HTTP 409: Produto com esse nome já existe
- HTTP 400: Categoria informada não existe

### 4. Obter Dados para Edição

```bash
curl http://localhost/api/products/edit/1 \
  -H "Authorization: Bearer <token>"
```

Retorna:
```json
{
  "product": {
    "id": 1,
    "name": "Notebook Dell",
    "description": "Notebook 15 polegadas",
    "price": 2500.00,
    "stock_qty": 100,
    "category_id": 1,
    "category_name": "Eletrônicos"
  },
  "categories": [
    {
      "id": 1,
      "name": "Eletrônicos",
      "description": "Produtos eletrônicos em geral"
    },
    {
      "id": 2,
      "name": "Livros",
      "description": "Livros e publicações"
    }
  ]
}
```

### 5. Atualizar Produto

```bash
curl -X PUT http://localhost/api/products/update/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "name": "Notebook Dell Atualizado",
    "description": "Notebook 15 polegadas - Versão 2025",
    "price": 2800.00,
    "stock_qty": 100,
    "category_id": 1,
    "is_active": 1
  }'
```

Retorna:
```json
{
  "product": {
    "id": 1,
    "name": "Notebook Dell Atualizado",
    "description": "Notebook 15 polegadas - Versão 2025",
    "price": 2800.00,
    "stock_qty": 100,
    "category_id": 1
  }
}
```

### 6. Deletar Produto

```bash
curl -X DELETE http://localhost/api/products/delete/1 \
  -H "Authorization: Bearer <token>"
```

Retorna HTTP 204 ou mensagem de sucesso.

**Nota:** Todas as movimentações de estoque associadas ao produto serão deletadas (ON DELETE CASCADE).

---

## Segurança

### Autenticação

- Operações de leitura (GET) são públicas
- Operações de escrita (POST, PUT, DELETE) requerem token JWT válido
- Token validado pelo middleware `ApiAuthMiddleware`

### Autorização

- Apenas usuários com roles 'admin' ou 'vendedor' podem gerenciar produtos
- Validação implementada via `Access::requireWebRoleJson()`

### Validações

- Dados obrigatórios verificados em todas as operações
- Tipos de dados validados (numérico, positivo, enums)
- Duplicação de nomes prevenida (case-insensitive)
- Relacionamentos verificados antes de operações

---

## Integração com Movimentações de Estoque

O módulo de produtos integra-se com o sistema de movimentações de estoque:

- Ao criar produto com `stock_qty > 0`, uma movimentação inicial é criada automaticamente
- Cada movimentação de estoque (entrada ou saída) atualiza `stock_qty` do produto automaticamente
- Histórico completo de movimentações serve como auditoria do estoque

Para mais detalhes sobre movimentações, veja: `docs/stock-movements.md`

---

## Próximos Passos Sugeridos

- Implementar paginação para lista de produtos
- Adicionar filtros avançados (por categoria, preço, status)
- Implementar busca por nome/descrição
- Adicionar upload de imagens
- Implementar variações de produtos (tamanhos, cores)
- Adicionar relacionamento com fornecedores
- Implementar histórico de alterações de preço
- Adicionar desconto/promoção de produtos
