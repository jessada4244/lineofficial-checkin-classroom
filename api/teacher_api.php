<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
elseif ($action === 'get_class_details') {
    $classId = $input['class_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$classId, $teacherId]);
    $class = $stmt->fetch();

    // ดึงรายชื่อนิสิตในห้อง
    $stmtMembers = $pdo->prepare("
        SELECT u.id, u.student_id, u.name 
        FROM classroom_members cm 
        JOIN users u ON cm.student_id = u.id 
        WHERE cm.classroom_id = ?
    ");
    $stmtMembers->execute([$classId]);
    $members = $stmtMembers->fetchAll();

    if ($class) {
        $class['members'] = $members;
        echo json_encode(['status' => 'success', 'class' => $class]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบวิชา']);
    }
}

// --- กรณี: อัปเดตข้อมูลวิชา (Update Class) ---
elseif ($action === 'update_class') {
    $classId = $input['class_id'];
    $name = $input['name'];
    $color = $input['color'];
    $limit = $input['limit'];
    $time = $input['time'];
    $lat = $input['lat'];
    $lng = $input['lng'];

    $sql = "UPDATE classrooms 
            SET subject_name = ?, room_color = ?, student_limit = ?, checkin_limit_time = ?, lat = ?, lng = ?
            WHERE id = ? AND teacher_id = ?";
    
    $stmt = $pdo->prepare($sql);
    
    try {
        if ($stmt->execute([$name, $color, $limit, $time, $lat, $lng, $classId, $teacherId])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    }
}
elseif ($action === 'add_member') {
    $studentCode = $input['student_code'];
    
    // 1. ค้นหา student_id จากรหัสนิสิต (student_code)
    $stmtStudent = $pdo->prepare("SELECT id FROM users WHERE student_id = ? AND role = 'student'");
    $stmtStudent->execute([$studentCode]);
    $student = $stmtStudent->fetch();

    if (!$student) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสนิสิต หรือไม่ใช่ Role Student']);
        exit;
    }
    
    $studentId = $student['id'];
    $classId = $input['class_id'];

    // 2. เพิ่มเข้าตาราง classroom_members
    try {
        $sql = "INSERT INTO classroom_members (classroom_id, student_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$classId, $studentId]);
        echo json_encode(['status' => 'success']);
    } catch (\PDOException $e) {
        // Error 23000 มักเป็น Duplicate entry (นิสิตซ้ำ)
        if ($e->getCode() == '23000') {
            echo json_encode(['status' => 'error', 'message' => 'นิสิตคนนี้อยู่ในห้องแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $e->getMessage()]);
        }
    }
}

// --- กรณี: ลบนิสิตออกจากห้อง (Remove Member) ---
elseif ($action === 'remove_member') {
    $studentIdToRemove = $input['student_id_to_remove']; // users.id ของนิสิต
    $classId = $input['class_id'];

    try {
        $sql = "DELETE FROM classroom_members WHERE classroom_id = ? AND student_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$classId, $studentIdToRemove]);
        
        if ($stmt->rowCount() > 0) {
             echo json_encode(['status' => 'success']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'ไม่พบสมาชิกในห้องนี้']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $e->getMessage()]);
    }
}
?>