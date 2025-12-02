<?php
// api/register.php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php';

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$name     = $input['name'] ?? '';
$role     = $input['role'] ?? ''; 
// ** แก้ตรงนี้: รับค่า edu_id แทน student_id **
$eduId    = $input['edu_id'] ?? null; 
$lineUserId = $input['line_user_id'] ?? null;

// 1. Validation
if (empty($username) || empty($password) || empty($name) || empty($role) || empty($lineUserId)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']); exit;
}
if (($role === 'student' || $role === 'teacher') && empty($eduId)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกรหัสประจำตัว (รหัสนิสิต/อาจารย์)']); exit;
}

// 2. เช็คข้อมูลซ้ำ
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? OR line_user_id = ?");
$stmtCheck->execute([$username, $lineUserId]);
if ($stmtCheck->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username หรือ LINE Account นี้ถูกใช้งานแล้ว']); exit;
}

// ** แก้ตรงนี้: เช็คซ้ำที่ edu_id **
if ($eduId) {
    $stmtCheckEdu = $pdo->prepare("SELECT id FROM users WHERE edu_id = ?");
    $stmtCheckEdu->execute([$eduId]);
    if ($stmtCheckEdu->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสประจำตัวนี้มีในระบบแล้ว']); exit;
    }
}

// 3. บันทึก (INSERT)
try {
    // ** แก้ตรงนี้: Insert ลงคอลัมน์ edu_id **
    $sql = "INSERT INTO users (username, password, name, role, edu_id, line_user_id, active) VALUES (?, ?, ?, ?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$username, $password, $name, $role, $eduId, $lineUserId])) {
        
        // แจ้งเตือนแอดมิน
        $notifyMsg = "🆕 มีสมาชิกใหม่สมัครเข้ามา!\n\n";
        $notifyMsg .= "👤 ชื่อ: $name\n";
        $notifyMsg .= "🏷️ สถานะ: ".strtoupper($role)."\n";
        $notifyMsg .= "🆔 รหัส: $eduId\n";
        $notifyMsg .= "📱 Username: $username";

        notifyAllAdmins($pdo, $notifyMsg, CHANNEL_ACCESS_TOKEN);

        echo json_encode(['status' => 'success', 'message' => 'สมัครสมาชิกสำเร็จ!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'บันทึกข้อมูลไม่สำเร็จ']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}

function notifyAllAdmins($pdo, $text, $token) {
    $stmt = $pdo->query("SELECT line_user_id FROM users WHERE role = 'admin' AND line_user_id IS NOT NULL");
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($admins)) {
        $url = "https://api.line.me/v2/bot/message/multicast";
        foreach (array_chunk($admins, 150) as $chunk) {
            $body = json_encode(["to" => $chunk, "messages" => [[ "type" => "text", "text" => $text ]]]);
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$body, CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_HTTPHEADER=>["Content-Type: application/json", "Authorization: Bearer $token"]]);
            curl_exec($ch); curl_close($ch);
        }
    }
}
?>