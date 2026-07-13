<?php
// backend/services/EasySlipService.php

require_once __DIR__ . '/../config/easyslip.php';

class EasySlipService {
    public static function verifySlip($imagePath) {
        $url = 'https://api.easyslip.com/v2/verify/bank';
        $apiKey = EASYSLIP_API_KEY;
        
        if ($apiKey === 'YOUR_EASYSLIP_API_KEY_HERE') {
            return [
                'success' => false,
                'message' => 'API Key ไม่ถูกต้อง กรุณาตั้งค่า EASYSLIP_API_KEY'
            ];
        }

        $cfile = new CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath));

        $data = [
            'image' => $cfile
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error
            ];
        }

        $result = json_decode($response, true);
        $result = is_array($result) ? $result : [];
        
        if ($httpCode >= 200 && $httpCode < 300 && !empty($result['success'])) {
            return [
                'success' => true,
                'data' => $result['data'] ?? []
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'เกิดข้อผิดพลาดในการตรวจสอบสลิป',
                'raw' => $result
            ];
        }
    }
}
