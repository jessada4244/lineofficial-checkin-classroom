<?php
// liff/teacher/report_sessions.php
require_once '../../config/security.php';
checkLogin('teacher');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกวันเช็คชื่อ</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap'); body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="bg-white px-4 py-4 shadow-sm sticky top-0 z-10 flex items-center gap-3">
        <button onclick="window.history.back()" class="text-gray-500 hover:text-blue-600 bg-gray-100 p-2 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </button>
        <div>
            <h1 class="text-lg font-bold text-gray-800 leading-none" id="subjectName">Loading...</h1>
            <p class="text-xs text-gray-400 mt-1">ประวัติการเช็คชื่อทั้งหมด</p>
        </div>
    </div>

    <div class="p-4 max-w-md mx-auto">
        <div id="sessionList" class="space-y-3">
            <div class="text-center py-10 text-gray-400">กำลังโหลดรายการ...</div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบ Class ID");
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                loadSessions();
            } catch (err) { alert("LIFF Error"); }
        }
        main();

        async function loadSessions() {
            try {
                const profile = await liff.getProfile();
                // ใช้ API เดิม get_checkin_sessions ได้เลย
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_checkin_sessions',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });
                
                const list = document.getElementById('sessionList');
                list.innerHTML = '';
                
                if(res.data.status === 'success') {
                    document.getElementById('subjectName').innerText = res.data.subject_name;
                    
                    if(res.data.sessions.length === 0) {
                        list.innerHTML = `
                            <div class="text-center py-10 bg-white rounded-xl shadow-sm">
                                <p class="text-gray-300 text-5xl mb-3">📅</p>
                                <p class="text-gray-400">ยังไม่มีการเช็คชื่อในวิชานี้</p>
                            </div>`;
                        return;
                    }

                    res.data.sessions.forEach(s => {
                        // สร้าง Card สำหรับแต่ละวันที่
                        list.innerHTML += `
                            <div onclick="goToDetail('${s.session_token}')" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex justify-between items-center cursor-pointer hover:bg-blue-50 transition">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-lg flex flex-col items-center justify-center">
                                        <span class="text-[10px] font-bold uppercase leading-none mt-1">DATE</span>
                                        <span class="font-bold text-lg leading-none">${s.date.split('/')[0]}</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800">วันที่ ${s.date}</p>
                                        <p class="text-xs text-gray-500">เริ่มเช็คเวลา ${s.time} น.</p>
                                    </div>
                                </div>
                                <div class="text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </div>
                        `;
                    });
                }
            } catch(e) { 
                console.error(e);
                alert("Error loading data"); 
            }
        }

        function goToDetail(token) {
            window.location.href = `./report_detail.php?class_id=${CLASS_ID}&token=${token}`;
        }
    </script>
</body>
</html>