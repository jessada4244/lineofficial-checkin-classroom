<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$name     = $input['name'] ?? '';
$role     = $input['role'] ?? ''; 
$studentId = $input['student_id'] ?? null;
$lineUserId = $input['line_user_id'] ?? null; // รับค่า Line User ID

// 1. Validation
if (empty($username) || empty($password) || empty($name) || empty($role) || empty($lineUserId)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน (Line ID Missing)']);
    exit;
}

if ($role === 'student' && empty($studentId)) {
    echo json_encode(['status' => 'error', 'message' => 'นิสิตต้องกรอกรหัสนิสิต']);
    exit;
}

// 2. เช็คข้อมูลซ้ำ (Username, Student ID, และ Line User ID)
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? OR line_user_id = ?");
$stmtCheck->execute([$username, $lineUserId]);
if ($stmtCheck->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username หรือ LINE Account นี้ถูกใช้งานแล้ว']);
    exit;
}

if ($role === 'student') {
    $stmtCheckStd = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
    $stmtCheckStd->execute([$studentId]);
    if ($stmtCheckStd->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสนิสิตนี้มีในระบบแล้ว']);
        exit;
    }
}

// 3. บันทึก
try {
    // เพิ่ม line_user_id ลงใน SQL
    $sql = "INSERT INTO users (username, password, name, role, student_id, line_user_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // บันทึกรหัสผ่านแบบธรรมดา (ตามระบบเดิม) หรือใช้ password_hash ก็ได้
    if ($stmt->execute([$username, $password, $name, $role, $studentId, $lineUserId])) {
        echo json_encode(['status' => 'success', 'message' => 'สมัครสมาชิกสำเร็จ!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'บันทึกข้อมูลไม่สำเร็จ']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>