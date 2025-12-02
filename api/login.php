<?php
// api/login.php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$lineUserId = $input['lineUserId'] ?? '';

if (empty($username) || empty($lineUserId)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']); exit;
}

// 1. ตรวจสอบ Username + Password + LINE UID
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND line_user_id = ?"); 
$stmt->execute([$username, $password, $lineUserId]); 
$user = $stmt->fetch();

if ($user) {
    // ** เพิ่มการเช็ค Active **
    if ($user['active'] == 0) {
        echo json_encode(['status' => 'error', 'message' => 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ']);
        exit;
    }

    // สร้าง Session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['line_user_id'] = $user['line_user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];

    // 2. เปลี่ยน Rich Menu
    $richMenuId = RICHMENU_GUEST; 
    if ($user['role'] == 'admin') $richMenuId = RICHMENU_ADMIN;
    if ($user['role'] == 'teacher') $richMenuId = RICHMENU_TEACHER;
    if ($user['role'] == 'student') $richMenuId = RICHMENU_STUDENT;

    linkRichMenu($lineUserId, $richMenuId, CHANNEL_ACCESS_TOKEN);

    // 3. แจ้งเตือนเข้าไลน์
    $roleTH = ($user['role']=='student') ? 'นิสิต' : (($user['role']=='teacher') ? 'อาจารย์' : 'ผู้ดูแลระบบ');
    $msg = "🔓 เข้าสู่ระบบสำเร็จ!\nยินดีต้อนรับคุณ {$user['name']}\nสถานะ: $roleTH\n\n(ระบบกำลังโหลดเมนูใช้งาน...)";
    pushLineMessage($lineUserId, $msg, CHANNEL_ACCESS_TOKEN);

    echo json_encode(['status' => 'success', 'role' => $user['role']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง หรือบัญชี LINE ไม่ตรงกัน']);
}

// Functions
function linkRichMenu($userId, $richMenuId, $token) {
    $url = "https://api.line.me/v2/bot/user/$userId/richmenu/$richMenuId";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>["Authorization: Bearer $token", "Content-Length: 0"], CURLOPT_SSL_VERIFYPEER=>false]);
    curl_exec($ch); curl_close($ch);
}
function pushLineMessage($userId, $text, $token) {
    $ch = curl_init("https://api.line.me/v2/bot/message/push");
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode(["to"=>$userId,"messages"=>[["type"=>"text","text"=>$text]]]), CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>["Authorization: Bearer $token","Content-Type: application/json"], CURLOPT_SSL_VERIFYPEER=>false]);
    curl_exec($ch); curl_close($ch);
}
?>