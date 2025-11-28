<?php
// api/register.php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php'; // โหลด Token

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$name     = $input['name'] ?? '';
$role     = $input['role'] ?? ''; 
$studentId = $input['student_id'] ?? null;
$lineUserId = $input['line_user_id'] ?? null;

// 1. Validation (เหมือนเดิม)
if (empty($username) || empty($password) || empty($name) || empty($role) || empty($lineUserId)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']); exit;
}
if ($role === 'student' && empty($studentId)) {
    echo json_encode(['status' => 'error', 'message' => 'นิสิตต้องกรอกรหัสนิสิต']); exit;
}

// 2. เช็คข้อมูลซ้ำ (เหมือนเดิม)
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? OR line_user_id = ?");
$stmtCheck->execute([$username, $lineUserId]);
if ($stmtCheck->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username หรือ LINE Account นี้ถูกใช้งานแล้ว']); exit;
}
if ($role === 'student') {
    $stmtCheckStd = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
    $stmtCheckStd->execute([$studentId]);
    if ($stmtCheckStd->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสนิสิตนี้มีในระบบแล้ว']); exit;
    }
}

// 3. บันทึก
try {
    $sql = "INSERT INTO users (username, password, name, role, student_id, line_user_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$username, $password, $name, $role, $studentId, $lineUserId])) {
        
        // --- ส่วนที่เพิ่ม: แจ้งเตือนแอดมิน ---
        $notifyMsg = "🆕 มีสมาชิกใหม่สมัครเข้ามา!\n\n";
        $notifyMsg .= "👤 ชื่อ: $name\n";
        $notifyMsg .= "🏷️ สถานะ: ".strtoupper($role)."\n";
        if($role==='student') $notifyMsg .= "🆔 รหัสนิสิต: $studentId\n";
        $notifyMsg .= "📱 Username: $username";

        notifyAllAdmins($pdo, $notifyMsg, CHANNEL_ACCESS_TOKEN);
        // ----------------------------------

        echo json_encode(['status' => 'success', 'message' => 'สมัครสมาชิกสำเร็จ!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'บันทึกข้อมูลไม่สำเร็จ']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}

// ฟังก์ชันแจ้งเตือน (Copy มาวาง หรือ Include ไฟล์กลางก็ได้)
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