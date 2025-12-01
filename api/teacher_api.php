<?php
// api/teacher_api.php

// 1. ตั้งค่า Timezone และ Error Reporting
date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php'; // จำเป็นต้องใช้ Token ในการส่งไลน์ Broadcast

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
    // =================================================================
    // GROUP 1: จัดการห้องเรียน (CRUD)
    // =================================================================

    // ดึงรายวิชาทั้งหมดของครู
    if ($action === 'get_classes') {
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY id DESC");
        $stmt->execute([$teacherId]);
        echo json_encode(['status' => 'success', 'classes' => $stmt->fetchAll()]);
    }

    // สร้างวิชาใหม่
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

    // ดึงรายละเอียดวิชา (เพื่อไปแสดงในหน้า Edit)
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

    // อัปเดตข้อมูลวิชา (รวมถึงลิงก์ Zoom/Teams)
    elseif ($action === 'update_class') {
        $classId = $input['class_id'];
        
        $sqlParts = []; $params = [];

        if (isset($input['name'])) { $sqlParts[] = "subject_name = ?"; $params[] = $input['name']; }
        if (isset($input['course_code'])) { $sqlParts[] = "course_code = ?"; $params[] = $input['course_code']; }
        if (isset($input['color'])) { $sqlParts[] = "room_color = ?"; $params[] = $input['color']; }
        if (isset($input['time'])) { $sqlParts[] = "checkin_limit_time = ?"; $params[] = $input['time']; }
        if (isset($input['lat'])) { $sqlParts[] = "lat = ?"; $params[] = ($input['lat']===''?NULL:$input['lat']); }
        if (isset($input['lng'])) { $sqlParts[] = "lng = ?"; $params[] = ($input['lng']===''?NULL:$input['lng']); }
        
        // อัปเดตลิงก์ถาวร
        if (isset($input['zoom_link'])) { $sqlParts[] = "zoom_link = ?"; $params[] = $input['zoom_link']; }
        if (isset($input['teams_link'])) { $sqlParts[] = "teams_link = ?"; $params[] = $input['teams_link']; }

        if (empty($sqlParts)) { echo json_encode(['status' => 'success', 'message' => 'Nothing to update']); exit; }

        $sql = "UPDATE classrooms SET " . implode(', ', $sqlParts) . " WHERE id = ? AND teacher_id = ?";
        $params[] = $classId; $params[] = $teacherId;
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) echo json_encode(['status' => 'success']);
        else throw new Exception("Update Failed");
    }

    // เพิ่มสมาชิก (นิสิต) เข้าห้อง
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
    
    // ลบสมาชิกออกจากห้อง
    elseif ($action === 'remove_member') {
        $stmt = $pdo->prepare("DELETE FROM classroom_members WHERE classroom_id = ? AND student_id = ?");
        $stmt->execute([$input['class_id'], $input['student_id_to_remove']]);
        echo json_encode(['status' => 'success']);
    }

    // ลบห้องเรียนถาวร
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

    // =================================================================
    // GROUP 2: ระบบเช็คชื่อ (QR Code & Live Session)
    // =================================================================

    // 1. เริ่มต้น Session ใหม่
    elseif ($action === 'start_new_session') {
        $classId = $input['class_id'];
        $mode = $input['mode'] ?? 'onsite'; 
        $notify = $input['notify'] ?? false; // รับค่า checkbox ว่าจะส่งไลน์ไหม

        $sessionId = uniqid('SESS_');
        $qrToken = bin2hex(random_bytes(8));
        $isOnline = ($mode !== 'onsite') ? 1 : 0;
        
        // 1. ดึงข้อมูลวิชา และ ลิงก์ที่บันทึกไว้ใน DB
        $stmtInfo = $pdo->prepare("SELECT subject_name, checkin_limit_time, zoom_link, teams_link FROM classrooms WHERE id = ?");
        $stmtInfo->execute([$classId]);
        $classInfo = $stmtInfo->fetch();
        $subjectName = $classInfo['subject_name'];

        // 2. เลือก Link ตามโหมดที่ส่งมา
        $meetingLink = null;
        if ($mode === 'zoom') {
            $meetingLink = $classInfo['zoom_link'];
        } elseif ($mode === 'teams') {
            $meetingLink = $classInfo['teams_link'];
        }

        // 3. อัปเดต Database (บันทึก Session ใหม่ + Link ที่ใช้ในรอบนี้)
        $stmt = $pdo->prepare("UPDATE classrooms SET current_session_id = ?, qr_token = ?, is_online_session = ?, session_link = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$sessionId, $qrToken, $isOnline, $meetingLink, $classId, $teacherId]);

        // 4. ส่ง Broadcast แจ้งเตือนนิสิต (เฉพาะ Online + มี Link + ติ๊กเลือกส่ง)
        if ($isOnline && !empty($meetingLink) && $notify) {
            $sqlStudents = "SELECT u.line_user_id FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ? AND u.line_user_id IS NOT NULL";
            $stmtStd = $pdo->prepare($sqlStudents);
            $stmtStd->execute([$classId]);
            $studentLineIds = $stmtStd->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($studentLineIds)) {
                $platformName = ($mode === 'zoom') ? "Zoom" : "MS Teams";
                $msgText = "🔔 เริ่มคลาสแล้ว: $subjectName\n";
                $msgText .= "เข้าเรียนผ่าน $platformName ได้ที่นี่ 👇\n";
                $msgText .= $meetingLink;
                
                // ส่งทีละ 150 คน (ข้อจำกัด LINE Multicast)
                foreach (array_chunk($studentLineIds, 150) as $chunk) {
                    sendLineMulticast($chunk, $msgText, CHANNEL_ACCESS_TOKEN);
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'session_id' => $sessionId,
            'qr_token' => $qrToken,
            'subject_name' => $subjectName,
            'limit_time' => $classInfo['checkin_limit_time'],
            'meeting_link' => $meetingLink, // ส่งกลับไปให้หน้าจออาจารย์เปิดปุ่ม Host
            'server_time' => date('H:i:s')
        ]);
    }

    // 2. หมุน QR Code (เปลี่ยน Token ทุก 5 วินาที)
    elseif ($action === 'rotate_qr_token') {
        $classId = $input['class_id'];
        $newToken = bin2hex(random_bytes(8));
        $stmt = $pdo->prepare("UPDATE classrooms SET qr_token = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$newToken, $classId, $teacherId]);
        echo json_encode(['status' => 'success', 'new_qr_token' => $newToken]);
    }

    // 3. ดึงสถานะสด (Live Status) ว่าใครมาแล้วบ้าง
    elseif ($action === 'get_live_status') {
        $classId = $input['class_id'];
        
        // หา Session ปัจจุบัน
        $stmtC = $pdo->prepare("SELECT current_session_id FROM classrooms WHERE id = ?");
        $stmtC->execute([$classId]);
        $currSession = $stmtC->fetchColumn();

        // ดึงนักเรียนทั้งหมด
        $sqlStd = "SELECT u.id, u.student_id, u.name FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ? ORDER BY u.student_id ASC";
        $stmtStd = $pdo->prepare($sqlStd); 
        $stmtStd->execute([$classId]); 
        $allStudents = $stmtStd->fetchAll();

        // ดึงคนที่เช็คชื่อแล้วใน Session นี้
        $sqlAtt = "SELECT student_id, status, checkin_time FROM attendance WHERE classroom_id = ? AND session_token = ?";
        $stmtAtt = $pdo->prepare($sqlAtt); 
        $stmtAtt->execute([$classId, $currSession]); 
        $attendees = $stmtAtt->fetchAll();
        
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

    // 4. ดึงรายการรอบการเช็คชื่อย้อนหลัง (History List)
    elseif ($action === 'get_checkin_sessions') {
        $classId = $input['class_id'];
        $sql = "SELECT session_token, MIN(checkin_time) as first_checkin FROM attendance WHERE classroom_id = ? AND session_token IS NOT NULL GROUP BY session_token ORDER BY first_checkin DESC";
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
        $stmtStd = $pdo->prepare($sqlStd); 
        $stmtStd->execute([$classId]); 
        $allStudents = $stmtStd->fetchAll();

        $sqlAtt = "SELECT student_id, status, checkin_time FROM attendance WHERE classroom_id = ? AND session_token = ?";
        $stmtAtt = $pdo->prepare($sqlAtt); 
        $stmtAtt->execute([$classId, $token]); 
        $attendees = $stmtAtt->fetchAll();
        
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

// =================================================================
// HELPER FUNCTIONS
// =================================================================

function sendLineMulticast($userIds, $text, $token) {
    $url = "https://api.line.me/v2/bot/message/multicast";
    $body = json_encode([
        "to" => $userIds,
        "messages" => [[ "type" => "text", "text" => $text ]]
    ]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    curl_exec($ch);
    curl_close($ch);
}
?>