<?php
// api/user_setting.php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

if (empty($lineId)) { echo json_encode(['status' => 'error', 'message' => 'No Line ID']); exit; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE line_user_id = ?");
$stmt->execute([$lineId]);
$user = $stmt->fetch();

if (!$user) { echo json_encode(['status' => 'error', 'message' => 'User not found']); exit; }

try {
    if ($action === 'get_profile') {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role'],
                'student_id' => $user['edu_id'], // ** แก้ตรงนี้: ส่ง edu_id กลับไปในชื่อ key เดิมก็ได้ เพื่อไม่ต้องแก้หน้าเว็บเยอะ **
                'phone' => $user['phone'],
                'id' => $user['id']
            ]
        ]);
    }
    elseif ($action === 'update_profile') {
        $newName = $input['name'];
        $newPhone = $input['phone'];
        $sql = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
        if ($pdo->prepare($sql)->execute([$newName, $newPhone, $user['id']])) {
            $_SESSION['name'] = $newName;
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ']);
        }
    }
    elseif ($action === 'change_password') {
        // ... (โค้ดเปลี่ยนรหัสผ่านเหมือนเดิม)
        $oldPass = $input['old_pass']; $newPass = $input['new_pass'];
        if ($user['password'] !== $oldPass) { echo json_encode(['status' => 'error', 'message' => 'รหัสเดิมผิด']); exit; }
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newPass, $user['id']]);
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>