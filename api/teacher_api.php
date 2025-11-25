<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

// หา teacher_id จาก line_id ก่อน
$stmt = $pdo->prepare("SELECT id FROM users WHERE line_user_id = ? AND role = 'teacher'");
$stmt->execute([$lineId]);
$teacher = $stmt->fetch();

if (!$teacher) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลอาจารย์']);
    exit;
}

$teacherId = $teacher['id'];

// --- กรณี: ดึงรายการวิชา (Get Classes) ---
if ($action === 'get_classes') {
    $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY id DESC");
    $stmt->execute([$teacherId]);
    $classes = $stmt->fetchAll();
    echo json_encode(['status' => 'success', 'classes' => $classes]);
}

// --- กรณี: สร้างวิชาใหม่ (Create Class) ---
elseif ($action === 'create_class') {
    $name = $input['name'];
    $time = $input['time'];
    $lat = $input['lat'];
    $lng = $input['lng'];

    $sql = "INSERT INTO classrooms (teacher_id, subject_name, checkin_limit_time, lat, lng) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$teacherId, $name, $time, $lat, $lng])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ']);
    }
}
?>