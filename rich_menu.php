<?php
// auto_update_menu.php
// สคริปต์อัปเดตเมนูแบบ One-Stop Service (ลบเก่า -> สร้างใหม่ -> แก้ Config ให้เอง)

header('Content-Type: text/html; charset=utf-8');
require_once 'config/line_config.php';

// 1. เก็บ Token เดิมไว้ก่อน (เดี๋ยวต้องใช้เขียนกลับลงไฟล์)
$oldToken  = CHANNEL_ACCESS_TOKEN;
$oldSecret = CHANNEL_SECRET;
$imagePathPrefix = 'assets/images/'; 

// 2. กำหนดโครงสร้างเมนู (เหมือนเดิม)
$menus = [
    'guest' => [
        'name' => 'Guest Menu',
        'image' => $imagePathPrefix . 'guest.jpg', // เปลี่ยนชื่อรูปให้ตรงกับที่มีจริง
        'areas' => [
            [ "bounds" => ["x"=>0, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"message", "text"=>"ติดต่อเจ้าหน้าที่"] ],
            [ "bounds" => ["x"=>833, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> "https://liff.line.me/2008573640-Z1aN5Eyn"] ], // **อย่าลืมแก้ Link**
            [ "bounds" => ["x"=>1666, "y"=>0, "width"=>834, "height"=>843], "action" => ["type"=>"uri", "uri"=> "https://liff.line.me/2008573640-9pYeN4Dn"] ]   // **อย่าลืมแก้ Link**
        ]
    ],
    'admin' => [
        'name' => 'Admin Menu',
        'image' => $imagePathPrefix . 'admin.jpg',
        'areas' => [
            [ "bounds" => ["x"=>0, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> "https://liff.line.me/2008573640-Xlr1jY4w"] ],     // **อย่าลืมแก้ Link**
            [ "bounds" => ["x"=>833, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"message", "text"=>"เมนูประกาศ"] ],
            [ "bounds" => ["x"=>1666, "y"=>0, "width"=>834, "height"=>843], "action" => ["type"=>"message", "text"=>"Admin Tools"] ]
        ]
    ],
    'teacher' => [
        'name' => 'Teacher Menu',
        'image' => $imagePathPrefix . 'teacher.jpg',
        'areas' => [
            [ "bounds" => ["x"=>0, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> "https://liff.line.me/2008573640-qQxJWXLz"] ],   // **อย่าลืมแก้ Link**
            [ "bounds" => ["x"=>833, "y"=>0, "width"=>1667, "height"=>843], "action" => ["type"=>"uri", "uri"=> "https://liff.line.me/2008573640-qQxJWXLz"] ] // **อย่าลืมแก้ Link**
        ]
    ],
    'student' => [
        'name' => 'Student Menu',
        'image' => $imagePathPrefix . 'student.jpg',
        'areas' => [
            [ "bounds" => ["x"=>0, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> "https://liff.line.me/2008573640-jb4bpE5J"] ],   // **อย่าลืมแก้ Link**
            [ "bounds" => ["x"=>833, "y"=>0, "width"=>833, "height"=>843], "action" => ["type"=>"uri", "uri"=> "https://liff.line.me/2008573640-jb4bpE5J"] ], // **อย่าลืมแก้ Link**
            [ "bounds" => ["x"=>1666, "y"=>0, "width"=>834, "height"=>843], "action" => ["type"=>"message", "text"=>"คู่มือการใช้งาน"] ]
        ]
    ]
];

echo "<pre><h1>🚀 เริ่มกระบวนการอัปเดตอัตโนมัติ</h1>";

// -----------------------------------------------------
// STEP 1: ลบเมนูเก่าทิ้งให้หมด (Cleanup)
// -----------------------------------------------------
echo "1. กำลังลบเมนูเก่า... ";
$allMenus = getRichMenuList($oldToken);
if (!empty($allMenus['richmenus'])) {
    foreach ($allMenus['richmenus'] as $m) {
        deleteRichMenu($oldToken, $m['richMenuId']);
    }
    echo "✅ ลบเรียบร้อย (" . count($allMenus['richmenus']) . " รายการ)\n";
} else {
    echo "⚪ ไม่มีเมนูเก่าค้างอยู่\n";
}

