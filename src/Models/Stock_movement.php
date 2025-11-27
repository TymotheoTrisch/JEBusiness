<?php
namespace Models;

use Core\Database;

class Stock_movement
{
    protected $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }
    
    public function getAll()
    {
        $stmt = $this->pdo->query('SELECT * FROM stock_movements ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }
    
    public function findById($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stock_movements WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO stock_movements (product_id, qty, type, reason, user_id) VALUES (:product_id, :qty, :type, :reason, :user_id)");
        $stmt->execute([
            ':product_id' => $data['product_id'],
            ':qty' => $data['qty'],
            ':type' => $data['type'],
            ':reason' => $data['reason'] ?? null,
            ':user_id' => $data['user_id']
        ]);
        return $this->pdo->lastInsertId();
    }
    
        
    public function update($id, array $data)
    {
        $stmt = $this->pdo->prepare("UPDATE stock_movements SET product_id = :product_id, qty = :qty, type = :type, reason = :reason, user_id = :user_id WHERE id = :id");
        return $stmt->execute([
            ':product_id' => $data['product_id'],
            ':qty' => $data['qty'],
            ':type' => $data['type'],
            ':reason' => $data['reason'] ?? null,
            ':user_id' => $data['user_id'],
            ':id' => $id
        ]);
    }
    
    public function delete($id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM stock_movements WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
    
    
}
