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

    // --- Create Class ---
    elseif ($action === 'create_class') {
        $name = $input['name'];
        $courseCode = $input['course_code']; 
        $color = $input['color'] ?? '#FFFFFF';
        $limit = 40; 
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

        $stmtMembers = $pdo->prepare("SELECT u.id, u.student_id, u.name FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ?");
        $stmtMembers->execute([$classId]);
        $class['members'] = $stmtMembers->fetchAll();
        
        echo json_encode(['status' => 'success', 'class' => $class]);
    }

    // --- Update Class ---
    elseif ($action === 'update_class') {
        $classId = $input['class_id'];
        $name = $input['name'] ?? null;
        $courseCode = $input['course_code'] ?? null;
        $roomColor = $input['color'] ?? null;
        $time = $input['time'] ?? null; 
        $lat = $input['lat'] ?? null;
        $lng = $input['lng'] ?? null;

        $sqlParts = []; $params = [];
        if ($name !== null) { $sqlParts[] = "subject_name = ?"; $params[] = $name; }
        if ($courseCode !== null) { $sqlParts[] = "course_code = ?"; $params[] = $courseCode; }
        if ($roomColor !== null) { $sqlParts[] = "room_color = ?"; $params[] = $roomColor; }
        if ($time !== null) { $sqlParts[] = "checkin_limit_time = ?"; $params[] = $time; }
        if ($lat !== null) { $sqlParts[] = "lat = ?"; $params[] = ($lat === '' ? NULL : $lat); }
        if ($lng !== null) { $sqlParts[] = "lng = ?"; $params[] = ($lng === '' ? NULL : $lng); }

        if (empty($sqlParts)) {
            echo json_encode(['status' => 'success', 'message' => 'Nothing to update']);
            exit;
        }

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

    // --- Generate QR Token (สร้าง Token สำหรับเช็คชื่อรอบใหม่) ---
    elseif ($action === 'generate_qr') {
        $classId = $input['class_id'];
        
        // สุ่ม Token 32 ตัวอักษร
        $token = bin2hex(random_bytes(16));
        
        $stmt = $pdo->prepare("UPDATE classrooms SET qr_token = ? WHERE id = ? AND teacher_id = ?");
        if ($stmt->execute([$token, $classId, $teacherId])) {
            // ดึงชื่อวิชา
            $stmtName = $pdo->prepare("SELECT subject_name FROM classrooms WHERE id = ?");
            $stmtName->execute([$classId]);
            $sub = $stmtName->fetchColumn();
            
            echo json_encode(['status' => 'success', 'qr_token' => $token, 'subject_name' => $sub]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'สร้าง QR ไม่สำเร็จ']);
        }
    }

    // --- Add/Remove Member ---
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

    // --- Delete Class ---
    elseif ($action === 'delete_class') {
        $classId = $input['class_id'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM classroom_members WHERE classroom_id = ?")->execute([$classId]);
            $pdo->prepare("DELETE FROM attendance WHERE classroom_id = ?")->execute([$classId]);
            $stmt3 = $pdo->prepare("DELETE FROM classrooms WHERE id = ? AND teacher_id = ?");
            $stmt3->execute([$classId, $teacherId]);

            if ($stmt3->rowCount() > 0) {
                $pdo->commit();
                echo json_encode(['status' => 'success']);
            } else {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'ลบไม่สำเร็จ']);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // --- 1. ดึงรายการรอบการเช็คชื่อ (Sessions List) ---
    elseif ($action === 'get_checkin_sessions') {
        $classId = $input['class_id'];
        
        // Group by session_token เพื่อดูว่ามีกี่รอบ เรียงตามเวลาล่าสุด
        // เลือกเฉพาะ record ที่มี session_token (คือเช็คด้วยระบบ QR ใหม่)
        $sql = "SELECT session_token, MIN(checkin_time) as first_checkin 
                FROM attendance 
                WHERE classroom_id = ? AND session_token IS NOT NULL 
                GROUP BY session_token 
                ORDER BY first_checkin DESC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$classId]);
        $rounds = $stmt->fetchAll();

        $sessionList = [];
        foreach($rounds as $r) {
            $dt = new DateTime($r['first_checkin']);
            // สร้าง Label ให้อ่านง่าย เช่น "รอบวันที่ 28/05/2024 (10:30)"
            $sessionList[] = [
                'session_token' => $r['session_token'],
                'date' => $dt->format('d/m/Y'),
                'time' => $dt->format('H:i')
            ];
        }

        // ดึงชื่อวิชา
        $stmtName = $pdo->prepare("SELECT subject_name FROM classrooms WHERE id = ?");
        $stmtName->execute([$classId]);
        $sub = $stmtName->fetchColumn();

        echo json_encode(['status' => 'success', 'subject_name' => $sub, 'sessions' => $sessionList]);
    }

    // --- 2. ดึงรายงานรายชื่อในรอบนั้นๆ (Session Detail Report) ---
    elseif ($action === 'get_session_report') {
        $classId = $input['class_id'];
        $token = $input['session_token'];

        // 1. ดึงนศ.ทั้งหมดในห้อง (เพื่อแสดงคนขาดด้วย)
        $sqlStd = "SELECT u.id, u.student_id, u.name 
                   FROM classroom_members cm 
                   JOIN users u ON cm.student_id = u.id 
                   WHERE cm.classroom_id = ? 
                   ORDER BY u.student_id ASC";
        $stmtStd = $pdo->prepare($sqlStd);
        $stmtStd->execute([$classId]);
        $allStudents = $stmtStd->fetchAll();

        // 2. ดึงคนที่เช็คชื่อใน Session นี้
        $sqlAtt = "SELECT student_id, status, checkin_time 
                   FROM attendance 
                   WHERE classroom_id = ? AND session_token = ?";
        $stmtAtt = $pdo->prepare($sqlAtt);
        $stmtAtt->execute([$classId, $token]);
        $attendees = $stmtAtt->fetchAll();
        
        // Map ข้อมูลเพื่อค้นหาง่าย
        $attMap = [];
        foreach($attendees as $a) { $attMap[$a['student_id']] = $a; }

        $report = [];
        $summary = ['present'=>0, 'late'=>0, 'absent'=>0];

        foreach($allStudents as $std) {
            $sid = $std['id'];
            $status = 'absent';
            $time = '-';

            if(isset($attMap[$sid])) {
                $status = $attMap[$sid]['status'];
                $time = date('H:i', strtotime($attMap[$sid]['checkin_time']));
            }
            
            // นับยอดรวม
            if(isset($summary[$status])) {
                $summary[$status]++;
            }

            $report[] = [
                'name' => $std['name'],
                'student_id' => $std['student_id'],
                'status' => $status,
                'checkin_time' => $time
            ];
        }

        echo json_encode(['status'=>'success', 'summary'=>$summary, 'report'=>$report]);
    }

    // --- Legacy Daily Report (เก็บไว้เผื่อดูแบบรายวันรวมๆ ถ้าต้องการ) ---
    elseif ($action === 'get_daily_report') {
        $classId = $input['class_id'];
        $date = $input['date'] ?? date('Y-m-d');

        $sqlStudents = "SELECT u.id, u.student_id, u.name FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ? ORDER BY u.student_id ASC";
        $stmtStd = $pdo->prepare($sqlStudents); $stmtStd->execute([$classId]); $allStudents = $stmtStd->fetchAll();

        $sqlAttend = "SELECT student_id, status, checkin_time FROM attendance WHERE classroom_id = ? AND DATE(checkin_time) = ?";
        $stmtAtt = $pdo->prepare($sqlAttend); $stmtAtt->execute([$classId, $date]); $attendanceData = $stmtAtt->fetchAll();

        $attendanceMap = [];
        foreach ($attendanceData as $row) { $attendanceMap[$row['student_id']] = $row; }

        $report = [];
        $summary = ['present' => 0, 'late' => 0, 'absent' => 0];

        foreach ($allStudents as $std) {
            $sid = $std['id'];
            $status = 'absent';
            $time = '-';
            if (isset($attendanceMap[$sid])) {
                $status = $attendanceMap[$sid]['status'];
                $time = date('H:i', strtotime($attendanceMap[$sid]['checkin_time']));
            }
            $summary[$status]++;
            $report[] = ['student_id' => $std['student_id'], 'name' => $std['name'], 'status' => $status, 'checkin_time' => $time];
        }

        $stmtClass = $pdo->prepare("SELECT subject_name FROM classrooms WHERE id = ?");
        $stmtClass->execute([$classId]);
        $subject = $stmtClass->fetchColumn();

        echo json_encode(['status' => 'success', 'subject_name' => $subject, 'date' => $date, 'summary' => $summary, 'report' => $report]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>