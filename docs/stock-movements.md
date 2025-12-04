# Movimentações de Estoque

Este documento descreve a implementação do módulo de Movimentações de Estoque desenvolvido como parte da task #5 [STOCK].

---

## Visão Geral

O sistema de movimentações de estoque foi implementado para rastrear todas as entradas e saídas de produtos, mantendo histórico completo e sincronizando automaticamente o saldo disponível.

---

## Modelo

O modelo `Stock_movement` (`src/Models/Stock_movement.php`) rastreia todas as alterações de estoque:

```php
class Stock_movement {
    - getAll()              // Lista todas as movimentações ordenadas por data DESC
    - findById($id)         // Localiza movimentação específica
    - create(array $data)   // Registra nova movimentação
    - update($id, array $data) // Atualiza movimentação
    - delete($id)           // Remove movimentação
}
```

**Campos da Tabela:**
- `id` (INT): Identificador único
- `product_id` (INT): Referência ao produto (FK com ON DELETE CASCADE)
- `qty` (INT): Quantidade movimentada (sempre positiva)
- `type` (ENUM): Tipo - 'in' (entrada) ou 'out' (saída)
- `reason` (VARCHAR): Motivo/descrição da movimentação (opcional)
- `user_id` (INT): ID do usuário que realizou a movimentação
- `created_at` (TIMESTAMP): Data/hora da movimentação (automática)

---

## Controlador API

`ApiStockMovementsController` (`src/Controllers/Api/ApiStockMovementsController.php`):

### Endpoints

- **GET `/api/stock-movements`** - Lista todas as movimentações com datas formatadas
  * Retorna array com todas as movimentações ordenadas por data decrescente (mais recentes primeiro)
  * Formata datas em DD/MM/YYYY HH:MM:SS
  * Requer autenticação e role ['admin', 'vendedor']

- **GET `/api/stock-movements/show/{id}`** - Retorna movimentação específica
  * Retorna dados da movimentação com data formatada
  * HTTP 404 se movimentação não existir
  * Requer autenticação e role ['admin', 'vendedor']

- **GET `/api/stock-movements/edit/{id}`** - Retorna dados para edição
  * Retorna dados da movimentação para formulário de edição
  * HTTP 404 se movimentação não existir
  * Requer autenticação e role ['admin', 'vendedor']

- **POST `/api/stock-movements/create`** - Registra nova movimentação
  * Requer autenticação e role ['admin', 'vendedor']
  * Retorna HTTP 201 com dados da movimentação criada
  * Suporta JSON e form-data
  * Atualiza automaticamente `stock_qty` do produto

- **PUT `/api/stock-movements/update/{id}`** - Atualiza movimentação
  * Requer autenticação e role ['admin', 'vendedor']
  * Retorna dados atualizados ou HTTP 404
  * Re-sincroniza estoque automaticamente
  * Suporta JSON e form-data

- **DELETE `/api/stock-movements/delete/{id}`** - Remove movimentação
  * Requer autenticação e role ['admin', 'vendedor']
  * Remove e reverte efeito no stock_qty
  * HTTP 404 se movimentação não existir

### Validações Implementadas

1. **Validação de dados obrigatórios:**
   - `product_id`, `qty` e `type` são obrigatórios
   - Retorna HTTP 400 se faltarem

2. **Validação de quantidade:**
   - Quantidade deve ser numérica e positiva (> 0)
   - Rejeita 0, negativos e não-numéricos
   - Retorna HTTP 400 se inválido

3. **Validação de tipo:**
   - Type deve ser 'in' ou 'out' (case-insensitive, normalizado para lowercase)
   - Retorna HTTP 400 se inválido

4. **Validação de produto:**
   - Verifica se o produto existe antes de processar
   - Retorna HTTP 404 se produto não existir

5. **Validação de estoque (para saídas):**
   - Para tipo 'out': valida se há quantidade suficiente
   - Retorna HTTP 400 com saldo atual disponível se insuficiente
   - Previne estoque negativo de forma segura

