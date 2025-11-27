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
        // attach current API user id when available (do not rely on client)
        $current = \Middlewares\ApiAuthMiddleware::check();
        if ($current && empty($data['user_id'])) {
            $data['user_id'] = $current['id'];
        }

        // ensure types and qty numeric
        $data['qty'] = (int)$data['qty'];

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
        // attach current API user if missing
        $current = \Middlewares\ApiAuthMiddleware::check();
        if ($current && empty($data['user_id'])) {
            $data['user_id'] = $current['id'];
        }

        $data['qty'] = (int)$data['qty'];

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
        $deleted = $this->model->delete($id);
        header('Content-Type: application/json; charset=utf-8');
        if (!$deleted) {
            http_response_code(404);
            echo json_encode(['error' => 'Movimentação de estoque não encontrada']);
            return;
        }

        echo json_encode(['message' => 'Movimentação de estoque deletada com sucesso']);
    }
}