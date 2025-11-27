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
<body class="bg-gray-100 flex items-center justify-center h-screen px-4">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">เข้าสู่ระบบ</h1>
            <p class="text-sm text-gray-500">ระบบเช็คชื่อด้วย LINE Official</p>
        </div>

        <div id="status" class="hidden mb-4 p-3 rounded-lg text-sm text-center"></div>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อผู้ใช้งาน</label>
                <input type="text" id="username" class="w-full px-3 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" placeholder="Username" required>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">รหัสผ่าน</label>
                <input type="password" id="password" class="w-full px-3 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" placeholder="Password" required>
            </div>
            
            <button type="submit" id="btnLogin" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition shadow-lg transform active:scale-95">
                เข้าสู่ระบบ
            </button>
        </form>
    </div>

    <script>
        // *** ใส่ LIFF ID ของหน้า Login ตรงนี้ ***
        const LIFF_ID = "2008573640-9pYeN4Dn"; 
        

        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) {
                    liff.login();
                }
            } catch (err) {
                showStatus('error', 'LIFF Init Failed: ' + err.message);
            }
        }
        main();

        async function handleLogin(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnLogin');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            // ล็อคปุ่มกันกดซ้ำ
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังตรวจสอบ...`;
            showStatus('loading', '');

            try {
                const profile = await liff.getProfile();
                
                // ยิง API Login
                const res = await axios.post('../api/login.php', {
                    username: username,
                    password: password,
                    lineUserId: profile.userId
                });

                if (res.data.status === 'success') {
                    showStatus('success', 'เข้าสู่ระบบสำเร็จ! กำลังเปลี่ยนหน้า...');
                    
                    // รอ 1 วินาทีให้คนอ่านทัน แล้วค่อยไปต่อ
                    setTimeout(() => {
                        const role = res.data.role;

                        // 1. ลองสั่งปิดหน้าต่าง (เพื่อให้กลับไปเจอ Rich Menu ใหม่)
                        if (liff.isInClient()) {
                            liff.closeWindow(); 
                        } 
                        
                        // 2. ถ้าปิดไม่ได้ (เช่นเปิดในคอม/Chrome) ให้เด้งไปหน้าหลักของ Role นั้นๆ
                        // หรือถ้าปิดได้ บรรทัดล่างๆ นี้จะไม่ทำงาน เพราะหน้าต่างปิดไปแล้ว
                        if (role === 'teacher') {
                            window.location.href = 'teacher/manage_class.php';
                        } else if (role === 'student') {
                            window.location.href = 'student/class_list.php';
                        } else if (role === 'admin') {
                            alert("Admin Login Success"); // Admin อาจจะไม่มีหน้ามือถือ
                        }
                    }, 1000);

                } else {
                    showStatus('error', res.data.message);
                    resetBtn();
                }

            } catch (err) {
                console.error(err);
                showStatus('error', 'ติดต่อ Server ไม่ได้ หรือรหัสผิดพลาด');
                resetBtn();
            }
        }

        function showStatus(type, msg) {
            const el = document.getElementById('status');
            el.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
            
            if (type === 'error') {
                el.classList.add('bg-red-100', 'text-red-700');
                el.innerText = '❌ ' + msg;
                el.classList.remove('hidden');
            } else if (type === 'success') {
                el.classList.add('bg-green-100', 'text-green-700');
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