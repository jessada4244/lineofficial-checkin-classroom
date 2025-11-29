<?php
// api/teacher_api.php

// 1. ตั้งค่า Timezone และ Error Reporting
date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

// 2. ตรวจสอบสิทธิ์ (Teacher Check)
if (empty($lineId)) { echo json_encode(['status' => 'error', 'message' => 'No Line ID']); exit; }
$stmt = $pdo->prepare("SELECT id FROM users WHERE line_user_id = ? AND role = 'teacher'");
$stmt->execute([$lineId]);
$teacher = $stmt->fetch();
if (!$teacher) { echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit; }
$teacherId = $teacher['id'];

try {
    // -----------------------------------------------------------------
    // กลุ่ม Action เดิม (จัดการห้องเรียน)
    // -----------------------------------------------------------------

    // ดึงรายวิชา
    if ($action === 'get_classes') {
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY id DESC");
        $stmt->execute([$teacherId]);
        echo json_encode(['status' => 'success', 'classes' => $stmt->fetchAll()]);
    }

    // สร้างวิชา
    elseif ($action === 'create_class') {
        $name = $input['name'];
        $courseCode = $input['course_code']; 
        $color = $input['color'] ?? '#FFFFFF';
        $limit = 40; 
        $classCode = rand(100000, 999999); 

        if (empty($name) || empty($courseCode)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกรหัสวิชาและชื่อวิชา']); exit;
        }

        $sql = "INSERT INTO classrooms (teacher_id, subject_name, course_code, class_code, room_color, student_limit) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$teacherId, $name, $courseCode, $classCode, $color, $limit])) {
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception("Save Failed");
        }
    }

    // ดึงรายละเอียดวิชา
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

    // อัปเดตวิชา
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

        if (empty($sqlParts)) { echo json_encode(['status' => 'success', 'message' => 'Nothing to update']); exit; }

        $sql = "UPDATE classrooms SET " . implode(', ', $sqlParts) . " WHERE id = ? AND teacher_id = ?";
        $params[] = $classId; $params[] = $teacherId;
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) echo json_encode(['status' => 'success']);
        else throw new Exception("Update Failed");
    }

    // เพิ่ม/ลบ นิสิตในห้อง
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
        } catch (\PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'มีนิสิตคนนี้แล้ว']); }
    }
    elseif ($action === 'remove_member') {
        $stmt = $pdo->prepare("DELETE FROM classroom_members WHERE classroom_id = ? AND student_id = ?");
        $stmt->execute([$input['class_id'], $input['student_id_to_remove']]);
        echo json_encode(['status' => 'success']);
    }

    // ลบห้องเรียน
    elseif ($action === 'delete_class') {
        $classId = $input['class_id'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM classroom_members WHERE classroom_id = ?")->execute([$classId]);
            $pdo->prepare("DELETE FROM attendance WHERE classroom_id = ?")->execute([$classId]);
            $stmt3 = $pdo->prepare("DELETE FROM classrooms WHERE id = ? AND teacher_id = ?");
            $stmt3->execute([$classId, $teacherId]);
            if ($stmt3->rowCount() > 0) {
                $pdo->commit(); echo json_encode(['status' => 'success']);
            } else {
                $pdo->rollBack(); echo json_encode(['status' => 'error', 'message' => 'ลบไม่สำเร็จ']);
            }
        } catch (Exception $e) { $pdo->rollBack(); throw $e; }
    }

    // -----------------------------------------------------------------
    // กลุ่ม Action ใหม่ (ระบบ QR Code & Live Session)
    // -----------------------------------------------------------------

    // 1. เริ่มต้น Session ใหม่ (เปิดหน้าจอ QR)
    elseif ($action === 'start_new_session') {
        $classId = $input['class_id'];
        
        // สร้าง Session ID ใหม่ (SESS_timestamp_random)
        $sessionId = uniqid('SESS_');
        // สร้าง QR Token แรก
        $qrToken = bin2hex(random_bytes(8));

        // อัปเดตลงตาราง classrooms
        $stmt = $pdo->prepare("UPDATE classrooms SET current_session_id = ?, qr_token = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$sessionId, $qrToken, $classId, $teacherId]);

        // ดึงข้อมูลเวลา Limit เพื่อไปนับถอยหลัง
        $stmt2 = $pdo->prepare("SELECT subject_name, checkin_limit_time FROM classrooms WHERE id = ?");
        $stmt2->execute([$classId]);
        $classInfo = $stmt2->fetch();

        echo json_encode([
            'status' => 'success',
            'session_id' => $sessionId,
            'qr_token' => $qrToken,
            'subject_name' => $classInfo['subject_name'],
            'limit_time' => $classInfo['checkin_limit_time'],
            'server_time' => date('H:i:s')
        ]);
    }

    // 2. หมุน QR Code (เปลี่ยนทุก 5 วินาที)
    elseif ($action === 'rotate_qr_token') {
        $classId = $input['class_id'];
        
        // สร้าง Token ใหม่
        $newToken = bin2hex(random_bytes(8));

        // อัปเดตเฉพาะ qr_token (session_id เดิมยังคงอยู่)
        $stmt = $pdo->prepare("UPDATE classrooms SET qr_token = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$newToken, $classId, $teacherId]);

        echo json_encode(['status' => 'success', 'new_qr_token' => $newToken]);
    }

    // 3. ดึงสถานะสด (Live Status)
    elseif ($action === 'get_live_status') {
        $classId = $input['class_id'];
        
        // หา Session ปัจจุบัน
        $stmtC = $pdo->prepare("SELECT current_session_id FROM classrooms WHERE id = ?");
        $stmtC->execute([$classId]);
        $currSession = $stmtC->fetchColumn();

        // ดึงนักเรียนทั้งหมดในห้อง
        $sqlStd = "SELECT u.id, u.student_id, u.name 
                   FROM classroom_members cm 
                   JOIN users u ON cm.student_id = u.id 
                   WHERE cm.classroom_id = ? 
                   ORDER BY u.student_id ASC";
        $stmtStd = $pdo->prepare($sqlStd); 
        $stmtStd->execute([$classId]);
        $allStudents = $stmtStd->fetchAll();

        // ดึงคนที่เช็คชื่อแล้ว (เฉพาะใน Session ปัจจุบัน)
        $sqlAtt = "SELECT student_id, status, checkin_time 
                   FROM attendance 
                   WHERE classroom_id = ? AND session_token = ?";
        $stmtAtt = $pdo->prepare($sqlAtt); 
        $stmtAtt->execute([$classId, $currSession]);
        $attendees = $stmtAtt->fetchAll();
        
        // Map ข้อมูลเพื่อแยกกลุ่ม
        $attMap = [];
        foreach($attendees as $a) { $attMap[$a['student_id']] = $a; }

        $checked_in = [];
        $not_checked_in = [];

        foreach($allStudents as $std) {
            if (isset($attMap[$std['id']])) {
                $checked_in[] = [
                    'name' => $std['name'],
                    'student_id' => $std['student_id'],
                    'status' => $attMap[$std['id']]['status'],
                    'time' => date('H:i:s', strtotime($attMap[$std['id']]['checkin_time']))
                ];
            } else {
                $not_checked_in[] = [
                    'name' => $std['name'],
                    'student_id' => $std['student_id']
                ];
            }
        }

        echo json_encode([
            'status' => 'success', 
            'checked_in' => $checked_in, 
            'not_checked_in' => $not_checked_in,
            'count_in' => count($checked_in),
            'count_not' => count($not_checked_in)
        ]);
    }

    // 4. ดึงรายการรอบการเช็คชื่อย้อนหลัง (Sessions List)
    elseif ($action === 'get_checkin_sessions') {
        $classId = $input['class_id'];
        
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
            $sessionList[] = [
                'session_token' => $r['session_token'],
                'date' => $dt->format('d/m/Y'),
                'time' => $dt->format('H:i')
            ];
        }

        $stmtName = $pdo->prepare("SELECT subject_name FROM classrooms WHERE id = ?");
        $stmtName->execute([$classId]);
        $sub = $stmtName->fetchColumn();

        echo json_encode(['status' => 'success', 'subject_name' => $sub, 'sessions' => $sessionList]);
    }

    // 5. ดึงรายงานรายชื่อในรอบนั้นๆ (Session Report Detail)
    elseif ($action === 'get_session_report') {
        $classId = $input['class_id'];
        $token = $input['session_token'];

        $sqlStd = "SELECT u.id, u.student_id, u.name FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ? ORDER BY u.student_id ASC";
        $stmtStd = $pdo->prepare($sqlStd); $stmtStd->execute([$classId]); $allStudents = $stmtStd->fetchAll();

        $sqlAtt = "SELECT student_id, status, checkin_time FROM attendance WHERE classroom_id = ? AND session_token = ?";
        $stmtAtt = $pdo->prepare($sqlAtt); $stmtAtt->execute([$classId, $token]); $attendees = $stmtAtt->fetchAll();
        
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
            if(isset($summary[$status])) $summary[$status]++;
            
            $report[] = ['name' => $std['name'], 'student_id' => $std['student_id'], 'status' => $status, 'checkin_time' => $time];
        }
        echo json_encode(['status'=>'success', 'summary'=>$summary, 'report'=>$report]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>