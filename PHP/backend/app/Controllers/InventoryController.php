<?php
namespace App\Controllers;

use App\Services\InventoryService;
use App\Core\Request;
use App\Core\Validation\Validator;
use App\Core\Helpers\UploadHelper;

class InventoryController extends Controller {
    private $service;
    private $request;

    public function __construct(InventoryService $service, Request $request) {
        $this->service = $service;
        $this->request = $request;
    }

    public function index() {
        $equipment = $this->service->getAllEquipment();
        return $this->success(['equipment' => $equipment], "Inventory List");
    }

    public function store() {
        $data = $this->request->all();
        
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = UploadHelper::uploadImage($_FILES['image'], 'equipment');
        }

        $id = $this->service->createEquipment($data, $imagePath);
        return $this->success(['id' => $id], "Equipment created successfully", 201);
    }

    public function update() {
        $validated = Validator::validate($this->request, [
            'id' => 'required|numeric'
        ]);

        $data = $this->request->all();
        
        $imagePath = false;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = UploadHelper::uploadImage($_FILES['image'], 'equipment');
        }

        $this->service->updateEquipment($validated['id'], $data, $imagePath);
        return $this->success(null, "Equipment updated successfully");
    }

    public function destroy() {
        $validated = Validator::validate($this->request, [
            'id' => 'required|numeric'
        ]);

        $this->service->deleteEquipment($validated['id']);
        return $this->success(null, "Equipment deleted successfully");
    }
}
