<?php
namespace App\Repositories;

use PDO;

class IssueRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAllIssuesWithUserDetails() {
        $stmt = $this->db->query("
            SELECT i.*, u.username, u.email 
            FROM issues i 
            LEFT JOIN users u ON i.user_id = u.id 
            ORDER BY i.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIssuesByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM issues WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM issues WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($userId, $title, $category, $description, $location, $lat, $lng, $imagePath) {
        $stmt = $this->db->prepare('INSERT INTO issues (user_id, title, category, description, location, lat, lng, image_path, cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1.00)');
        $stmt->execute([$userId, $title, $category, $description, $location, $lat, $lng, $imagePath]);
        return (int)$this->db->lastInsertId();
    }

    public function markAsPaid($id, $slipImagePath) {
        $stmt = $this->db->prepare("
            UPDATE issues
            SET payment_status = 'paid', paid_at = NOW(), slip_image_path = ?
            WHERE id = ?
        ");
        $stmt->execute([$slipImagePath, $id]);
    }
}
