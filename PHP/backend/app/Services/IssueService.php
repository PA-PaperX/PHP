<?php
namespace App\Services;

use App\Repositories\IssueRepository;
use App\Core\Exceptions\AppException;
use App\Core\Exceptions\UnauthorizedException;

class IssueService {
    private $issueRepo;

    public function __construct(IssueRepository $issueRepo) {
        $this->issueRepo = $issueRepo;
    }

    public function getIssuesForUser($user) {
        if ($user['role'] === 'admin') {
            return $this->issueRepo->getAllIssuesWithUserDetails();
        } else {
            return $this->issueRepo->getIssuesByUserId($user['id']);
        }
    }

    public function createIssue($userId, $title, $category, $description, $location, $lat, $lng, $imagePath) {
        $issueId = $this->issueRepo->create($userId, $title, $category, $description, $location, $lat, $lng, $imagePath);
        
        // Notify Discord (Moved from Controller/Legacy)
        require_once __DIR__ . '/../../utils/discord.php';
        
        // Wait, Discord webhook might need user info, we can pass it if we want, or skip it
        $maxLen = 150;
        $truncatedDesc = mb_strlen($description) > $maxLen ? mb_substr($description, 0, $maxLen) . '... *<อ่านต่อในระบบ>*' : $description;
        $desc = "📌 **รายละเอียด:**\n> " . str_replace("\n", "\n> ", $truncatedDesc);
        
        $options = [
            'event' => 'new_issue',
            'author_name' => '🚨 New Issue Created',
            'title' => "Issue #{$issueId} • {$category}",
            'ticket_id' => $issueId,
            'color' => '#F39C12',
            'description' => $desc,
            'fields' => [
                ['name' => 'ความสำคัญ', 'value' => 'สูง', 'inline' => true],
                ['name' => 'สถานะ', 'value' => 'รอรับเรื่อง', 'inline' => true],
                ['name' => 'สถานที่', 'value' => empty($location) ? '-' : $location, 'inline' => true]
            ]
        ];
        
        sendDiscordWebhook('issue', $options); 
        
        return $issueId;
    }

    public function processSlip($issueId, $userId, $slipImagePath, $tmpPath) {
        // 1. Check ownership
        $issue = $this->issueRepo->findById($issueId);
        if (!$issue || $issue['user_id'] != $userId) {
            throw new UnauthorizedException("Issue not found or unauthorized");
        }

        // 2. Verify with EasySlip (Legacy logic adapted)
        require_once __DIR__ . '/../../services/EasySlipService.php';
        $verifyResult = \EasySlipService::verifySlip($tmpPath);

        if (!$verifyResult['success']) {
            throw new AppException($verifyResult['message'], 400);
        }

        $slipData = $verifyResult['data'];
        if (!empty($slipData['isDuplicate'])) {
            throw new AppException("สลิปนี้ถูกใช้งานไปแล้ว (Duplicate Slip)", 400);
        }

        // 3. Check Promtpay Receiver
        $rawSlip = $slipData['rawSlip'] ?? [];
        $receiverProxy = $rawSlip['receiver']['account']['proxy']['account'] ?? '';
        $receiverBank = $rawSlip['receiver']['account']['bank']['account'] ?? '';
        $receiverAccount = $receiverProxy ?: $receiverBank;

        preg_match('/\d{4}$/', str_replace(['-', 'x', 'X', '*'], '', $receiverAccount), $slipLast4);
        $expectedLast4 = defined('PROMPTPAY_ID') ? substr(PROMPTPAY_ID, -4) : '0000'; // Fallback if not defined

        $amountInSlip = (float)($slipData['amountInSlip'] ?? 0);
        $expectedAmount = 1.00;

        if ($amountInSlip < $expectedAmount) {
            throw new AppException("ยอดเงินไม่ถูกต้อง (ยอดขั้นต่ำ " . number_format($expectedAmount, 2) . " บาท)", 400);
        }

        if (empty($slipLast4) || $slipLast4[0] !== $expectedLast4) {
            $parsedAccount = empty($slipLast4) ? 'empty' : $slipLast4[0];
            throw new AppException("สลิปนี้โอนไปผิดบัญชี (ระบบเห็นบัญชีลงท้ายด้วย: $parsedAccount, คาดหวัง: $expectedLast4) Raw: " . json_encode($rawSlip['receiver']), 400);
        }

        // 4. Update Database
        $this->issueRepo->markAsPaid($issueId, $slipImagePath);

        return $slipData;
    }
}
