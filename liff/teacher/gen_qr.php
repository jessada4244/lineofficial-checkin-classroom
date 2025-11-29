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

    <div class="max-w-4xl mx-auto w-full grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-gray-800 p-6 rounded-3xl shadow-2xl flex flex-col items-center text-center">
            <h1 class="text-2xl font-bold mb-1" id="subjectName">Loading...</h1>
            <p class="text-gray-400 text-sm mb-6">สแกนเพื่อเช็คชื่อเข้าเรียน</p>

            <div class="bg-white p-4 rounded-xl mb-6 shadow-inner">
                <div id="qrcode"></div>
            </div>

            <div class="w-full bg-gray-700 rounded-xl p-4 border border-gray-600">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">เวลาที่เหลือก่อนสาย</p>
                <div id="timer" class="text-4xl font-mono font-bold text-green-400">00:00:00</div>
                <p id="limitInfo" class="text-xs text-gray-500 mt-2">กำหนดเวลา: -</p>
            </div>

            <p class="text-xs text-gray-500 mt-6 animate-pulse">QR Code เปลี่ยนอัตโนมัติทุก 5 วินาที</p>
            <button onclick="window.history.back()" class="mt-4 text-gray-400 hover:text-white underline text-sm">ปิดหน้านี้</button>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col text-gray-800">
            <div class="p-4 bg-gray-100 border-b flex justify-between items-center">
                <h2 class="font-bold text-lg">สถานะการเช็คชื่อ</h2>
                <div class="flex gap-2 text-xs font-bold">
                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-md">มา: <span id="countIn">0</span></span>
                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded-md">ขาด: <span id="countNot">0</span></span>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-4" style="max-height: 500px;">
                
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase mb-2">เช็คชื่อแล้ว</h3>
                    <div id="listIn" class="space-y-2">
                        </div>
                </div>

                <hr class="border-gray-100">

                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase mb-2">ยังไม่เช็คชื่อ</h3>
                    <div id="listNot" class="space-y-2">
                         </div>
                </div>

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
            
            // 1. เริ่มต้น Session
            startSession();
        }
        main();

        async function startSession() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'start_new_session',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });

                if (res.data.status === 'success') {
                    document.getElementById('subjectName').innerText = res.data.subject_name;
                    limitTimeStr = res.data.limit_time;
                    document.getElementById('limitInfo').innerText = `กำหนดเวลาสาย: ${limitTimeStr ? limitTimeStr.substring(0,5) : 'ไม่กำหนด'}`;
                    
                    // Render QR แรก
                    renderQR(res.data.qr_token);
                    
                    // เริ่ม Loop ต่างๆ
                    startCountdown(); // นาฬิกานับถอยหลัง
                    
                    // หมุน QR ทุก 5 วิ
                    qrTimer = setInterval(rotateQR, 5000);
                    
                    // ดึงรายชื่อสด ทุก 3 วิ
                    updateLiveStatus();
                    liveTimer = setInterval(updateLiveStatus, 3000);

                } else {
                    alert("เริ่มการเช็คชื่อไม่สำเร็จ: " + res.data.message);
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
            } catch(e) { console.error("Rotate Failed"); }
        }

        async function updateLiveStatus() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_live_status',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });
                if (res.data.status === 'success') {
                    renderLists(res.data);
                }
            } catch(e) { console.error("Live Status Failed"); }
        }

        function renderQR(token) {
            const qrData = JSON.stringify({ class_id: CLASS_ID, token: token });
            document.getElementById('qrcode').innerHTML = "";
            new QRCode(document.getElementById("qrcode"), {
                text: qrData, width: 200, height: 200,
                colorDark : "#000000", colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.L
            });
        }

        function renderLists(data) {
            document.getElementById('countIn').innerText = data.count_in;
            document.getElementById('countNot').innerText = data.count_not;

            const listIn = document.getElementById('listIn');
            listIn.innerHTML = "";
            data.checked_in.forEach(s => {
                const statusColor = s.status === 'present' ? 'text-green-600' : 'text-yellow-600';
                const statusText = s.status === 'present' ? 'ทันเวลา' : 'สาย';
                listIn.innerHTML += `
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">✓</div>
                            <div>
                                <p class="text-sm font-bold text-gray-700 leading-none">${s.name}</p>
                                <p class="text-[10px] text-gray-400">${s.student_id}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold ${statusColor}">${statusText}</span>
                            <p class="text-[10px] text-gray-400">${s.time}</p>
                        </div>
                    </div>`;
            });

            const listNot = document.getElementById('listNot');
            listNot.innerHTML = "";
            data.not_checked_in.forEach(s => {
                listNot.innerHTML += `
                    <div class="flex justify-between items-center p-2 bg-white rounded-lg border border-gray-100 opacity-60">
                         <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-[10px] font-bold">?</div>
                            <div>
                                <p class="text-sm font-bold text-gray-700 leading-none">${s.name}</p>
                                <p class="text-[10px] text-gray-400">${s.student_id}</p>
                            </div>
                        </div>
                    </div>`;
            });
        }

        function startCountdown() {
            if (!limitTimeStr) {
                document.getElementById('timer').innerText = "--:--:--";
                return;
            }

            countdownTimer = setInterval(() => {
                const now = new Date();
                const [h, m, s] = limitTimeStr.split(':');
                const limit = new Date();
                limit.setHours(h, m, s);

                let diff = limit - now;
                
                if (diff < 0) {
                    // เลยกำหนดเวลาแล้ว
                    document.getElementById('timer').innerHTML = "<span class='text-red-500'>หมดเวลา (สาย)</span>";
                    document.getElementById('timer').classList.remove('text-green-400');
                    return;
                }

                const hh = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mm = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const ss = Math.floor((diff % (1000 * 60)) / 1000);

                document.getElementById('timer').innerText = 
                    `${hh.toString().padStart(2,'0')}:${mm.toString().padStart(2,'0')}:${ss.toString().padStart(2,'0')}`;
            }, 1000);
        }
    </script>
</body>
</html>