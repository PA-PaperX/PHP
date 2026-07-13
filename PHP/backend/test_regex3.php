<?php
$json = '{"amountInSlip":420,"rawSlip":{"payload":"0041000600000101030040220016189200313CTF044255102TH9104CEB4","transRef":"016189200313CTF04425","date":"2026-07-08T20:03:13+07:00","countryCode":"TH","amount":{"amount":420,"local":{"amount":420,"currency":"764"}},"fee":0,"ref1":"","ref2":"","ref3":"","sender":{"bank":{"id":"004","name":"\u0e18\u0e19\u0e32\u0e04\u0e32\u0e23\u0e01\u0e2a\u0e34\u0e01\u0e23\u0e44\u0e17\u0e22","short":"KBANK"},"account":{"name":{"th":"\u0e19.\u0e2a. \u0e01\u0e31\u0e19\u0e22\u0e23\u0e31\u0e15\u0e19\u0e4c \u0e23","en":"MS. KANYARAT R"},"bank":{"type":"BANKAC","account":"xxx-x-x2588-x"}}},"receiver":{"bank":{"id":"004","name":"\u0e18\u0e19\u0e32\u0e04\u0e32\u0e23\u0e01\u0e2a\u0e34\u0e01\u0e23\u0e44\u0e17\u0e22","short":"KBANK"},"account":{"name":{"th":"\u0e19.\u0e2a. \u0e0a\u0e19\u0e01\u0e19\u0e31\u0e19\u0e17\u0e4c \u0e23","en":"MS. CHANOKNAN R"},"bank":{"type":"BANKAC","account":"xxx-x-x3426-x"}},"merchantId":""}}}';

$slipData = json_decode($json, true);

$rawSlip = $slipData['rawSlip'] ?? [];
$receiverProxy = $rawSlip['receiver']['proxy']['account'] ?? '';
$receiverBank = $rawSlip['receiver']['account']['bank']['account'] ?? '';
$receiverAccount = $receiverProxy ?: $receiverBank;

var_dump($receiverProxy, $receiverBank, $receiverAccount);

preg_match('/\d{4}$/', str_replace(['-', 'x', 'X', '*'], '', $receiverAccount), $slipLast4);

var_dump($slipLast4);
