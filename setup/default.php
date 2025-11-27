<?php
// setup/default.php
// --------------------------------------------------------
// 1. SECURITY CHECK
// --------------------------------------------------------
require_once '../config/security.php';
checkLogin('admin'); // บังคับ Admin เท่านั้น

header('Content-Type: text/html; charset=utf-8');

// --------------------------------------------------------
// 2. INCLUDE CONFIG (แก้ Path ให้ถูกต้อง)
// --------------------------------------------------------
// เดิม: require_once 'config/line_config.php';
// แก้เป็น:
if (!file_exists('../config/line_config.php')) {
    die("❌ Error: ไม่พบไฟล์ config/line_config.php กรุณารัน rich_menu.php ก่อน");
}
require_once '../config/line_config.php';

// ตรวจสอบว่ามีค่า RICHMENU_GUEST หรือไม่
if (!defined('RICHMENU_GUEST')) {
    die("❌ Error: ไม่พบค่า RICHMENU_GUEST ในไฟล์ config กรุณากลับไปรัน rich_menu.php ใหม่อีกครั้ง");
}

$targetMenuId = RICHMENU_GUEST; 

// --------------------------------------------------------
// 3. LOGIC (ยิง API ไปเปลี่ยนเมนูให้ทุกคน)
// --------------------------------------------------------
$ch = curl_init("https://api.line.me/v2/bot/user/all/richmenu/$targetMenuId");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . CHANNEL_ACCESS_TOKEN,
        "Content-Length: 0"
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ตั้งค่า Rich Menu Default</title>
<style>
body {font-family:"Prompt",sans-serif;background:#f7f8fc;margin:0;padding:40px;}
.card {max-width:800px;margin:auto;background:#fff;padding:30px;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,.08);}
h1 {margin-top:0;font-size:30px;color:#3630a3;}
.status {font-size:20px;margin-top:20px;padding:15px;border-radius:10px;}
.success {background:#e8fff2;color:#00a859;border-left:6px solid #00a859;}
.error {background:#ffecec;color:#ff3b30;border-left:6px solid #ff3b30;}
.btn {display:block;margin:30px auto 0;background:#4b48df;color:#fff;border:none;padding:16px 50px;font-size:20px;border-radius:10px;cursor:pointer;transition:.3s;text-decoration:none;text-align:center;}
.btn:hover {background:#3b39c8;}
code {background:#272822;color:#f8f8f2;padding:10px;border-radius:8px;display:block;margin-top:15px;overflow:auto;}
</style>
</head>
<body>

<div class="card">
    <h1>Set Default Menu Result</h1>

    <p>กำลังตั้งค่าเมนูเริ่มต้นเป็น:</p>
    <code><?php echo $targetMenuId; ?></code>

    <?php if ($httpCode == 200): ?>
        <div class="status success">
            ✅ <b>สำเร็จ!</b> <br>
            ระบบได้เปลี่ยนเมนูเริ่มต้นของทุกคนเป็น "Guest Menu" เรียบร้อยแล้ว
        </div>
        <a href="#" onclick="window.close()" class="btn">เสร็จสิ้น (ปิดหน้าต่าง)</a>

    <?php else: ?>
        <div class="status error">
            ❌ <b>ตั้งค่าไม่สำเร็จ</b><br>
            HTTP CODE: <?php echo $httpCode; ?>
        </div>
        <p>Response จาก LINE:</p>
        <code><?php echo $result; ?></code>
        <a href="rich_menu.php" class="btn" style="background:#666;">กลับไปลองใหม่</a>
    <?php endif; ?>
</div>

</body>
</html>