6. **Validação de autenticação:**
   - Requer token JWT válido para todas as operações
   - Obtém `user_id` automaticamente do middleware `ApiAuthMiddleware`
   - Não confia em dados de `user_id` vindos do cliente

7. **Suporte flexível de parâmetros:**
   - Suporta tanto `quantity` quanto `qty` como parâmetro (normaliza para `qty`)
   - Suporta JSON e form-data
   - Detecção automática do tipo de conteúdo

---

## Banco de Dados

### Migration

Criada migration `scripts/migrations/005_create_stok_movements.php`:

```sql
CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `qty` INT NOT NULL,
    `type` ENUM('in','out') NOT NULL,
    `reason` VARCHAR(255) NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_movements_product
      FOREIGN KEY (product_id) REFERENCES products(id)
      ON DELETE CASCADE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Características:**
- Foreign key com `ON DELETE CASCADE` (movimentações deletadas quando produto é deletado)
- ENUM para garantir apenas valores válidos em `type`
- Charset UTF-8 para suportar descrições em português
- Timestamp automático de criação (auditoria)
- Índice automático via PRIMARY KEY para queries rápidas

### Execução da Migration

```bash
php scripts/migration.php  # Executa todas as migrations, incluindo movimentações de estoque
```

---

## Sincronização Automática de Estoque

Um dos principais recursos é a sincronização automática entre `products.stock_qty` e as movimentações:

### Fluxo de Sincronização

1. **Criar movimentação 'in' de 50 unidades para produto ID 1:**
   ```json
   {
     "product_id": 1,
     "qty": 50,
     "type": "in",
     "reason": "Reposição de estoque"
   }
   ```
   → Imediatamente: `products.stock_qty` += 50

2. **Criar movimentação 'out' de 10 unidades para produto ID 1:**
   ```json
   {
     "product_id": 1,
     "qty": 10,
     "type": "out",
     "reason": "Venda para cliente"
   }
   ```
   → Imediatamente: `products.stock_qty` -= 10

3. **Deletar movimentação 'in' de 50 unidades:**
   ```
   DELETE /api/stock-movements/delete/1
   ```
   → Imediatamente: `products.stock_qty` -= 50 (reverte o efeito)

### Benefícios

- Estoque sempre atualizado em tempo real
- Não é necessário fazer cálculos em tempo de execução
- Queries de listagem de produtos são rápidas
- Histórico completo e auditável

---

## Movimentação Inicial Automática

Integrado com `ApiProductController`, quando um produto é criado com `stock_qty > 0`, uma movimentação de entrada é criada automaticamente:

```bash
# Criar produto com stock_qty = 100
POST /api/products/create
{
  "name": "Notebook Dell",
  "price": 2500.00,
  "stock_qty": 100,
  "category_id": 1
}
```

Isso cria automaticamente:
```json
{
  "product_id": 1,
  "qty": 100,
  "type": "in",
  "reason": "Saldo inicial do produto Notebook Dell",
  "user_id": 1  // Usuário autenticado
}
```

### Benefícios

- Rastreabilidade desde o início do produto
- Não há "mistério" sobre de onde veio o estoque inicial
- Mantém histórico completo de movimentações

---

## Formatação de Datas

As datas são retornadas formatadas em português (DD/MM/YYYY HH:MM:SS) através do método `formatDate()`:

```php
public function formatDate($contentDate) {
    $timestamp = strtotime($contentDate);
    return date('d/m/Y H:i:s', $timestamp);
}
```

**Exemplo:**
```json
{
  "stock_movement": {
    "id": 1,
    "product_id": 1,
    "qty": 50,
    "type": "in",
    "reason": "Reposição",
    "user_id": 1,
    "created_at": "2025-12-04 14:30:15",  // YYYY-MM-DD HH:MM:SS (BD)
    "formatted_date": "04/12/2025 14:30:15"  // DD/MM/YYYY HH:MM:SS (Resposta)
  }
}
```

---

## Rotas

### Rotas de API

Arquivo: `src/Routes/StockMovements/StockMovementsApiRoutes.php`

```php
GET  /api/stock-movements              // Listar (autenticado)
GET  /api/stock-movements/show/{id}    // Detalhar (autenticado)
GET  /api/stock-movements/edit/{id}    // Dados para edição (autenticado)
POST /api/stock-movements/create       // Criar (autenticado)
PUT  /api/stock-movements/update/{id}  // Atualizar (autenticado)
DELETE /api/stock-movements/delete/{id} // Deletar (autenticado)
```

**Proteção:**
- Todas as rotas requerem autenticação
- Todas as rotas requerem role ['admin', 'vendedor']
- Middleware `ApiAuthMiddleware` valida o token

### Rotas Web

Arquivo: `src/Routes/StockMovements/StockMovementsWebRoutes.php`

```php
GET /stock-movements  // Renderiza página HTML de movimentações
```

**Proteção:**
- Requer autenticação
- Requer role ['admin', 'vendedor']

---

## Exemplos de Uso

### 1. Listar Movimentações

```bash
curl http://localhost/api/stock-movements \
  -H "Authorization: Bearer <token>"
