<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php';

// รับค่า JSON
$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$lineUserId = $input['lineUserId'] ?? '';

if (empty($username) || empty($lineUserId)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// 1. ตรวจสอบ Username (แบบง่าย ไม่ได้ Hash Password เพื่อเทสก่อน)
// ถ้าจะใช้จริงต้องเปลี่ยนเป็น password_verify()
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?"); 
$stmt->execute([$username, $password]); 
$user = $stmt->fetch();

if ($user) {
    // 2. อัปเดต Line User ID ของคนนี้ลง Database
    $update = $pdo->prepare("UPDATE users SET line_user_id = ? WHERE id = ?");
    $update->execute([$lineUserId, $user['id']]);

    // 3. เลือกเมนูตาม Role
    $richMenuId = RICHMENU_GUEST; // default
    if ($user['role'] == 'admin') $richMenuId = RICHMENU_ADMIN;
    if ($user['role'] == 'teacher') $richMenuId = RICHMENU_TEACHER;
    if ($user['role'] == 'student') $richMenuId = RICHMENU_STUDENT;

    // 4. ยิง API เปลี่ยนเมนู
    linkRichMenu($lineUserId, $richMenuId, CHANNEL_ACCESS_TOKEN);

    echo json_encode(['status' => 'success', 'role' => $user['role']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Username หรือ Password ไม่ถูกต้อง']);
}

// ฟังก์ชันยิง LINE API
function linkRichMenu($userId, $richMenuId, $token) {
    $url = "https://api.line.me/v2/bot/user/$userId/richmenu/$richMenuId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ปิด SSL Check (Localhost)
    curl_exec($ch);
    curl_close($ch);
}
?>