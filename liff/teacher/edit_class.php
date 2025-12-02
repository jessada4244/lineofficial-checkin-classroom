<?php
require_once '../../config/security.php';
checkLogin('teacher'); 
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดห้องเรียน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .color-swatch.selected { border: 3px solid #000; box-shadow: 0 0 0 2px #fff; transform: scale(1.1); }
        .view-section { display: none; }
        .view-section.active { display: block; }
        #map { z-index: 0; }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div id="loading" class="text-center py-20 text-gray-500">กำลังโหลด...</div>

    <div id="view-dashboard" class="view-section max-w-md mx-auto min-h-screen bg-gray-100 relative pb-24">
        
        <div class="bg-blue-600 px-5 pt-6 pb-8 rounded-b-[2rem] text-white shadow-lg mb-6">
            <div class="flex justify-between items-center mb-4">
                <button onclick="window.history.back()" class="bg-white/20 p-2 rounded-full hover:bg-white/30 backdrop-blur-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <div class="text-center">
                    <h1 id="dash-className" class="text-xl font-bold">Loading...</h1>
                    <p id="dash-courseCode" class="text-blue-200 text-sm font-medium">CODE</p>
                </div>
                <button onclick="switchView('settings')" class="bg-white/20 p-2 rounded-full hover:bg-white/30 backdrop-blur-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </button>
            </div>
            <div class="flex justify-between items-center bg-white/10 p-4 rounded-2xl backdrop-blur-md border border-white/10">
                <div>
                    <p class="text-xs text-blue-100 mb-1">จำนวนนิสิต</p>
                    <p id="dash-studentCount" class="text-2xl font-bold">0</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-blue-100 mb-1">รหัสเข้าห้อง</p>
                    <div onclick="copyCode()" class="bg-white text-blue-800 px-3 py-1 rounded-lg font-mono font-bold text-lg cursor-pointer active:scale-95 transition shadow-sm">
                        <span id="dash-classCode">...</span>
                        <span class="text-[10px] text-gray-400 ml-1">📋</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-5 space-y-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-gray-800 font-bold mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                    ตั้งค่าพื้นฐาน
                </h2>
                <div class="mb-4">
                    <label class="text-xs text-gray-500 font-bold mb-1 block">⏰ สายหลังเวลา</label>
                    <div class="flex gap-2">
                        <input type="time" id="limitTime" class="flex-1 bg-gray-50 border border-gray-200 rounded-xl p-2 text-lg font-medium outline-none focus:ring-2 focus:ring-blue-500">
                        <button onclick="saveCheckinConfig()" class="bg-gray-800 text-white px-4 rounded-xl text-sm font-bold shadow hover:bg-black">บันทึก</button>
                    </div>
                </div>
                
                <div class="relative h-24 rounded-xl overflow-hidden border border-gray-200 mb-2">
                     <div id="map" class="w-full h-full z-0"></div>
                     <div class="absolute inset-0 bg-black/10 flex items-center justify-center pointer-events-none">
                         <span class="text-[10px] bg-white/80 px-2 py-1 rounded text-gray-600">พิกัดปัจจุบัน</span>
                     </div>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-[10px] text-gray-400">Lat: <span id="disp_lat">-</span>, Lng: <span id="disp_lng">-</span></p>
                    <button onclick="getUserLocation()" class="text-[10px] text-blue-600 font-bold hover:underline">📍 อัปเดตพิกัด</button>
                </div>
            </div>

            <button onclick="goToGenQR()" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-4 rounded-xl shadow-lg font-bold text-lg flex items-center justify-center gap-2 transform active:scale-95 transition mt-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                เริ่มเช็คชื่อ / สร้าง QR
            </button>

        </div>
    </div>

    <div id="view-settings" class="view-section max-w-md mx-auto min-h-screen bg-gray-50 pb-20 hidden">
        <div class="bg-white p-4 shadow-sm flex items-center gap-3 sticky top-0 z-50">
            <button onclick="switchView('dashboard')" class="text-gray-500 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <h1 class="text-lg font-bold">แก้ไขข้อมูลวิชา</h1>
        </div>
        <div class="p-5 space-y-6">
            <div class="bg-white p-5 rounded-xl shadow-sm">
                <h2 class="text-xs font-bold text-gray-400 mb-4 uppercase tracking-wider">ข้อมูลวิชา</h2>
                <div class="mb-4">
                    <label class="text-xs text-gray-500 mb-1 block">รหัสวิชา</label>
                    <input type="text" id="edit-courseCode" class="w-full border-b border-gray-200 py-2 outline-none font-medium">
                </div>
                <div class="mb-4">
                    <label class="text-xs text-gray-500 mb-1 block">ชื่อวิชา</label>
                    <input type="text" id="edit-subjectName" class="w-full border-b border-gray-200 py-2 outline-none text-lg font-medium">
                </div>
                <div class="mb-2">
                    <label class="text-xs text-gray-500 block mb-2">สี Card</label>
                    <div id="colorSelection" class="flex gap-2 justify-between"></div>
                    <input type="hidden" id="edit-roomColor">
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm mt-4">
                <h2 class="text-xs font-bold text-gray-400 mb-4 uppercase tracking-wider">ลิงก์ห้องเรียน (ถาวร)</h2>
                <div class="mb-3">
                    <label class="text-xs text-blue-600 font-bold block mb-1">Zoom Link</label>
                    <input type="text" id="edit-zoomLink" placeholder="https://zoom.us/j/..." class="w-full border p-2 rounded text-sm bg-blue-50">
                </div>
                <div>
                    <label class="text-xs text-indigo-600 font-bold block mb-1">MS Teams Link</label>
                    <input type="text" id="edit-teamsLink" placeholder="https://teams.microsoft.com/..." class="w-full border p-2 rounded text-sm bg-indigo-50">
                </div>
                <p class="text-[10px] text-gray-400 mt-2">* บันทึกไว้เพื่อใช้งานตอนกดเริ่มคลาส</p>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm">
                <h2 class="text-xs font-bold text-gray-400 mb-4 uppercase tracking-wider">รายชื่อนิสิต</h2>
                <div class="flex gap-2 mb-4">
                    <input type="text" id="add-studentCode" placeholder="รหัสนิสิต" class="w-full bg-gray-50 border border-gray-200 p-2.5 rounded-lg text-sm">
                    <button onclick="addStudent()" class="bg-green-500 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-sm">+ เพิ่ม</button>
                </div>
                <div id="studentList" class="space-y-2 max-h-64 overflow-y-auto pr-1"></div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-4">
                <button onclick="switchView('dashboard')" class="bg-gray-200 text-gray-600 py-3 rounded-xl font-bold">ยกเลิก</button>
                <button onclick="saveGeneralSettings()" class="bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg">บันทึก</button>
            </div>
            
             <div class="pt-6 mt-4">
                <button onclick="deleteClass()" class="w-full text-red-500 text-sm underline">ลบห้องเรียนนี้</button>
            </div>
        </div>
    </div>

    <input type="hidden" id="current_lat"><input type="hidden" id="current_lng">

    <script>
        const LIFF_ID = "2008573640-qQxJWXLz"; // <-- ตรวจสอบ LIFF ID ของคุณ
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];
        let map, marker, classData = {};

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบ Class ID");
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                renderColorSwatches();
                loadClassData();
            } catch (err) {
                alert("LIFF Init Error: " + err.message);
            }
        }
        main();

        function switchView(viewName) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active', 'block'));
            document.querySelectorAll('.view-section').forEach(el => el.classList.add('hidden'));
            const target = document.getElementById(`view-${viewName}`);
            target.classList.remove('hidden'); target.classList.add('active', 'block');
            if(viewName === 'dashboard' && map) setTimeout(() => map.invalidateSize(), 200);
        }

        function goToGenQR() {
            window.location.href = `./gen_qr.php?class_id=${CLASS_ID}`;
        }

        // --- Map & Location ---
        function initMap(lat, lng) {
            const startLat = lat || 13.7563;
            const startLng = lng || 100.5018;
            if (map) {
                marker.setLatLng([startLat, startLng]);
                map.setView([startLat, startLng], 15);
            } else {
                map = L.map('map', {zoomControl:false}).setView([startLat, startLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(map);
                marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
                marker.on('dragend', function(e) {
                    const pos = marker.getLatLng();
                    updateLatLngInputs(pos.lat, pos.lng);
                });
            }
            updateLatLngInputs(startLat, startLng);
        }
        function updateLatLngInputs(lat, lng) {
            document.getElementById('current_lat').value = lat;
            document.getElementById('current_lng').value = lng;
            document.getElementById('disp_lat').innerText = lat.toFixed(4);
            document.getElementById('disp_lng').innerText = lng.toFixed(4);
        }
        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    initMap(pos.coords.latitude, pos.coords.longitude);
                    saveCheckinConfig(true); 
                });
            }
        }

        // --- Load Data (Updated with Error Handling) ---
        async function loadClassData() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_class_details',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });
                
                if (res.data.status === 'success') {
                    classData = res.data.class;
                    renderDashboard();
                    renderSettings();
                    document.getElementById('loading').classList.add('hidden');
                    switchView('dashboard');
                } else {
                    alert("❌ โหลดข้อมูลไม่สำเร็จ: " + res.data.message);
                }
            } catch (err) {
                console.error("Load Class Error:", err);
                alert("⚠️ System Error: " + err.message + "\n(กรุณาเช็ค Console)");
                document.getElementById('loading').innerText = "โหลดไม่สำเร็จ";
            }
        }

        function renderDashboard() {
            document.getElementById('dash-className').innerText = classData.subject_name;
            document.getElementById('dash-courseCode').innerText = classData.course_code;
            document.getElementById('dash-studentCount').innerText = classData.members.length;
            document.getElementById('dash-classCode').innerText = classData.class_code;
            
            const time = classData.checkin_limit_time ? classData.checkin_limit_time.substring(0, 5) : '';
            document.getElementById('limitTime').value = time;
            const lat = classData.lat ? parseFloat(classData.lat) : null;
            const lng = classData.lng ? parseFloat(classData.lng) : null;
            initMap(lat, lng);
        }

        function renderSettings() {
            document.getElementById('edit-subjectName').value = classData.subject_name;
            document.getElementById('edit-courseCode').value = classData.course_code;
            // ใส่ข้อมูล Link ลงในช่อง Input (ถ้าไม่มีช่องนี้ใน HTML จะเกิด Error)
            document.getElementById('edit-zoomLink').value = classData.zoom_link || '';
            document.getElementById('edit-teamsLink').value = classData.teams_link || '';
            selectColor(classData.room_color || COLORS[0]);
            renderMemberList();
        }

        function renderMemberList() {
    const list = document.getElementById('studentList');
    list.innerHTML = '';
    classData.members.forEach(m => {
        // แก้ตรงนี้: ใช้ m.edu_id แทน m.student_id
        list.innerHTML += `<div class="flex justify-between items-center bg-gray-50 p-2 rounded mb-1 border"><span class="text-sm">${m.name} (${m.edu_id})</span><button onclick="removeStudent('${m.edu_id}', ${m.id})" class="text-red-500 text-xs">ลบ</button></div>`;
    });
}

        // --- Save Functions ---
        async function saveCheckinConfig(silent = false) {
            const time = document.getElementById('limitTime').value;
            const lat = document.getElementById('current_lat').value;
            const lng = document.getElementById('current_lng').value;
            if(!time) return alert("กรุณากำหนดเวลาสาย");
            if(!lat) return alert("กรุณาปักหมุดพิกัด");
            await updateAPI({ time: time, lat: lat, lng: lng });
            if(!silent) alert("✅ บันทึกการตั้งค่าเรียบร้อย");
        }
        async function saveGeneralSettings() {
            await updateAPI({ 
                name: document.getElementById('edit-subjectName').value,
                course_code: document.getElementById('edit-courseCode').value,
                color: document.getElementById('edit-roomColor').value,
                zoom_link: document.getElementById('edit-zoomLink').value,
                teams_link: document.getElementById('edit-teamsLink').value
            });
            alert("✅ บันทึกข้อมูลวิชาแล้ว");
            loadClassData();
        }
        async function updateAPI(dataToUpdate) {
            await axios.post('../../api/teacher_api.php', {
                action: 'update_class',
                line_id: (await liff.getProfile()).userId,
                class_id: CLASS_ID,
                ...dataToUpdate 
            });
        }
        
        // --- Helper ---
        function copyCode() { navigator.clipboard.writeText(document.getElementById('dash-classCode').innerText); alert("คัดลอกรหัสเข้าห้องแล้ว"); }
        function renderColorSwatches() {
            const container = document.getElementById('colorSelection'); container.innerHTML = '';
            COLORS.forEach(hex => {
                const swatch = document.createElement('div'); swatch.className = `color-swatch w-8 h-8 rounded-full cursor-pointer transition duration-150`; swatch.style.backgroundColor = hex; swatch.onclick = () => selectColor(hex); container.appendChild(swatch);
            });
        }
        function selectColor(hex) {
            document.getElementById('edit-roomColor').value = hex;
            document.querySelectorAll('.color-swatch').forEach(swatch => {
                swatch.classList.remove('selected');
                if (swatch.style.backgroundColor.includes(hexToRgb(hex))) swatch.classList.add('selected');
            });
        }
        function hexToRgb(hex) {
            const bigint = parseInt(hex.slice(1), 16);
            return [(bigint >> 16) & 255, (bigint >> 8) & 255, bigint & 255].join(", ");
        }
        async function deleteClass() {
             if(confirm('ยืนยันลบห้องเรียน?')) {
                 const profile = await liff.getProfile();
                 await axios.post('../../api/teacher_api.php', { action: 'delete_class', line_id: profile.userId, class_id: CLASS_ID });
                 window.location.href='./manage_class.php';
             }
        }
        async function addStudent() {
            const code = document.getElementById('add-studentCode').value;
            if(!code) return;
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'add_member', line_id: profile.userId, class_id: CLASS_ID, student_code: code
                });
                if(res.data.status === 'success') { document.getElementById('add-studentCode').value = ''; loadClassData(); }
                else { alert(res.data.message); }
            } catch(e) {}
        }
        async function removeStudent(code, id) {
            if(!confirm(`ลบนิสิตรหัส ${code}?`)) return;
            try {
                const profile = await liff.getProfile();
                await axios.post('../../api/teacher_api.php', {
                    action: 'remove_member', line_id: profile.userId, class_id: CLASS_ID, student_id_to_remove: id
                });
                loadClassData();
            } catch(e) {}
        }
    </script>
</body>
</html>