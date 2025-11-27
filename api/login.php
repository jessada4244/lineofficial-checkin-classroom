<?php
// api/login.php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$lineUserId = $input['lineUserId'] ?? '';

if (empty($username) || empty($lineUserId)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// 1. ตรวจสอบ Username + Password + LINE UID
// ต้องตรงกันทั้ง 3 ค่า ถึงจะยอมให้ผ่าน
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND line_user_id = ?"); 
$stmt->execute([$username, $password, $lineUserId]); 
$user = $stmt->fetch();

if ($user) {
    // 2. เลือก Rich Menu ตาม Role
    $richMenuId = RICHMENU_GUEST; 
    if ($user['role'] == 'admin') $richMenuId = RICHMENU_ADMIN;
    if ($user['role'] == 'teacher') $richMenuId = RICHMENU_TEACHER;
    if ($user['role'] == 'student') $richMenuId = RICHMENU_STUDENT;

    // 3. ยิง API เปลี่ยนเมนู (Link Rich Menu)
    linkRichMenu($lineUserId, $richMenuId, CHANNEL_ACCESS_TOKEN);

    // 4. ส่งข้อความ Push เพื่อ Refresh หน้าจอ LINE ทันที
    $roleTH = ($user['role']=='student') ? 'นิสิต' : (($user['role']=='teacher') ? 'อาจารย์' : 'ผู้ดูแลระบบ');
    $msg = "🔓 เข้าสู่ระบบสำเร็จ!\nยินดีต้อนรับคุณ {$user['name']}\nสถานะ: $roleTH\n\n(ระบบกำลังโหลดเมนูใช้งาน...)";
    pushLineMessage($lineUserId, $msg, CHANNEL_ACCESS_TOKEN);

    echo json_encode(['status' => 'success', 'role' => $user['role']]);
} else {
    // กรณีไม่เจอ User (อาจเป็นเพราะ UID ไม่ตรง หรือรหัสผิด)
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง หรือคุณใช้บัญชี LINE ผิดในการเข้าสู่ระบบ']);
}

// --- Helper Functions ---

function linkRichMenu($userId, $richMenuId, $token) {
    $url = "https://api.line.me/v2/bot/user/$userId/richmenu/$richMenuId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Length: 0" // สำคัญมากสำหรับ POST แบบไม่มี Body
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

function pushLineMessage($userId, $text, $token) {
    $url = "https://api.line.me/v2/bot/message/push";
    $body = json_encode([
        "to" => $userId,
        "messages" => [[
            "type" => "text",
            "text" => $text
        ]]
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}
?>