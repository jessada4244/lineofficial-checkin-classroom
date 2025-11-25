<?php
require_once '../config/db.php';
require_once '../config/line_config.php';

// รับค่า JSON จากหน้า LIFF Login
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'];
$password = $data['password'];
$lineUserId = $data['lineUserId']; // ส่งมาจาก LIFF (liff.getProfile)

// 1. ตรวจสอบ User/Pass ใน Database
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) { // แนะนำให้ Hash password
    
    // 2. อัปเดต line_user_id ลงในตาราง users
    $update = $pdo->prepare("UPDATE users SET line_user_id = ? WHERE id = ?");
    $update->execute([$lineUserId, $user['id']]);

    // 3. เลือก Rich Menu ตาม Role
    $richMenuIdToLink = '';
    if ($user['role'] == 'admin') {
        $richMenuIdToLink = RICHMENU_ADMIN; // ค่าจาก config
    } elseif ($user['role'] == 'teacher') {
        $richMenuIdToLink = RICHMENU_TEACHER;
    } else {
        $richMenuIdToLink = RICHMENU_STUDENT;
    }

    // 4. ยิง cURL ไปหา LINE เพื่อเปลี่ยนเมนู
    linkRichMenu($lineUserId, $richMenuIdToLink, CHANNEL_ACCESS_TOKEN);

    echo json_encode(['status' => 'success', 'role' => $user['role']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Login Failed']);
}

// ฟังก์ชันยิง API LINE
function linkRichMenu($userId, $richMenuId, $token) {
    $url = "https://api.line.me/v2/bot/user/$userId/richmenu/$richMenuId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token"
    ]);
    curl_exec($ch);
    curl_close($ch);
}
?>