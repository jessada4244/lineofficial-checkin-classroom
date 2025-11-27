<?php
// ตรวจสอบการเชื่อมต่อฐานข้อมูลเบื้องต้น
$dbStatus = false;
$dbMessage = "";
try {
    require_once 'config/db.php';
    if($pdo) {
        $dbStatus = true;
        $dbMessage = "เชื่อมต่อฐานข้อมูลสำเร็จ ✅";
    }
} catch (Exception $e) {
    $dbMessage = "เชื่อมต่อฐานข้อมูลไม่ได้ ❌ (" . $e->getMessage() . ")";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบเช็คชื่อ LINE Official</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex flex-col items-center justify-center p-4">

    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden">
        
        <div class="bg-blue-600 p-8 text-center text-white">
            <h1 class="text-3xl font-bold mb-2">🏫 ระบบเช็คชื่อเข้าเรียน</h1>
            <p class="text-blue-100">LINE Official Account Classroom Check-in</p>
        </div>

        <div class="p-8">
            
            <div class="mb-8 flex items-center justify-center gap-2 p-3 rounded-lg <?php echo $dbStatus ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                <div class="w-3 h-3 rounded-full <?php echo $dbStatus ? 'bg-green-500' : 'bg-red-500'; ?>"></div>
                <span class="font-bold text-sm">สถานะระบบ: <?php echo $dbMessage; ?></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-gray-700 border-b pb-2">📱 สำหรับผู้ใช้งาน (Mobile/LIFF)</h2>
                    
                    <a href="liff/login.php" class="block p-4 bg-white border border-gray-200 rounded-xl hover:shadow-md hover:border-blue-400 transition group">
                        <div class="flex items-center gap-4">
                            <div class="bg-blue-100 text-blue-600 p-3 rounded-full group-hover:bg-blue-600 group-hover:text-white transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">เข้าสู่ระบบ (Login)</h3>
                                <p class="text-xs text-gray-500">ลิงก์สำหรับเข้าใช้งานระบบ</p>
                            </div>
                        </div>
                    </a>

                    <a href="liff/register.php" class="block p-4 bg-white border border-gray-200 rounded-xl hover:shadow-md hover:border-green-400 transition group">
                        <div class="flex items-center gap-4">
                            <div class="bg-green-100 text-green-600 p-3 rounded-full group-hover:bg-green-600 group-hover:text-white transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">สมัครสมาชิก (Register)</h3>
                                <p class="text-xs text-gray-500">ลงทะเบียน อาจารย์/นิสิต ใหม่</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-gray-700 border-b pb-2">🛠️ สำหรับผู้ดูแลระบบ</h2>

                    <a href="liff/admin/dashboard.php" class="block p-4 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition shadow-lg">
                        <div class="flex items-center gap-4">
                            <div class="bg-gray-700 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div>
                                <h3 class="font-bold">Admin Dashboard</h3>
                                <p class="text-xs text-gray-400">จัดการผู้ใช้งาน / ส่งประกาศ</p>
                            </div>
                        </div>
                    </a>

                    <div class="grid grid-cols-2 gap-3">
                        <a href="rich_menu.php" target="_blank" class="text-center p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700 hover:bg-yellow-100 transition">
                            <span class="block text-sm font-bold">Create Menu</span>
                            <span class="text-[10px]">สร้างเมนู</span>
                        <a href="default.php" target="_blank" class="text-center p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700 hover:bg-yellow-100 transition">
                            <span class="block text-sm font-bold">Update Menu</span>
                            <span class="text-[10px]">อัปเดตเมนูใหม่</span>
                        </a>
                        
                    </div>
                    
                </div>

            </div>
        </div>
        
        <div class="bg-gray-50 p-4 text-center text-xs text-gray-400">
            &copy; <?php echo date("Y"); ?> University Check-in System | Powered by LINE Messaging API
        </div>
    </div>

</body>
</html>