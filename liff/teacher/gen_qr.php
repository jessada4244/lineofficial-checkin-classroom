<?php
require_once '../../config/security.php';
checkLogin('teacher');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code เช็คชื่อ</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap'); body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-900 min-h-screen flex flex-col p-4 text-white">

    <div id="modeSelector" class="fixed inset-0 z-50 bg-white flex flex-col items-center justify-center p-6 text-gray-800">
        <h2 class="text-2xl font-bold mb-6">เลือกรูปแบบการสอน</h2>
        
        <button onclick="startClass('onsite')" class="w-full bg-gray-100 hover:bg-gray-200 p-4 rounded-xl mb-3 flex items-center gap-4 border border-gray-200">
            <span class="text-3xl">🏫</span>
            <div class="text-left">
                <div class="font-bold text-lg">เรียนที่ห้อง (Onsite)</div>
                <div class="text-xs text-gray-500">เช็คชื่อด้วย GPS + QR Code</div>
            </div>
        </button>

        <hr class="w-full my-4 border-gray-100">
        <p class="text-xs text-gray-400 mb-2 w-full text-left">เรียนออนไลน์ (Online)</p>

        <div class="w-full mb-3">
            <button onclick="selectOnline('zoom')" class="w-full bg-blue-50 hover:bg-blue-100 border border-blue-200 p-4 rounded-xl flex items-center gap-4">
                <span class="text-3xl">🎥</span>
                <div class="text-left flex-1">
                    <div class="font-bold text-blue-800 text-lg">Online (Zoom)</div>
                    <div class="text-xs text-blue-600">ปิด GPS + ใช้ลิงก์ที่ตั้งค่าไว้</div>
                </div>
            </button>
            <div id="opt-zoom" class="hidden mt-2 ml-2 flex items-center gap-2">
                <input type="checkbox" id="chk-zoom" class="w-4 h-4 text-blue-600 rounded" checked>
                <label for="chk-zoom" class="text-sm text-gray-600">📢 ส่งลิงก์เข้าแชทนิสิต (LINE)</label>
            </div>
        </div>

        <div class="w-full mb-3">
            <button onclick="selectOnline('teams')" class="w-full bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 p-4 rounded-xl flex items-center gap-4">
                <span class="text-3xl">📞</span>
                <div class="text-left flex-1">
                    <div class="font-bold text-indigo-800 text-lg">Online (Teams)</div>
                    <div class="text-xs text-indigo-600">ปิด GPS + ใช้ลิงก์ที่ตั้งค่าไว้</div>
                </div>
            </button>
             <div id="opt-teams" class="hidden mt-2 ml-2 flex items-center gap-2">
                <input type="checkbox" id="chk-teams" class="w-4 h-4 text-indigo-600 rounded" checked>
                <label for="chk-teams" class="text-sm text-gray-600">📢 ส่งลิงก์เข้าแชทนิสิต (LINE)</label>
            </div>
        </div>
        
        <button onclick="window.history.back()" class="mt-6 text-gray-400 text-sm hover:underline">ยกเลิก</button>
    </div>

    <div id="mainContent" class="hidden max-w-4xl mx-auto w-full grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-800 p-6 rounded-3xl shadow-2xl flex flex-col items-center text-center">
            <h1 class="text-2xl font-bold mb-1" id="subjectName">Loading...</h1>
            <p class="text-gray-400 text-sm mb-4">สแกนเพื่อเช็คชื่อเข้าเรียน</p>

            <div id="teacherLinkBtn" class="hidden w-full mb-4">
                <a id="btnOpenLink" href="#" target="_blank" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                    <span id="linkIcon">🎥</span> เปิดห้องเรียน (Host)
                </a>
            </div>

            <div class="bg-white p-4 rounded-xl mb-6 shadow-inner">
                <div id="qrcode"></div>
            </div>

            <div class="bg-gray-700/50 rounded-xl p-2 w-full mb-4 border border-gray-600">
                <p class="text-[10px] text-gray-400">รหัสเช็คชื่อ (Token)</p>
                <p id="txtToken" class="text-2xl font-mono font-bold text-yellow-400 tracking-widest">------</p>
            </div>

            <div class="w-full bg-gray-700 rounded-xl p-4 border border-gray-600">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">เวลาที่เหลือก่อนสาย</p>
                <div id="timer" class="text-4xl font-mono font-bold text-green-400">00:00:00</div>
                <p id="limitInfo" class="text-xs text-gray-500 mt-2">กำหนดเวลา: -</p>
            </div>
            
            <button onclick="window.history.back()" class="mt-4 text-gray-400 hover:text-white underline text-sm">ปิดหน้านี้</button>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col text-gray-800">
             <div class="p-4 bg-gray-100 border-b flex justify-between items-center">
                <h2 class="font-bold text-lg">สถานะ</h2>
                <div class="flex gap-2 text-xs font-bold">
                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-md">มา: <span id="countIn">0</span></span>
                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded-md">ขาด: <span id="countNot">0</span></span>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4" style="max-height: 500px;">
                <div id="listIn" class="space-y-2"></div>
                <hr class="border-gray-100">
                <div id="listNot" class="space-y-2"></div>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        let limitTimeStr = null;
        let qrTimer, liveTimer, countdownTimer;

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบรหัสวิชา");
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
        }
        main();

        // กดปุ่มเลือก Online -> แสดง Checkbox -> เริ่มทันที
        function selectOnline(mode) {
            // โชว์ตัวเลือก checkbox (เผื่ออยากเปลี่ยนใจ) หรือจะให้เริ่มเลยก็ได้
            // ในที่นี้ ถ้ากดแล้ว ให้เช็คค่า Checkbox แล้วเริ่มเลย
            const isChecked = document.getElementById('chk-' + mode).checked;
            startClass(mode, isChecked);
        }

        async function startClass(mode, notify = false) {
            document.getElementById('modeSelector').classList.add('hidden');
            document.getElementById('mainContent').classList.remove('hidden');
            await startSession(mode, notify);
        }

        async function startSession(mode, notify) {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'start_new_session',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    mode: mode,
                    notify: notify // ส่งค่า checkbox ไป
                });

                if (res.data.status === 'success') {
                    document.getElementById('subjectName').innerText = res.data.subject_name;
                    limitTimeStr = res.data.limit_time;
                    document.getElementById('limitInfo').innerText = `กำหนดเวลาสาย: ${limitTimeStr ? limitTimeStr.substring(0,5) : 'ไม่กำหนด'}`;
                    
                    renderQR(res.data.qr_token);

                    if (res.data.meeting_link) {
                        document.getElementById('teacherLinkBtn').classList.remove('hidden');
                        document.getElementById('btnOpenLink').href = res.data.meeting_link;
                        if(mode==='teams') document.getElementById('linkIcon').innerText = '📞';
                    }

                    startCountdown();
                    qrTimer = setInterval(rotateQR, 5000);
                    updateLiveStatus();
                    liveTimer = setInterval(updateLiveStatus, 3000);

                } else {
                    alert("Error: " + res.data.message);
                }
            } catch (e) { alert("Server Error"); }
        }

        async function rotateQR() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'rotate_qr_token',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });
                if (res.data.status === 'success') {
                    renderQR(res.data.new_qr_token);
                }
            } catch(e) {}
        }
        
        function renderQR(token) {
            document.getElementById('txtToken').innerText = token;
            const qrData = JSON.stringify({ class_id: CLASS_ID, token: token });
            document.getElementById('qrcode').innerHTML = "";
            new QRCode(document.getElementById("qrcode"), {
                text: qrData, width: 200, height: 200,
                colorDark : "#000000", colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.L
            });
        }
        
        // ... (Functions updateLiveStatus, startCountdown, renderLists เหมือนเดิมจากไฟล์เก่า) ...
        async function updateLiveStatus() {
             const profile = await liff.getProfile();
             const res = await axios.post('../../api/teacher_api.php', { action: 'get_live_status', line_id: profile.userId, class_id: CLASS_ID });
             if(res.data.status==='success') renderLists(res.data);
        }
        function renderLists(data) {
             document.getElementById('countIn').innerText = data.count_in; document.getElementById('countNot').innerText = data.count_not;
             const listIn = document.getElementById('listIn'); listIn.innerHTML='';
             data.checked_in.forEach(s => {
                 listIn.innerHTML += `<div class="flex justify-between items-center p-2 bg-gray-50 border rounded"><span class="text-sm font-bold text-gray-700">${s.name}</span><span class="text-xs ${s.status==='present'?'text-green-600':'text-yellow-600'}">${s.status}</span></div>`;
             });
        }
        function startCountdown() {
            if (!limitTimeStr) { document.getElementById('timer').innerText = "--:--:--"; return; }
            countdownTimer = setInterval(() => {
                const now = new Date();
                const [h, m, s] = limitTimeStr.split(':');
                const limit = new Date(); limit.setHours(h, m, s);
                let diff = limit - now;
                if (diff < 0) {
                    document.getElementById('timer').innerHTML = "<span class='text-red-500'>หมดเวลา (สาย)</span>";
                    document.getElementById('timer').classList.remove('text-green-400'); return;
                }
                const hh = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mm = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const ss = Math.floor((diff % (1000 * 60)) / 1000);
                document.getElementById('timer').innerText = `${hh.toString().padStart(2,'0')}:${mm.toString().padStart(2,'0')}:${ss.toString().padStart(2,'0')}`;
            }, 1000);
        }
    </script>
</body>
</html>