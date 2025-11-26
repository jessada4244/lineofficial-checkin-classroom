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
    // --- Update Class (แก้ใหม่: ระบุตัวแปรชัดเจน ป้องกันการไม่อัปเดต) ---
    elseif ($action === 'update_class') {
        $classId = $input['class_id'];
        
        // รับค่าจาก input (ถ้าไม่ส่งมา ให้เป็น null)
        $name = $input['name'] ?? null;
        $courseCode = $input['course_code'] ?? null;
        $roomColor = $input['color'] ?? null;
        
        // ** จุดสำคัญ: รับค่า time มาใส่ checkin_limit_time **
        $time = $input['time'] ?? null; 
        
        // พิกัด
        $lat = $input['lat'] ?? null;
        $lng = $input['lng'] ?? null;

        // เริ่มสร้าง SQL แบบ Dynamic
        $sqlParts = [];
        $params = [];

        if ($name !== null) { $sqlParts[] = "subject_name = ?"; $params[] = $name; }
        if ($courseCode !== null) { $sqlParts[] = "course_code = ?"; $params[] = $courseCode; }
        if ($roomColor !== null) { $sqlParts[] = "room_color = ?"; $params[] = $roomColor; }
        
        // แก้ปัญหาเวลาไม่บันทึก
        if ($time !== null) { $sqlParts[] = "checkin_limit_time = ?"; $params[] = $time; }
        
        // แก้ปัญหาพิกัด
        if ($lat !== null) { 
            $sqlParts[] = "lat = ?"; $params[] = ($lat === '' ? NULL : $lat); 
        }
        if ($lng !== null) { 
            $sqlParts[] = "lng = ?"; $params[] = ($lng === '' ? NULL : $lng); 
        }

        if (empty($sqlParts)) {
            echo json_encode(['status' => 'success', 'message' => 'Nothing to update']);
            exit;
        }

        // ต่อ SQL
        $sql = "UPDATE classrooms SET " . implode(', ', $sqlParts) . " WHERE id = ? AND teacher_id = ?";
        $params[] = $classId;
        $params[] = $teacherId;
        
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
    // --- Action: ลบห้องเรียน (Delete Class) ---
    elseif ($action === 'delete_class') {
        $classId = $input['class_id'];

        try {
            // เริ่ม Transaction (เพื่อให้ลบทุกอย่างพร้อมกัน)
            $pdo->beginTransaction();

            // 1. ลบสมาชิกในห้อง (classroom_members)
            $stmt1 = $pdo->prepare("DELETE FROM classroom_members WHERE classroom_id = ?");
            $stmt1->execute([$classId]);

            // 2. ลบประวัติการเช็คชื่อ (attendance) - ถ้ามีตารางนี้
            $stmt2 = $pdo->prepare("DELETE FROM attendance WHERE classroom_id = ?");
            $stmt2->execute([$classId]);

            // 3. ลบตัวห้องเรียน (classrooms)
            $stmt3 = $pdo->prepare("DELETE FROM classrooms WHERE id = ? AND teacher_id = ?");
            $stmt3->execute([$classId, $teacherId]);

            if ($stmt3->rowCount() > 0) {
                $pdo->commit(); // ยืนยันการลบ
                echo json_encode(['status' => 'success']);
            } else {
                $pdo->rollBack(); // ยกเลิกถ้าลบไม่ได้
                echo json_encode(['status' => 'error', 'message' => 'ลบไม่สำเร็จ หรือคุณไม่ใช่เจ้าของห้อง']);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    // ... (ต่อจาก delete_class)

    // --- Action: ดูรายงานเช็คชื่อรายวัน (Daily Report) ---
    elseif ($action === 'get_daily_report') {
        $classId = $input['class_id'];
        $date = $input['date'] ?? date('Y-m-d'); // ถ้าไม่ส่งวันที่มา ใช้วันปัจจุบัน

        // 1. ดึงรายชื่อนิสิตทุกคนในห้อง (เรียงตามรหัส)
        $sqlStudents = "SELECT u.id, u.student_id, u.name 
                        FROM classroom_members cm
                        JOIN users u ON cm.student_id = u.id
                        WHERE cm.classroom_id = ?
                        ORDER BY u.student_id ASC";
        $stmtStd = $pdo->prepare($sqlStudents);
        $stmtStd->execute([$classId]);
        $allStudents = $stmtStd->fetchAll();

        // 2. ดึงข้อมูลการเช็คชื่อของวันที่เลือก
        $sqlAttend = "SELECT student_id, status, checkin_time, location_lat, location_lng 
                      FROM attendance 
                      WHERE classroom_id = ? AND DATE(checkin_time) = ?";
        $stmtAtt = $pdo->prepare($sqlAttend);
        $stmtAtt->execute([$classId, $date]);
        $attendanceData = $stmtAtt->fetchAll();

        // แปลงข้อมูลเช็คชื่อให้อยู่ในรูปแบบ Key-Value (student_id -> data) เพื่อหาง่ายๆ
        $attendanceMap = [];
        foreach ($attendanceData as $row) {
            $attendanceMap[$row['student_id']] = $row;
        }

        // 3. รวมข้อมูล (Merge) เพื่อระบุสถานะของแต่ละคน
        $report = [];
        $summary = ['present' => 0, 'late' => 0, 'absent' => 0];

        foreach ($allStudents as $std) {
            $sid = $std['id'];
            $status = 'absent'; // ค่าเริ่มต้นคือ ขาดเรียน
            $time = '-';
            
            if (isset($attendanceMap[$sid])) {
                $status = $attendanceMap[$sid]['status']; // present หรือ late
                $time = date('H:i', strtotime($attendanceMap[$sid]['checkin_time']));
            }

            // นับยอดรวม
            $summary[$status]++;

            $report[] = [
                'student_id' => $std['student_id'],
                'name' => $std['name'],
                'status' => $status,
                'checkin_time' => $time
            ];
        }

        // ดึงชื่อวิชา
        $stmtClass = $pdo->prepare("SELECT subject_name FROM classrooms WHERE id = ?");
        $stmtClass->execute([$classId]);
        $subject = $stmtClass->fetchColumn();

        echo json_encode([
            'status' => 'success',
            'subject_name' => $subject,
            'date' => $date,
            'summary' => $summary,
            'report' => $report
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>