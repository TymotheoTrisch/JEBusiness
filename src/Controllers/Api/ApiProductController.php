<?php
namespace Controllers\Api;

use Models\Product;

class ApiProductController
{
    private $model;
    
    public function __construct()
    {
        $this->model = new Product();
    }

    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        $products = $this->model->getAll();
        echo json_encode(['products' => $products]);
    }

    public function show($id)
    {
        header('Content-Type: application/json; charset=utf-8');
        $product = $this->model->findById($id);
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado']);
            return;
        }

        echo json_encode(['product' => $product]);
    }

    public function edit($id)
    {
        $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
        header('Content-Type: application/json; charset=utf-8');
        $product = $this->model->findById($id);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado']);
            return;
        }

        // também retornar lista de categorias para popular selects
        $categoryModel = new \Models\Category();
        $categories = $categoryModel->findAll();

        echo json_encode(['product' => $product, 'categories' => $categories]);
    }

    public function store()
    {
        $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

        $data = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
        $data = $data ?: [];
        
        if (empty($data['name']) || !isset($data['price'])) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Nome e preço do produto são obrigatórios']);
            } else {
                echo 'Nome e preço do produto são obrigatórios';
            }
            return;
        }
        
        // validar se o nome do produto já existe
        $allProducts = $this->model->getAll();
        foreach ($allProducts as $prod) {
            if (isset($prod['name']) && strcasecmp($prod['name'], $data['name']) === 0) {
                http_response_code(409);
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Já existe um produto com esse nome']);
                } else {
                    echo 'Já existe um produto com esse nome';
                }
                return;
            }
        }
        
        //verificar se a categoria existe, se foi passada
        if (isset($data['category_id'])) {
            $categoryModel = new \Models\Category();
            $category = $categoryModel->findById((int)$data['category_id']);
            if (!$category) {
                http_response_code(400);
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Categoria inválida']);
                } else {
                    echo 'Categoria inválida';
                }
                return;
            }
        }
        
        // verificar se os campos numéricos são válidos e positivos
        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Preço inválido. Deve ser dado numérico e positivo']);
            } else {
                echo 'Preço inválido. Deve ser dado numérico e positivo';
            }
            return;
        }
        if (isset($data['stock_qty']) && (!is_numeric($data['stock_qty']) || $data['stock_qty'] < 0)) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Quantidade em estoque inválida']);
            } else {
                echo 'Quantidade em estoque inválida';
            }
            return;
        }

        $product = $this->model->create($data);
        
        // Se foi criado com estoque, criar movimentação inicial automática
        if (isset($data['stock_qty']) && (int)$data['stock_qty'] > 0) {
            $stockMovementModel = new \Models\Stock_movement();
            $current = \Middlewares\ApiAuthMiddleware::check();
            // garantir um user_id válido para a inserção (se não houver usuário, usar 0)
            $userId = $current ? $current['id'] : 0;

            $stockMovementModel->create([
                'product_id' => $product['id'],
                'qty' => (int)$data['stock_qty'],
                'type' => 'in',
                'reason' => 'Saldo inicial do produto ' . ($product['name'] ?? ''),
                'user_id' => $userId
            ]);
        }

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(201);
            echo json_encode(['product' => $product]);
            return;
        }

        header('Location: /products');
    }

    public function update($id)
    {
        $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

        $data = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
        $data = $data ?: [];
        
        if (empty($data['name']) || !isset($data['price'])) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Nome e preço do produto são obrigatórios']);
            } else {
                echo 'Nome e preço do produto são obrigatórios';
            }
            return;
        }
        
        // verificar se os campos numéricos são válidos e positivos
        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Preço inválido. Deve ser dado numérico e positivo']);
            } else {
                echo 'Preço inválido. Deve ser dado numérico e positivo';
            }
            return;
        }
        if (isset($data['stock_qty']) && (!is_numeric($data['stock_qty']) || $data['stock_qty'] < 0)) {
            http_response_code(400);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Quantidade em estoque inválida']);
            } else {
                echo 'Quantidade em estoque inválida';
            }
            return;
        }
        
        // validar se o nome do produto já existe, para que não seja possível editar para um nome já existente
        $allProducts = $this->model->getAll();
        foreach ($allProducts as $prod) {
            if ($prod['id'] != $id && isset($prod['name']) && strcasecmp($prod['name'], $data['name']) === 0) {
                http_response_code(409);
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Já existe um produto com esse nome']);
                } else {
                    echo 'Já existe um produto com esse nome';
                }
                return;
            }
        }

        $product = $this->model->update((int)$id, $data);
        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['product' => $product]);
            return;
        }

        header('Location: /products');
    }

    public function delete($id)
    {
        $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

        $success = $this->model->delete((int)$id);
        
        if (!$success) {
            http_response_code(404);
            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Produto não encontrado ou já foi deletado']);
            } else {
                echo 'Produto não encontrado ou já foi deletado';
            }
            return;
        }

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode(['message' => 'Produto deletado com sucesso']);
            return;
        }

        header('Location: /products');
    }
    
}