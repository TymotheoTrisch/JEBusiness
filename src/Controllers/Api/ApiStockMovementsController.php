<?php
namespace Controllers\Api;

use Models\Stock_movement;

class ApiStockMovementsController
{
    private $model;
    
    public function __construct()
    {
        $this->model = new Stock_movement();
    }

    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        $movements = $this->model->getAll();
        echo json_encode(['stock_movements' => $movements]);
    }

    public function show($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $movement = $this->model->findById($id);
        
        if (!$movement) {
            http_response_code(404);
            echo json_encode(['error' => 'Movimentação de estoque não encontrada']);
            return;
        }

        echo json_encode(['stock_movement' => $movement]);
    }
    
    public function edit($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $movement = $this->model->findById($id);
        if (!$movement) {
            http_response_code(404);
            echo json_encode(['error' => 'Movimentação de estoque não encontrada']);
            return;
        }
        echo json_encode(['stock_movement' => $movement]);
    }
    
    public function store()
    {
        $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

        $data = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
        $data = $data ?: [];

        // accept either 'quantity' or 'qty' from client, normalize to 'qty'
        if (isset($data['quantity']) && !isset($data['qty'])) {
            $data['qty'] = $data['quantity'];
        }

        if (empty($data['product_id']) || !isset($data['qty']) || !isset($data['type'])) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Dados inválidos para movimentação de estoque']);
            } else {
                echo 'Dados inválidos para movimentação de estoque';
            }
            return;
        }

        // Validar se a quantidade é positiva
        if (!is_numeric($data['qty']) || (int)$data['qty'] <= 0) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Quantidade deve ser um valor positivo']);
            } else {
                echo 'Quantidade deve ser um valor positivo';
            }
            return;
        }

        // Validar tipo de movimentação
        $data['type'] = strtolower($data['type']);
        if (!in_array($data['type'], ['in', 'out'])) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Tipo de movimentação inválido. Use: in ou out']);
            } else {
                echo 'Tipo de movimentação inválido';
            }
            return;
        }

        // Verificar se o produto existe
        $productModel = new \Models\Product();
        $product = $productModel->findById((int)$data['product_id']);
        if (!$product) {
            http_response_code(404);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Produto não encontrado']);
            } else {
                echo 'Produto não encontrado';
            }
            return;
        }

        // Validar se não vai gerar saldo negativo para saídas
        if ($data['type'] === 'out') {
            $currentStock = (int)($product['stock_qty'] ?? 0);
            $movementQty = (int)$data['qty'];
            if ($currentStock < $movementQty) {
                http_response_code(400);
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Quantidade em estoque insuficiente. Disponível: ' . $currentStock]);
                } else {
                    echo 'Quantidade em estoque insuficiente';
                }
                return;
            }
        }

        // attach current API user id when available (do not rely on client)
        $current = \Middlewares\ApiAuthMiddleware::check();
        if ($current && empty($data['user_id'])) {
            $data['user_id'] = $current['id'];
        }

        // ensure types and qty numeric
        $data['qty'] = (int)$data['qty'];

        // Atualizar stock_qty do produto
        $newStock = (int)($product['stock_qty'] ?? 0);
        if ($data['type'] === 'in') {
            $newStock += $data['qty'];
        } elseif ($data['type'] === 'out') {
            $newStock -= $data['qty'];
        }

        // Atualizar stock_qty na tabela de produtos
        $productModel->update((int)$data['product_id'], [
            'name' => $product['name'],
            'description' => $product['description'] ?? null,
            'price' => $product['price'],
            'stock_qty' => $newStock,
            'category_id' => $product['category_id'] ?? null,
            'image_path' => $product['image_path'] ?? null,
            'thumbnail_path' => $product['thumbnail_path'] ?? null,
            'is_active' => $product['is_active'] ?? 1
        ]);

        $newId = $this->model->create($data);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Movimentação de estoque criada com sucesso', 'id' => $newId]);
    }
    
    public function update($id)
    {
        $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

        $data = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
        $data = $data ?: [];

        // normalize quantity -> qty
        if (isset($data['quantity']) && !isset($data['qty'])) {
            $data['qty'] = $data['quantity'];
        }

        if (empty($data['product_id']) || !isset($data['qty']) || !isset($data['type'])) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Dados inválidos para movimentação de estoque']);
            } else {
                echo 'Dados inválidos para movimentação de estoque';
            }
            return;
        }

        // Validar se a quantidade é positiva
        if (!is_numeric($data['qty']) || (int)$data['qty'] <= 0) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Quantidade deve ser um valor positivo']);
            } else {
                echo 'Quantidade deve ser um valor positivo';
            }
            return;
        }

        // Validar tipo de movimentação
        $data['type'] = strtolower($data['type']);
        if (!in_array($data['type'], ['in', 'out'])) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Tipo de movimentação inválido. Use: in ou out']);
            } else {
                echo 'Tipo de movimentação inválido';
            }
            return;
        }

        // Buscar movimentação original
        $oldMovement = $this->model->findById($id);
        if (!$oldMovement) {
            http_response_code(404);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Movimentação de estoque não encontrada']);
            } else {
                echo 'Movimentação de estoque não encontrada';
            }
            return;
        }

        // Verificar se o produto existe
        $productModel = new \Models\Product();
        $product = $productModel->findById((int)$data['product_id']);
        if (!$product) {
            http_response_code(404);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Produto não encontrado']);
            } else {
                echo 'Produto não encontrado';
            }
            return;
        }

        // Recalcular estoque: reverter movimento anterior e aplicar novo
        $currentStock = (int)($product['stock_qty'] ?? 0);
        $oldQty = (int)($oldMovement['qty'] ?? 0);
        $newQty = (int)$data['qty'];

        // Reverter movimento antigo
        if ($oldMovement['type'] === 'in') {
            $currentStock -= $oldQty;
        } elseif ($oldMovement['type'] === 'out') {
            $currentStock += $oldQty;
        }

        // Validar se não vai gerar saldo negativo para saídas
        if ($data['type'] === 'out') {
            if ($currentStock < $newQty) {
                http_response_code(400);
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Quantidade em estoque insuficiente. Disponível: ' . $currentStock]);
                } else {
                    echo 'Quantidade em estoque insuficiente';
                }
                return;
            }
        }

        // Aplicar novo movimento
        if ($data['type'] === 'in') {
            $currentStock += $newQty;
        } elseif ($data['type'] === 'out') {
            $currentStock -= $newQty;
        }

        // attach current API user if missing
        $current = \Middlewares\ApiAuthMiddleware::check();
        if ($current && empty($data['user_id'])) {
            $data['user_id'] = $current['id'];
        }

        $data['qty'] = (int)$data['qty'];

        // Atualizar stock_qty na tabela de produtos
        $productModel->update((int)$data['product_id'], [
            'name' => $product['name'],
            'description' => $product['description'] ?? null,
            'price' => $product['price'],
            'stock_qty' => $currentStock,
            'category_id' => $product['category_id'] ?? null,
            'image_path' => $product['image_path'] ?? null,
            'thumbnail_path' => $product['thumbnail_path'] ?? null,
            'is_active' => $product['is_active'] ?? 1
        ]);

        $updated = $this->model->update($id, $data);
        if (!$updated) {
            http_response_code(404);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Movimentação de estoque não encontrada']);
            } else {
                echo 'Movimentação de estoque não encontrada';
            }
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Movimentação de estoque atualizada com sucesso']);
    }
    
    public function delete($id)
    {
        header('Content-Type: application/json; charset=utf-8');

        // buscar movimentação para poder reverter estoque
        $movement = $this->model->findById($id);
        if (!$movement) {
            http_response_code(404);
            echo json_encode(['error' => 'Movimentação de estoque não encontrada']);
            return;
        }

        // buscar produto
        $productModel = new \Models\Product();
        $product = $productModel->findById((int)$movement['product_id']);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto associado não encontrado']);
            return;
        }

        $currentStock = (int)($product['stock_qty'] ?? 0);
        $qty = (int)($movement['qty'] ?? 0);

        // reverter efeito da movimentação
        if ($movement['type'] === 'in') {
            $newStock = $currentStock - $qty;
        } elseif ($movement['type'] === 'out') {
            $newStock = $currentStock + $qty;
        } else {
            // tipos inesperados: apenas tente ajustar inversamente
            $newStock = $currentStock;
        }

        // não permitir que a remoção gere saldo negativo
        if ($newStock < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Não é possível deletar movimentação: resultaria em saldo negativo. Disponível: ' . $currentStock]);
            return;
        }

        // atualizar produto
        $productModel->update((int)$product['id'], [
            'name' => $product['name'],
            'description' => $product['description'] ?? null,
            'price' => $product['price'],
            'stock_qty' => $newStock,
            'category_id' => $product['category_id'] ?? null,
            'image_path' => $product['image_path'] ?? null,
            'thumbnail_path' => $product['thumbnail_path'] ?? null,
            'is_active' => $product['is_active'] ?? 1
        ]);

        // deletar movimento
        $deleted = $this->model->delete($id);
        if (!$deleted) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao deletar movimentação']);
            return;
        }

        echo json_encode(['message' => 'Movimentação de estoque deletada com sucesso']);
    }
}