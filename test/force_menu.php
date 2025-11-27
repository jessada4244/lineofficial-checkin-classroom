<?php
// force_menu.php
require_once 'config/line_config.php';

// 1. ใส่ User ID ของคุณ (ดูได้จากหน้า Developers Console หรือใน Database ถ้าเคย Login)
// หรือถ้าไม่รู้ ให้ใช้โค้ดนี้เพื่อตั้งค่าให้ "ทุกคน" อีกรอบ
$userId = 'all'; 

// 2. เลือกเมนูที่ต้องการบังคับโชว์ (เอา ID จาก config มา)
$richMenuId = RICHMENU_GUEST; 

echo "กำลังบังคับเมนู ID: $richMenuId ให้กับ: $userId <br>";

$ch = curl_init("https://api.line.me/v2/bot/user/$userId/richmenu/$richMenuId");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . CHANNEL_ACCESS_TOKEN
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "<h1>✅ สำเร็จ! เปิดไลน์ดูได้เลยครับ</h1>";
} else {
    echo "<h1>❌ ไม่สำเร็จ (Code: $httpCode)</h1>";
    echo "Response: " . $result;
}
?>