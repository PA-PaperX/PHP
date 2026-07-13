<?php
require_once __DIR__ . '/config/easyslip.php';
require_once __DIR__ . '/services/EasySlipService.php';

$tmpPath = 'C:\Users\Administrator\Downloads\c795e27a-871f-475b-9577-bb2c88127a57.jpg';

// Verify with EasySlip
$verifyResult = EasySlipService::verifySlip($tmpPath);

if (!$verifyResult['success']) {
    echo "FAILED: " . $verifyResult['message'] . "\n";
    exit;
}

$slipData = $verifyResult['data'];
if (isset($slipData['isDuplicate']) && $slipData['isDuplicate'] === true) {
    echo "FAILED: สลิปนี้ถูกใช้งานไปแล้ว (Duplicate Slip)\n";
    exit;
}

$rawSlip = $slipData['rawSlip'] ?? [];
$receiverProxy = $rawSlip['receiver']['proxy']['account'] ?? '';
$receiverBank = $rawSlip['receiver']['account']['bank']['account'] ?? '';
$receiverAccount = $receiverProxy ?: $receiverBank;

preg_match('/\d{4}$/', str_replace(['-', 'x', 'X', '*'], '', $receiverAccount), $slipLast4);
$expectedLast4 = substr(PROMPTPAY_ID, -4);

// ตรวจสอบจำนวนเงิน (อนุญาตให้โอนเกินได้ แต่ห้ามขาด)
$amountInSlip = (float)($slipData['amountInSlip'] ?? 0);
$expectedAmount = 1.00; // สมมติว่าค่าธรรมเนียมการแจ้งปัญหาคือ 1.00 บาท (ควรดึงจาก DB หรือ Settings จริงๆ)

if ($amountInSlip < $expectedAmount) {
    echo "FAILED: ยอดเงินไม่ถูกต้อง (ต้องชำระขั้นต่ำ " . number_format($expectedAmount, 2) . " บาท)\n";
    exit;
}

if (empty($slipLast4) || $slipLast4[0] !== $expectedLast4) {
    echo "FAILED: สลิปนี้โอนไปผิดบัญชี (รับเงินไม่ตรงกับระบบ)\n";
    echo "EXPECTED: $expectedLast4, GOT: " . ($slipLast4[0] ?? 'EMPTY') . "\n";
    exit;
}

echo "SUCCESS!\n";
