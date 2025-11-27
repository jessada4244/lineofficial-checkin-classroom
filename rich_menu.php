<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config/line_config.php';

$oldToken  = CHANNEL_ACCESS_TOKEN;
$oldSecret = CHANNEL_SECRET;
$imagePathPrefix = 'assets/images/';


// สร้างเมนู

$menus = [
    'guest' => [
        'name' => 'Guest Menu',
        'image' => $imagePathPrefix . 'guest.jpg',
        'areas' => [
            [ "bounds"=>["x"=>0,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"message","text"=>"ติดต่อเจ้าหน้าที่"] ],
            [ "bounds"=>["x"=>833,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=>"https://liff.line.me/xxxx"] ],
            [ "bounds"=>["x"=>1666,"y"=>0,"width"=>834,"height"=>843], "action"=>["type"=>"uri","uri"=>"https://liff.line.me/yyyy"] ]
        ]
    ],
    'admin' => [
        'name' => 'Admin Menu',
        'image' => $imagePathPrefix . 'admin.jpg',
        'areas' => [
            [ "bounds"=>["x"=>0,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=>"https://liff.line.me/admin"] ],
            [ "bounds"=>["x"=>833,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"message","text"=>"เมนูประกาศ"] ],
            [ "bounds"=>["x"=>1666,"y"=>0,"width"=>834,"height"=>843], "action"=>["type"=>"message","text"=>"Admin Tools"] ]
        ]
    ],
    'teacher' => [
        'name' => 'Teacher Menu',
        'image' => $imagePathPrefix . 'teacher.jpg',
        'areas' => [
            [ "bounds"=>["x"=>0,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=>"https://liff.line.me/teacher"] ],
            [ "bounds"=>["x"=>833,"y"=>0,"width"=>1667,"height"=>843], "action"=>["type"=>"uri","uri"=>"https://liff.line.me/teacher2"] ]
        ]
    ],
    'student' => [
        'name' => 'Student Menu',
        'image' => $imagePathPrefix . 'student.png',
        'areas' => [
            [ "bounds"=>["x"=>0,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=>"https://liff.line.me/student"] ],
            [ "bounds"=>["x"=>833,"y"=>0,"width"=>833,"height"=>843], "action"=>["type"=>"uri","uri"=>"https://liff.line.me/student"] ],
            [ "bounds"=>["x"=>1666,"y"=>0,"width"=>834,"height"=>843], "action"=>["type"=>"message","text"=>"คู่มือการใช้งาน"] ]
        ]
    ]
];

ob_start(); // จับข้อความทั้งหมดเพื่อนำไปใส่ UI

// ลบ Rich Menu เดิม

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

// สร้างใหม่
echo "STEP 2: สร้างเมนูใหม่...\n";

$newIds = [];

foreach ($menus as $role => $config) {

    if (!file_exists($config['image'])) {
        die("❌ ไม่พบรูปภาพ: " . $config['image']);
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
        echo "ตั้งเป็น Guest แล้ว\n";
    }
}
echo "\n";

// เขียน config ใหม่

echo "STEP 3: อัปเดต config...\n";

$config = "<?php\n";
$config .= "define('CHANNEL_ACCESS_TOKEN', '$oldToken');\n";
$config .= "define('CHANNEL_SECRET', '$oldSecret');\n\n";

foreach ($newIds as $role => $id) {
    $config .= "define('RICHMENU_" . strtoupper($role) . "', '$id');\n";
}
$config .= "?>";

if (@file_put_contents('config/line_config.php', $config)) {
    echo "อัปเดตไฟล์ config สำเร็จ\n";
} else {
    echo "เขียนทับไฟล์ไม่ได้ ดำเนินการแก้ไขไฟล์เอง\n";
    echo "--------------------------------------------\n";
    echo $config;
    echo "--------------------------------------------\n";
}

$log = nl2br(ob_get_clean()); // แปลง log เป็น HTML


?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Auto Update Rich Menu</title>
<style>
body{font-family:"Prompt",sans-serif;background:#f7f8fc;margin:0;padding:40px;}
.card{max-width:900px;margin:auto;background:#fff;padding:30px;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,.08);}
h1{color:#3630a3;margin-top:0;}
pre{background:#272822;color:#f8f8f2;padding:20px;border-radius:8px;overflow:auto;font-size:15px;}
.btn{display:block;margin:35px auto 0;background:#4b48df;color:#fff;border:none;padding:18px 60px;font-size:22px;border-radius:10px;cursor:pointer;transition:.3s;}
.btn:hover{background:#3b39c8;}
</style>
</head>
<body>
<div class="card">
    <h1>Update Rich Menu</h1>
    <p>สถานะการทำงานของระบบ:</p>
    <pre><?php echo $log; ?></pre>
    <button class="btn" onclick="location.href='default.php'">ดำเนินการต่อ</button>
</div>
</body>
</html>


<?php
// ฟังก์ชัน
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
