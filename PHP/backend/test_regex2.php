<?php
require_once __DIR__ . '/config/easyslip.php';

$tests = [
    'empty' => '',
    'proxy' => '1499900494606',
    'bank' => 'xxx-x-x3426-x',
    'other' => '0917478273'
];

$expectedLast4 = substr(PROMPTPAY_ID, -4);
echo "Expected Last 4: $expectedLast4\n\n";

foreach ($tests as $name => $receiverAccount) {
    preg_match('/\d{4}$/', str_replace(['-', 'x', 'X', '*'], '', $receiverAccount), $slipLast4);
    
    echo "Test $name ($receiverAccount): ";
    if (empty($slipLast4) || $slipLast4[0] !== $expectedLast4) {
        echo "REJECTED (Last 4 matched: " . ($slipLast4[0] ?? 'NONE') . ")\n";
    } else {
        echo "ACCEPTED\n";
    }
}
