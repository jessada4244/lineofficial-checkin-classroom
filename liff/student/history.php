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

    <div class="bg-white p-5 shadow-sm sticky top-0 z-10 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <button onclick="window.history.back()" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div>
                <h1 class="text-lg font-bold text-gray-800 leading-tight" id="subjectName">กำลังโหลด...</h1>
                <p class="text-xs text-blue-600 font-medium" id="courseCode">...</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 p-4">
        <div class="bg-green-50 p-3 rounded-xl border border-green-100 text-center">
            <p class="text-xs text-green-600 mb-1">เข้าเรียน (ครั้ง)</p>
            <p id="countPresent" class="text-2xl font-bold text-green-700">-</p>
        </div>
        <div class="bg-yellow-50 p-3 rounded-xl border border-yellow-100 text-center">
            <p class="text-xs text-yellow-600 mb-1">มาสาย (ครั้ง)</p>
            <p id="countLate" class="text-2xl font-bold text-yellow-700">-</p>
        </div>
    </div>

    <div id="historyList" class="px-4 pb-10 space-y-3">
        <div class="text-center py-10 text-gray-400">กำลังโหลดข้อมูล...</div>
    </div>

    <script>
        const LIFF_ID = "2008562649-LEXWJgaD"; // ใช้ ID เดียวกับ class_list
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบรหัสวิชา");
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            
            loadHistory();
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
                    document.getElementById('historyList').innerHTML = `<p class="text-center text-red-500 mt-10">${res.data.message}</p>`;
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
                    <div class="text-center py-10 opacity-50">
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

                // แปลงวันที่และเวลา
                const dateObj = new Date(h.checkin_time);
                const dateStr = dateObj.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
                const timeStr = dateObj.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });

                // กำหนดสีตามสถานะ
                const isLate = h.status === 'late';
                const statusText = isLate ? 'มาสาย' : 'ทันเวลา';
                const statusColor = isLate ? 'text-yellow-600 bg-yellow-50 border-yellow-100' : 'text-green-600 bg-green-50 border-green-100';
                const icon = isLate ? '⚠️' : '✅';

                list.innerHTML += `
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg bg-gray-50">
                                ${icon}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">${dateStr}</p>
                                <p class="text-xs text-gray-400">เวลา ${timeStr}</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-3 py-1 rounded-full border ${statusColor}">
                            ${statusText}
                        </span>
                    </div>
                `;
            });

            // อัปเดตตัวเลขสรุป
            document.getElementById('countPresent').innerText = present;
            document.getElementById('countLate').innerText = late;
        }
    </script>
</body>
</html>