```

Retorna:
```json
{
  "stock_movements": [
    {
      "id": 2,
      "product_id": 1,
      "qty": 10,
      "type": "out",
      "reason": "Venda para cliente ABC",
      "user_id": 1,
      "created_at": "2025-12-04 14:35:00",
      "formatted_date": "04/12/2025 14:35:00"
    },
    {
      "id": 1,
      "product_id": 1,
      "qty": 100,
      "type": "in",
      "reason": "Saldo inicial do produto Notebook Dell",
      "user_id": 1,
      "created_at": "2025-12-04 14:30:00",
      "formatted_date": "04/12/2025 14:30:00"
    }
  ]
}
```

### 2. Obter Movimentação Específica

```bash
curl http://localhost/api/stock-movements/show/1 \
  -H "Authorization: Bearer <token>"
```

Retorna:
```json
{
  "stock_movement": {
    "id": 1,
    "product_id": 1,
    "qty": 100,
    "type": "in",
    "reason": "Saldo inicial do produto Notebook Dell",
    "user_id": 1,
    "created_at": "2025-12-04 14:30:00",
    "formatted_date": "04/12/2025 14:30:00"
  }
}
```

### 3. Criar Movimentação - Entrada de Estoque

```bash
curl -X POST http://localhost/api/stock-movements/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "product_id": 1,
    "qty": 50,
    "type": "in",
    "reason": "Reposição de estoque fornecedor XYZ"
  }'
```

Retorna HTTP 201:
```json
{
  "stock_movement": {
    "id": 3,
    "product_id": 1,
    "qty": 50,
    "type": "in",
    "reason": "Reposição de estoque fornecedor XYZ",
    "user_id": 1,
    "created_at": "2025-12-04 15:00:00"
  }
}
```

**Resultado:** Produto ID 1 tem seu `stock_qty` incrementado em 50 unidades.

### 4. Criar Movimentação - Saída de Estoque

```bash
curl -X POST http://localhost/api/stock-movements/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "product_id": 1,
    "qty": 10,
    "type": "out",
    "reason": "Venda para cliente ABC"
  }'
```

Retorna HTTP 201:
```json
{
  "stock_movement": {
    "id": 4,
    "product_id": 1,
    "qty": 10,
    "type": "out",
    "reason": "Venda para cliente ABC",
    "user_id": 1,
    "created_at": "2025-12-04 15:05:00"
  }
}
```

**Resultado:** Produto ID 1 tem seu `stock_qty` decrementado em 10 unidades.

### 5. Validação - Estoque Insuficiente

```bash
curl -X POST http://localhost/api/stock-movements/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "product_id": 1,
    "qty": 200,
    "type": "out",
    "reason": "Tentativa de retirada"
  }'
