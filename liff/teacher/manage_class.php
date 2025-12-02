<?php
require_once '../../config/security.php';
checkLogin('teacher');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องเรียน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .color-swatch.selected { border: 3px solid #000; transform: scale(1.1); }
    </style>
</head>
<body class="bg-gray-100 p-4 min-h-screen">

    <div class="max-w-md mx-auto pb-20">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">รายวิชาของฉัน</h1>
            
            <button onclick="window.location.href='../settings.php'" class="bg-white p-2 rounded-full shadow-sm text-gray-600 hover:text-blue-600 transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
</button>
        </div>

        <button onclick="openCreateModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-xl shadow-lg flex justify-center items-center font-bold mb-6 transition transform active:scale-95">
            <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            สร้างห้องเรียนใหม่
        </button>

        <div id="classList" class="space-y-4">
            <div class="text-center mt-10 text-gray-400">กำลังโหลดข้อมูล...</div>
        </div>
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
        const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];

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

        // --- Functions เดิม ---
        async function loadClasses() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', { action: 'get_classes', line_id: profile.userId });
                const list = document.getElementById('classList');
                list.innerHTML = '';
                if (res.data.classes.length === 0) { list.innerHTML = `<div class="text-center py-10 bg-white rounded-xl shadow-sm text-gray-400">ยังไม่มีรายวิชา</div>`; return; }
                res.data.classes.forEach(c => {
                    const isDark = isDarkColor(c.room_color);
                    const textColor = isDark ? 'text-white' : 'text-gray-800';
                    const subColor = isDark ? 'text-white/80' : 'text-gray-600';
                    const badgeBg = isDark ? 'bg-white/20' : 'bg-gray-200';
                    list.innerHTML += `
                        <div style="background-color: ${c.room_color};" class="relative p-5 rounded-2xl shadow-lg mb-4 cursor-pointer" onclick="goToEditClass(${c.id})">
                            <div class="flex justify-between items-start">
                                <div class="w-2/3">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded ${badgeBg} ${textColor} mb-2 inline-block">${c.course_code}</span>
                                    <h3 class="font-bold text-xl ${textColor} leading-tight mb-1 truncate">${c.subject_name}</h3>
                                    <p class="text-sm ${subColor} mt-2">🔑 Code: <span class="font-mono font-bold">${c.class_code}</span></p>
                                </div>
                                <div class="absolute top-4 right-4 flex flex-col gap-2">
                                    <button onclick="event.stopPropagation(); goToEditClass(${c.id})" class="w-10 h-10 bg-white/20 rounded-full ${textColor} flex items-center justify-center">⚙️</button>
                                    <button onclick="event.stopPropagation(); goToReport(${c.id})" class="w-10 h-10 bg-white/20 rounded-full ${textColor} flex items-center justify-center">📊</button>
                                </div>
                            </div>
                        </div>`;
                });
            } catch (err) { console.error(err); }
        }

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
        function goToEditClass(id) { window.location.href = './edit_class.php?class_id=' + id; }
        function goToReport(id) { window.location.href = './checkin_report.php?class_id=' + id; }
    </script>
  
</body>
</html>