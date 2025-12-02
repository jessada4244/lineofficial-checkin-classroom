<?php
// api/teacher_api.php
date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/line_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lineId = $input['line_id'] ?? '';

// Check Teacher
if (empty($lineId)) { echo json_encode(['status' => 'error', 'message' => 'No Line ID']); exit; }
$stmt = $pdo->prepare("SELECT id FROM users WHERE line_user_id = ? AND role = 'teacher'");
$stmt->execute([$lineId]);
$teacher = $stmt->fetch();
if (!$teacher) { echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit; }
$teacherId = $teacher['id'];

try {
    if ($action === 'get_classes') {
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY id DESC");
        $stmt->execute([$teacherId]);
        echo json_encode(['status' => 'success', 'classes' => $stmt->fetchAll()]);
    }
    elseif ($action === 'create_class') {
        $name = $input['name']; $courseCode = $input['course_code']; 
        $color = $input['color'] ?? '#FFFFFF'; $limit = 40; $classCode = rand(100000, 999999); 
        $sql = "INSERT INTO classrooms (teacher_id, subject_name, course_code, class_code, room_color, student_limit) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$teacherId, $name, $courseCode, $classCode, $color, $limit])) echo json_encode(['status' => 'success']);
        else throw new Exception("Save Failed");
    }
    elseif ($action === 'get_class_details') {
        $classId = $input['class_id'];
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$classId, $teacherId]);
        $class = $stmt->fetch();
        if (!$class) { echo json_encode(['status' => 'error', 'message' => 'Not Found']); exit; }
        
        // ** แก้ไข: ดึง edu_id แทน student_id **
        $stmtMembers = $pdo->prepare("SELECT u.id, u.edu_id, u.name FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ?");
        $stmtMembers->execute([$classId]);
        $class['members'] = $stmtMembers->fetchAll();
        
        echo json_encode(['status' => 'success', 'class' => $class]);
    }
    elseif ($action === 'update_class') {
        $classId = $input['class_id'];
        $sqlParts = []; $params = [];
        if (isset($input['name'])) { $sqlParts[] = "subject_name = ?"; $params[] = $input['name']; }
        if (isset($input['course_code'])) { $sqlParts[] = "course_code = ?"; $params[] = $input['course_code']; }
        if (isset($input['color'])) { $sqlParts[] = "room_color = ?"; $params[] = $input['color']; }
        if (isset($input['time'])) { $sqlParts[] = "checkin_limit_time = ?"; $params[] = $input['time']; }
        if (isset($input['lat'])) { $sqlParts[] = "lat = ?"; $params[] = ($input['lat']===''?NULL:$input['lat']); }
        if (isset($input['lng'])) { $sqlParts[] = "lng = ?"; $params[] = ($input['lng']===''?NULL:$input['lng']); }
        if (isset($input['zoom_link'])) { $sqlParts[] = "zoom_link = ?"; $params[] = $input['zoom_link']; }
        if (isset($input['teams_link'])) { $sqlParts[] = "teams_link = ?"; $params[] = $input['teams_link']; }
        if (empty($sqlParts)) { echo json_encode(['status' => 'success']); exit; }
        $sql = "UPDATE classrooms SET " . implode(', ', $sqlParts) . " WHERE id = ? AND teacher_id = ?";
        $params[] = $classId; $params[] = $teacherId;
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) echo json_encode(['status' => 'success']);
        else throw new Exception("Update Failed");
    }
    elseif ($action === 'add_member') {
        $eduId = $input['student_code']; // รับรหัสที่กรอกเข้ามา (ซึ่งตอนนี้คือ edu_id)
        $classId = $input['class_id'];
        
        // ** แก้ไข: ค้นหาด้วย edu_id **
        $stmtUser = $pdo->prepare("SELECT id FROM users WHERE edu_id = ? AND role = 'student'");
        $stmtUser->execute([$eduId]); 
        $student = $stmtUser->fetch();
        
        if (!$student) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสนิสิตนี้ในระบบ']); exit; }
        try { 
            $pdo->prepare("INSERT INTO classroom_members (classroom_id, student_id) VALUES (?, ?)")->execute([$classId, $student['id']]); 
            echo json_encode(['status' => 'success']); 
        } 
        catch (\PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'มีนิสิตคนนี้แล้ว']); }
    }
    elseif ($action === 'remove_member') {
        $pdo->prepare("DELETE FROM classroom_members WHERE classroom_id = ? AND student_id = ?")->execute([$input['class_id'], $input['student_id_to_remove']]);
        echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'delete_class') {
        $classId = $input['class_id'];
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM classroom_members WHERE classroom_id = ?")->execute([$classId]);
        $pdo->prepare("DELETE FROM attendance WHERE classroom_id = ?")->execute([$classId]);
        $pdo->prepare("DELETE FROM classrooms WHERE id = ? AND teacher_id = ?")->execute([$classId, $teacherId]);
        $pdo->commit(); echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'start_new_session') {
        $classId = $input['class_id'];
        $mode = $input['mode'] ?? 'onsite'; 
        $notify = $input['notify'] ?? false; 
        $newTime = $input['time'] ?? null;
        $customLink = $input['link'] ?? '';
        $sessionId = uniqid('SESS_');
        $qrToken = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT); 
        $isOnline = ($mode !== 'onsite') ? 1 : 0;
        
        $stmtInfo = $pdo->prepare("SELECT subject_name FROM classrooms WHERE id = ?");
        $stmtInfo->execute([$classId]);
        $subjectName = $stmtInfo->fetchColumn();
        $meetingLink = $customLink;

        $sql = "UPDATE classrooms SET current_session_id = ?, qr_token = ?, is_online_session = ?, session_link = ?, checkin_limit_time = ? WHERE id = ? AND teacher_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sessionId, $qrToken, $isOnline, $meetingLink, $newTime, $classId, $teacherId]);

        if ($isOnline && !empty($meetingLink) && $notify) {
            $sqlStudents = "SELECT u.line_user_id FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ? AND u.line_user_id IS NOT NULL";
            $stmtStd = $pdo->prepare($sqlStudents);
            $stmtStd->execute([$classId]);
            $studentLineIds = $stmtStd->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($studentLineIds)) {
                $platformName = ($mode === 'zoom') ? "Zooms" : "Microsoft Teams";
                $msgText = "สามารถเข้าร่วมห้องเรียนออนไลน์: $subjectName\nเข้าเรียนผ่าน $platformName ตาม Link ดังนี้ 👇\n$meetingLink";
                foreach (array_chunk($studentLineIds, 150) as $chunk) {
                    sendLineMulticast($chunk, $msgText, CHANNEL_ACCESS_TOKEN);
                }
            }
        }
        echo json_encode(['status' => 'success', 'session_id' => $sessionId, 'qr_token' => $qrToken, 'subject_name' => $subjectName, 'limit_time' => $newTime, 'meeting_link' => $meetingLink]);
    }
    elseif ($action === 'rotate_qr_token') {
        $classId = $input['class_id'];
        $newToken = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("UPDATE classrooms SET qr_token = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$newToken, $classId, $teacherId]);
        echo json_encode(['status' => 'success', 'new_qr_token' => $newToken]);
    }
    elseif ($action === 'get_live_status') {
        $classId = $input['class_id'];
        $stmtC = $pdo->prepare("SELECT current_session_id FROM classrooms WHERE id = ?"); $stmtC->execute([$classId]); $currSession = $stmtC->fetchColumn();
        
        // ** แก้ไข: ดึง edu_id **
        $stmtStd = $pdo->prepare("SELECT u.id, u.edu_id, u.name FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ? ORDER BY u.edu_id ASC"); 
        $stmtStd->execute([$classId]); $allStudents = $stmtStd->fetchAll();
        
        $stmtAtt = $pdo->prepare("SELECT student_id, status, checkin_time FROM attendance WHERE classroom_id = ? AND session_token = ?"); $stmtAtt->execute([$classId, $currSession]); $attendees = $stmtAtt->fetchAll();
        $attMap = []; foreach($attendees as $a) { $attMap[$a['student_id']] = $a; }
        $checked_in = []; $not_checked_in = [];
        foreach($allStudents as $std) {
            // ใช้ edu_id ในการส่งกลับไปแสดงผล
            if (isset($attMap[$std['id']])) { $checked_in[] = ['name' => $std['name'], 'student_id' => $std['edu_id'], 'status' => $attMap[$std['id']]['status'], 'time' => date('H:i:s', strtotime($attMap[$std['id']]['checkin_time']))]; } 
            else { $not_checked_in[] = ['name' => $std['name'], 'student_id' => $std['edu_id']]; }
        }
        echo json_encode(['status' => 'success', 'checked_in' => $checked_in, 'not_checked_in' => $not_checked_in, 'count_in' => count($checked_in), 'count_not' => count($not_checked_in)]);
    }
    elseif ($action === 'get_checkin_sessions') {
        $classId = $input['class_id'];
        $stmt = $pdo->prepare("SELECT session_token, MIN(checkin_time) as first_checkin FROM attendance WHERE classroom_id = ? AND session_token IS NOT NULL GROUP BY session_token ORDER BY first_checkin DESC"); $stmt->execute([$classId]); $rounds = $stmt->fetchAll();
        $sessionList = []; foreach($rounds as $r) { $dt = new DateTime($r['first_checkin']); $sessionList[] = ['session_token' => $r['session_token'], 'date' => $dt->format('d/m/Y'), 'time' => $dt->format('H:i')]; }
        $stmtName = $pdo->prepare("SELECT subject_name FROM classrooms WHERE id = ?"); $stmtName->execute([$classId]); $sub = $stmtName->fetchColumn();
        echo json_encode(['status' => 'success', 'subject_name' => $sub, 'sessions' => $sessionList]);
    }
    elseif ($action === 'get_session_report') {
        $classId = $input['class_id']; $token = $input['session_token'];
        
        // ** แก้ไข: ดึง edu_id **
        $stmtStd = $pdo->prepare("SELECT u.id, u.edu_id, u.name FROM classroom_members cm JOIN users u ON cm.student_id = u.id WHERE cm.classroom_id = ? ORDER BY u.edu_id ASC"); 
        $stmtStd->execute([$classId]); $allStudents = $stmtStd->fetchAll();
        
        $stmtAtt = $pdo->prepare("SELECT student_id, status, checkin_time FROM attendance WHERE classroom_id = ? AND session_token = ?"); $stmtAtt->execute([$classId, $token]); $attendees = $stmtAtt->fetchAll();
        $attMap = []; foreach($attendees as $a) { $attMap[$a['student_id']] = $a; }
        $report = []; $summary = ['present'=>0, 'late'=>0, 'absent'=>0];
        foreach($allStudents as $std) {
            $sid = $std['id']; $status = 'absent'; $time = '-';
            if(isset($attMap[$sid])) { $status = $attMap[$sid]['status']; $time = date('H:i', strtotime($attMap[$sid]['checkin_time'])); }
            if(isset($summary[$status])) $summary[$status]++;
            $report[] = ['name' => $std['name'], 'student_id' => $std['edu_id'], 'status' => $status, 'checkin_time' => $time];
        }
        echo json_encode(['status'=>'success', 'summary'=>$summary, 'report'=>$report]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
function sendLineMulticast($userIds, $text, $token) {
    $ch = curl_init("https://api.line.me/v2/bot/message/multicast");
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode(["to"=>$userIds,"messages"=>[["type"=>"text","text"=>$text]]]), CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>["Content-Type: application/json","Authorization: Bearer $token"], CURLOPT_SSL_VERIFYPEER=>false]);
    curl_exec($ch); curl_close($ch);
}
?>