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
    $color = $input['color'] ?? '#FFFFFF';
    $limit = $input['limit'] ?? 40;
    
    // ลบ $time, $lat, $lng ออกไป

    if(empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อวิชา']);
        exit;
    }

    // แก้ SQL ให้เหลือเฉพาะคอลัมน์ที่จำเป็น (โดยปล่อยให้ checkin_limit_time, lat, lng เป็น NULL/Default ใน DB)
    $sql = "INSERT INTO classrooms (teacher_id, subject_name, room_color, student_limit) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        if ($stmt->execute([$teacherId, $name, $color, $limit])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . implode(" ", $stmt->errorInfo())]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    }
}
?>