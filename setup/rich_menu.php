<?php
// setup/rich_menu.php
// --------------------------------------------------------
// 1. SECURITY CHECK (เพิ่มใหม่)
// --------------------------------------------------------
require_once '../config/security.php';
// บังคับว่าต้อง Login เป็น Admin เท่านั้นถึงจะรันไฟล์นี้ได้
checkLogin('admin'); 

header('Content-Type: text/html; charset=utf-8');

// แก้ไข Path เรียกไฟล์ config (ถอยกลับ 1 ชั้น)
require_once '../config/line_config.php';

$oldToken  = CHANNEL_ACCESS_TOKEN;
$oldSecret = CHANNEL_SECRET;

// แก้ไข Path รูปภาพ (ถอยกลับ 1 ชั้น)
$imagePathPrefix = '../assets/images/';

// ==========================================
// 2. DEFINE MENUS
// ==========================================
// ลิงก์ LIFF ที่ใช้ (ควรแก้ให้ตรงกับของคุณ)
$liff_contact_admin  = "https://liff.line.me/2008573640-4dv1PmaJ";
$liff_contact  = "https://liff.line.me/2008573640-4dv1PmaJ";
$liff_login    = "https://liff.line.me/2008573640-9pYeN4Dn"; // แก้เป็น LIFF ID หน้า Login ของคุณ
$liff_register = "https://liff.line.me/2008573640-Z1aN5Eyn"; // แก้เป็น LIFF ID หน้า Register
$liff_teacher  = "https://liff.line.me/2008573640-qQxJWXLz"; // หน้าจัดการสอน
$liff_student  = "https://liff.line.me/2008573640-jb4bpE5J"; // หน้าเช็คชื่อ
$liff_admin    = "https://liff.line.me/2008573640-Xlr1jY4w"; // หน้า Admin Dashboard


$menus = [
    'guest' => [
        'name' => 'Guest Menu',
        'image' => $imagePathPrefix . 'guest.jpg', // ชื่อไฟล์รูปต้องตรงกับที่มีใน assets/images/
        'areas' => [
            [ "bounds"=>["x"=>0,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_contact] ],
            [ "bounds"=>["x"=>833,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_register] ],
            [ "bounds"=>["x"=>1666,"y"=>0,"width"=>834,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_login] ]
        ]
    ],
    'admin' => [
        'name' => 'Admin Menu',
        'image' => $imagePathPrefix . 'admin.jpg',
        'areas' => [
            // [ "bounds"=>["x"=>0,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_admin] ],
            [ "bounds"=>["x"=>833,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_admin] ],
            [ "bounds"=>["x"=>1666,"y"=>0,"width"=>834,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_admin] ]
        ]
    ],
    'teacher' => [
        'name' => 'Teacher Menu',
        'image' => $imagePathPrefix . 'teacher.jpg',
        'areas' => [
            // [ "bounds"=>["x"=>0,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_contact] ],
            [ "bounds"=>["x"=>833,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_contact] ],
            [ "bounds"=>["x"=>1666,"y"=>0,"width"=>834,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_teacher] ]
        ]
    ],
    'student' => [
        'name' => 'Student Menu',
        'image' => $imagePathPrefix . 'student.jpg', // แก้ชื่อไฟล์เป็น .jpg หรือ .png ตามจริง
        'areas' => [
            // [ "bounds"=>["x"=>0,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_contact] ],
            [ "bounds"=>["x"=>833,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_contact] ],
            [ "bounds"=>["x"=>1666,"y"=>0,"width"=>834,"height"=>843], "action"=>["type"=>"uri","uri"=> $liff_student] ]
        ]
    ]
];

ob_start(); 

// --------------------------------------------------------
// 3. PROCESS
// --------------------------------------------------------

echo "STEP 1: ลบเมนูเก่า...\n";
$allMenus = getRichMenuList($oldToken);

if (!empty($allMenus['richmenus'])) {
    foreach ($allMenus['richmenus'] as $m) {
        deleteRichMenu($oldToken, $m['richMenuId']);
    }
    echo "✔ ลบทั้งหมด " . count($allMenus['richmenus']) . " รายการ\n\n";
} else {
    echo "ℹ ไม่มีข้อมูลเมนูค้างอยู่\n\n";
}

echo "STEP 2: สร้างเมนูใหม่...\n";

$newIds = [];

