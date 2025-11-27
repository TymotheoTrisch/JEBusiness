<?php

namespace Models;

use Core\Database;

class Product
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function getAll()
    {
        $stmt = $this->pdo->query('SELECT * FROM products ORDER BY name');
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $product['category'] = $this->getCategory($product);
        }

        return $products;
    }
    
    public function getCategory($product)
    {
        if (!$product || !isset($product['category_id'])) return null;

        $categoryModel = new \Models\Category();
        return $categoryModel->findById($product['category_id']);
    }


    public function findById($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        
        $product = $stmt->fetch();

        if ($product) {
            $product['category'] = $this->getCategory($product);
            return $product;
        }

        return null;
    }

    public function create(array $data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO products (name, description, price, stock_qty, category_id, image_path, thumbnail_path, is_active) VALUES (:name, :description, :price, :stock_qty, :category_id, :image_path, :thumbnail_path, :is_active)");
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'],
            ':stock_qty' => $data['stock_qty'] ?? 0,
            ':category_id' => $data['category_id'] ?? null,
            ':image_path' => $data['image_path'] ?? './a/a',
            ':thumbnail_path' => $data['thumbnail_path'] ?? './a/a',
            ':is_active' => $data['is_active'] ?? 1
        ]);
        return $this->findById($this->pdo->lastInsertId());
    }

    public function update(int $id, array $data)
    {
        $stmt = $this->pdo->prepare("UPDATE products SET name = :name, description = :description, price = :price, stock_qty = :stock_qty, category_id = :category_id, image_path = :image_path, thumbnail_path = :thumbnail_path, is_active = :is_active WHERE id = :id");
        $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'],
            ':stock_qty' => $data['stock_qty'] ?? 0,
            ':category_id' => $data['category_id'] ?? null,
            ':image_path' => $data['image_path'] ?? null,
            ':thumbnail_path' => $data['thumbnail_path'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]);
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
