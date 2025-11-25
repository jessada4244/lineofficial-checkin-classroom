<?php
// setup_richmenu.php
// เวอร์ชันแก้ไข SSL Error และรวมทุกอย่างไว้ในไฟล์เดียว

// ==========================================
// 1. CONFIGURATION
// ==========================================
// *** ใส่ Channel Access Token ของคุณที่นี่ ***
$accessToken = 'XBHB0gvzj+/rd4xFbdkdkCvifDB/doyl5TVk04bojp2Gffq4EzvYivlTHXqQ4uTbFitI+dN5JfoRa2W7Y2lZKVWoQ4cZxp1WSdXMPLWjE99VWDNawnxLFghlFoAPwFALkMgRZtbZ65oMUmZTJBi6TAdB04t89/1O/w1cDnyilFU='; 

$imagePathPrefix = 'assets/images/'; // ตรวจสอบว่ารูปอยู่ที่นี่จริง

// กำหนดรายการเมนูทั้ง 4 แบบ
$menus = [
    'guest' => [
        'name' => 'Guest Menu',
        'image' => $imagePathPrefix . 'guest.jpg',
        'areas' => getThreeButtonAreas()
    ],
    'admin' => [
        'name' => 'Admin Menu',
        'image' => $imagePathPrefix . 'admin.jpg',
        'areas' => getThreeButtonAreas()
    ],
    'student' => [
        'name' => 'Student Menu',
        'image' => $imagePathPrefix . 'student.jpg',
        'areas' => getThreeButtonAreas()
    ],
    'teacher' => [
        'name' => 'Teacher Menu',
        'image' => $imagePathPrefix . 'teacher.jpg',
        'areas' => getTeacherMenuAreas() // ใช้แบบ 2 ปุ่ม (เล็ก-ยาว)
    ]
];

// ==========================================
// 2. PROCESS LOOP
// ==========================================
echo "<h1>Rich Menu Setup Log</h1><pre>";
$results = [];

// ตรวจสอบ Token ก่อน
if ($accessToken === 'YOUR_CHANNEL_ACCESS_TOKEN_HERE' || strlen($accessToken) < 20) {
    die("<h3 style='color:red;'>❌ กรุณาใส่ Channel Access Token ในบรรทัดที่ 9 ก่อนครับ</h3>");
}

foreach ($menus as $role => $config) {
    echo "Processing: <strong>" . strtoupper($role) . "</strong>...\n";

    // เช็คว่ามีรูปภาพจริงไหม
    if (!file_exists($config['image'])) {
        echo " [ERROR] ไม่พบไฟล์รูปภาพ: {$config['image']}\n";
        continue;
    }

    // A. สร้างโครงสร้างเมนู (Create Rich Menu)
    $jsonBody = json_encode([
        "size" => ["width" => 2500, "height" => 843],
        "selected" => false,
        "name" => $config['name'],
        "chatBarText" => "เมนูใช้งาน",
        "areas" => $config['areas']
    ]);

    $richMenuId = createRichMenu($accessToken, $jsonBody);

    if ($richMenuId) {
        echo " [SUCCESS] Created ID: $richMenuId\n";

        // B. อัปโหลดรูปภาพ (Upload Image)
        $ext = pathinfo($config['image'], PATHINFO_EXTENSION);
        $contentType = ($ext == 'png') ? 'image/png' : 'image/jpeg';
        
        $uploadResult = uploadRichMenuImage($accessToken, $richMenuId, $config['image'], $contentType);
        
        if (empty($uploadResult) || strpos($uploadResult, '{}') !== false) { 
            echo " [SUCCESS] Image uploaded.\n";
        } else {
            echo " [ERROR] Image upload failed: " . print_r($uploadResult, true) . "\n";
        }

        // C. ตั้งค่า Default สำหรับ Guest
        if ($role === 'guest') {
            setDefaultRichMenu($accessToken, $richMenuId);
            echo " [INFO] Set as DEFAULT menu.\n";
        }

        // เก็บ ID ไว้แสดงผล
        $results[$role] = $richMenuId;
    } else {
        echo " [ERROR] Failed to create menu logic. Check Token.\n";
    }
    echo "---------------------------------------------------\n";
}
echo "</pre>";

// ==========================================
// 3. RESULT DISPLAY
// ==========================================
if (!empty($results)) {
    echo "<h3>✅ Copy Code ด้านล่างไปใส่ในไฟล์ <code>config/line_config.php</code></h3>";
    echo "<textarea rows='10' cols='90' style='padding:15px; font-family:monospace; background:#f4f4f4; border:1px solid #ccc;'>";
    echo "<?php\n";
    echo "define('CHANNEL_ACCESS_TOKEN', '$accessToken');\n\n";
    foreach ($results as $role => $id) {
        echo "define('RICHMENU_" . strtoupper($role) . "', '$id');\n";
    }
    echo "?>";
    echo "</textarea>";
} else {
    echo "<h3 style='color:red;'>❌ ไม่สามารถสร้างเมนูได้ กรุณาตรวจสอบ Token และไฟล์รูปภาพ</h3>";
}


// ==========================================
// 4. FUNCTIONS (Fixed SSL & Types)
// ==========================================

function getThreeButtonAreas() {
    // 3 ปุ่ม (Guest/Admin/Student)
    return [
        [ "bounds" => ["x" => 0, "y" => 0, "width" => 833, "height" => 843], "action" => ["type" => "message", "text" => "Menu Left"] ],
        [ "bounds" => ["x" => 833, "y" => 0, "width" => 833, "height" => 843], "action" => ["type" => "message", "text" => "Menu Center"] ],
        [ "bounds" => ["x" => 1666, "y" => 0, "width" => 834, "height" => 843], "action" => ["type" => "message", "text" => "Menu Right"] ]
    ];
}

function getTeacherMenuAreas() {
    // 2 ปุ่ม (Teacher)
    return [
        [ "bounds" => ["x" => 0, "y" => 0, "width" => 833, "height" => 843], "action" => ["type" => "message", "text" => "รายงานการเช็คชื่อ"] ],
        [ "bounds" => ["x" => 833, "y" => 0, "width" => 1667, "height" => 843], "action" => ["type" => "message", "text" => "จัดการห้องเรียน"] ]
    ];
}

function createRichMenu($token, $body) {
    $ch = curl_init("https://api.line.me/v2/bot/richmenu");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // แก้ไข: ใช้ Constant โดยไม่มี Quotes
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ปิด SSL Verify
    
    $result = curl_exec($ch);
    $data = json_decode($result, true);
    return $data['richMenuId'] ?? null;
}

function uploadRichMenuImage($token, $richMenuId, $imagePath, $contentType) {
    $ch = curl_init("https://api-data.line.me/v2/bot/richmenu/$richMenuId/content");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($imagePath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // แก้ไข: ใช้ Constant โดยไม่มี Quotes
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: $contentType"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ปิด SSL Verify
    
    return curl_exec($ch);
}

function setDefaultRichMenu($token, $richMenuId) {
    $ch = curl_init("https://api.line.me/v2/bot/user/all/richmenu/$richMenuId");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // แก้ไข: ใช้ Constant โดยไม่มี Quotes
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ปิด SSL Verify
    
    curl_exec($ch);
}
?>