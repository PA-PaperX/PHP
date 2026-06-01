<?php

function sendDiscordWebhook($title, $description, $fields = [], $color = "3447003", $imageUrl = null, $type = 'issue') {
    // 3447003 is Blue. Use 16738740 for Orange, 3066993 for Green, 15158332 for Red
    
    $webhooks = [
        'issue' => 'https://discord.com/api/webhooks/1509181086184509482/JzBIWeCht_Z65Ek_gCl66_yaaEDoqIWER7YaMLbuByeDVNh2sgxRJPTjJW5Vcfn4PuBB',
        'borrow' => 'https://discord.com/api/webhooks/1509286652667756555/tOkqn3f6xG0An_-4RS5JwVSooL4AistPGALk1e0Q4ZjpySlw_VM9CAaW8Qoukceldona',
        'accept' => 'https://discord.com/api/webhooks/1509287248993194055/Hu1ct9YuTbiWVplaGjO3cKokTS5xdD58P0GQqduFkZwHDkKfMJbAl0zEib8koOMeV0u9',
        'resolve' => 'https://discord.com/api/webhooks/1509287456716099595/lYk2xH8-gjqjkVUAGPqCETSGfAZwMw4fgsvQtxV1OHoDxV8p5GWCLEhQqkubFVbQv-sJ',
        'ticket' => 'https://discord.com/api/webhooks/1509287621015240856/_jeRtgbOHOVT6SF27EIDq3c4mwdkCMFEB7XRviGZlzP4SP5U7ToVSmTMd3Ipv-o7cgol'
    ];
    
    $webhookUrl = $webhooks[$type] ?? $webhooks['issue'];
    
    if (empty($webhookUrl)) {
        return false;
    }

    $timestamp = date("c", strtotime("now"));

    $embed = [
        "title" => $title,
        "description" => $description,
        "type" => "rich",
        "timestamp" => $timestamp,
        "color" => hexdec(ltrim($color, '#')),
        "fields" => $fields,
        "footer" => [
            "text" => "ไอย๊าห์ Iya - IT Service System"
        ]
    ];

    if ($imageUrl) {
        $embed["image"] = ["url" => $imageUrl];
    }

    $json_data = json_encode([
        "embeds" => [$embed]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
