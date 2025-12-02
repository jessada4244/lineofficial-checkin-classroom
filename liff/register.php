<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-blue-50 min-h-screen flex flex-col items-center justify-center p-4">

    <div id="loading" class="fixed inset-0 bg-white z-50 flex flex-col items-center justify-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
        <p class="text-gray-500 text-sm">กำลังยืนยันตัวตนผ่าน LINE...</p>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">สมัครสมาชิก</h1>
            <div id="lineProfile" class="hidden mt-2 flex flex-col items-center">
                <img id="profileImage" src="" class="w-16 h-16 rounded-full border-2 border-blue-100 mb-2">
                <p class="text-xs text-green-600 font-bold bg-green-50 px-2 py-1 rounded-full">✓ ยืนยันตัวตนแล้ว</p>
            </div>
        </div>

        <div id="alertBox" class="hidden mb-4 p-3 rounded-lg text-sm text-center"></div>

        <form id="regForm" onsubmit="handleRegister(event)" class="space-y-4">
            
            <div>
                <label class="block text-gray-700 text-xs font-bold mb-1">ชื่อ-นามสกุล</label>
                <input type="text" id="name" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50" placeholder="เช่น นายสมชาย ใจดี" required>
            </div>

            <div>
                <label class="block text-gray-700 text-xs font-bold mb-1">ชื่อผู้ใช้งาน (Username)</label>
                <input type="text" id="username" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50" placeholder="ตั้งชื่อผู้ใช้งาน" required>
            </div>
            
            <div>
                <label class="block text-gray-700 text-xs font-bold mb-1">รหัสผ่าน</label>
                <input type="password" id="password" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50" placeholder="ตั้งรหัสผ่าน" required>
            </div>

            <div>
                <label class="block text-gray-700 text-xs font-bold mb-2">สถานะ</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="student" class="peer sr-only" checked onchange="toggleStudentId()">
                        <div class="text-center p-2 rounded-lg border border-gray-200 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition">
                            👨‍🎓 นิสิต
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="teacher" class="peer sr-only" onchange="toggleStudentId()">
                        <div class="text-center p-2 rounded-lg border border-gray-200 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition">
                            👨‍🏫 อาจารย์
                        </div>
                    </label>
                </div>
            </div>

            <div id="studentIdContainer">
                <label class="block text-gray-700 text-xs font-bold mb-1">รหัสนิสิต</label>
                <input type="text" id="studentId" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-blue-50 border-blue-100 text-blue-800 font-bold tracking-wider" placeholder="เช่น 6601xxxx">
            </div>
            
            <button type="submit" id="btnReg" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700 transition shadow-lg mt-2">
                ยืนยันการสมัคร
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <p class="text-gray-500">มีบัญชีอยู่แล้ว?</p>
            <a href="./login.php" class="text-blue-600 font-bold hover:underline">เข้าสู่ระบบที่นี่</a>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-Z1aN5Eyn"; // สร้าง LIFF ใหม่สำหรับหน้านี้
        let lineUserId = ""; // ตัวแปรเก็บ UserId

        // 1. เริ่มต้น LIFF
        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) {
                    liff.login();
                } else {
                    const profile = await liff.getProfile();
                    lineUserId = profile.userId;
                    
                    // (Optional) เอารูปโปรไฟล์มาโชว์เท่ๆ
                    if(profile.pictureUrl) {
                        document.getElementById('profileImage').src = profile.pictureUrl;
                        document.getElementById('lineProfile').classList.remove('hidden');
                    }
                    
                    // ปิดหน้า Loading
                    document.getElementById('loading').classList.add('hidden');
                }
            } catch (err) {
                alert("LIFF Error: " + err.message);
                document.getElementById('loading').classList.add('hidden'); // ให้กรอกได้แม้ Error แต่จะไม่มี UserId
            }
        }
        main();

        function toggleStudentId() {
    const role = document.querySelector('input[name="role"]:checked').value;
    const container = document.getElementById('studentIdContainer');
    const label = container.querySelector('label');
    const input = document.getElementById('studentId');

    // ไม่ว่าจะเลือก Student หรือ Teacher ก็ให้โชว์ช่องกรอก ID
    container.classList.remove('hidden');
    
    if (role === 'student') {
        label.innerText = "รหัสนิสิต";
        input.placeholder = "เช่น 6601xxxx";
    } else {
        label.innerText = "รหัสอาจารย์";
        input.placeholder = "เช่น T-001";
    }
}
        
        
       async function handleRegister(e) {
    e.preventDefault();
    
    const name = document.getElementById('name').value;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const role = document.querySelector('input[name="role"]:checked').value;
    
    // จุดที่ 1: ดึงค่าจากช่อง input (id="studentId")
    const eduId = document.getElementById('studentId').value; 

    // Validation ฝั่งหน้าเว็บ
    if (!eduId) return showAlert('error', 'กรุณากรอกรหัสประจำตัว');
    if (!lineUserId) return showAlert('error', 'ไม่พบข้อมูล LINE (LIFF Error)');

    const btn = document.getElementById('btnReg');
    btn.disabled = true;
    btn.innerText = "กำลังบันทึก...";

    try {
        // จุดที่ 2: ส่งข้อมูลไปที่ API (สำคัญมาก! ต้องส่งเป็น edu_id)
        const res = await axios.post('../api/register.php', {
            name: name,
            username: username,
            password: password,
            role: role,
            edu_id: eduId,       // <--- ต้องใช้ชื่อ edu_id ให้ตรงกับ API
            line_user_id: lineUserId 
        });

        if (res.data.status === 'success') {
            showAlert('success', 'สมัครสมาชิกสำเร็จ!');
            setTimeout(() => {
                if (liff.isInClient()) {
                    liff.closeWindow();
                } else {
                    window.location.href = './login.php';
                }
            }, 1500);
        } else {
            showAlert('error', res.data.message);
            btn.disabled = false;
            btn.innerText = "ยืนยันการสมัคร";
        }
    } catch (err) {
        showAlert('error', 'Server Error: ' + err.message);
        btn.disabled = false;
        btn.innerText = "ยืนยันการสมัคร";
    }
}

        function showAlert(type, msg) {
            const box = document.getElementById('alertBox');
            box.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
            if (type === 'error') {
                box.classList.add('bg-red-100', 'text-red-700');
                box.innerText = '❌ ' + msg;
            } else {
                box.classList.add('bg-green-100', 'text-green-700');
                box.innerText = '✅ ' + msg;
            }
            box.classList.remove('hidden');
        }

        toggleStudentId();
    </script>
</body>
</html>