
<?php
// fix_my_menu.php
require_once 'config/line_config.php';

// *** ใส่ User ID  ***
$myUserId = 'Ub7e74e1847e675152553e08898635861';

// เลือกเมนูที่จะเทส (เอา Guest ก่อน)
$targetMenuId = RICHMENU_GUEST;

echo "<h2>🛠️ เริ่มปฏิบัติการแก้เมนู...</h2>";
echo "Target User: $myUserId <br>";
echo "Target Menu: $targetMenuId <br><hr>";

// 1. สั่ง UNLINK (ล้างเมนูเดิมที่ค้างอยู่ออก)
echo "1. กำลังล้างเมนูเดิม... ";
$ch = curl_init("https://api.line.me/v2/bot/user/$myUserId/richmenu");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . CHANNEL_ACCESS_TOKEN]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "✅ ล้างสำเร็จ!<br>";
} else {
    echo "⚠️ ล้างไม่ผ่าน (อาจจะไม่มีเมนูอยู่แล้ว) Code: $httpCode <br>";
}

// 2. ตรวจสอบว่าเมนู Guest มีรูปภาพจริงไหม?
echo "2. เช็คความสมบูรณ์ของเมนู Guest... ";
$ch = curl_init("https://api.line.me/v2/bot/richmenu/$targetMenuId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . CHANNEL_ACCESS_TOKEN]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
$info = json_decode($result, true);
curl_close($ch);

if (isset($info['richMenuId'])) {
    echo "✅ เมนูมีอยู่จริง (Size: " . $info['size']['width'] . "x" . $info['size']['height'] . ")<br>";
} else {
    die("❌ เมนูนี้ไม่มีอยู่จริง! กรุณารัน setup_richmenu_all.php ใหม่อีกรอบ");
}
// 3. สั่ง LINK ใหม่ (ยัดเยียดเมนู)
echo "3. กำลังยัดเยียดเมนูใหม่... ";
$ch = curl_init("https://api.line.me/v2/bot/user/$myUserId/richmenu/$targetMenuId");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// *** แก้ไขตรงนี้: เพิ่ม Content-Length: 0 ***
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . CHANNEL_ACCESS_TOKEN,
    "Content-Length: 0"
]);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "<h1>🎉 เสร็จสิ้น! เปิดมือถือดูได้เลย</h1>";
} else {
    echo "<h1>❌ พัง! Code: $httpCode</h1> Response: $result";
}
?>