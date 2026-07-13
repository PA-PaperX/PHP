<?php
namespace App\Controllers;

use App\Services\IssueService;
use App\Core\Request;
use App\Core\Validation\Validator;
use App\Core\Helpers\UploadHelper;

class IssueController extends Controller
{
    private $service;
    private $request;

    public function __construct(IssueService $service, Request $request)
    {
        $this->service = $service;
        $this->request = $request;
    }

    public function index()
    {
        $user = $this->request->attribute('user');
        $issues = $this->service->getIssuesForUser($user);
        return $this->success($issues, "Issues retrieved successfully");
    }
    
    public function show()
    {
        $validated = Validator::validate($this->request, [
            'id' => 'required|numeric'
        ]);
        
        return $this->success(null, "Show issue " . $validated['id']);
    }
    
    public function store()
    {
        $user = $this->request->attribute('user');
        $validated = Validator::validate($this->request, [
            'title' => 'required',
            'category' => 'required'
        ]);
        
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = UploadHelper::uploadImage($_FILES['image']);
        }

        $issueId = $this->service->createIssue(
            $user['id'], 
            $validated['title'], 
            $validated['category'], 
            $this->request->input('description', ''), 
            $this->request->input('location', ''), 
            $this->request->input('lat'), 
            $this->request->input('lng'), 
            $imagePath
        );

        return $this->success(['id' => $issueId], "Issue reported successfully", 201);
    }
    
    public function uploadSlip()
    {
        $user = $this->request->attribute('user');
        $validated = Validator::validate($this->request, [
            'id' => 'required|numeric'
        ]);

        if (!isset($_FILES['slip_image']) || $_FILES['slip_image']['error'] !== UPLOAD_ERR_OK) {
            return $this->error("กรุณาอัปโหลดรูปภาพสลิป", 400);
        }

        // Secure Upload
        $slipImagePath = UploadHelper::uploadImage($_FILES['slip_image'], 'slips');

        // Pass the actual absolute path to the moved file
        $absolutePath = realpath(__DIR__ . '/../../') . $slipImagePath;

        // Process Slip logic (Verify PromptPay)
        $slipData = $this->service->processSlip($validated['id'], $user['id'], $slipImagePath, $absolutePath);

        return $this->success($slipData, "ตรวจสอบสลิปและบันทึกการชำระเงินสำเร็จ");
    }

    public function update()
    {
        $validated = Validator::validate($this->request, [
            'id' => 'required|numeric'
        ]);
        return $this->success(null, "Update issue " . $validated['id']);
    }
    
    public function destroy()
    {
        $validated = Validator::validate($this->request, [
            'id' => 'required|numeric'
        ]);
        return $this->success(null, "Delete issue " . $validated['id']);
    }
}
