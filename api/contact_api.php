<?php
// api/contact_api.php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php'; // โหลด Token

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'send_report') {
    $lineId = $input['line_id'];
    $displayName = $input['display_name']; // รับชื่อโปรไฟล์ LINE
    $topic = $input['topic'];
    $msg = $input['message'];

    if(empty($msg)) { echo json_encode(['status'=>'error','message'=>'ข้อความว่างเปล่า']); exit; }

    // 1. พยายามหา User ID (ถ้าเป็นสมาชิก)
    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE line_user_id = ?");
    $stmt->execute([$lineId]);
    $user = $stmt->fetch();

    $userId = $user ? $user['id'] : null;
    $senderName = $user ? $user['name'] . " (" . $user['role'] . ")" : $displayName . " (Guest)";

    // 2. บันทึกลง Database
    $sql = "INSERT INTO reports (user_id, sender_name, line_user_id, topic, message) VALUES (?, ?, ?, ?, ?)";
    $stmtInsert = $pdo->prepare($sql);
    
    if($stmtInsert->execute([$userId, $displayName, $lineId, $topic, $msg])) {
        
        // 3. แจ้งเตือนแอดมิน (Notify Admin)
        $notifyMsg = "📢 มีเรื่องร้องเรียนใหม่!\n\n";
        $notifyMsg .= "👤 จาก: $senderName\n";
        $notifyMsg .= "📌 หัวข้อ: $topic\n";
        $notifyMsg .= "💬 ข้อความ: $msg";
        
        notifyAllAdmins($pdo, $notifyMsg, CHANNEL_ACCESS_TOKEN);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ']);
    }
}

// ฟังก์ชันแจ้งเตือนแอดมินทุกคน
function notifyAllAdmins($pdo, $text, $token) {
    // ดึง Line ID ของ Admin ทุกคน
    $stmt = $pdo->query("SELECT line_user_id FROM users WHERE role = 'admin' AND line_user_id IS NOT NULL");
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($admins)) {
        // ส่งแบบ Multicast (ทีละหลายคน)
        $url = "https://api.line.me/v2/bot/message/multicast";
        foreach (array_chunk($admins, 150) as $chunk) {
            $body = json_encode([
                "to" => $chunk,
                "messages" => [[ "type" => "text", "text" => $text ]]
            ]);
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer $token"
                ],
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
?>