<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../config/security.php';
checkLogin('teacher');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Panel & QR</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap'); body { font-family: 'Sarabun', sans-serif; }</style>
    <script>
        window.onpageshow = function(event) {
            // ถ้า Browser บอกว่าหน้านี้ถูกโหลดมาจาก Cache (ย้อนกลับมา) ให้สั่ง Reload ใหม่ทันที
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</head>
<body class="bg-gray-100 min-h-screen pb-20">

    <div class="bg-white p-4 shadow-sm sticky top-0 z-10 flex items-center gap-3">
        <button onclick="window.history.back()" class="text-gray-500 hover:text-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </button>
        <div>
            <h1 class="font-bold text-lg text-gray-800 leading-none" id="subjectName">Loading...</h1>
            <p class="text-xs text-gray-400" id="classCode">Code: ...</p>
        </div>
    </div>

    <div class="max-w-md mx-auto p-4 space-y-4">

        <div id="settingPanel" class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100">
            <h2 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                ⚙️ ตั้งค่าคลาสเรียน
            </h2>
            
            <div class="grid grid-cols-3 gap-2 mb-4">
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="onsite" class="peer sr-only" checked onchange="toggleMode()">
                    <div class="peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 border border-gray-200 rounded-xl p-2 text-center transition">
                        <div class="text-xl">🏫</div>
                        <div class="text-[10px] font-bold">Onsite</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="zoom" class="peer sr-only" onchange="toggleMode()">
                    <div class="peer-checked:bg-blue-500 peer-checked:text-white peer-checked:border-blue-500 border border-gray-200 rounded-xl p-2 text-center transition">
                        <div class="text-xl">🎥</div>
                        <div class="text-[10px] font-bold">Zoom</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="teams" class="peer sr-only" onchange="toggleMode()">
                    <div class="peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 border border-gray-200 rounded-xl p-2 text-center transition">
                        <div class="text-xl">📞</div>
                        <div class="text-[10px] font-bold">Teams</div>
                    </div>
                </label>
            </div>

            <div class="mb-4">
                <label class="text-xs font-bold text-gray-500 mb-1 block">⏰ ตัดสายหลังเวลา</label>
                <input type="time" id="limitTime" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2 text-center font-bold text-lg outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div id="onlineOption" class="hidden space-y-3 mb-4 p-3 bg-blue-50 rounded-xl border border-blue-100">
                <div>
                    <label class="text-xs font-bold text-blue-600 mb-1 block">🔗 ลิงก์ห้องเรียน</label>
                    <input type="text" id="meetingLink" placeholder="วางลิงก์ Zoom/Teams ที่นี่" class="w-full border border-blue-200 rounded-lg p-2 text-sm">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="notifyLine" class="w-4 h-4 text-blue-600 rounded" checked>
                    <label for="notifyLine" class="text-xs text-gray-600">ส่งแจ้งเตือนเข้า LINE กลุ่มนิสิต</label>
                </div>
            </div>

            <button onclick="startSession()" id="btnStart" class="w-full bg-gray-800 hover:bg-black text-white py-3 rounded-xl font-bold shadow-lg transition transform active:scale-95">
                🚀 เริ่มคลาส / สร้าง QR
            </button>
        </div>

        <div id="activePanel" class="hidden space-y-4">
            
            <div class="bg-white rounded-3xl shadow-lg p-6 flex flex-col items-center text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
                
                <h3 class="font-bold text-gray-800 mb-2">สแกนเพื่อเช็คชื่อ</h3>
                <div class="bg-white p-2 rounded-xl shadow-inner border border-gray-100 mb-4">
                    <div id="qrcode"></div>
                </div>

                <div class="flex items-center gap-2 bg-gray-100 px-4 py-2 rounded-full mb-4">
                    <span class="text-xs text-gray-500">CODE:</span>
                    <span id="txtToken" class="font-mono font-bold text-xl text-blue-600 tracking-widest">---</span>
                </div>

                <div class="w-full grid grid-cols-2 gap-3">
                    <a id="hostLink" href="#" target="_blank" class="hidden col-span-2 bg-blue-50 text-blue-600 py-2 rounded-lg text-sm font-bold border border-blue-100">
                        🎥 เปิดห้องเรียน (Host)
                    </a>
                    <div class="bg-red-50 p-2 rounded-lg border border-red-100">
                        <p class="text-[10px] text-red-400">หมดเวลาสาย</p>
                        <p id="timer" class="font-mono font-bold text-red-600 text-lg">--:--:--</p>
                    </div>
                    <button onclick="stopSession()" class="bg-gray-200 text-gray-600 py-2 rounded-lg text-sm font-bold hover:bg-gray-300">
                        ⏹ จบคาบ
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="flex justify-between items-center mb-3 border-b pb-2">
                    <h3 class="font-bold text-gray-700">🔴 Live Status</h3>
                    <div class="flex gap-2 text-xs">
                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded">มา: <span id="countIn" class="font-bold">0</span></span>
                        <span class="bg-red-50 text-red-400 px-2 py-0.5 rounded">ยังไม่มา: <span id="countNot" class="font-bold">0</span></span>
                    </div>
                </div>
                <div id="studentList" class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    </div>
            </div>

        </div>

    </div>

    <script>
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        let refreshInterval, qrInterval, countdownInterval;
        let classData = {};

        async function main() {
            if(!CLASS_ID) return alert("Error: No Class ID");
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            
            // โหลดข้อมูลวิชามาแสดงก่อน
            loadClassInfo();
        }
        main();

        async function loadClassInfo() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_class_details',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });
                if(res.data.status==='success') {
                    classData = res.data.class;
                    document.getElementById('subjectName').innerText = classData.subject_name;
                    document.getElementById('classCode').innerText = classData.course_code;
                    document.getElementById('limitTime').value = classData.checkin_limit_time ? classData.checkin_limit_time.substring(0,5) : '';
                    
                    // Pre-fill Links
                    document.getElementById('meetingLink').dataset.zoom = classData.zoom_link || '';
                    document.getElementById('meetingLink').dataset.teams = classData.teams_link || '';
                }
            } catch(e) { console.error(e); }
        }

        function toggleMode() {
            const mode = document.querySelector('input[name="mode"]:checked').value;
            const onlineOpt = document.getElementById('onlineOption');
            const linkInput = document.getElementById('meetingLink');
            
            if(mode === 'onsite') {
                onlineOpt.classList.add('hidden');
            } else {
                onlineOpt.classList.remove('hidden');
                // สลับลิงก์ตามโหมดที่เลือก
                if(mode === 'zoom') linkInput.value = linkInput.dataset.zoom || '';
                if(mode === 'teams') linkInput.value = linkInput.dataset.teams || '';
            }
        }

        async function startSession() {
            const mode = document.querySelector('input[name="mode"]:checked').value;
            const time = document.getElementById('limitTime').value;
            const link = document.getElementById('meetingLink').value;
            const notify = document.getElementById('notifyLine').checked;
            
            if(!time) return alert("กรุณากำหนดเวลาตัดสาย");

            // UI Changes
            document.getElementById('btnStart').innerText = "กำลังสร้าง...";
            document.getElementById('btnStart').disabled = true;

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'start_new_session',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    mode: mode,
                    time: time,
                    link: link,
                    notify: notify
                });

                if(res.data.status === 'success') {
                    // Hide Settings, Show Active Panel
                    document.getElementById('settingPanel').classList.add('hidden');
                    document.getElementById('activePanel').classList.remove('hidden');

                    // Setup QR & Info
                    renderQR(res.data.qr_token);
                    startCountdown(time);
                    
                    if(mode !== 'onsite' && res.data.meeting_link) {
                        const btnLink = document.getElementById('hostLink');
                        btnLink.href = res.data.meeting_link;
                        btnLink.classList.remove('hidden');
                    }

                    // Start Loops
                    updateLiveStatus();
                    refreshInterval = setInterval(updateLiveStatus, 3000);
                    qrInterval = setInterval(rotateQR, 7000); // เปลี่ยน QR ทุก 5 วิ
                } else {
                    alert("Error: " + res.data.message);
                    document.getElementById('btnStart').innerText = "🚀 เริ่มคลาส / สร้าง QR";
                    document.getElementById('btnStart').disabled = false;
                }
            } catch(e) {
                alert("Server Error");
                document.getElementById('btnStart').disabled = false;
            }
        }

        async function rotateQR() {
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'rotate_qr_token', line_id: pf.userId, class_id: CLASS_ID
                });
                if(res.data.status === 'success') renderQR(res.data.new_qr_token);
            } catch(e) {}
        }

        function renderQR(token) {
            document.getElementById('txtToken').innerText = token;
            const qrData = JSON.stringify({ class_id: CLASS_ID, token: token });
            document.getElementById('qrcode').innerHTML = "";
            new QRCode(document.getElementById("qrcode"), {
                text: qrData, width: 220, height: 220,
                colorDark : "#000000", colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.L
            });
        }

        async function updateLiveStatus() {
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_live_status', line_id: pf.userId, class_id: CLASS_ID
                });
                if(res.data.status==='success') {
                    document.getElementById('countIn').innerText = res.data.count_in;
                    document.getElementById('countNot').innerText = res.data.count_not;
                    
                    const list = document.getElementById('studentList');
                    list.innerHTML = '';
                    
                    // เรียงคนมาแล้วขึ้นก่อน
                    const sorted = [...res.data.checked_in, ...res.data.not_checked_in];
                    
                    sorted.forEach(s => {
                        let statusBadge = `<span class="text-xs text-gray-300">รอเช็คชื่อ...</span>`;
                        if(s.status === 'present') statusBadge = `<span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-bold">ทันเวลา (${s.time})</span>`;
                        else if(s.status === 'late') statusBadge = `<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded font-bold">สาย (${s.time})</span>`;
                        
                        list.innerHTML += `
                            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <div>
                                    <p class="text-sm font-bold text-gray-800">${s.name}</p>
                                    <p class="text-[10px] text-gray-400">${s.student_id}</p>
                                </div>
                                <div>${statusBadge}</div>
                            </div>
                        `;
                    });
                }
            } catch(e){}
        }

        function startCountdown(limitTimeStr) {
            clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                const now = new Date();
                const [h, m] = limitTimeStr.split(':');
                const limit = new Date();
                limit.setHours(h, m, 0);
                
                let diff = limit - now;
                if (diff < 0) {
                    document.getElementById('timer').innerText = "00:00:00 (หมดเวลา)";
                    return;
                }
                const hh = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mm = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const ss = Math.floor((diff % (1000 * 60)) / 1000);
                document.getElementById('timer').innerText = `${hh}:${mm}:${ss}`;
            }, 1000);
        }

        function stopSession() {
            if(confirm("ต้องการจบคาบเรียนหรือไม่? (QR Code จะใช้งานไม่ได้อีก)")) {
                window.location.reload(); // รีโหลดเพื่อเคลียร์หน้าจอ
            }
        }
    </script>
</body>
</html>