```

Se saldo insuficiente, retorna HTTP 400:
```json
{
  "error": "Quantidade em estoque insuficiente. Disponível: 140"
}
```

### 6. Validação - Produto Não Existe

```bash
curl -X POST http://localhost/api/stock-movements/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "product_id": 999,
    "qty": 10,
    "type": "in",
    "reason": "Teste"
  }'
```

Retorna HTTP 404:
```json
{
  "error": "Produto não encontrado"
}
```

### 7. Validação - Tipo Inválido

```bash
curl -X POST http://localhost/api/stock-movements/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "product_id": 1,
    "qty": 10,
    "type": "invalid",
    "reason": "Teste"
  }'
```

Retorna HTTP 400:
```json
{
  "error": "Tipo de movimentação inválido. Use: in ou out"
}
```

### 8. Obter Dados para Edição

```bash
curl http://localhost/api/stock-movements/edit/1 \
  -H "Authorization: Bearer <token>"
```

Retorna:
```json
{
  "stock_movement": {
    "id": 1,
    "product_id": 1,
    "qty": 100,
    "type": "in",
    "reason": "Saldo inicial do produto Notebook Dell",
    "user_id": 1,
    "created_at": "2025-12-04 14:30:00"
  }
}
```

### 9. Atualizar Movimentação

```bash
curl -X PUT http://localhost/api/stock-movements/update/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "product_id": 1,
    "qty": 120,
    "type": "in",
    "reason": "Saldo inicial atualizado",
    "user_id": 1
  }'
```

Retorna dados atualizados:
```json
{
  "stock_movement": {
    "id": 1,
    "product_id": 1,
    "qty": 120,
    "type": "in",
    "reason": "Saldo inicial atualizado",
    "user_id": 1
  }
}
```

**Resultado:** A diferença (20 unidades) é refletida em `products.stock_qty`.

### 10. Deletar Movimentação

```bash
curl -X DELETE http://localhost/api/stock-movements/delete/1 \
  -H "Authorization: Bearer <token>"
```

Retorna HTTP 204 (No Content) ou:
```json
{
  "message": "Movimentação deletada com sucesso"
}
```

**Resultado:** O efeito da movimentação é revertido em `products.stock_qty`.

---

## Segurança

### Autenticação

- Todas as operações requerem token JWT válido
- Token validado pelo middleware `ApiAuthMiddleware`
- `user_id` obtido do middleware (não confiável em dados do cliente)

### Autorização

- Apenas usuários com roles 'admin' ou 'vendedor' podem gerenciar movimentações
- Validação implementada via `Access::requireWebRoleJson()`

### Validações em Múltiplas Camadas

- Validação de dados obrigatórios
- Validação de tipos (numérico, positivo, enums)
- Validação de relacionamentos (produto existe)
- Validação de negócio (estoque suficiente)
- Prevenção de SQL injection (prepared statements)

---

## Integração com Outros Módulos

### Com Produtos

- Cada movimentação referencia um produto via `product_id`
- Atualiza `products.stock_qty` automaticamente
- DELETE em cascata: deletar produto remove todas as movimentações

### Com Autenticação

- Vincula movimentação ao usuário autenticado
- Obtém `user_id` do middleware, não confia em dados de cliente
- Middleware `ApiAuthMiddleware` valida o token

### Com Categorias (via Produtos)

- Movimentações rastreiam produtos que podem estar em categorias
- Reportes podem filtrar por categoria através do produto

---

## Próximos Passos Sugeridos

- Implementar relatórios de movimentação por período
- Adicionar filtros avançados (por produto, tipo, usuário, data)
- Implementar paginação para lista de movimentações
- Adicionar busca por motivo/descrição
- Implementar alertas de estoque baixo
- Adicionar histórico de custos (custo de entrada)
- Implementar ajustes de estoque (inventário físico)
- Adicionar suporte a múltiplos depósitos/locais
- Implementar transferências entre produtos (devolução de lote defeituoso)
- Gerar relatórios PDF de histórico de movimentações
