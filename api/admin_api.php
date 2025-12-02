<?php
// api/admin_api.php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

// Check Admin
$stmt = $pdo->prepare("SELECT id FROM users WHERE line_user_id = ? AND role = 'admin'");
$stmt->execute([$lineId]);
if (!$stmt->fetch()) { echo json_encode(['status' => 'error', 'message' => 'Access Denied']); exit; }

try {
    // 1. Get All Users (เพิ่ม edu_id ใน select)
    if ($action === 'get_all_users') {
        $stmtUsers = $pdo->query("SELECT id, username, name, role, edu_id, line_user_id, active, phone FROM users ORDER BY id DESC");
        $users = $stmtUsers->fetchAll();
        $stats = ['teacher'=>0, 'student'=>0, 'total'=>count($users)];
        foreach ($users as $u) { if(isset($stats[$u['role']])) $stats[$u['role']]++; }
        echo json_encode(['status' => 'success', 'users' => $users, 'stats' => $stats]);
    }
    
    // 2. Toggle Status
    elseif ($action === 'toggle_status') {
        $pdo->prepare("UPDATE users SET active = NOT active WHERE id = ?")->execute([$input['user_id']]);
        echo json_encode(['status' => 'success']);
    }
    // 3. Delete User
    elseif ($action === 'delete_user') {
        $uid = $input['user_id'];
        // ลบข้อมูลที่เกี่ยวข้อง (ใช้ user_id ที่เป็น ID หลักของ users)
        $pdo->prepare("DELETE FROM attendance WHERE user_id = ?")->execute([$uid]); // FK ชื่อเดิมแต่เก็บ ID
        $pdo->prepare("DELETE FROM classroom_members WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM reports WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        echo json_encode(['status' => 'success']);
    }
    // 4. Update User
    elseif ($action === 'update_user') {
        $sql = "UPDATE users SET name=?, username=?, role=?, edu_id=? WHERE id=?";
        $pdo->prepare($sql)->execute([$input['name'], $input['username'], $input['role'], $input['edu_id'], $input['user_id']]);
        echo json_encode(['status' => 'success']);
    }
    // 5. Broadcast
    elseif ($action === 'broadcast') {
        $targetRole = $input['target_role'];
        $message = $input['message'];
        if ($targetRole === 'all') $stmt = $pdo->query("SELECT line_user_id FROM users WHERE line_user_id IS NOT NULL");
        else { $stmt = $pdo->prepare("SELECT line_user_id FROM users WHERE role = ? AND line_user_id IS NOT NULL"); $stmt->execute([$targetRole]); }
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach (array_chunk($recipients, 150) as $chunk) {
            sendLineMulticast($chunk, $message, CHANNEL_ACCESS_TOKEN);
        }
        echo json_encode(['status' => 'success', 'count' => count($recipients)]);
    }
    // 6. Reports
    elseif ($action === 'get_reports') {
        $sql = "SELECT r.*, u.name as sender_name, u.role as sender_role, u.line_user_id as sender_line_id 
                FROM reports r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.status ASC, r.created_at DESC";
        $reports = $pdo->query($sql)->fetchAll();
        echo json_encode(['status' => 'success', 'reports' => $reports]);
    }
    elseif ($action === 'reply_report') {
        pushLineMessage($input['target_line_id'], "💬 ตอบกลับจาก Admin:\n\n" . $input['message'], CHANNEL_ACCESS_TOKEN);
        $pdo->prepare("UPDATE reports SET status = 'replied' WHERE id = ?")->execute([$input['report_id']]);
        echo json_encode(['status' => 'success']);
    }

} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }

function sendLineMulticast($userIds, $text, $token) {
    executeCurl("https://api.line.me/v2/bot/message/multicast", json_encode(["to"=>$userIds, "messages"=>[["type"=>"text","text"=>"📢 ประกาศ:\n".$text]]]), $token);
}
function pushLineMessage($userId, $text, $token) {
    executeCurl("https://api.line.me/v2/bot/message/push", json_encode(["to"=>$userId, "messages"=>[["type"=>"text","text"=>$text]]]), $token);
}
function executeCurl($url, $body, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$body, CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_HTTPHEADER=>["Content-Type: application/json", "Authorization: Bearer $token"]]);
    curl_exec($ch); curl_close($ch);
}
?>