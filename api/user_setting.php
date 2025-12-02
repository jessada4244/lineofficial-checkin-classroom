<?php
// api/user_api.php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

if (empty($lineId)) {
    echo json_encode(['status' => 'error', 'message' => 'No Line ID']);
    exit;
}

// ดึง User ID จาก LINE ID
$stmt = $pdo->prepare("SELECT * FROM users WHERE line_user_id = ?");
$stmt->execute([$lineId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

try {
    // 1. ดึงข้อมูลโปรไฟล์
    if ($action === 'get_profile') {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role'],
                'student_id' => $user['student_id'], // จะเป็น null ถ้าไม่ใช่ student
                'phone' => $user['phone'],
                'id' => $user['id']
            ]
        ]);
    }
    
    // 2. อัปเดตข้อมูลทั่วไป (ชื่อ, เบอร์โทร)
    elseif ($action === 'update_profile') {
        $newName = $input['name'];
        $newPhone = $input['phone'];
        
        $sql = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
        $stmtUpdate = $pdo->prepare($sql);
        
        if ($stmtUpdate->execute([$newName, $newPhone, $user['id']])) {
            $_SESSION['name'] = $newName; // อัปเดต Session ด้วย
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ']);
        }
    }

    // 3. เปลี่ยนรหัสผ่าน
    elseif ($action === 'change_password') {
        $oldPass = $input['old_pass'];
        $newPass = $input['new_pass'];

        // เช็ครหัสเดิม
        if ($user['password'] !== $oldPass) {
            echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านเดิมไม่ถูกต้อง']);
            exit;
        }

        // บันทึกรหัสใหม่
        $stmtPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmtPass->execute([$newPass, $user['id']])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เปลี่ยนรหัสผ่านไม่สำเร็จ']);
        }
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>