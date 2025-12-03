<?php
// liff/teacher/manage_class.php
require_once '../../config/security.php';
checkLogin('teacher');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการการสอน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .nav-item.active { color: #2563EB; font-weight: bold; }
        .nav-item.active svg { stroke: #2563EB; }
        .pb-safe { padding-bottom: 80px; } /* เว้นที่ให้เมนูล่าง */
    </style>
</head>
<body class="bg-gray-100 min-h-screen relative">

    <div class="bg-white px-4 py-3 shadow-sm sticky top-0 z-20 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800" id="pageTitle">จัดการห้องเรียน</h1>
        <div class="flex gap-3">
                <button onclick="window.location.reload()" class="text-gray-400 hover:text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
                <button onclick="window.location.href='../settings.php'" class="text-gray-400 hover:text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>
    </div>

    <div class="p-4 pb-safe max-w-md mx-auto">
        
        <div id="view-class" class="block">
            <button onclick="openCreateModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-xl shadow-lg flex justify-center items-center font-bold mb-6 transition transform active:scale-95">
                <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                สร้างห้องเรียนใหม่
            </button>
            <p class="text-xs text-gray-500 font-bold mb-2">รายวิชาทั้งหมด</p>
            <div id="classListContainer" class="space-y-4">
                <div class="text-center mt-10 text-gray-400">กำลังโหลด...</div>
            </div>
        </div>

        <div id="view-report" class="hidden">
            <!-- <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl mb-4 flex gap-3 items-center">
                <div class="text-2xl">📊</div>
                <div>
                    <h3 class="font-bold text-yellow-800">เลือกวิชาเพื่อดูรายงาน</h3>
                    <p class="text-xs text-yellow-600">กดที่รายวิชาเพื่อดูประวัติการเช็คชื่อย้อนหลัง</p>
                </div>
            </div> -->
            <div id="reportListContainer" class="space-y-4">
                </div>
        </div>

    </div>

    <div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 flex justify-around py-3 pb-5 z-30 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <button onclick="switchTab('class')" id="nav-class" class="nav-item active flex flex-col items-center text-gray-400 w-1/2">
            <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
            <span class="text-[10px]">ห้องเรียน</span>
        </button>
        <button onclick="switchTab('report')" id="nav-report" class="nav-item flex flex-col items-center text-gray-400 w-1/2">
            <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="text-[10px]">รายงานผล</span>
        </button>
    </div>

    <div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">สร้างห้องเรียนใหม่</h2>
            <div class="mb-4">
                <label class="text-xs text-gray-500 font-semibold ml-1">รหัสวิชา</label>
                <input type="text" id="courseCode" class="w-full border bg-gray-50 p-2.5 rounded-lg mb-3 focus:ring-2 focus:ring-blue-500 outline-none">
                <label class="text-xs text-gray-500 font-semibold ml-1">ชื่อวิชา</label>
                <input type="text" id="subjectName" class="w-full border bg-gray-50 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-6">
                <label class="text-xs text-gray-500 block mb-2 font-semibold ml-1">สีประจำวิชา</label>
                <div id="colorSelection" class="flex gap-2 justify-between p-3 bg-gray-50 rounded-lg border border-gray-100"></div>
                <input type="hidden" id="roomColor" value="#3B82F6">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button onclick="document.getElementById('createModal').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg">ยกเลิก</button>
                <button onclick="createClass()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow">บันทึก</button>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const COLORS = ['#0062ffff', '#67f380ff', '#ffc96cff', '#f79191ff', '#ae8ef6ff', '#f78dc2ff', '#92dfedff'];
        let myClasses = [];

        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                else {
                    loadClasses();
                    renderColorSwatches();
                }
            } catch (err) { alert("LIFF Init Failed"); }
        }
        main();

        function switchTab(tab) {
            // UI Update
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active', 'text-blue-600'));
            document.getElementById('nav-' + tab).classList.add('active');
            
            document.getElementById('view-class').classList.add('hidden');
            document.getElementById('view-report').classList.add('hidden');
            document.getElementById('view-' + tab).classList.remove('hidden');

            document.getElementById('pageTitle').innerText = (tab === 'class') ? "จัดการห้องเรียน" : "รายงานการเข้าเรียน";
        }

        async function loadClasses() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', { action: 'get_classes', line_id: profile.userId });
                
                myClasses = res.data.classes;
                renderClassList();

            } catch (err) { console.error(err); }
        }

        function renderClassList() {
            const listClass = document.getElementById('classListContainer');
            const listReport = document.getElementById('reportListContainer');
            
            listClass.innerHTML = '';
            listReport.innerHTML = '';

            if (myClasses.length === 0) {
                const empty = `<div class="text-center py-10 bg-white rounded-xl shadow-sm text-gray-400">ยังไม่มีรายวิชา</div>`;
                listClass.innerHTML = empty;
                listReport.innerHTML = empty;
                return;
            }

            myClasses.forEach(c => {
                const isDark = isDarkColor(c.room_color);
                const textColor = isDark ? 'text-white' : 'text-white';
                const subColor = isDark ? 'text-black' : 'text-black';
                const badgeBg = isDark ? 'bg-black' : 'bg-black';

                // Card Template
                const cardHTML = `
                    <div style="background-color: ${c.room_color};" class="relative p-5 rounded-2xl shadow-lg mb-4 cursor-pointer transform transition hover:scale-[1.02]">
                        <div class="flex justify-between items-start">
                            <div class="w-full">
                                <span class="text-xs font-bold px-2 py-0.5 rounded ${badgeBg} ${textColor} mb-2 inline-block">รหัสวิชา: ${c.course_code}</span>
                                <h3 class="font-bold text-xl ${textColor} leading-tight mb-1 truncate">${c.subject_name}</h3>
                                <p class="text-sm ${subColor} mt-2">รหัสเข้าห้องเรียน: <span class="font-mono font-bold">${c.class_code}</span></p>
                            </div>
                            <div class="absolute top-4 right-4 text-2xl opacity-50">
                                กดเพื่อเริ่ม
                            </div>
                        </div>
                    </div>`;

                // 1. เพิ่มใน Tab ห้องเรียน (กดแล้วไปหน้า Edit/Start)
                const itemClass = document.createElement('div');
                itemClass.innerHTML = cardHTML;
                itemClass.onclick = () => window.location.href = `./edit_class.php?class_id=${c.id}`;
                listClass.appendChild(itemClass);

                // 2. เพิ่มใน Tab รายงาน (กดแล้วไปหน้าเลือก Session)
                const itemReport = document.createElement('div');
                itemReport.innerHTML = cardHTML;
                itemReport.onclick = () => window.location.href = `./report_sessions.php?class_id=${c.id}`;
                listReport.appendChild(itemReport);
            });
        }

        // --- Functions เดิม (Create, Color, Helper) ---
        function openCreateModal() { document.getElementById('createModal').classList.remove('hidden'); }
        async function createClass() {
            const courseCode = document.getElementById('courseCode').value;
            const name = document.getElementById('subjectName').value;
            const color = document.getElementById('roomColor').value;
            if (!name || !courseCode) return alert("กรุณากรอกข้อมูลให้ครบ");
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', { action: 'create_class', line_id: pf.userId, course_code: courseCode, name: name, color: color });
                if (res.data.status === 'success') { alert("✅ สร้างห้องเรียนสำเร็จ!"); document.getElementById('createModal').classList.add('hidden'); loadClasses(); }
                else alert(res.data.message);
            } catch (err) { alert("Server Error"); }
        }
        function renderColorSwatches() {
            const c = document.getElementById('colorSelection'); c.innerHTML = '';
            COLORS.forEach(hex => {
                const s = document.createElement('div'); s.className = `color-swatch w-8 h-8 rounded-full cursor-pointer transition`; s.style.backgroundColor = hex;
                s.onclick = () => { document.getElementById('roomColor').value=hex; document.querySelectorAll('.color-swatch').forEach(e=>e.classList.remove('selected')); s.classList.add('selected'); };
                c.appendChild(s);
            });
        }
        function isDarkColor(hex) { const r=parseInt(hex.substr(1,2),16),g=parseInt(hex.substr(3,2),16),b=parseInt(hex.substr(5,2),16); return (0.2126*r+0.7152*g+0.0722*b)<128; }
    </script>
</body>
</html>