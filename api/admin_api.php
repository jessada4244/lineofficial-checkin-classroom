<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php'; // ต้องใช้ TOKEN เพื่อส่งข้อความ

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

// 1. ตรวจสอบสิทธิ์ Admin
$stmt = $pdo->prepare("SELECT id FROM users WHERE line_user_id = ? AND role = 'admin'");
$stmt->execute([$lineId]);
if (!$stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

try {
    
    // --- Action: ดึงรายชื่อผู้ใช้ทั้งหมด ---
    if ($action === 'get_all_users') {
        $stmtUsers = $pdo->query("SELECT id, username, name, role, student_id, created_at FROM users ORDER BY id DESC");
        $users = $stmtUsers->fetchAll();
        
        // นับจำนวนแยกประเภท
        $stats = [
            'teacher' => 0,
            'student' => 0,
            'total' => count($users)
        ];
        foreach ($users as $u) {
            if(isset($stats[$u['role']])) $stats[$u['role']]++;
        }

        echo json_encode(['status' => 'success', 'users' => $users, 'stats' => $stats]);
    }

    // --- Action: ลบผู้ใช้งาน ---
    elseif ($action === 'delete_user') {
        $userId = $input['user_id'];
        
        // ลบข้อมูลที่เกี่ยวข้อง (Attendance, ClassMember, Classrooms) 
        // *ในการใช้งานจริงควรทำ Soft Delete หรือแจ้งเตือนก่อน*
        $pdo->prepare("DELETE FROM attendance WHERE student_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM classroom_members WHERE student_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        
        echo json_encode(['status' => 'success']);
    }

    // --- Action: ส่งประกาศ (Broadcast) ---
    elseif ($action === 'broadcast') {
        $targetRole = $input['target_role']; // all, teacher, student
        $message = $input['message'];

        if (empty($message)) { echo json_encode(['status' => 'error', 'message' => 'ข้อความว่างเปล่า']); exit; }

        // 1. หา Line User ID ของกลุ่มเป้าหมาย
        if ($targetRole === 'all') {
            $sql = "SELECT line_user_id FROM users WHERE line_user_id IS NOT NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        } else {
            $sql = "SELECT line_user_id FROM users WHERE role = ? AND line_user_id IS NOT NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$targetRole]);
        }
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($recipients)) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบผู้รับในกลุ่มเป้าหมาย']);
            exit;
        }

        // 2. ส่งข้อความ (ใช้ Multicast API ของ LINE)
        // LINE อนุญาตให้ส่งได้ทีละ 500 คน (ในที่นี้ทำแบบง่าย ส่งทีละ 150 คน)
        $chunkedRecipients = array_chunk($recipients, 150);
        $count = 0;

        foreach ($chunkedRecipients as $chunk) {
            $response = sendLineMulticast($chunk, $message, CHANNEL_ACCESS_TOKEN);
            if ($response) $count += count($chunk);
        }

        echo json_encode(['status' => 'success', 'count' => $count]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// ฟังก์ชันยิง LINE API
function sendLineMulticast($userIds, $text, $token) {
    $url = "https://api.line.me/v2/bot/message/multicast";
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token
    ];
    $body = json_encode([
        "to" => $userIds,
        "messages" => [[
            "type" => "text",
            "text" => "📢 ประกาศจากระบบ:\n" . $text
        ]]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>