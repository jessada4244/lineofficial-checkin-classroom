<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการเช็คชื่อ</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen pb-20">

    <div class="bg-white p-4 shadow-sm sticky top-0 z-10 border-b border-gray-100">
        <div class="flex items-center gap-3 mb-3">
            <button onclick="window.history.back()" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <h1 class="text-lg font-bold text-gray-800 truncate" id="subjectName">กำลังโหลด...</h1>
        </div>
        
        <input type="date" id="reportDate" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-center font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="loadReport()">
    </div>

    <div class="grid grid-cols-3 gap-2 p-4">
        <div class="bg-green-50 p-2 rounded-xl border border-green-100 text-center">
            <p class="text-[10px] text-green-600 font-bold">มาเรียน</p>
            <p id="sumPresent" class="text-xl font-bold text-green-700">-</p>
        </div>
        <div class="bg-yellow-50 p-2 rounded-xl border border-yellow-100 text-center">
            <p class="text-[10px] text-yellow-600 font-bold">สาย</p>
            <p id="sumLate" class="text-xl font-bold text-yellow-700">-</p>
        </div>
        <div class="bg-red-50 p-2 rounded-xl border border-red-100 text-center">
            <p class="text-[10px] text-red-600 font-bold">ขาด</p>
            <p id="sumAbsent" class="text-xl font-bold text-red-700">-</p>
        </div>
    </div>

    <div id="studentList" class="px-4 space-y-2">
        <div class="text-center py-10 text-gray-400">กำลังประมวลผล...</div>
    </div>

    <script>
        // *** ใช้ LIFF ID เดียวกับ manage_class.php ได้เลย ***
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบรหัสวิชา");
            
            // ตั้งค่าวันที่ปัจจุบันใน Input Date
            document.getElementById('reportDate').valueAsDate = new Date();

            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                loadReport();
            } catch (err) { alert("LIFF Init Failed"); }
        }
        main();

        async function loadReport() {
            const date = document.getElementById('reportDate').value;
            
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_daily_report',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    date: date
                });

                if (res.data.status === 'success') {
                    renderReport(res.data);
                } else {
                    alert(res.data.message);
                }
            } catch (err) {
                console.error(err);
                alert("โหลดข้อมูลล้มเหลว");
            }
        }

        function renderReport(data) {
            document.getElementById('subjectName').innerText = data.subject_name;
            
            // Update Summary
            document.getElementById('sumPresent').innerText = data.summary.present;
            document.getElementById('sumLate').innerText = data.summary.late;
            document.getElementById('sumAbsent').innerText = data.summary.absent;

            const list = document.getElementById('studentList');
            list.innerHTML = '';

            if (data.report.length === 0) {
                list.innerHTML = `<p class="text-center text-gray-400 py-4">ห้องเรียนนี้ยังไม่มีนิสิต</p>`;
                return;
            }

            data.report.forEach(row => {
                let statusBadge = '';
                let timeText = '';

                if (row.status === 'present') {
                    statusBadge = '<span class="text-xs font-bold bg-green-100 text-green-700 px-2 py-1 rounded">มาเรียน</span>';
                    timeText = `<span class="text-green-600">${row.checkin_time} น.</span>`;
                } else if (row.status === 'late') {
                    statusBadge = '<span class="text-xs font-bold bg-yellow-100 text-yellow-700 px-2 py-1 rounded">มาสาย</span>';
                    timeText = `<span class="text-yellow-600">${row.checkin_time} น.</span>`;
                } else {
                    statusBadge = '<span class="text-xs font-bold bg-red-100 text-red-700 px-2 py-1 rounded">ขาดเรียน</span>';
                    timeText = '<span class="text-red-300">-</span>';
                }

                list.innerHTML += `
                    <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                ${row.name.charAt(0)}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">${row.name}</p>
                                <p class="text-xs text-gray-400">${row.student_id}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            ${statusBadge}
                            <p class="text-xs font-bold mt-1">${timeText}</p>
                        </div>
                    </div>
                `;
            });
        }
    </script>
</body>
</html>