<?php
namespace App\Repositories;

use PDO;

class InventoryRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM equipment ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM equipment WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($name, $category, $description, $quantity, $imagePath) {
        $stmt = $this->db->prepare("
            INSERT INTO equipment (name, category, description, quantity, available, image_path) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        // new equipment has available = quantity
        $stmt->execute([$name, $category, $description, $quantity, $quantity, $imagePath]);
        return $this->db->lastInsertId();
    }

    public function update($id, $name, $category, $description, $quantity, $available, $imagePath) {
        $sql = "UPDATE equipment SET name = ?, category = ?, description = ?, quantity = ?, available = ?";
        $params = [$name, $category, $description, $quantity, $available];
        
        if ($imagePath !== false) {
            $sql .= ", image_path = ?";
            $params[] = $imagePath;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->execute([$id]);
    }
}
