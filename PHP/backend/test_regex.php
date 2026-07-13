<?php
require_once __DIR__ . '/config/easyslip.php';

$slipData = [
    'receiver' => [
        'account' => [
            'bank' => [
                'account' => 'xxx-x-x3426-x'
            ]
        ]
    ]
];

$receiverProxy = $slipData['receiver']['proxy']['account'] ?? '';
$receiverBank = $slipData['receiver']['account']['bank']['account'] ?? '';
$receiverAccount = $receiverProxy ?: $receiverBank;

preg_match('/\d{4}$/', str_replace(['-', 'x', 'X', '*'], '', $receiverAccount), $slipLast4);
$expectedLast4 = substr(PROMPTPAY_ID, -4);

if (empty($slipLast4) || $slipLast4[0] !== $expectedLast4) {
    echo "FAILED: Expected $expectedLast4 but got " . ($slipLast4[0] ?? 'empty') . "\n";
} else {
    echo "SUCCESS\n";
}
