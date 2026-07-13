<?php
namespace App\Services;

use App\Repositories\InventoryRepository;
use App\Core\Exceptions\AppException;
use App\Core\Exceptions\ValidationException;

class InventoryService {
    private $inventoryRepo;

    public function __construct(InventoryRepository $inventoryRepo) {
        $this->inventoryRepo = $inventoryRepo;
    }

    public function getAllEquipment() {
        return $this->inventoryRepo->getAll();
    }

    public function createEquipment($data, $imagePath = null) {
        $name = trim($data['name'] ?? '');
        $category = trim($data['category'] ?? '');
        $description = trim($data['description'] ?? '');
        $quantity = (int)($data['quantity'] ?? 1);
        
        if (empty($name) || empty($category)) {
            throw new ValidationException("Name and Category are required", 400, ['name' => 'Required', 'category' => 'Required']);
        }
        if ($quantity < 1) {
            throw new ValidationException("Quantity must be at least 1", 400, ['quantity' => 'Invalid quantity']);
        }

        return $this->inventoryRepo->create($name, $category, $description, $quantity, $imagePath);
    }

    public function updateEquipment($id, $data, $imagePath = false) {
        $equipment = $this->inventoryRepo->findById($id);
        if (!$equipment) {
            throw new AppException("Equipment not found", 404);
        }

        $name = isset($data['name']) ? trim($data['name']) : $equipment['name'];
        $category = isset($data['category']) ? trim($data['category']) : $equipment['category'];
        $description = isset($data['description']) ? trim($data['description']) : $equipment['description'];
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : $equipment['quantity'];

        if (empty($name) || empty($category)) {
            throw new ValidationException("Name and Category are required", 400, ['name' => 'Required', 'category' => 'Required']);
        }

        if ($quantity < 1) {
            throw new ValidationException("Quantity must be at least 1", 400, ['quantity' => 'Invalid quantity']);
        }

        // Calculate new available
        $diff = $quantity - $equipment['quantity'];
        $available = $equipment['available'] + $diff;
        
        if ($available < 0) {
            throw new AppException("ไม่สามารถลดจำนวนลงขนาดนี้ได้ เนื่องจากมีของบางชิ้นกำลังถูกยืมอยู่", 400);
        }

        $this->inventoryRepo->update($id, $name, $category, $description, $quantity, $available, $imagePath);
    }

    public function deleteEquipment($id) {
        $equipment = $this->inventoryRepo->findById($id);
        if (!$equipment) {
            throw new AppException("Equipment not found", 404);
        }
        
        if ($equipment['available'] < $equipment['quantity']) {
            throw new AppException("ไม่สามารถลบอุปกรณ์นี้ได้ เนื่องจากกำลังถูกยืมอยู่", 400);
        }

        $this->inventoryRepo->delete($id);
    }
}
