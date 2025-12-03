<?php
// liff/settings.php
require_once '../config/security.php';
checkLogin(); // บังคับว่าต้อง Login แล้วเท่านั้น

// กำหนด LIFF ID ตาม Role ของผู้ใช้ (เพื่อให้ liff.init ทำงานได้ถูกต้องตาม Endpoint)
$role = $_SESSION['role'] ?? 'guest';
$liff_ids = [
    'admin'   => '2008573640-Xlr1jY4w', // ID หน้า Admin
    'teacher' => '2008573640-qQxJWXLz', // ID หน้า Teacher
    'student' => '2008573640-jb4bpE5J'  // ID หน้า Student
];
$myLiffId = $liff_ids[$role] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าผู้ใช้งาน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap'); body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen pb-10">

    <div class="bg-white px-4 py-4 shadow-sm sticky top-0 z-10 flex items-center gap-3">
        <button onclick="goBack()" class="text-gray-500 hover:text-blue-600 bg-gray-100 p-2 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </button>
        <h1 class="text-lg font-bold text-gray-800">บัญชีของฉัน</h1>
    </div>

    <div id="loading" class="text-center py-20 text-gray-400">กำลังโหลดข้อมูล...</div>

    <div id="view-main" class="hidden p-4 max-w-md mx-auto space-y-6">
        
        <div class="bg-white p-6 rounded-3xl shadow-sm text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-20 bg-gradient-to-r from-blue-500 to-indigo-600 z-0"></div>
            <div class="relative z-10 mt-8">
                <img id="u-img" src="https://via.placeholder.com/150" class="w-24 h-24 rounded-full border-4 border-white shadow-md object-cover mx-auto bg-gray-200">
                <h2 id="u-name" class="text-xl font-bold text-gray-800 mt-3">...</h2>
                <div class="flex justify-center gap-2 mt-1">
                    <span id="u-role" class="text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-600 uppercase">-</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <span class="text-gray-500 text-sm">ชื่อผู้ใช้งาน</span>
                <span id="u-username" class="font-bold text-gray-800">-</span>
            </div>
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <span class="text-gray-500 text-sm" id="lbl-id">ID</span>
                <span id="u-id" class="font-bold text-gray-800">-</span>
            </div>
            <div class="p-4 flex justify-between items-center">
                <span class="text-gray-500 text-sm">เบอร์โทรศัพท์</span>
                <span id="u-phone" class="font-bold text-gray-800">-</span>
            </div>
        </div>

        <div class="space-y-3">
            <button onclick="switchView('edit')" class="w-full bg-white hover:bg-gray-50 p-4 rounded-2xl shadow-sm flex items-center justify-between text-gray-700 font-bold transition">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></div>
                    แก้ไขข้อมูลส่วนตัว
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>

            <button onclick="switchView('password')" class="w-full bg-white hover:bg-gray-50 p-4 rounded-2xl shadow-sm flex items-center justify-between text-gray-700 font-bold transition">
                <div class="flex items-center gap-3">
                    <div class="bg-yellow-100 text-yellow-600 p-2 rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg></div>
                    เปลี่ยนรหัสผ่าน
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>
            
            <a href="logout.php" onclick="return confirm('ต้องการออกจากระบบ?')" class="block w-full bg-red-50 hover:bg-red-100 p-4 rounded-2xl shadow-sm text-center text-red-600 font-bold mt-4">
                ออกจากระบบ
            </a>
        </div>
    </div>

    <div id="view-edit" class="hidden p-4 max-w-md mx-auto">
        <h2 class="font-bold text-xl mb-6">✏️ แก้ไขข้อมูล</h2>
        <div class="bg-white p-6 rounded-2xl shadow-sm space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">ชื่อ-นามสกุล</label>
                <input type="text" id="ep-name" class="w-full border border-gray-200 bg-gray-50 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">เบอร์โทรศัพท์</label>
                <input type="tel" id="ep-phone" class="w-full border border-gray-200 bg-gray-50 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="pt-4 flex gap-3">
                <button onclick="switchView('main')" class="flex-1 py-3 bg-gray-100 rounded-xl font-bold text-gray-600">ยกเลิก</button>
                <button onclick="saveProfile()" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-bold shadow-lg">บันทึก</button>
            </div>
        </div>
    </div>

    <div id="view-password" class="hidden p-4 max-w-md mx-auto">
        <h2 class="font-bold text-xl mb-6">🔑 เปลี่ยนรหัสผ่าน</h2>
        <div class="bg-white p-6 rounded-2xl shadow-sm space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">รหัสผ่านเดิม</label>
                <input type="password" id="cp-old" class="w-full border border-gray-200 bg-gray-50 p-3 rounded-xl outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">รหัสผ่านใหม่</label>
                <input type="password" id="cp-new" class="w-full border border-gray-200 bg-gray-50 p-3 rounded-xl outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" id="cp-conf" class="w-full border border-gray-200 bg-gray-50 p-3 rounded-xl outline-none">
            </div>
            <div class="pt-4 flex gap-3">
                <button onclick="switchView('main')" class="flex-1 py-3 bg-gray-100 rounded-xl font-bold text-gray-600">ยกเลิก</button>
                <button onclick="savePassword()" class="flex-1 py-3 bg-green-600 text-white rounded-xl font-bold shadow-lg">เปลี่ยนรหัส</button>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "<?php echo $myLiffId; ?>"; // รับค่าจาก PHP

        async function main() {
            try {
                if(LIFF_ID) {
                    await liff.init({ liffId: LIFF_ID });
                    if (!liff.isLoggedIn()) liff.login();
                }
                loadData();
            } catch(e) { 
                console.error(e); 
                // กรณี LIFF Init พัง ก็ยังให้โหลดข้อมูลจาก Session PHP ได้ (ถ้าทำระบบไว้) 
                // แต่ในที่นี้เราใช้ line_id เป็น key หลัก ถ้าไม่มี liff อาจต้องพึ่ง session
                loadData(); 
            }
        }
        main();

        function goBack() {
            // เช็คประวัติการถอยหลัง ถ้าไม่มีให้ปิดหน้าต่าง หรือกลับไปหน้าหลักตาม role
            if(document.referrer) {
                window.history.back();
            } else {
                // Fallback กรณีเปิดมาตรงๆ
                window.location.href = './login.php'; 
            }
        }

        function switchView(view) {
            document.querySelectorAll('[id^="view-"]').forEach(el => el.classList.add('hidden'));
            document.getElementById('view-' + view).classList.remove('hidden');
        }

        async function loadData() {
            try {
                const profile = await liff.getProfile();
                if(profile.pictureUrl) document.getElementById('u-img').src = profile.pictureUrl;

                const res = await axios.post('../api/user_setting.php', {
                    action: 'get_profile',
                    line_id: profile.userId
                });

                if(res.data.status === 'success') {
                    const u = res.data.data;
                    document.getElementById('u-name').innerText = u.name;
                    document.getElementById('u-username').innerText = u.username;
                    document.getElementById('u-role').innerText = u.role;
                    document.getElementById('u-phone').innerText = u.phone || '-';
                    
                    if(u.role === 'student') {
                        document.getElementById('lbl-id').innerText = 'รหัสนิสิต';
                        document.getElementById('u-id').innerText = u.student_id;
                    } else {
                        document.getElementById('lbl-id').innerText = 'User ID';
                        document.getElementById('u-id').innerText = u.id;
                    }

                    // Pre-fill Forms
                    document.getElementById('ep-name').value = u.name;
                    document.getElementById('ep-phone').value = u.phone || '';

                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('view-main').classList.remove('hidden');
                }
            } catch(e) {
                alert("โหลดข้อมูลไม่สำเร็จ กรุณาลองใหม่");
            }
        }

        async function saveProfile() {
            const name = document.getElementById('ep-name').value;
            const phone = document.getElementById('ep-phone').value;
            if(!name) return alert('กรุณากรอกชื่อ');

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../api/user_setting.php', {
                    action: 'update_profile', line_id: profile.userId, name: name, phone: phone
                });
                if(res.data.status==='success') {
                    alert('บันทึกเรียบร้อย');
                    loadData(); // โหลดข้อมูลใหม่
                    switchView('main');
                } else alert(res.data.message);
            } catch(e){ alert('Error'); }
        }

        async function savePassword() {
            const o = document.getElementById('cp-old').value;
            const n = document.getElementById('cp-new').value;
            const c = document.getElementById('cp-conf').value;
            if(!o || !n) return alert('กรอกข้อมูลให้ครบ');
            if(n!==c) return alert('รหัสผ่านใหม่ไม่ตรงกัน');

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../api/user_setting.php', {
                    action: 'change_password', line_id: profile.userId, old_pass: o, new_pass: n
                });
                if(res.data.status==='success') {
                    alert('เปลี่ยนรหัสผ่านสำเร็จ');
                    document.getElementById('cp-old').value=''; document.getElementById('cp-new').value=''; document.getElementById('cp-conf').value='';
                    switchView('main');
                } else alert(res.data.message);
            } catch(e){ alert('Error'); }
        }
    </script>
</body>
</html>