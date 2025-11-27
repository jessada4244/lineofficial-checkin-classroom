<?php
// set_default.php
require_once 'config/line_config.php';

// ใช้ ID ของเมนู Guest ที่มีอยู่แล้วใน Config
$targetMenuId = RICHMENU_GUEST; 

echo "<h2>🔧 กำลังตั้งค่าเมนู Default...</h2>";
echo "Menu ID: $targetMenuId <br><hr>";

// ยิงคำสั่งไปที่ /user/all/richmenu/{id}
$ch = curl_init("https://api.line.me/v2/bot/user/all/richmenu/$targetMenuId");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . CHANNEL_ACCESS_TOKEN,
    "Content-Length: 0"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "<h1>✅ ตั้งค่า Default สำเร็จ!</h1>";
    echo "<p>ตอนนี้เมนู Guest จะเป็นเมนูเริ่มต้นสำหรับทุกคนแล้วครับ</p>";
    echo "<a href='check_status.php'>👉 กลับไปเช็คสถานะอีกครั้ง</a>";
} else {
    echo "<h1>❌ ตั้งค่าไม่สำเร็จ (Code: $httpCode)</h1>";
    echo "Response: <pre>$result</pre>";
}
?>