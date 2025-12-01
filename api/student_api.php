<?php
// api/student_api.php

// 1. ตั้งค่า Timezone และการแสดงผล Error
date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/db.php';

// รับค่า JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

// 2. ตรวจสอบสิทธิ์ (ต้องมี Line ID และเป็น Role Student)
if (empty($lineId)) {
    echo json_encode(['status' => 'error', 'message' => 'No Line ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, student_id, name FROM users WHERE line_user_id = ? AND role = 'student'");
$stmt->execute([$lineId]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลนิสิต หรือคุณไม่ใช่ Student']);
    exit;
}

$studentUserId = $student['id'];

try {
    // =================================================================
    // Action 1: ดึงรายวิชาที่ลงทะเบียนไว้ (My Classes)
    // =================================================================
    if ($action === 'get_my_classes') {
        $sql = "SELECT c.*, u.name as teacher_name 
                FROM classrooms c 
                JOIN classroom_members cm ON c.id = cm.classroom_id 
                JOIN users u ON c.teacher_id = u.id 
                WHERE cm.student_id = ? 
                ORDER BY c.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$studentUserId]);
        echo json_encode(['status' => 'success', 'classes' => $stmt->fetchAll()]);
    }

    // =================================================================
    // Action 2: เข้าร่วมวิชาใหม่ (Join Class)
    // =================================================================
    elseif ($action === 'join_class') {
        $classCode = $input['class_code'];
        
        // ค้นหาห้องเรียนจากรหัสวิชา (Class Code)
        $stmtClass = $pdo->prepare("SELECT id, subject_name FROM classrooms WHERE class_code = ?");
        $stmtClass->execute([$classCode]);
        $classroom = $stmtClass->fetch();

        if (!$classroom) {
            echo json_encode(['status' => 'error', 'message' => 'รหัสเข้าห้องไม่ถูกต้อง']);
            exit;
        }

        // เพิ่มนิสิตเข้าห้อง (ถ้าซ้ำจะ Error ที่ Database constraint)
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO classroom_members (classroom_id, student_id) VALUES (?, ?)");
            $stmtInsert->execute([$classroom['id'], $studentUserId]);
            echo json_encode(['status' => 'success', 'subject_name' => $classroom['subject_name']]);
        } catch (\PDOException $e) {
            // Error code 23000 คือ Duplicate entry
            echo json_encode(['status' => 'error', 'message' => 'คุณเป็นสมาชิกห้องเรียนนี้อยู่แล้ว']);
        }
    }

    // =================================================================
    // Action 3: เช็คชื่อ (Check-in) รองรับ Scan GPS และ Manual Code
    // =================================================================
    elseif ($action === 'check_in_qr') {
        $classId = $input['class_id'];
        $lat = $input['lat'] ?? 0;
        $lng = $input['lng'] ?? 0;
        $qrToken = $input['qr_token']; // รหัส 6 หลัก หรือ Token ยาวๆ แล้วแต่การตั้งค่า
        $type = $input['submission_type'] ?? 'scan'; // รับค่า 'scan' หรือ 'manual'

        // 1. ดึงข้อมูลห้องเรียนและ Session ปัจจุบัน
        $stmtClass = $pdo->prepare("SELECT subject_name, lat, lng, checkin_limit_time, qr_token, current_session_id FROM classrooms WHERE id = ?");
        $stmtClass->execute([$classId]);
        $class = $stmtClass->fetch();

        // ถ้าไม่มี current_session_id แปลว่าอาจารย์ยังไม่กดเริ่มคลาส
        if (!$class || empty($class['current_session_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'ยังไม่มีการเปิดคลาสเรียนในขณะนี้']);
            exit;
        }

        // 2. ตรวจสอบรหัส (Token/Code)
        // ต้องตรงกับที่อาจารย์เปิดอยู่ (qr_token ใน DB)
        if ($class['qr_token'] !== $qrToken) {
            echo json_encode(['status' => 'error', 'message' => 'รหัสไม่ถูกต้อง หรือหมดอายุแล้ว']);
            exit;
        }

        // 3. ตรวจสอบว่าเช็คชื่อไปแล้วหรือยังใน Session นี้
        $currentSession = $class['current_session_id'];
        $stmtCheck = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND classroom_id = ? AND session_token = ?");
        $stmtCheck->execute([$studentUserId, $classId, $currentSession]);
        
        if ($stmtCheck->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'คุณเช็คชื่อในคาบนี้ไปแล้ว']);
            exit;
        }

        // 4. ตรวจสอบระยะทาง GPS (เฉพาะโหมด Scan)
        $distance = 0;
        $distanceText = "";

        if ($type === 'scan') {
            // สูตรคำนวณระยะทาง Haversine
            $earthRadius = 6371000; // เมตร
            $latFrom = deg2rad($lat);
            $lonFrom = deg2rad($lng);
            $latTo = deg2rad($class['lat']);
            $lonTo = deg2rad($class['lng']);

            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;

            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            $distance = $earthRadius * $angle;
            $distanceText = round($distance) . ' ม.';

            // ถ้าระยะเกิน 50 เมตร ห้ามเช็คชื่อ
            if ($distance > 50) {
                echo json_encode(['status' => 'error', 'message' => 'คุณอยู่นอกพื้นที่ห้องเรียน (ห่าง ' . round($distance) . ' ม.)']);
                exit;
            }
        } else {
            // โหมด Manual (กรอกรหัส) -> ข้ามการเช็ค GPS
            // ถือว่าเป็นการเรียน Online หรือได้รับอนุญาตเป็นกรณีพิเศษ
            $distance = -1; 
            $distanceText = "Online/Remote";
        }

        // 5. คำนวณสถานะ มาเรียน/มาสาย
        $status = 'present';
        $currentTime = date('H:i:s');
        // ถ้ามีการกำหนดเวลาสาย และเวลาปัจจุบันเกินเวลาสาย -> ปรับเป็น late
        if (!empty($class['checkin_limit_time']) && $class['checkin_limit_time'] !== '00:00:00') {
            if ($currentTime > $class['checkin_limit_time']) {
                $status = 'late';
            }
        }

        // 6. บันทึกลงฐานข้อมูล
        $sqlInsert = "INSERT INTO attendance (student_id, classroom_id, status, location_lat, location_lng, session_token, checkin_time) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);

        if ($stmtInsert->execute([$studentUserId, $classId, $status, $lat, $lng, $currentSession])) {
            echo json_encode([
                'status' => 'success',
                'subject_name' => $class['subject_name'],
                'checkin_status' => ($status === 'present' ? 'มาเรียน' : 'มาสาย'),
                'time' => date('H:i'),
                'distance' => $distanceText
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
        }
    }

    // =================================================================
    // Action 4: ดูประวัติการเข้าเรียน (History)
    // =================================================================
    elseif ($action === 'get_history') {
        $classId = $input['class_id'];
        
        // ดึงชื่อวิชา
        $stmtClass = $pdo->prepare("SELECT subject_name, course_code FROM classrooms WHERE id = ?");
        $stmtClass->execute([$classId]);
        $classInfo = $stmtClass->fetch();

        if (!$classInfo) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวิชา']);
            exit;
        }

        // ดึงประวัติทั้งหมดของนักเรียนคนนี้ ในวิชานี้
        $stmtHist = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND classroom_id = ? ORDER BY checkin_time DESC");
        $stmtHist->execute([$studentUserId, $classId]);
        $history = $stmtHist->fetchAll();

        echo json_encode([
            'status' => 'success',
            'subject_name' => $classInfo['subject_name'],
            'course_code' => $classInfo['course_code'],
            'history' => $history
        ]);
    }

    // Default case
    else {
        echo json_encode(['status' => 'error', 'message' => 'Unknown Action']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>