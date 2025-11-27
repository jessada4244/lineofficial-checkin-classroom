<?php
// reset_all_users.php
set_time_limit(0); // ป้องกัน script timeout ถ้ายูสเซอร์เยอะ
require_once 'config/db.php';
require_once 'config/line_config.php';

echo "<h1>🔄 กำลังล้างเมนูส่วนตัวของทุกคน...</h1>";
echo "<p>ระบบกำลังเปลี่ยนทุกคนให้กลับไปใช้เมนูเริ่มต้น (Guest)</p><hr>";

try {
    // 1. ดึง User ID ของทุกคนที่มีในระบบ
    $stmt = $pdo->query("SELECT id, name, line_user_id FROM users WHERE line_user_id IS NOT NULL AND line_user_id != ''");
    $users = $stmt->fetchAll();

    $count = 0;
    $total = count($users);

    if ($total == 0) {
        echo "⚠️ ไม่พบ User ที่มี LINE ID ในระบบ<br>";
        exit;
    }

    // 2. วนลูปสั่งล้างเมนูทีละคน
    foreach ($users as $u) {
        $uid = $u['line_user_id'];
        $name = $u['name'];
        
        $ch = curl_init("https://api.line.me/v2/bot/user/$uid/richmenu");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . CHANNEL_ACCESS_TOKEN
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            echo "✅ ล้างสำเร็จ: $name <small>($uid)</small><br>";
            $count++;
        } else {
            // 404 แปลว่าเขาไม่ได้ผูกเมนูอะไรไว้อยู่แล้ว (คือใช้ Default อยู่แล้ว) ถือว่าปกติ
            if($httpCode == 404) {
                echo "⚪ ปกติ (ใช้ Default อยู่แล้ว): $name<br>";
            } else {
                echo "❌ Error ($httpCode): $name<br>";
            }
        }
        
        // หน่วงเวลาเล็กน้อยป้องกัน LINE บล็อก (ถ้าคนเยอะ)
        usleep(50000); // 0.05 วินาที
    }

    echo "<hr><h2>🎉 เสร็จสิ้น! ล้างไปทั้งหมด $count / $total คน</h2>";
    echo "ทุกคนจะกลับไปเห็นเมนู Guest (Guest Menu) โดยอัตโนมัติครับ";

} catch (Exception $e) {
    echo "Server Error: " . $e->getMessage();
}
?>