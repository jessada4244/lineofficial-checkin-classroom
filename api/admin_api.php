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
    // ---------------- [1. USER MANAGEMENT] ----------------
    if ($action === 'get_all_users') {
        $stmtUsers = $pdo->query("SELECT id, username, name, role, student_id, line_user_id, active FROM users ORDER BY id DESC");
        $users = $stmtUsers->fetchAll();
        $stats = ['teacher'=>0, 'student'=>0, 'total'=>count($users)];
        foreach ($users as $u) { if(isset($stats[$u['role']])) $stats[$u['role']]++; }
        echo json_encode(['status' => 'success', 'users' => $users, 'stats' => $stats]);
    }
    elseif ($action === 'toggle_status') {
        $pdo->prepare("UPDATE users SET active = NOT active WHERE id = ?")->execute([$input['user_id']]);
        echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'delete_user') {
        $uid = $input['user_id'];
        $pdo->prepare("DELETE FROM attendance WHERE student_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM classroom_members WHERE student_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM reports WHERE user_id = ?")->execute([$uid]); // ลบข้อความที่เคยส่งด้วย
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'update_user') {
        $sql = "UPDATE users SET name=?, username=?, role=?, student_id=? WHERE id=?";
        $pdo->prepare($sql)->execute([$input['name'], $input['username'], $input['role'], $input['student_id'], $input['user_id']]);
        echo json_encode(['status' => 'success']);
    }

    // ---------------- [2. BROADCAST] ----------------
    elseif ($action === 'broadcast') {
        $targetRole = $input['target_role'];
        $message = $input['message'];
        if (empty($message)) { echo json_encode(['status' => 'error', 'message' => 'ข้อความว่างเปล่า']); exit; }

        if ($targetRole === 'all') $stmt = $pdo->query("SELECT line_user_id FROM users WHERE line_user_id IS NOT NULL");
        else { $stmt = $pdo->prepare("SELECT line_user_id FROM users WHERE role = ? AND line_user_id IS NOT NULL"); $stmt->execute([$targetRole]); }
        
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($recipients)) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบผู้รับ']); exit; }

        foreach (array_chunk($recipients, 150) as $chunk) {
            sendLineMulticast($chunk, $message, CHANNEL_ACCESS_TOKEN);
        }
        echo json_encode(['status' => 'success', 'count' => count($recipients)]);
    }

    // ---------------- [3. REPORTS / INBOX] ----------------
    elseif ($action === 'get_reports') {
        $sql = "SELECT r.*, u.name as sender_name, u.role as sender_role, u.line_user_id as sender_line_id 
                FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.status ASC, r.created_at DESC";
        $reports = $pdo->query($sql)->fetchAll();
        echo json_encode(['status' => 'success', 'reports' => $reports]);
    }
    elseif ($action === 'reply_report') {
        $targetUid = $input['target_line_id'];
        $replyMsg = $input['message'];
        $reportId = $input['report_id'];

        pushLineMessage($targetUid, "💬 ตอบกลับจาก Admin:\n\n" . $replyMsg, CHANNEL_ACCESS_TOKEN);
        $pdo->prepare("UPDATE reports SET status = 'replied' WHERE id = ?")->execute([$reportId]);
        echo json_encode(['status' => 'success']);
    }

} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }

// Helpers
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