// -----------------------------------------------------
// STEP 2: สร้างเมนูใหม่ + อัปโหลดรูป (Create & Upload)
// -----------------------------------------------------
$newIds = [];
echo "2. กำลังสร้างเมนูใหม่...\n";

foreach ($menus as $role => $config) {
    if (!file_exists($config['image'])) {
        die("❌ Error: ไม่พบไฟล์รูปภาพ " . $config['image']);
    }

    // สร้าง
    $jsonBody = json_encode([
        "size" => ["width" => 2500, "height" => 843],
        "selected" => false,
        "name" => $config['name'],
        "chatBarText" => "เมนูใช้งาน",
        "areas" => $config['areas']
    ]);
    $richMenuId = createRichMenu($oldToken, $jsonBody);

    if ($richMenuId) {
        // อัปรูป
        $ext = pathinfo($config['image'], PATHINFO_EXTENSION);
        $contentType = ($ext == 'png') ? 'image/png' : 'image/jpeg';
        uploadRichMenuImage($oldToken, $richMenuId, $config['image'], $contentType);
        
        $newIds[$role] = $richMenuId;
        echo "   - $role : สร้างเสร็จ (ID: $richMenuId)\n";

        // ถ้าเป็น Guest ให้ตั้งเป็น Default เลย
        if ($role === 'guest') {
            setDefaultRichMenu($oldToken, $richMenuId);
            echo "     -> ⭐ ตั้งเป็น Default เรียบร้อย\n";
        }
    } else {
        die("❌ Error: สร้างเมนู $role ไม่สำเร็จ");
    }
}

// -----------------------------------------------------
// STEP 3: เขียนทับไฟล์ Config (Auto Write Config)
// -----------------------------------------------------
echo "3. กำลังอัปเดตไฟล์ config/line_config.php ... \n";

$configFileContent = "<?php\n";
$configFileContent .= "define('CHANNEL_ACCESS_TOKEN', '$oldToken');\n";
$configFileContent .= "define('CHANNEL_SECRET', '$oldSecret');\n\n";

foreach ($newIds as $role => $id) {
    $configFileContent .= "define('RICHMENU_" . strtoupper($role) . "', '$id');\n";
}
$configFileContent .= "?>";

// พยายามเขียนไฟล์ (ใส่ @ เพื่อซ่อน Error ตัวแดง)
if (@file_put_contents('config/line_config.php', $configFileContent)) {
    echo "✅ บันทึกไฟล์สำเร็จ!\n";
} else {
    // ถ้าเขียนไม่ได้ ให้แสดง Textarea ให้คนก๊อปปี้แทน
    echo "❌ Error: เขียนไฟล์ไม่ได้ (ติด Permission)\n";
    echo "------------------------------------------------------\n";
    echo "⚠️ ไม่ต้องตกใจ! ให้ก๊อปปี้โค้ดในกล่องด้านล่าง \n";
    echo "👉 ไปวางทับในไฟล์ 'config/line_config.php' ด้วยตัวเองครับ\n";
    echo "------------------------------------------------------\n\n";
    echo "<textarea rows='10' style='width:100%; padding:10px; background:#f0f0f0; border:1px solid #ccc; font-family:monospace;'>";
    echo htmlspecialchars($configFileContent);
    echo "</textarea>\n";
}

echo "\n<h1>🎉 เสร็จสิ้นกระบวนการ!</h1>";
echo "</pre>";


// ================= HELPER FUNCTIONS =================

function getRichMenuList($token) {
    $ch = curl_init("https://api.line.me/v2/bot/richmenu/list");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    return json_decode(curl_exec($ch), true);
}

function deleteRichMenu($token, $id) {
    $ch = curl_init("https://api.line.me/v2/bot/richmenu/$id");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
}

function createRichMenu($token, $body) {
    $ch = curl_init("https://api.line.me/v2/bot/richmenu");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = json_decode(curl_exec($ch), true);
    return $data['richMenuId'] ?? null;
}

function uploadRichMenuImage($token, $id, $path, $type) {
    $ch = curl_init("https://api-data.line.me/v2/bot/richmenu/$id/content");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($path));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: $type"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
}

function setDefaultRichMenu($token, $id) {
    $ch = curl_init("https://api.line.me/v2/bot/user/all/richmenu/$id");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
}

?>