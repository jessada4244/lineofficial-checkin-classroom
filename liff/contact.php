
<?php 
// ไม่ต้อง require security.php หรือ checkLogin() เพราะหน้านี้เปิดสาธารณะ
// แต่ถ้าอยากใช้ session ก็ใส่แค่ session_start(); ได้
session_start(); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเจ้าหน้าที่</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap'); body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen p-4">
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg p-6">
        <h1 class="text-xl font-bold text-gray-800 mb-4">📬 ติดต่อเจ้าหน้าที่</h1>
        
        <div class="flex items-center gap-3 mb-6 bg-blue-50 p-3 rounded-lg border border-blue-100">
            <img id="uPic" src="https://via.placeholder.com/50" class="w-10 h-10 rounded-full bg-gray-200 object-cover">
            <div>
                <p class="text-xs text-gray-500">ผู้ส่ง:</p>
                <p id="uName" class="font-bold text-sm text-blue-800">กำลังโหลด...</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">หัวข้อ</label>
                <select id="topic" class="w-full border rounded-lg p-2 bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option>แจ้งปัญหาการใช้งาน</option>
                    <option>สอบถามข้อมูล</option>
                    <option>สมัครสมาชิกไม่ได้</option>
                    <option>อื่นๆ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">ข้อความ</label>
                <textarea id="message" rows="5" class="w-full border rounded-lg p-3 bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="รายละเอียด..."></textarea>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">เบอร์โทร</label>
                <input  type= "tel" id="phone" maxlength="10" rows="5" class="w-full border rounded-lg p-3 bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="รายละเอียด..."></input>
            </div>
            <button onclick="sendReport()" id="btnSend" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-blue-700 transition transform active:scale-95">ส่งข้อความ</button>
        </div>
    </div>

    <script>
        // *** ใส่ LIFF ID ของหน้า Contact (ที่ได้จาก LINE Developers) ***
        const LIFF_ID = "2008573640-4dv1PmaJ"; 
        
        let userProfile = null;

        async function main() { 
            try {
                await liff.init({ liffId: LIFF_ID }); 
                // เช็คว่าเปิดใน LINE หรือไม่
                if (!liff.isLoggedIn()) {
                    liff.login();
                } else {
                    // ดึงโปรไฟล์มาโชว์ (ทำได้แม้ไม่ได้เป็นสมาชิกในระบบเรา)
                    userProfile = await liff.getProfile();
                    document.getElementById('uName').innerText = userProfile.displayName;
                    if(userProfile.pictureUrl) document.getElementById('uPic').src = userProfile.pictureUrl;
                }
            } catch (err) {
                console.error(err);
                document.getElementById('uName').innerText = "Guest User";
                // กรณี Error (เช่น test นอก LINE) ให้จำลองข้อมูลเพื่อไม่ให้จอขาว
                userProfile = { userId: "guest_id", displayName: "Guest User" };
            }
        }
        main();

        async function sendReport() {
            if(!userProfile) return alert("กรุณารอสักครู่ กำลังโหลดข้อมูล LINE...");
            
            const topic = document.getElementById('topic').value;
            const msg = document.getElementById('message').value;
            const ph = document.getElementById('phone').value;
            
            if(!msg) return alert("กรุณาพิมพ์ข้อความที่ต้องการส่ง");

            // UI Loading
            const btn = document.getElementById('btnSend');
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังส่ง...`;

            try {
                // ส่งไปที่ API
                const res = await axios.post('../api/contact_api.php', {
                    action: 'send_report', 
                    line_id: userProfile.userId, 
                    display_name: userProfile.displayName, // ส่งชื่อ Guest ไปด้วย
                    topic: topic, 
                    message: msg,
                    phone: ph
                });

                if(res.data.status === 'success') {
                    alert("✅ ส่งข้อความเรียบร้อยแล้ว! เจ้าหน้าที่จะรีบตอบกลับครับ");
                    if (liff.isInClient()) {
                        liff.closeWindow();
                    } else {
                        window.location.reload();
                    }
                } else { 
                    alert("❌ เกิดข้อผิดพลาด: " + res.data.message); 
                    resetBtn();
                }
            } catch(e) { 
                console.error(e);
                alert("❌ ไม่สามารถเชื่อมต่อ Server ได้"); 
                resetBtn();
            }
        }

        function resetBtn() {
            const btn = document.getElementById('btnSend');
            btn.disabled = false;
            btn.innerText = "ส่งข้อความ";
        }
    </script>
</body>
</html>