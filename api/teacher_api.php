<?php
// api/teacher_api.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

// Check Teacher Logic
if (empty($lineId)) { echo json_encode(['status' => 'error', 'message' => 'No Line ID']); exit; }
$stmt = $pdo->prepare("SELECT id FROM users WHERE line_user_id = ? AND role = 'teacher'");
$stmt->execute([$lineId]);
$teacher = $stmt->fetch();
if (!$teacher) { echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit; }
$teacherId = $teacher['id'];

try {
    // --- Get Classes ---
    if ($action === 'get_classes') {
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY id DESC");
        $stmt->execute([$teacherId]);
        echo json_encode(['status' => 'success', 'classes' => $stmt->fetchAll()]);
    }

    // --- Create Class (แก้ไขใหม่: สุ่มรหัส Class Code เอง) ---
    elseif ($action === 'create_class') {
        $name = $input['name'];
        $courseCode = $input['course_code']; // รับรหัสวิชา
        $color = $input['color'] ?? '#FFFFFF';
        $limit = 40; // ค่า Default

        // *** สุ่มรหัสเข้าห้อง 6 หลักตรงนี้ ***
        $classCode = rand(100000, 999999); 

        if (empty($name) || empty($courseCode)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกรหัสวิชาและชื่อวิชา']);
            exit;
        }

        $sql = "INSERT INTO classrooms (teacher_id, subject_name, course_code, class_code, room_color, student_limit) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$teacherId, $name, $courseCode, $classCode, $color, $limit])) {
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception("Save Failed");
        }
    }

    // --- Get Details ---
    elseif ($action === 'get_class_details') {
        $classId = $input['class_id'];
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$classId, $teacherId]);
        $class = $stmt->fetch();

        if (!$class) { echo json_encode(['status' => 'error', 'message' => 'Not Found']); exit; }

        $stmtMembers = $pdo->prepare("
            SELECT u.id, u.student_id, u.name 
            FROM classroom_members cm 
            JOIN users u ON cm.student_id = u.id 
            WHERE cm.classroom_id = ?
        ");
        $stmtMembers->execute([$classId]);
        $class['members'] = $stmtMembers->fetchAll();
        
        echo json_encode(['status' => 'success', 'class' => $class]);
    }
// --- Update Class (แบบ Dynamic: ส่งค่าไหนมา แก้แค่นั้น) ---
    elseif ($action === 'update_class') {
        $classId = $input['class_id'];
        
        // รายชื่อฟิลด์ที่อนุญาตให้แก้ไขได้
        $allowedFields = ['subject_name', 'course_code', 'room_color', 'checkin_limit_time', 'lat', 'lng', 'class_code'];
        
        $setClauses = [];
        $params = [];

        // วนลูปเช็คว่า Front-end ส่งค่าอะไรมาบ้าง
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $setClauses[] = "$field = ?";
                
                // จัดการค่าว่างสำหรับ Lat/Lng ให้เป็น NULL
                $val = $input[$field];
                if (($field == 'lat' || $field == 'lng') && $val === '') {
                    $val = NULL;
                }
                $params[] = $val;
            }
        }

        if (empty($setClauses)) {
            echo json_encode(['status' => 'success', 'message' => 'ไม่มีการเปลี่ยนแปลง']);
            exit;
        }

        // เพิ่มเงื่อนไข WHERE
        $params[] = $classId;
        $params[] = $teacherId;

        $sql = "UPDATE classrooms SET " . implode(', ', $setClauses) . " WHERE id = ? AND teacher_id = ?";
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception("Update Failed");
        }
    }

    // --- Add/Remove Member (เหมือนเดิม) ---
    elseif ($action === 'add_member') {
        $studentCode = $input['student_code'];
        $classId = $input['class_id'];
        
        $stmtUser = $pdo->prepare("SELECT id FROM users WHERE student_id = ? AND role = 'student'");
        $stmtUser->execute([$studentCode]);
        $student = $stmtUser->fetch();

        if (!$student) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสนิสิต']); exit; }

        try {
            $stmtInsert = $pdo->prepare("INSERT INTO classroom_members (classroom_id, student_id) VALUES (?, ?)");
            $stmtInsert->execute([$classId, $student['id']]);
            echo json_encode(['status' => 'success']);
        } catch (\PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'มีนิสิตคนนี้แล้ว']);
        }
    }

    elseif ($action === 'remove_member') {
        $stmt = $pdo->prepare("DELETE FROM classroom_members WHERE classroom_id = ? AND student_id = ?");
        $stmt->execute([$input['class_id'], $input['student_id_to_remove']]);
        echo json_encode(['status' => 'success']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>