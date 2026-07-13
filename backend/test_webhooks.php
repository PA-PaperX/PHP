<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/discord.php';

// 1. Test Ticket
sendDiscordWebhook(
    "🎫 คำขอรีเซ็ตรหัสผ่านใหม่ (Ticket #999)", 
    "มีผู้ใช้ลืมรหัสผ่านและต้องการรีเซ็ตใหม่\n📧 **อีเมล:** testuser@gmail.com", 
    [], 
    "#8B5CF6", 
    null, 
    'ticket'
);

// 2. Test Borrow
sendDiscordWebhook(
    "📦 คำขอยืมอุปกรณ์ใหม่ (Borrow #999)", 
    "💻 **อุปกรณ์:** Test Laptop\n🔢 **จำนวน:** 1 ชิ้น\n📅 **กำหนดคืน:** 2026-05-30\n❓ **เหตุผล:** ทดสอบระบบ Webhook", 
    [["name" => "👤 ผู้แจ้ง", "value" => "testuser@gmail.com", "inline" => true]], 
    "#3B82F6", 
    "https://media1.tenor.com/m/kF0-Zw6qaBkAAAAC/itachi-uchiha.gif", 
    'borrow'
);

// 3. Test Issue
sendDiscordWebhook(
    "🚨 แจ้งซ่อมใหม่ (Issue #999)", 
    "📁 **หมวดหมู่:** Hardware\n📝 **หัวข้อ:** ทดสอบระบบแจ้งซ่อม\n📌 **รายละเอียด:** ทดสอบ Webhook แจ้งซ่อม\n📍 **สถานที่:** ห้อง Server", 
    [["name" => "👤 ผู้แจ้ง", "value" => "testuser@gmail.com", "inline" => true]], 
    "#F97316", 
    null, 
    'issue'
);

// 4. Test Accept
sendDiscordWebhook(
    "🛠️ แอดมินรับเรื่องแล้ว (Issue #999)", 
    "ปัญหา **ทดสอบระบบแจ้งซ่อม** กำลังถูกดำเนินการแก้ไข\n👨‍🔧 **รับเรื่องโดย:** testadmin@gmail.com", 
    [], 
    "#3B82F6", 
    null, 
    'accept'
);

// 5. Test Resolve
sendDiscordWebhook(
    "✅ ปิดงาน: แก้ไขเสร็จสิ้น (Issue #999)", 
    "ปัญหา **ทดสอบระบบแจ้งซ่อม** ได้รับการแก้ไขเรียบร้อยแล้ว\n👨‍🔧 **ปิดงานโดย:** testadmin@gmail.com\n📝 **รายละเอียดการแก้ไข:** ซ่อมเสร็จแล้วครับ", 
    [], 
    "#10B981", 
    null, 
    'resolve'
);

echo "Webhooks sent successfully.";
