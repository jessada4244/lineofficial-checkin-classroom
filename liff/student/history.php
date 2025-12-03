<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../config/security.php';
checkLogin('student'); // บังคับว่าเป็น teacher เท่านั้น
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการเช็คชื่อ</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="bg-white px-5 py-4 shadow-sm sticky top-0 z-10 border-b border-gray-100 flex items-center gap-4">
        <button onclick="window.history.back()" class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </button>
        <div>
            <h1 class="text-lg font-bold text-gray-800 leading-none" id="subjectName">Loading...</h1>
            <p class="text-xs text-blue-500 font-bold mt-1" id="courseCode">...</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 p-4">
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-green-100 flex flex-col items-center">
            <div class="bg-green-100 text-green-600 w-8 h-8 rounded-full flex items-center justify-center mb-2 text-xs font-bold">✓</div>
            <p class="text-xs text-gray-400 mb-0.5">เข้าเรียน (ครั้ง)</p>
            <p id="countPresent" class="text-2xl font-bold text-gray-800">-</p>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-yellow-100 flex flex-col items-center">
            <div class="bg-yellow-100 text-yellow-600 w-8 h-8 rounded-full flex items-center justify-center mb-2 text-xs font-bold">!</div>
            <p class="text-xs text-gray-400 mb-0.5">มาสาย (ครั้ง)</p>
            <p id="countLate" class="text-2xl font-bold text-gray-800">-</p>
        </div>
    </div>

    <h2 class="px-5 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">รายการล่าสุด</h2>

    <div id="historyList" class="px-4 pb-10 space-y-3">
        <div class="text-center py-10 text-gray-400">กำลังโหลดข้อมูล...</div>
    </div>

    <script>
        // *** ใช้ LIFF ID เดียวกับ class_list.php ได้เลยครับ ***
        const LIFF_ID = "2008573640-jb4bpE5J"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบรหัสวิชา");
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                loadHistory();
            } catch (err) { alert("LIFF Error"); }
        }
        main();

        async function loadHistory() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', {
                    action: 'get_history',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });

                if (res.data.status === 'success') {
                    renderPage(res.data);
                } else {
                    document.getElementById('historyList').innerHTML = `<p class="text-center text-red-400 mt-10">${res.data.message}</p>`;
                }
            } catch (err) {
                console.error(err);
                alert("โหลดข้อมูลล้มเหลว");
            }
        }

        function renderPage(data) {
            document.getElementById('subjectName').innerText = data.subject_name;
            document.getElementById('courseCode').innerText = data.course_code;

            const list = document.getElementById('historyList');
            list.innerHTML = '';

            let present = 0;
            let late = 0;

            if (data.history.length === 0) {
                list.innerHTML = `
                    <div class="text-center py-12 opacity-40">
                        <svg class="w-16 h-16 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p>ยังไม่มีประวัติการเช็คชื่อ</p>
                    </div>`;
                document.getElementById('countPresent').innerText = 0;
                document.getElementById('countLate').innerText = 0;
                return;
            }

            data.history.forEach(h => {
                // นับจำนวน
                if (h.status === 'present') present++;
                else if (h.status === 'late') late++;

                // Format DateTime
                const dateObj = new Date(h.checkin_time);
                const dateStr = dateObj.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
                const timeStr = dateObj.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });

                // UI Status
                const isLate = h.status === 'late';
                const statusText = isLate ? 'มาสาย' : 'ทันเวลา';
                const statusBg = isLate ? 'bg-yellow-50 border-yellow-100' : 'bg-white border-gray-100';
                const iconBg = isLate ? 'bg-yellow-100 text-yellow-600' : 'bg-green-100 text-green-600';
                const icon = isLate ? '!' : '✓';

                list.innerHTML += `
                    <div class="${statusBg} p-4 rounded-xl shadow-sm border flex justify-between items-center transition hover:shadow-md">
                        <div class="flex items-center gap-3">
                            <div class="${iconBg} w-10 h-10 rounded-full flex items-center justify-center font-bold">
                                ${icon}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">${dateStr}</p>
                                <p class="text-xs text-gray-400">เวลา ${timeStr} น.</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wide ${isLate ? 'bg-yellow-200 text-yellow-800' : 'bg-green-100 text-green-700'}">
                            ${statusText}
                        </span>
                    </div>
                `;
            });

            document.getElementById('countPresent').innerText = present;
            document.getElementById('countLate').innerText = late;
        }
    </script>
</body>
</html>