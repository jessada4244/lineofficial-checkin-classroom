<?php
require_once 'config/line_config.php';
$targetMenuId = RICHMENU_GUEST; // ใช้ ID เมนู Guest จาก config

// -------------------- LOGIC --------------------
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
.btn {display:block;margin:30px auto 0;background:#4b48df;color:#fff;border:none;padding:16px 50px;font-size:20px;border-radius:10px;cursor:pointer;transition:.3s;}
.btn:hover {background:#3b39c8;}
code {background:#272822;color:#f8f8f2;padding:10px;border-radius:8px;display:block;margin-top:15px;overflow:auto;}
</style>
</head>
<body>

<div class="card">
    <h1>Restart Rich Menu</h1>

    <p>Rich Menu ที่จะตั้งเป็นค่าเริ่มต้น:</p>
    <code><?php echo $targetMenuId; ?></code>

    <?php if ($httpCode == 200): ?>
        <div class="status success">
            ✅ สำเร็จ! <br>
            เมนู Guest ถูกกำหนดให้เป็นเมนูเริ่มต้นสำหรับผู้ใช้ทุกคน
        </div>
        <button class="btn" onclick="location.href='index.php'">กลับหน้าหลัก</button>

    <?php else: ?>
        <div class="status error">
            ❌ ตั้งค่าไม่สำเร็จ<br>
            CODE: <?php echo $httpCode; ?>
        </div>
        <code><?php echo $result; ?></code>
    <?php endif; ?>
</div>

</body>
</html>
