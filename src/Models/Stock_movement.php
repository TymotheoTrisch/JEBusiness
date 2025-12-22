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
        $stmt = $this->pdo->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, reason, created_by) VALUES (:product_id, :quantity, :movement_type, :reason, :created_by)");
        $ok = $stmt->execute([
            ':product_id' => $data['product_id'],
            ':quantity' => $data['quantity'],
            ':movement_type' => $data['movement_type'],
            ':reason' => $data['reason'] ?? null,
            ':created_by' => $data['created_by'] ?? null
        ]);

        if (!$ok) {
            return false;
        }

        return $this->pdo->lastInsertId();
    }
    
        
    public function update($id, array $data)
    {
        $stmt = $this->pdo->prepare("UPDATE stock_movements SET product_id = :product_id, quantity = :quantity, movement_type = :movement_type, reason = :reason, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id");
        return $stmt->execute([
            ':product_id' => $data['product_id'],
            ':quantity' => $data['quantity'],
            ':movement_type' => $data['movement_type'],
            ':reason' => $data['reason'] ?? null,
            ':updated_by' => $data['updated_by'] ?? null,
            ':updated_at' => $data['updated_at'] ?? null,
            ':id' => $id
        ]);
    }
    
    public function delete($id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM stock_movements WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
    
    
}
