<?php
require_once '../config/db.php';

// สูตรคำนวณระยะทาง (Haversine Formula)
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // รัศมีโลก (เมตร)
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c; // ผลลัพธ์เป็นเมตร
}

// รับค่าพิกัดจาก LIFF (Student)
$studentLat = $_POST['lat'];
$studentLng = $_POST['lng'];
$classroomId = $_POST['class_id'];

// ดึงพิกัดห้องเรียนจาก Database
$stmt = $pdo->prepare("SELECT lat, lng, checkin_limit_time FROM classrooms WHERE id = ?");
$stmt->execute([$classroomId]);
$classroom = $stmt->fetch();

// คำนวณ
$distance = getDistance($studentLat, $studentLng, $classroom['lat'], $classroom['lng']);

if ($distance <= 50) {
    // เช็คเวลา
    $currentTime = date('H:i:s');
    $status = ($currentTime <= $classroom['checkin_limit_time']) ? 'present' : 'late';
    
    // บันทึกลง DB (Insert into attendance...)
    echo json_encode(['status' => 'success', 'msg' => 'เช็คชื่อสำเร็จ', 'attendance' => $status]);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'คุณอยู่นอกพื้นที่ (ระยะห่าง: ' . round($distance) . ' ม.)']);
}
?>