foreach ($menus as $role => $config) {
    // เช็คว่าไฟล์รูปมีจริงไหม (สำคัญมาก เพราะเปลี่ยน Path แล้ว)
    if (!file_exists($config['image'])) {
        die("❌ ไม่พบรูปภาพ: " . $config['image'] . " (กรุณาเช็ค Path หรือชื่อไฟล์)");
    }

    $jsonBody = json_encode([
        "size" => ["width" => 2500, "height" => 843],
        "selected" => false,
        "name" => $config['name'],
        "chatBarText" => "เมนูใช้งาน",
        "areas" => $config['areas']
    ]);

    $richMenuId = createRichMenu($oldToken, $jsonBody);
    if (!$richMenuId) die("❌ สร้างเมนู $role ไม่สำเร็จ\n");

    $ext = pathinfo($config['image'], PATHINFO_EXTENSION);
    $contentType = ($ext == 'png') ? 'image/png' : 'image/jpeg';
    
    uploadRichMenuImage($oldToken, $richMenuId, $config['image'], $contentType);

    $newIds[$role] = $richMenuId;
    echo "✔ $role : สร้างแล้ว ID = $richMenuId\n";

    if ($role === "guest") {
        setDefaultRichMenu($oldToken, $richMenuId);
        echo "   -> ตั้งเป็น Default Menu แล้ว\n";
    }
}
echo "\n";

// --------------------------------------------------------
// 4. UPDATE CONFIG FILE
// --------------------------------------------------------
echo "STEP 3: อัปเดตไฟล์ config...\n";

$configContent = "<?php\n";
$configContent .= "define('CHANNEL_ACCESS_TOKEN', '$oldToken');\n";
$configContent .= "define('CHANNEL_SECRET', '$oldSecret');\n\n";

foreach ($newIds as $role => $id) {
    $configContent .= "define('RICHMENU_" . strtoupper($role) . "', '$id');\n";
}
$configContent .= "?>";

// แก้ไข Path บันทึกไฟล์ (ถอยกลับ 1 ชั้น)
if (@file_put_contents('../config/line_config.php', $configContent)) {
    echo "✅ อัปเดตไฟล์ ../config/line_config.php สำเร็จ\n";
} else {
    echo "❌ เขียนไฟล์ config ไม่ได้ (ติด Permission) กรุณาแก้ไขไฟล์ด้วยตัวเอง\n";
}

$log = nl2br(ob_get_clean());
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>System Setup Result</title>
<style>
body{font-family:"Prompt",sans-serif;background:#f7f8fc;margin:0;padding:40px;}
.card{max-width:900px;margin:auto;background:#fff;padding:30px;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,.08);}
h1{color:#3630a3;margin-top:0;}
pre{background:#272822;color:#f8f8f2;padding:20px;border-radius:8px;overflow:auto;font-size:15px;}
.btn{display:block;margin:35px auto 0;background:#4b48df;color:#fff;border:none;padding:18px 60px;font-size:18px;border-radius:10px;cursor:pointer;transition:.3s;text-decoration:none;text-align:center;}
.btn:hover{background:#3b39c8;}
</style>
</head>
<body>
<div class="card">
    <h1>Update Rich Menu Result</h1>
    <pre><?php echo $log; ?></pre>
    
    <a href="default.php" class="btn">ดำเนินการต่อ</a>
</div>
</body>
</html>

<?php
// --- Functions ---
function getRichMenuList($token){
    $ch=curl_init("https://api.line.me/v2/bot/richmenu/list");
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>["Authorization: Bearer $token"]]);
    return json_decode(curl_exec($ch),true);
}
function deleteRichMenu($token,$id){
    $ch=curl_init("https://api.line.me/v2/bot/richmenu/$id");
    curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>"DELETE",CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>["Authorization: Bearer $token"]]);
    curl_exec($ch);
}
function createRichMenu($token,$body){
    $ch=curl_init("https://api.line.me/v2/bot/richmenu");
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>["Authorization: Bearer $token","Content-Type: application/json"]]);
    $data=json_decode(curl_exec($ch),true);
    return $data['richMenuId']??null;
}
function uploadRichMenuImage($token,$id,$path,$type){
    $ch=curl_init("https://api-data.line.me/v2/bot/richmenu/$id/content");
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>file_get_contents($path),CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>["Authorization: Bearer $token","Content-Type:$type"]]);
    curl_exec($ch);
}
function setDefaultRichMenu($token,$id){
    $ch=curl_init("https://api.line.me/v2/bot/user/all/richmenu/$id");
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>["Authorization: Bearer $token"]]);
    curl_exec($ch);
}
?>