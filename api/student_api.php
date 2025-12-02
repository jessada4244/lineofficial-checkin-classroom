<?php
// api/student_api.php
date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

if (empty($lineId)) { echo json_encode(['status' => 'error', 'message' => 'No Line ID']); exit; }

// ** แก้ไข: SELECT edu_id **
$stmt = $pdo->prepare("SELECT id, edu_id, name FROM users WHERE line_user_id = ? AND role = 'student'");
$stmt->execute([$lineId]);
$student = $stmt->fetch();

if (!$student) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลนิสิต หรือคุณไม่ใช่ Student']); exit; }
$studentUserId = $student['id'];

try {
    if ($action === 'get_my_classes') {
        $sql = "SELECT c.*, u.name as teacher_name FROM classrooms c JOIN classroom_members cm ON c.id = cm.classroom_id JOIN users u ON c.teacher_id = u.id WHERE cm.student_id = ? ORDER BY c.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$studentUserId]);
        echo json_encode(['status' => 'success', 'classes' => $stmt->fetchAll()]);
    }
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
        } catch (\PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'คุณเป็นสมาชิกห้องเรียนนี้อยู่แล้ว']); }
    }
    elseif ($action === 'check_in_qr') {
        $classId = $input['class_id'];
        $lat = $input['lat'] ?? 0;
        $lng = $input['lng'] ?? 0;
        $qrToken = $input['qr_token']; 
        $type = $input['submission_type'] ?? 'scan'; 

        $stmtClass = $pdo->prepare("SELECT subject_name, lat, lng, checkin_limit_time, qr_token, current_session_id FROM classrooms WHERE id = ?");
        $stmtClass->execute([$classId]);
        $class = $stmtClass->fetch();

        if (!$class || empty($class['current_session_id'])) { echo json_encode(['status' => 'error', 'message' => 'ยังไม่มีการเปิดคลาสเรียนในขณะนี้']); exit; }
        if ($class['qr_token'] !== $qrToken) { echo json_encode(['status' => 'error', 'message' => 'รหัสไม่ถูกต้อง หรือหมดอายุแล้ว']); exit; }

        $currentSession = $class['current_session_id'];
        $stmtCheck = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND classroom_id = ? AND session_token = ?");
        $stmtCheck->execute([$studentUserId, $classId, $currentSession]);
        if ($stmtCheck->fetch()) { echo json_encode(['status' => 'error', 'message' => 'คุณเช็คชื่อในคาบนี้ไปแล้ว']); exit; }

        $distance = 0; $distanceText = "";
        if ($type === 'scan') {
            $earthRadius = 6371000; 
            $latFrom = deg2rad($lat); $lonFrom = deg2rad($lng); $latTo = deg2rad($class['lat']); $lonTo = deg2rad($class['lng']);
            $latDelta = $latTo - $latFrom; $lonDelta = $lonTo - $lonFrom;
            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            $distance = $earthRadius * $angle;
            $distanceText = round($distance) . ' ม.';
            if ($distance > 50) { echo json_encode(['status' => 'error', 'message' => 'คุณอยู่นอกพื้นที่ห้องเรียน (ห่าง ' . round($distance) . ' ม.)']); exit; }
        } else {
            $distance = -1; $distanceText = "Online/Remote";
        }

        $status = 'present';
        $currentTime = date('H:i:s');
        if (!empty($class['checkin_limit_time']) && $class['checkin_limit_time'] !== '00:00:00') {
            if ($currentTime > $class['checkin_limit_time']) { $status = 'late'; }
        }

        $sqlInsert = "INSERT INTO attendance (student_id, classroom_id, status, location_lat, location_lng, session_token, checkin_time) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);

        if ($stmtInsert->execute([$studentUserId, $classId, $status, $lat, $lng, $currentSession])) {
            echo json_encode([
                'status' => 'success', 'subject_name' => $class['subject_name'],
                'checkin_status' => ($status === 'present' ? 'มาเรียน' : 'มาสาย'),
                'time' => date('H:i'), 'distance' => $distanceText
            ]);
        } else { echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']); }
    }
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

} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]); }
?>