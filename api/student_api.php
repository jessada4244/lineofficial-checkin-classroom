<?php
// api/student_api.php

// 1. ตั้งค่า Timezone
date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

// 2. ตรวจสอบสิทธิ์ (Student Check)
if (empty($lineId)) { echo json_encode(['status' => 'error', 'message' => 'No Line ID']); exit; }
$stmt = $pdo->prepare("SELECT id, student_id, name FROM users WHERE line_user_id = ? AND role = 'student'");
$stmt->execute([$lineId]);
$student = $stmt->fetch();
if (!$student) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลนิสิต']); exit; }
$studentUserId = $student['id'];

try {
    // ดึงรายวิชา
    if ($action === 'get_my_classes') {
        $sql = "SELECT c.*, u.name as teacher_name FROM classrooms c JOIN classroom_members cm ON c.id = cm.classroom_id JOIN users u ON c.teacher_id = u.id WHERE cm.student_id = ? ORDER BY c.id DESC";
        $stmt = $pdo->prepare($sql); $stmt->execute([$studentUserId]);
        echo json_encode(['status' => 'success', 'classes' => $stmt->fetchAll()]);
    }

    // เข้าร่วมวิชา
    elseif ($action === 'join_class') {
        $classCode = $input['class_code']; 
        $stmtClass = $pdo->prepare("SELECT id, subject_name FROM classrooms WHERE class_code = ?");
        $stmtClass->execute([$classCode]);
        $classroom = $stmtClass->fetch();

        if (!$classroom) { echo json_encode(['status' => 'error', 'message' => 'รหัสเข้าห้องไม่ถูกต้อง']); exit; }

        try {
            $stmtInsert = $pdo->prepare("INSERT INTO classroom_members (classroom_id, student_id) VALUES (?, ?)");
            $stmtInsert->execute([$classroom['id'], $studentUserId]);
            echo json_encode(['status' => 'success', 'subject_name' => $classroom['subject_name']]);
        } catch (\PDOException $e) {
            if ($e->getCode() == '23000') echo json_encode(['status' => 'error', 'message' => 'คุณอยู่ในห้องเรียนนี้อยู่แล้ว']);
            else throw $e;
        }
    }

    // -----------------------------------------------------------------
    // ACTION ใหม่: เช็คชื่อด้วย QR Code (รองรับ Session & Token Rotation)
    // -----------------------------------------------------------------
    elseif ($action === 'check_in_qr') {
        $classId = $input['class_id'];
        $lat = $input['lat'];
        $lng = $input['lng'];
        $qrToken = $input['qr_token']; // Token ที่ได้จากการสแกน

        // 1. ดึงข้อมูลห้องเรียน (Token ล่าสุด และ Session ปัจจุบัน)
        $stmtClass = $pdo->prepare("SELECT subject_name, lat, lng, checkin_limit_time, qr_token, current_session_id FROM classrooms WHERE id = ?");
        $stmtClass->execute([$classId]);
        $class = $stmtClass->fetch();

        if (!$class) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวิชา']); exit; }

        // 2. ตรวจสอบ Token (ต้องตรงกับที่อาจารย์เปิดอยู่ *วินาทีนี้*)
        if ($class['qr_token'] !== $qrToken) { 
            echo json_encode(['status' => 'error', 'message' => 'QR Code หมดอายุ/เปลี่ยนไปแล้ว (กรุณาสแกนใหม่)']); 
            exit; 
        }

        // 3. ตรวจสอบว่าเคยเช็คใน "Session นี้" ไปหรือยัง
        // (ใช้ current_session_id เป็นตัวคุมรอบ)
        $currentSession = $class['current_session_id'];
        $stmtCheck = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND classroom_id = ? AND session_token = ?");
        $stmtCheck->execute([$studentUserId, $classId, $currentSession]);
        if ($stmtCheck->fetch()) { 
            echo json_encode(['status' => 'error', 'message' => 'คุณเช็คชื่อในรอบนี้เรียบร้อยแล้ว']); 
            exit; 
        }

        // 4. ตรวจสอบ GPS (ระยะ 50 เมตร)
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat); $lonFrom = deg2rad($lng);
        $latTo = deg2rad($class['lat']); $lonTo = deg2rad($class['lng']);
        $latDelta = $latTo - $latFrom; $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        $distance = $earthRadius * $angle;

        if ($distance > 50) { 
            echo json_encode(['status' => 'error', 'message' => 'อยู่นอกพื้นที่ห้องเรียน (ห่าง ' . round($distance) . ' ม.)']); 
            exit; 
        }

        // 5. สถานะ มา/สาย
        $status = 'present';
        $currentTime = date('H:i:s');
        if (!empty($class['checkin_limit_time']) && $currentTime > $class['checkin_limit_time']) { 
            $status = 'late'; 
        }

        // 6. บันทึก (ใส่ session_token = current_session_id)
        $sqlInsert = "INSERT INTO attendance (student_id, classroom_id, status, location_lat, location_lng, session_token, checkin_time) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        
        if ($stmtInsert->execute([$studentUserId, $classId, $status, $lat, $lng, $currentSession])) {
            echo json_encode([
                'status' => 'success', 
                'subject_name' => $class['subject_name'],
                'checkin_status' => ($status=='present'?'มาเรียน':'มาสาย'), 
                'time' => date('H:i'),
                'distance' => round($distance)
            ]);
        }
    }

    // ดูประวัติ
    elseif ($action === 'get_history') {
        $classId = $input['class_id'];
        $stmtClass = $pdo->prepare("SELECT subject_name, course_code FROM classrooms WHERE id = ?");
        $stmtClass->execute([$classId]);
        $classInfo = $stmtClass->fetch();
        if (!$classInfo) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวิชา']); exit; }

        $stmtHist = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND classroom_id = ? ORDER BY checkin_time DESC");
        $stmtHist->execute([$studentUserId, $classId]);
        $history = $stmtHist->fetchAll();

        echo json_encode(['status' => 'success', 'subject_name' => $classInfo['subject_name'], 'course_code' => $classInfo['course_code'], 'history' => $history]);
    }

    else { echo json_encode(['status' => 'error', 'message' => 'Unknown Action']); }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>