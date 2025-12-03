<?php
// liff/teacher/report_detail.php
require_once '../../config/security.php';
checkLogin('teacher');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการเช็คชื่อ</title>
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
        <h1 class="text-lg font-bold text-gray-800">ผลการเช็คชื่อ</h1>
    </div>

    <div class="p-4 max-w-md mx-auto pb-10">
        
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-green-50 p-3 rounded-xl border border-green-200 text-center shadow-sm">
                <p class="text-xs text-green-600 font-bold mb-1">มาเรียน</p>
                <p id="sumPresent" class="text-2xl font-bold text-green-700">0</p>
            </div>
            <div class="bg-yellow-50 p-3 rounded-xl border border-yellow-200 text-center shadow-sm">
                <p class="text-xs text-yellow-600 font-bold mb-1">มาสาย</p>
                <p id="sumLate" class="text-2xl font-bold text-yellow-700">0</p>
            </div>
            <div class="bg-red-50 p-3 rounded-xl border border-red-200 text-center shadow-sm">
                <p class="text-xs text-red-600 font-bold mb-1">ขาดเรียน</p>
                <p id="sumAbsent" class="text-2xl font-bold text-red-700">0</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <span class="text-xs font-bold text-gray-500 uppercase">รายชื่อนิสิต</span>
                <span class="text-xs text-gray-400" id="totalCount">ทั้งหมด - คน</span>
            </div>
            <div id="studentList" class="divide-y divide-gray-100">
                <div class="p-4 text-center text-gray-400">กำลังโหลด...</div>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        const TOKEN = (new URLSearchParams(window.location.search)).get('token');

        async function main() {
            if (!CLASS_ID || !TOKEN) return alert("ข้อมูลไม่ครบถ้วน");
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                loadDetail();
            } catch (err) { alert("LIFF Error"); }
        }
        main();

        async function loadDetail() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_session_report',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    session_token: TOKEN
                });

                if(res.data.status === 'success') {
                    // Update Summary
                    document.getElementById('sumPresent').innerText = res.data.summary.present;
                    document.getElementById('sumLate').innerText = res.data.summary.late;
                    document.getElementById('sumAbsent').innerText = res.data.summary.absent;
                    document.getElementById('totalCount').innerText = `ทั้งหมด ${res.data.report.length} คน`;

                    const list = document.getElementById('studentList');
                    list.innerHTML = '';
                    
                    res.data.report.forEach(row => {
                        let statusHtml = '';
                        let rowBg = 'hover:bg-gray-50';

                        if(row.status === 'present') {
                            statusHtml = '<span class="text-[10px] bg-green-100 text-green-700 px-2 py-1 rounded font-bold">มาเรียน</span>';
                        } else if(row.status === 'late') {
                            statusHtml = '<span class="text-[10px] bg-yellow-100 text-yellow-700 px-2 py-1 rounded font-bold">มาสาย</span>';
                        } else {
                            statusHtml = '<span class="text-[10px] bg-red-100 text-red-700 px-2 py-1 rounded font-bold">ขาด</span>';
                            rowBg = 'bg-red-50/30'; // ไฮไลท์คนขาดบางๆ
                        }

                        const checkTime = (row.checkin_time && row.checkin_time !== '-') ? row.checkin_time : '--:--';

                        list.innerHTML += `
                            <div class="p-4 flex justify-between items-center ${rowBg} transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-xs">
                                        ${row.name.charAt(0)}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 text-sm">${row.name}</p>
                                        <p class="text-xs text-gray-400 font-mono tracking-wider">${row.student_id}</p>
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end gap-1">
                                    ${statusHtml}
                                    <span class="text-[10px] text-gray-400 font-mono">${checkTime}</span>
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