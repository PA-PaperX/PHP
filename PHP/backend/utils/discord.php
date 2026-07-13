<?php

function sendDiscordWebhook($type = 'issue', $options = []) {
    $webhooks = [
        'issue' => 'https://discord.com/api/webhooks/1509181086184509482/JzBIWeCht_Z65Ek_gCl66_yaaEDoqIWER7YaMLbuByeDVNh2sgxRJPTjJW5Vcfn4PuBB',
        'borrow' => 'https://discord.com/api/webhooks/1509286652667756555/tOkqn3f6xG0An_-4RS5JwVSooL4AistPGALk1e0Q4ZjpySlw_VM9CAaW8Qoukceldona',
        'accept' => 'https://discord.com/api/webhooks/1509287248993194055/Hu1ct9YuTbiWVplaGjO3cKokTS5xdD58P0GQqduFkZwHDkKfMJbAl0zEib8koOMeV0u9',
        'resolve' => 'https://discord.com/api/webhooks/1509287456716099595/lYk2xH8-gjqjkVUAGPqCETSGfAZwMw4fgsvQtxV1OHoDxV8p5GWCLEhQqkubFVbQv-sJ',
        'ticket' => 'https://discord.com/api/webhooks/1509287621015240856/_jeRtgbOHOVT6SF27EIDq3c4mwdkCMFEB7XRviGZlzP4SP5U7ToVSmTMd3Ipv-o7cgol'
    ];
    
    $webhookUrl = $webhooks[$type] ?? $webhooks['issue'];
    if (empty($webhookUrl)) return false;

    // Default icon based on event type
    $icons = [
        'new_issue' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/1f6a8.png', // 🚨
        'assigned' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/1f6e0.png', // 🛠️
        'technician' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/1f468-200d-1f4bb.png', // 👨‍💻
        'resolved' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/2705.png', // ✅
        'closed' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/2705.png', // ✅
        'system' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/2705.png', // ✅
        'cancelled' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/274c.png', // ❌
        'admin' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/26a0.png', // ⚠️
        'user' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/1f464.png', // 👤
        'borrow' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/1f4e6.png', // 📦
        'return' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/1f7e6.png', // 🟦
        'ticket' => 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/1f3ab.png' // 🎫
    ];
    
    $eventKey = $options['event'] ?? 'new_issue';
    $authorIconUrl = $icons[$eventKey] ?? 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/1f4cc.png'; // 📌
    $authorName = $options['author_name'] ?? 'IT Service System';
    
    $embed = [
        "author" => [
            "name" => $authorName,
            "icon_url" => $authorIconUrl
        ],
        "type" => "rich",
        "timestamp" => date("c"),
        "color" => hexdec(ltrim($options['color'] ?? '#3447003', '#')),
        "footer" => [
            "text" => "IT Service System" . (isset($options['ticket_id']) ? " • Ticket ID: #" . $options['ticket_id'] : "")
        ]
    ];
    
    if (!empty($options['title'])) $embed['title'] = $options['title'];
    if (!empty($options['description'])) $embed['description'] = $options['description'];
    if (!empty($options['fields'])) $embed['fields'] = $options['fields'];
    if (!empty($options['image_url'])) $embed['image'] = ["url" => $options['image_url']];

    $payload = [
        "username" => "IT Support Bot",
        "avatar_url" => $authorIconUrl,
        "embeds" => [$embed]
    ];

    $json_data = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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
