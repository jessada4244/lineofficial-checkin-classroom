<?php
// setup_richmenu_all.php
// รวมการสร้างเมนู + อัปโหลดรูป + ฝังลิงก์ LIFF ไว้ในไฟล์เดียว

// ==========================================
// 1. CONFIGURATION (แก้ไขตรงนี้)
// ==========================================

$accessToken = 'XBHB0gvzj+/rd4xFbdkdkCvifDB/doyl5TVk04bojp2Gffq4EzvYivlTHXqQ4uTbFitI+dN5JfoRa2W7Y2lZKVWoQ4cZxp1WSdXMPLWjE99VWDNawnxLFghlFoAPwFALkMgRZtbZ65oMUmZTJBi6TAdB04t89/1O/w1cDnyilFU=';
// *** ใส่ LIFF URL ของคุณที่นี่ ***
$liff_login   = "https://liff.line.me/2008562649-3z1WPZD2"; 
$liff_register = "https://liff.line.me/2008573640-Z1aN5Eyn";   // หน้า Login
$liff_teacher = "https://liff.line.me/2008562649-bkoEQOMg";  // หน้า Manage Class
$liff_student = "https://liff.line.me/2008562649-LEXWJgaD";  // หน้า Class List
$liff_admin   = "https://liff.line.me/2008562649-kEj37pqY";    // หน้า Admin Dashboard (ถ้ามี LIFF แยก)
// =================================================
// 1.3 Path ของรูปภาพ (ต้องมีไฟล์รูปจริงวางอยู่)
$imagePathPrefix = 'assets/images/'; 

// ==========================================
// 2. DEFINE MENUS STRUCTURE
// ==========================================

$menus = [
    // --- 1. Guest Menu (คนทั่วไป) ---
    'guest' => [
        'name' => 'Guest Menu',
        'image' => $imagePathPrefix . 'front.jpg',
        'areas' => [
            // ปุ่มซ้าย: ติดต่อ (Text)
            [ "bounds" => ["x"=>0, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"message", "text"=>"ติดต่อเจ้าหน้าที่"] ],
            // ปุ่มกลาง: สมัครสมาชิก (Link -> Register)
            [ "bounds" => ["x"=>833, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> $liff_register] ],
            // ปุ่มขวา: เข้าสู่ระบบ (Link -> Login)
            [ "bounds" => ["x"=>1666, "y"=>0, "width"=>834, "height"=>843], "action" => ["type"=>"uri", "uri"=> $liff_login] ]
        ]
    ],

    // --- 2. Admin Menu ---
    'admin' => [
        'name' => 'Admin Menu',
        'image' => $imagePathPrefix . 'admin.jpg',
        'areas' => [
            // ปุ่มซ้าย: Dashboard (Link -> Admin)
            [ "bounds" => ["x"=>0, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> $liff_admin] ],
            // ปุ่มกลาง: ประกาศ (Text Trigger หรือทำ Link เพิ่ม)
            [ "bounds" => ["x"=>833, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"message", "text"=>"เมนูประกาศ"] ],
            // ปุ่มขวา: อื่นๆ
            [ "bounds" => ["x"=>1666, "y"=>0, "width"=>834, "height"=>843], "action" => ["type"=>"message", "text"=>"Admin Tools"] ]
        ]
    ],

    // --- 3. Teacher Menu (2 ปุ่ม: เล็ก-ยาว) ---
    'teacher' => [
        'name' => 'Teacher Menu',
        'image' => $imagePathPrefix . 'teacher.jpg',
        'areas' => [
            // ปุ่มซ้าย (เล็ก): รายงาน/เช็คชื่อ (Link -> Teacher Page)
            [ "bounds" => ["x"=>0, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> $liff_teacher] ],
            // ปุ่มขวา (ยาว): จัดการห้องเรียน (Link -> Teacher Page)
            [ "bounds" => ["x"=>833, "y"=>0, "width"=>1667, "height"=>843], "action" => ["type"=>"uri", "uri"=> $liff_teacher] ]
        ]
    ],

    // --- 4. Student Menu ---
    'student' => [
        'name' => 'Student Menu',
        'image' => $imagePathPrefix . 'student.jpg',
        'areas' => [
            // ปุ่มซ้าย: เช็คชื่อ/เข้าห้อง (Link -> Student Page)
            [ "bounds" => ["x"=>0, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> $liff_student] ],
            // ปุ่มกลาง: ประวัติ (Link -> Student Page แล้วกดดูประวัติ)
            [ "bounds" => ["x"=>833, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> $liff_student] ],
            // ปุ่มขวา: คู่มือ/อื่นๆ
            [ "bounds" => ["x"=>1666, "y"=>0, "width"=>834, "height"=>843], "action" => ["type"=>"message", "text"=>"คู่มือการใช้งาน"] ]
        ]
    ]
];

// ==========================================
// 3. EXECUTION LOOP
// ==========================================
echo "<h1>Rich Menu Setup Log</h1><pre>";
$results = [];

// Check Token
if (strpos($accessToken, 'ใส่_') !== false) {
    die("<h3 style='color:red;'>❌ กรุณาแก้ไขไฟล์เพื่อใส่ Channel Access Token ก่อนครับ</h3>");
}

foreach ($menus as $role => $config) {
    echo "Processing: <strong>" . strtoupper($role) . "</strong>...\n";

    // 1. เช็คไฟล์รูป
    if (!file_exists($config['image'])) {
        echo " [ERROR] Image not found: {$config['image']}\n";
        continue;
    }

    // 2. สร้างเมนู
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

        // 3. อัปโหลดรูป
        $ext = pathinfo($config['image'], PATHINFO_EXTENSION);
        $contentType = ($ext == 'png') ? 'image/png' : 'image/jpeg';
        
        $uploadResult = uploadRichMenuImage($accessToken, $richMenuId, $config['image'], $contentType);
        
        if (empty($uploadResult) || strpos($uploadResult, '{}') !== false) { 
            echo " [SUCCESS] Image uploaded.\n";
        } else {
            echo " [ERROR] Image upload failed: " . print_r($uploadResult, true) . "\n";
        }

        // 4. ตั้งค่า Default (เฉพาะ Guest)
        if ($role === 'guest') {
            setDefaultRichMenu($accessToken, $richMenuId);
            echo " [INFO] Set as DEFAULT menu.\n";
        }

        $results[$role] = $richMenuId;
    } else {
        echo " [ERROR] Failed to create menu.\n";
    }
    echo "---------------------------------------------------\n";
}
echo "</pre>";

// ==========================================
// 4. OUTPUT CONFIG
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
    echo "<h3 style='color:red;'>❌ เกิดข้อผิดพลาด ไม่ได้ ID กลับมา</h3>";
}

// ==========================================
// 5. HELPER FUNCTIONS
// ==========================================

function createRichMenu($token, $body) {
    $ch = curl_init("https://api.line.me/v2/bot/richmenu");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix Localhost SSL
    
    $result = curl_exec($ch);
    $data = json_decode($result, true);
    return $data['richMenuId'] ?? null;
}

function uploadRichMenuImage($token, $richMenuId, $imagePath, $contentType) {
    $ch = curl_init("https://api-data.line.me/v2/bot/richmenu/$richMenuId/content");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($imagePath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: $contentType"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix Localhost SSL
    return curl_exec($ch);
}

function setDefaultRichMenu($token, $richMenuId) {
    $ch = curl_init("https://api.line.me/v2/bot/user/all/richmenu/$richMenuId");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix Localhost SSL
    curl_exec($ch);
}
?>