<?php
require_once '../../config/security.php';
checkLogin('teacher');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการเช็คชื่อ</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap'); body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="bg-white p-4 shadow-sm sticky top-0 z-10 border-b border-gray-100 flex items-center gap-3">
        <button onclick="window.history.back()" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </button>
        <h1 class="text-lg font-bold text-gray-800" id="subjectName">รายงานการเช็คชื่อ</h1>
    </div>

    <div class="p-4">
        <label class="block text-xs font-bold text-gray-500 mb-2">เลือกรอบการเช็คชื่อ</label>
        <select id="sessionSelect" onchange="loadSessionDetail()" class="w-full border border-gray-300 rounded-xl p-3 bg-white shadow-sm outline-none focus:ring-2 focus:ring-blue-500 mb-4">
            <option value="">กำลังโหลดรายการ...</option>
        </select>

        <div class="grid grid-cols-3 gap-2 mb-4">
            <div class="bg-green-50 p-3 rounded-xl border border-green-100 text-center">
                <p class="text-[10px] text-green-600 font-bold">มาเรียน</p>
                <p id="sumPresent" class="text-xl font-bold text-green-700">0</p>
            </div>
            <div class="bg-yellow-50 p-3 rounded-xl border border-yellow-100 text-center">
                <p class="text-[10px] text-yellow-600 font-bold">สาย</p>
                <p id="sumLate" class="text-xl font-bold text-yellow-700">0</p>
            </div>
            <div class="bg-red-50 p-3 rounded-xl border border-red-100 text-center">
                <p class="text-[10px] text-red-600 font-bold">ขาด</p>
                <p id="sumAbsent" class="text-xl font-bold text-red-700">0</p>
            </div>
        </div>

        <div id="studentList" class="space-y-2"></div>
    </div>

    <script>
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');

        async function main() {
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            loadSessions();
        }
        main();

        // 1. โหลดรายการรอบ (Sessions) ทั้งหมด
        async function loadSessions() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_checkin_sessions',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });
                
                const select = document.getElementById('sessionSelect');
                select.innerHTML = '';
                
                if(res.data.status === 'success') {
                    document.getElementById('subjectName').innerText = res.data.subject_name;
                    
                    if(res.data.sessions.length === 0) {
                        select.innerHTML = '<option>ยังไม่มีประวัติการเช็คชื่อ</option>';
                        return;
                    }

                    res.data.sessions.forEach((s, index) => {
                        // แสดงวันที่และเวลาเริ่มรอบ
                        const label = `รอบวันที่ ${s.date} (เริ่ม ${s.time})`;
                        const opt = document.createElement('option');
                        opt.value = s.session_token;
                        opt.innerText = label;
                        select.appendChild(opt);
                    });
                    
                    // โหลดข้อมูลรอบล่าสุดทันที
                    loadSessionDetail();
                }
            } catch(e) { alert("Error loading sessions"); }
        }

        // 2. โหลดรายละเอียดของรอบที่เลือก
        async function loadSessionDetail() {
            const token = document.getElementById('sessionSelect').value;
            if(!token) return;

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_session_report',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    session_token: token
                });

                if(res.data.status === 'success') {
                    document.getElementById('sumPresent').innerText = res.data.summary.present;
                    document.getElementById('sumLate').innerText = res.data.summary.late;
                    document.getElementById('sumAbsent').innerText = res.data.summary.absent;

                    const list = document.getElementById('studentList');
                    list.innerHTML = '';
                    
                    res.data.report.forEach(row => {
                        let statusHtml = '';
                        if(row.status === 'present') statusHtml = '<span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">มาเรียน</span>';
                        else if(row.status === 'late') statusHtml = '<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">มาสาย</span>';
                        else statusHtml = '<span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded">ขาด</span>';

                        list.innerHTML += `
                            <div class="bg-white p-3 rounded-xl border border-gray-100 flex justify-between items-center shadow-sm">
                                <div>
                                    <p class="font-bold text-gray-800 text-sm">${row.name}</p>
                                    <p class="text-xs text-gray-400">${row.student_id}</p>
                                </div>
                                <div class="text-right">
                                    ${statusHtml}
                                    <p class="text-[10px] text-gray-400 mt-1">${row.checkin_time}</p>
                                </div>
                            </div>
                        `;
                    });
                }
            } catch(e) { console.error(e); }
        }
    </script>
</body>
</html>