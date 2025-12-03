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
    <title>ระบบจัดการการสอน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .nav-item.active { color: #2563EB; font-weight: bold; }
        .nav-item.active svg { stroke: #2563EB; }
        .pb-safe { padding-bottom: 80px; } 
    </style>
    <script>
        window.onpageshow = function(event) {
            // ถ้า Browser บอกว่าหน้านี้ถูกโหลดมาจาก Cache (ย้อนกลับมา) ให้สั่ง Reload ใหม่ทันที
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</head>
<body class="bg-gray-50 min-h-screen relative">

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
            <button onclick="openCreateModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-2xl shadow-lg flex justify-center items-center font-bold mb-6 transition transform active:scale-95 border border-blue-500">
                <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                สร้างห้องเรียนใหม่
            </button>
            <div class="flex items-center gap-2 mb-4">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">รายการวิชาทั้งหมด</span>
                <div class="h-px bg-gray-200 flex-1"></div>
            </div>
            <div id="classListContainer" class="space-y-4">
                <div class="text-center mt-10 text-gray-400 animate-pulse">กำลังโหลด...</div>
            </div>
        </div>

        <div id="view-report" class="hidden">
            <div class="bg-yellow-50 border border-yellow-100 p-4 rounded-xl mb-6 flex gap-3 items-center">
                <div class="text-xl bg-yellow-100 p-2 rounded-full">📊</div>
                <div>
                    <h3 class="font-bold text-yellow-800 text-sm">เลือกวิชาเพื่อดูรายงาน</h3>
                    <p class="text-xs text-yellow-600">กดที่รายวิชาเพื่อดูประวัติการเช็คชื่อย้อนหลัง</p>
                </div>
            </div>
            <div id="reportListContainer" class="space-y-4">
                </div>
        </div>

    </div>

    <div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-100 flex justify-around py-3 pb-safe-area z-30 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]">
        <button onclick="switchTab('class')" id="nav-class" class="nav-item active flex flex-col items-center text-gray-400 w-1/2 transition">
            <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
            <span class="text-[10px]">ห้องเรียน</span>
        </button>
        <button onclick="switchTab('report')" id="nav-report" class="nav-item flex flex-col items-center text-gray-400 w-1/2 transition">
            <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="text-[10px]">รายงานผล</span>
        </button>
    </div>

    <div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-100 transition-transform">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h2 class="text-lg font-bold text-gray-800">🏫 สร้างห้องเรียนใหม่</h2>
                <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="mb-4">
                <label class="text-xs text-gray-500 font-bold ml-1 mb-1 block">รหัสวิชา</label>
                <input type="text" id="courseCode" placeholder="เช่น CP101" class="w-full border border-gray-200 bg-gray-50 p-3 rounded-xl mb-3 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium">
                
                <label class="text-xs text-gray-500 font-bold ml-1 mb-1 block">ชื่อวิชา</label>
                <input type="text" id="subjectName" placeholder="เช่น Computer Programming" class="w-full border border-gray-200 bg-gray-50 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium">
            </div>
            <div class="mb-6">
                <label class="text-xs text-gray-500 block mb-2 font-bold ml-1">เลือกสีประจำวิชา</label>
                <div id="colorSelection" class="flex gap-2 justify-between p-3 bg-gray-50 rounded-xl border border-gray-100"></div>
                <input type="hidden" id="roomColor" value="#3B82F6">
            </div>
            <div class="flex gap-3 pt-2">
                <button onclick="document.getElementById('createModal').classList.add('hidden')" class="flex-1 py-3 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">ยกเลิก</button>
                <button onclick="createClass()" class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow transition">สร้างห้องเรียน</button>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];
        let myClasses = [];

        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                else {
                    loadClasses();
                    renderColorSwatches();
                }
            } catch (err) { alert("LIFF Init Failed: " + err.message); }
        }
        main();

        function switchTab(tab) {
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

        function isDarkColor(hex) {
            const r = parseInt(hex.substr(1,2),16);
            const g = parseInt(hex.substr(3,2),16);
            const b = parseInt(hex.substr(5,2),16);
            return (0.2126*r + 0.7152*g + 0.0722*b) < 128;
        }

        function renderClassList() {
            const listClass = document.getElementById('classListContainer');
            const listReport = document.getElementById('reportListContainer');
            
            listClass.innerHTML = '';
            listReport.innerHTML = '';

            if (myClasses.length === 0) {
                const empty = `
                    <div class="text-center py-12 bg-white rounded-2xl shadow-sm border border-dashed border-gray-300">
                        <span class="text-4xl">📂</span>
                        <p class="text-gray-400 mt-2 text-sm">ยังไม่มีรายวิชา</p>
                    </div>`;
                listClass.innerHTML = empty;
                listReport.innerHTML = empty;
                return;
            }

            myClasses.forEach(c => {
                const isDark = isDarkColor(c.room_color);
                // Icon สีขาวถ้าพื้นหลังเข้ม
                const iconColor = isDark ? 'text-white opacity-80' : 'text-gray-600 opacity-60';
                
                // *** ปรับปรุง: ตัวหนังสือรหัสวิชาเป็นสีดำ (text-gray-800) ***
                const cardHTML = `
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4 overflow-hidden cursor-pointer hover:shadow-md transition transform duration-200 active:scale-95 group">
                        
                        <div class="px-5 py-3 flex justify-between items-center" style="background-color: ${c.room_color};">
                             <span class="text-xs font-bold px-3 py-1 rounded-lg bg-white shadow-sm text-gray-800">
                                ${c.course_code}
                            </span>
                             <div class="${iconColor} group-hover:opacity-100 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </div>
                        </div>

                        <div class="p-5 pt-4">
                            <h3 class="text-lg font-bold text-gray-800 mb-1 leading-snug line-clamp-2 group-hover:text-blue-600 transition">
                               วิชา : ${c.subject_name}
                            </h3>
                            
                            <div class="mt-4 pt-3 border-t border-gray-50 flex justify-between items-end">
                                <div>
                                    <p class="text-[10px] text-gray-400 font-bold mb-0.5 uppercase tracking-wide">รหัสเข้าร่วมห้อง</p>
                                    <div class="flex items-center gap-2">
                                        <p class="font-mono text-xl font-bold text-gray-700 tracking-widest leading-none">${c.class_code}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-lg">
                                    <span class="w-2 h-2 rounded-full" style="background-color:${c.room_color}"></span>
                                    <span class="font-medium">จัดการ</span>
                                </div>
                            </div>
                        </div>
                    </div>`;

                const itemClass = document.createElement('div');
                itemClass.innerHTML = cardHTML;
                itemClass.onclick = () => window.location.href = `./edit_class.php?class_id=${c.id}`;
                listClass.appendChild(itemClass);

                const itemReport = document.createElement('div');
                itemReport.innerHTML = cardHTML;
                itemReport.onclick = () => window.location.href = `./report_sessions.php?class_id=${c.id}`;
                listReport.appendChild(itemReport);
            });
        }

        function openCreateModal() { document.getElementById('createModal').classList.remove('hidden'); }
        
        async function createClass() {
            const courseCode = document.getElementById('courseCode').value;
            const name = document.getElementById('subjectName').value;
            const color = document.getElementById('roomColor').value;
            
            if (!name || !courseCode) return alert("กรุณากรอกข้อมูลให้ครบ");
            
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', { 
                    action: 'create_class', 
                    line_id: pf.userId, 
                    course_code: courseCode, 
                    name: name, 
                    color: color 
                });
                
                if (res.data.status === 'success') { 
                    alert("✅ สร้างห้องเรียนสำเร็จ!"); 
                    document.getElementById('createModal').classList.add('hidden'); 
                    document.getElementById('courseCode').value = '';
                    document.getElementById('subjectName').value = '';
                    loadClasses(); 
                } else {
                    alert(res.data.message);
                }
            } catch (err) { alert("Server Error"); }
        }

        function renderColorSwatches() {
            const c = document.getElementById('colorSelection'); 
            c.innerHTML = '';
            COLORS.forEach(hex => {
                const s = document.createElement('div'); 
                s.className = `w-8 h-8 rounded-full cursor-pointer transition transform hover:scale-110 border-2 border-white ring-1 ring-gray-200 shadow-sm`; 
                s.style.backgroundColor = hex;
                s.onclick = () => { 
                    document.getElementById('roomColor').value = hex; 
                    Array.from(c.children).forEach(el => { el.style.transform = 'scale(1)'; el.classList.remove('ring-offset-2', 'ring-blue-500'); });
                    s.style.transform = 'scale(1.2)';
                    s.classList.add('ring-offset-2', 'ring-blue-500');
                };
                c.appendChild(s);
            });
            if(c.firstChild) c.firstChild.click();
        }
    </script>
</body>
</html>