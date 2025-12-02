<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-blue-50 flex items-center justify-center min-h-screen px-4">

    <div id="loading" class="fixed inset-0 bg-white z-50 flex flex-col items-center justify-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
        <p class="text-gray-500 text-sm">กำลังยืนยันตัวตน...</p>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm hidden" id="loginCard">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">ยินดีต้อนรับ</h1>
            <p class="text-xs text-gray-400 mt-1">เข้าสู่ระบบเพื่อใช้งาน</p>
            
            <div id="lineProfile" class="mt-4 flex flex-col items-center animate-pulse">
                <img id="profileImage" src="https://via.placeholder.com/150" class="w-20 h-20 rounded-full border-4 border-blue-100 mb-2 shadow-sm object-cover">
                <p id="profileName" class="font-bold text-gray-700 text-lg">Loading...</p>
                <div class="flex items-center gap-1 mt-1 bg-green-50 px-3 py-1 rounded-full border border-green-100">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-[10px] text-green-700 font-bold">LINE Verified</span>
                </div>
            </div>
        </div>

        <div id="status" class="hidden mb-4 p-3 rounded-lg text-sm text-center border"></div>

        <form id="loginForm" onsubmit="handleLogin(event)" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-xs font-bold mb-1 ml-1">ชื่อผู้ใช้งาน</label>
                <input type="text" id="username" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 transition" placeholder="ระบุ Username" required>
            </div>
            
            <div>
                <label class="block text-gray-700 text-xs font-bold mb-1 ml-1">รหัสผ่าน</label>
                <input type="password" id="password" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 transition" placeholder="ระบุ Password" required>
            </div>
            
            <button type="submit" id="btnLogin" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 transform active:scale-95 mt-2">
                เข้าสู่ระบบ
            </button>
        </form>
        
        <p class="text-center text-xs text-gray-400 mt-6">
            ยังไม่มีบัญชี? <a href="./register.php" class="text-blue-600 font-bold hover:underline">ลงทะเบียนที่นี่</a>
        </p>
    </div>

    <script>
        // *** ใส่ LIFF ID ของหน้า Login ตรงนี้ ***
        const LIFF_ID = "2008573640-9pYeN4Dn"; 
        let currentLineUserId = "";

        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) {
                    liff.login();
                } else {
                    const profile = await liff.getProfile();
                    currentLineUserId = profile.userId;

                    // แสดงข้อมูลโปรไฟล์
                    if (profile.pictureUrl) {
                        document.getElementById('profileImage').src = profile.pictureUrl;
                    }
                    document.getElementById('profileName').innerText = profile.displayName;
                    document.getElementById('lineProfile').classList.remove('animate-pulse');

                    // เปิดหน้าจอ Login
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('loginCard').classList.remove('hidden');
                }
            } catch (err) {
                // แสดง Error ที่ชัดเจน
                showStatus('error', 'LIFF Init Failed: ' + err.message);
                console.error(err);
                
                // กรณี Error ยังให้เห็นหน้า Login ได้ (แต่อาจจะกด Login ไม่ผ่านเพราะไม่มี UID)
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('loginCard').classList.remove('hidden');
                document.getElementById('profileName').innerText = "Guest Mode";
            }
        }
        main();

        async function handleLogin(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnLogin');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (!currentLineUserId) {
                 return showStatus('error', 'ไม่พบข้อมูล LINE (LIFF Error) กรุณารีโหลดหรือตรวจสอบ URL');
            }

            // UI Loading
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังตรวจสอบ...`;
            showStatus('loading', '');

            try {
                // ส่งข้อมูลไปตรวจสอบที่ Backend
                const res = await axios.post('../api/login.php', {
                    username: username,
                    password: password,
                    lineUserId: currentLineUserId 
                });

                if (res.data.status === 'success') {
                    showStatus('success', 'เข้าสู่ระบบสำเร็จ! กำลังพาไปหน้าหลัก...');
                    const role = res.data.role;
                    setTimeout(() => {
                        if (role === 'teacher') window.location.href = './teacher/manage_class.php';
                        else if (role === 'student') window.location.href = './student/class_list.php';
                        else if (role === 'admin') window.location.href = './admin/dashboard.php'; 
                        else {
                            alert("ไม่พบ Role ที่ถูกต้อง (" + role + ")");
                            resetBtn();
                        }
                    }, 1000);
                } else {
                    showStatus('error', res.data.message);
                    resetBtn();
                }

            } catch (err) {
                console.error(err);
                showStatus('error', 'เชื่อมต่อ Server ไม่ได้: ' + err.message);
                resetBtn();
            }
        }

        function showStatus(type, msg) {
            const el = document.getElementById('status');
            el.classList.remove('hidden', 'bg-red-50', 'text-red-600', 'border-red-200', 'bg-green-50', 'text-green-600', 'border-green-200');
            
            if (type === 'error') {
                el.classList.add('bg-red-50', 'text-red-600', 'border-red-200');
                el.innerText = '⚠️ ' + msg;
                el.classList.remove('hidden');
            } else if (type === 'success') {
                el.classList.add('bg-green-50', 'text-green-600', 'border-green-200');
                el.innerText = '✅ ' + msg;
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }

        function resetBtn() {
            const btn = document.getElementById('btnLogin');
            btn.disabled = false;
            btn.innerText = "เข้าสู่ระบบ";
        }
    </script>
</body>
</html>