<?php
// api/admin_api.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
    // 1. ดึงรายชื่อ (เพิ่ม line_user_id และ active)
    if ($action === 'get_all_users') {
        $stmtUsers = $pdo->query("SELECT id, username, name, role, student_id, line_user_id, active FROM users ORDER BY id DESC");
        $users = $stmtUsers->fetchAll();
        
        // Stats
        $stats = ['teacher' => 0, 'student' => 0, 'total' => count($users)];
        foreach ($users as $u) { if(isset($stats[$u['role']])) $stats[$u['role']]++; }

        echo json_encode(['status' => 'success', 'users' => $users, 'stats' => $stats]);
    }

    // 2. ลบผู้ใช้งาน
    elseif ($action === 'delete_user') {
        $userId = $input['user_id'];
        $pdo->prepare("DELETE FROM attendance WHERE student_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM classroom_members WHERE student_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        echo json_encode(['status' => 'success']);
    }

    // 3. (ใหม่) เปลี่ยนสถานะ Active (เปิด-ปิดการใช้งาน)
    elseif ($action === 'toggle_status') {
        $userId = $input['user_id'];
        // สลับค่า active (ถ้า 1 เป็น 0, ถ้า 0 เป็น 1)
        $pdo->prepare("UPDATE users SET active = NOT active WHERE id = ?")->execute([$userId]);
        echo json_encode(['status' => 'success']);
    }

    // 4. (ใหม่) แก้ไขข้อมูลผู้ใช้
    elseif ($action === 'update_user') {
        $uid = $input['user_id'];
        $name = $input['name'];
        $username = $input['username'];
        $role = $input['role'];
        $studentId = $input['student_id'] ?? null;

        $sql = "UPDATE users SET name=?, username=?, role=?, student_id=? WHERE id=?";
        $pdo->prepare($sql)->execute([$name, $username, $role, $studentId, $uid]);
        echo json_encode(['status' => 'success']);
    }

    // 5. Broadcast (คงเดิม)
    elseif ($action === 'broadcast') {
        // ... (โค้ด broadcast เดิม) ...
        // เพื่อความกระชับ ขอละไว้ (ใช้โค้ดเดิมได้เลยครับ)
        echo json_encode(['status' => 'success', 'count' => 0]); 
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>