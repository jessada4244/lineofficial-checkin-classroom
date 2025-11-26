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
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div id="loading" class="text-center py-20 text-gray-500">กำลังโหลด...</div>

    <div id="view-dashboard" class="view-section max-w-md mx-auto min-h-screen bg-white shadow-lg relative pb-20">
        
        <div class="bg-blue-600 px-4 py-5 rounded-b-3xl text-white shadow-md">
            
            <div class="flex justify-between items-center mb-4">
                <button onclick="window.history.back()" class="bg-white/20 p-2 rounded-full hover:bg-white/30 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                
                <div class="text-center">
                    <h1 id="dash-className" class="text-xl font-bold leading-tight">ชื่อวิชา</h1>
                    <p id="dash-courseCode" class="text-blue-200 text-sm">รหัสวิชา</p>
                </div>

                <button onclick="switchView('settings')" class="bg-white/20 p-2 rounded-full hover:bg-white/30 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>

            <div class="flex justify-between items-end bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/20">
                <div>
                    <p class="text-xs text-blue-200">จำนวนนิสิต</p>
                    <p id="dash-studentCount" class="text-2xl font-bold">0 <span class="text-sm font-normal">คน</span></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-blue-200">รหัสเข้าห้องเรียน</p>
                    <p id="dash-classCode" class="text-xl font-mono font-bold tracking-wider bg-white text-blue-800 px-2 rounded cursor-pointer" onclick="copyCode()">XXXXXX</p>
                </div>
            </div>
        </div>

        <div class="p-5 space-y-6">
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm">
                <label class="text-sm font-bold text-gray-600 mb-2 block">⏰ กำหนดเวลาสาย</label>
                <div class="flex items-center gap-2">
                    <input type="time" id="limitTime" class="w-full text-lg p-2 rounded-lg border outline-none">
                    <button onclick="saveTimeOnly()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold">บันทึก</button>
                </div>
            </div>

            <div class="bg-gray-50 p-1 rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex justify-between items-center p-3">
                    <label class="text-sm font-bold text-gray-600">📍 จุดเช็คชื่อ</label>
                    <button onclick="getUserLocation()" class="text-xs text-blue-600 font-bold border border-blue-600 px-2 py-1 rounded">พิกัดปัจจุบัน</button>
                </div>
                <div id="map" class="w-full h-56 z-0 rounded-b-lg"></div>
                <div class="p-3 text-center">
                    <button onclick="saveLocationOnly()" class="w-full bg-green-600 text-white py-2 rounded-lg font-bold">บันทึกพิกัด</button>
                </div>
            </div>

            <button onclick="startCheckin()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xl p-5 rounded-2xl shadow-xl font-bold flex items-center justify-center gap-2">
                เริ่มเช็คชื่อ
            </button>
        </div>
    </div>

    <div id="view-settings" class="view-section max-w-md mx-auto min-h-screen bg-gray-50 pb-20 hidden">
        <div class="bg-white p-4 shadow-sm flex items-center gap-3 sticky top-0 z-50">
            <button onclick="switchView('dashboard')" class="text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <h1 class="text-lg font-bold">ตั้งค่าห้องเรียน</h1>
        </div>

        <div class="p-5 space-y-6">
            <div class="bg-white p-5 rounded-xl shadow-sm">
                <h2 class="text-sm font-bold text-gray-500 mb-3 uppercase">แก้ไขข้อมูล</h2>
                
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="text-xs text-gray-400">รหัสวิชา</label>
                        <input type="text" id="edit-courseCode" class="w-full border-b py-1 outline-none font-medium">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">รหัสเข้าห้อง</label>
                        <input type="text" id="edit-classCode" class="w-full border-b py-1 outline-none font-medium text-blue-600">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-xs text-gray-400">ชื่อห้องเรียน</label>
                    <input type="text" id="edit-subjectName" class="w-full border-b py-1 outline-none text-lg font-medium">
                </div>

                <div class="mb-2">
                    <label class="text-xs text-gray-400 block mb-2">สี Card</label>
                    <div id="colorSelection" class="flex gap-2 justify-between"></div>
                    <input type="hidden" id="edit-roomColor">
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm">
                <h2 class="text-sm font-bold text-gray-500 mb-3 uppercase">รายชื่อนิสิต</h2>
                
                <div class="flex gap-2 mb-4">
                    <input type="text" id="add-studentCode" placeholder="รหัสนิสิต" class="w-full border p-2 rounded-lg text-sm bg-gray-50">
                    <button onclick="addStudent()" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-bold">+ เพิ่ม</button>
                </div>

                <div id="studentList" class="space-y-2 max-h-64 overflow-y-auto"></div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button onclick="switchView('dashboard')" class="bg-gray-200 text-gray-600 py-3 rounded-xl font-bold">ยกเลิก</button>
                <button onclick="saveGeneralSettings()" class="bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg">บันทึก</button>
            </div>
        </div>
    </div>

    <input type="hidden" id="current_lat"><input type="hidden" id="current_lng">

    <script>
        const LIFF_ID = "2008562649-bkoEQOMg"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];
        let map, marker, classData = {};

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบ Class ID");
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            renderColorSwatches();
            loadClassData();
        }
        main();

        function switchView(viewName) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active', 'block'));
            document.querySelectorAll('.view-section').forEach(el => el.classList.add('hidden'));
            const target = document.getElementById(`view-${viewName}`);
            target.classList.remove('hidden'); target.classList.add('active', 'block');
            if(viewName === 'dashboard' && map) setTimeout(() => map.invalidateSize(), 200);
        }

        function initMap(lat, lng) {
            const startLat = lat || 13.7563;
            const startLng = lng || 100.5018;
            if (map) {
                marker.setLatLng([startLat, startLng]);
                map.setView([startLat, startLng], 15);
            } else {
                map = L.map('map').setView([startLat, startLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
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
        }

        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => initMap(pos.coords.latitude, pos.coords.longitude));
            }
        }

        async function loadClassData() {
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
            }
        }

        function renderDashboard() {
            document.getElementById('dash-className').innerText = classData.subject_name;
            document.getElementById('dash-courseCode').innerText = classData.course_code; // แสดงรหัสวิชา
            document.getElementById('dash-studentCount').innerHTML = `${classData.members.length} <span class="text-sm font-normal">คน</span>`;
            document.getElementById('dash-classCode').innerText = classData.class_code; // แสดงรหัสเข้าห้อง
            document.getElementById('limitTime').value = classData.checkin_limit_time ? classData.checkin_limit_time.substring(0, 5) : '09:00';
            initMap(parseFloat(classData.lat), parseFloat(classData.lng));
        }

        function renderSettings() {
            document.getElementById('edit-subjectName').value = classData.subject_name;
            document.getElementById('edit-courseCode').value = classData.course_code; // Input แก้ไขรหัสวิชา
            document.getElementById('edit-classCode').value = classData.class_code;   // Input แก้ไขรหัสเข้าห้อง
            selectColor(classData.room_color || COLORS[0]);
            renderMemberList();
        }

        function renderMemberList() {
            const list = document.getElementById('studentList');
            list.innerHTML = '';
            classData.members.forEach(m => {
                list.innerHTML += `
                    <div class="flex justify-between items-center bg-gray-100 p-2 rounded text-sm">
                        <span class="text-gray-700">${m.name} (${m.student_id})</span>
                        <button onclick="removeStudent('${m.student_id}', ${m.id})" class="text-red-500 text-xs font-bold px-2">ลบ</button>
                    </div>
                `;
            });
        }

        async function saveGeneralSettings() {
            await updateAPI({ 
                name: document.getElementById('edit-subjectName').value,
                course_code: document.getElementById('edit-courseCode').value,
                class_code: document.getElementById('edit-classCode').value,
                color: document.getElementById('edit-roomColor').value
            });
            alert("✅ บันทึกแล้ว");
            loadClassData();
        }
        
        async function saveTimeOnly() { await updateAPI({ time: document.getElementById('limitTime').value }); alert("✅ บันทึกเวลาแล้ว"); }
        async function saveLocationOnly() { await updateAPI({ lat: document.getElementById('current_lat').value, lng: document.getElementById('current_lng').value }); alert("✅ บันทึกพิกัดแล้ว"); }

        async function updateAPI(dataToUpdate) {
            const profile = await liff.getProfile();
            
            // สร้าง Payload เฉพาะข้อมูลใหม่ที่ต้องการบันทึก + ตัวระบุตัวตน
            const payload = {
                action: 'update_class',
                line_id: profile.userId,
                class_id: CLASS_ID,
                ...dataToUpdate // ส่งไปเฉพาะ Time หรือ Lat/Lng ที่เปลี่ยน (ไม่เอา classData เก่าไปทับ)
            };
            
            await axios.post('../../api/teacher_api.php', payload);
            
            // อัปเดตตัวแปร local เพื่อให้หน้าจอยังแสดงค่าล่าสุดอยู่
            classData = { ...classData, ...dataToUpdate };
        }
        async function addStudent() {
            /* เหมือนเดิม */
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
            if(!confirm(`ลบนิสิต?`)) return;
            try {
                const profile = await liff.getProfile();
                await axios.post('../../api/teacher_api.php', {
                    action: 'remove_member', line_id: profile.userId, class_id: CLASS_ID, student_id_to_remove: id
                });
                loadClassData();
            } catch(e) {}
        }

        function copyCode() {
            navigator.clipboard.writeText(document.getElementById('dash-classCode').innerText);
            alert("คัดลอกรหัสเข้าห้องแล้ว");
        }

        // Color Logic
        function renderColorSwatches() {
            const container = document.getElementById('colorSelection'); container.innerHTML = '';
            COLORS.forEach(hex => {
                const swatch = document.createElement('div');
                swatch.className = `color-swatch w-8 h-8 rounded-full cursor-pointer transition duration-150`;
                swatch.style.backgroundColor = hex;
                swatch.onclick = () => selectColor(hex);
                container.appendChild(swatch);
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
        function startCheckin() { alert("เริ่มเช็คชื่อ..."); }
    </script>
</body>
</html>