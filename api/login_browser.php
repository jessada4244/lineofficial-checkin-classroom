<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php';

date_default_timezone_set('Asia/Bangkok');
$input = json_decode(file_get_contents('php://input'), true);
$step = $input['step'] ?? ''; // 'verify_user' หรือ 'verify_otp'

// ==========================================
// STEP 1: ตรวจสอบ Username/Pass และส่ง OTP
// ==========================================
if ($step === 'verify_user') {
    $username = $input['username'];
    $password = $input['password'];

    // 1. เช็ค Username/Password
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
        exit;
    }

    if (empty($user['line_user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'บัญชีนี้ยังไม่ได้ผูกกับ LINE (กรุณาลงทะเบียนผ่าน LINE ก่อน)']);
        exit;
    }

    // 2. สร้าง OTP และบันทึก
    $otp = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes')); // หมดอายุใน 5 นาที

    $updateStmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
    $updateStmt->execute([$otp, $expiry, $user['id']]);

    // 3. ส่ง OTP เข้า LINE
    $msg = "🔐 รหัส OTP สำหรับเข้าสู่ระบบคือ: " . $otp . "\n(รหัสมีอายุ 5 นาที)";
    pushLineMessage($user['line_user_id'], $msg, CHANNEL_ACCESS_TOKEN);

    echo json_encode(['status' => 'success', 'message' => 'ส่ง OTP ไปยัง LINE แล้ว']);
}

// ==========================================
// STEP 2: ตรวจสอบ OTP และ Login
// ==========================================
elseif ($step === 'verify_otp') {
    $username = $input['username'];
    $otpInput = $input['otp'];

    // 1. ดึงข้อมูลมาเช็ค OTP
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User Not Found']);
        exit;
    }

    // 2. ตรวจสอบความถูกต้องและเวลาหมดอายุ
    if ($user['otp_code'] !== $otpInput) {
        echo json_encode(['status' => 'error', 'message' => 'รหัส OTP ไม่ถูกต้อง']);
        exit;
    }
    
    if (strtotime($user['otp_expiry']) < time()) {
        echo json_encode(['status' => 'error', 'message' => 'รหัส OTP หมดอายุแล้ว กรุณาขอใหม่']);
        exit;
    }

    // 3. Login สำเร็จ -> สร้าง Session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['line_user_id'] = $user['line_user_id'];

    // เคลียร์ OTP ทิ้ง
    $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE id = ?")->execute([$user['id']]);

    echo json_encode(['status' => 'success', 'role' => $user['role']]);
}

// ฟังก์ชันส่งไลน์ (Copy มาจากไฟล์เดิม)
function pushLineMessage($userId, $text, $token) {
    $url = "https://api.line.me/v2/bot/message/push";
    $body = json_encode([
        "to" => $userId,
        "messages" => [[ "type" => "text", "text" => $text ]]
    ]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch); curl_close($ch);
}
?>