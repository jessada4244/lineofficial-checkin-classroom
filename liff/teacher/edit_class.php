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

    <div id="view-dashboard" class="view-section max-w-md mx-auto min-h-screen bg-gray-100 relative pb-20">
        
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

        <div class="px-5 space-y-6">
            

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-gray-800 font-bold mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                    ตั้งค่าพิกัดและเวลา
                </h2>
                <div class="mb-4">
                    <label class="text-xs text-gray-500 font-bold mb-1 block">⏰ สายหลังเวลา</label>
                    <input type="time" id="limitTime" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-lg font-medium outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-xs text-gray-500 font-bold">📍 จุดเช็คชื่อ</label>
                        <button onclick="getUserLocation()" class="text-[10px] bg-blue-50 text-blue-600 px-2 py-1 rounded-md font-bold hover:bg-blue-100 transition">ดึงพิกัดปัจจุบัน</button>
                    </div>
                    <div class="rounded-xl overflow-hidden border border-gray-200 h-48 relative">
                        <div id="map" class="w-full h-full z-0"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 text-center mt-1">Lat: <span id="disp_lat">-</span>, Lng: <span id="disp_lng">-</span></p>
                </div>
                <button onclick="saveCheckinConfig()" class="w-full bg-gray-800 hover:bg-gray-900 text-white py-3 rounded-xl font-bold shadow-md transition">บันทึกการตั้งค่า</button>
            </div>
        </div>
        <br>
        <button onclick="startCheckin()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white p-5 rounded-2xl shadow-lg shadow-indigo-200 font-bold flex items-center justify-center gap-3 transform active:scale-95 transition">
                <span class="text-lg">เริ่มการเช็คชื่อ</span>
            </button>
    </div>


    <div id="view-settings" class="view-section max-w-md mx-auto min-h-screen bg-gray-50 pb-20 hidden">
        <div class="bg-white p-4 shadow-sm flex items-center gap-3 sticky top-0 z-50">
            <button onclick="switchView('dashboard')" class="text-gray-500 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <h1 class="text-lg font-bold">แก้ไขข้อมูลห้องเรียน</h1>
        </div>

        <div class="p-5 space-y-6">
            <div class="bg-white p-5 rounded-xl shadow-sm">
                <h2 class="text-xs font-bold text-gray-400 mb-4 uppercase tracking-wider">ข้อมูลวิชา</h2>
                <div class="mb-4">
                    <label class="text-xs text-gray-500 mb-1 block">รหัสวิชา</label>
                    <input type="text" id="edit-courseCode" class="w-full border-b border-gray-200 py-2 outline-none font-medium focus:border-blue-500 transition">
                </div>
                <div class="mb-4">
                    <label class="text-xs text-gray-500 mb-1 block">ชื่อวิชา</label>
                    <input type="text" id="edit-subjectName" class="w-full border-b border-gray-200 py-2 outline-none text-lg font-medium focus:border-blue-500 transition">
                </div>
                <div class="mb-2">
                    <label class="text-xs text-gray-500 block mb-2">สี Card</label>
                    <div id="colorSelection" class="flex gap-2 justify-between"></div>
                    <input type="hidden" id="edit-roomColor">
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm">
                <h2 class="text-xs font-bold text-gray-400 mb-4 uppercase tracking-wider">รายชื่อนิสิต</h2>
                <div class="flex gap-2 mb-4">
                    <input type="text" id="add-studentCode" placeholder="รหัสนิสิต" class="w-full bg-gray-50 border border-gray-200 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <button onclick="addStudent()" class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-sm transition">+ เพิ่ม</button>
                </div>
                <div id="studentList" class="space-y-2 max-h-64 overflow-y-auto pr-1"></div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-4">
                <button onclick="switchView('dashboard')" class="bg-gray-200 hover:bg-gray-300 text-gray-600 py-3 rounded-xl font-bold transition">ยกเลิก</button>
                <button onclick="saveGeneralSettings()" class="bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-lg transition">บันทึกข้อมูล</button>
            </div>

            <div class="pt-8 border-t border-gray-200 mt-8">
                <button onclick="deleteClass()" class="w-full bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 py-3 rounded-xl font-bold transition flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    ลบห้องเรียนนี้
                </button>
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

        // ======================= MAP & DATA LOADING =======================
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
            document.getElementById('disp_lat').innerText = lat.toFixed(5);
            document.getElementById('disp_lng').innerText = lng.toFixed(5);
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
            selectColor(classData.room_color || COLORS[0]);
            renderMemberList();
        }

        function renderMemberList() {
            const list = document.getElementById('studentList');
            list.innerHTML = '';
            if(classData.members.length === 0) list.innerHTML = '<p class="text-gray-400 text-center text-sm">ยังไม่มีนิสิต</p>';
            classData.members.forEach(m => {
                list.innerHTML += `
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <div><p class="font-bold text-gray-700 text-sm">${m.name}</p><p class="text-xs text-gray-400">${m.student_id}</p></div>
                        <button onclick="removeStudent('${m.student_id}', ${m.id})" class="text-red-500 hover:text-red-700 text-xs font-bold border border-red-100 bg-white px-3 py-1.5 rounded-md">ลบ</button>
                    </div>`;
            });
        }

        // ======================= SAVE & DELETE LOGIC =======================
        async function saveCheckinConfig() {
            const time = document.getElementById('limitTime').value;
            const lat = document.getElementById('current_lat').value;
            const lng = document.getElementById('current_lng').value;
            if(!time) return alert("กรุณากำหนดเวลาสาย");
            if(!lat) return alert("กรุณาปักหมุดพิกัด");
            await updateAPI({ time: time, lat: lat, lng: lng });
            alert("✅ บันทึกการตั้งค่าเช็คชื่อเรียบร้อย");
        }

        async function saveGeneralSettings() {
            await updateAPI({ 
                name: document.getElementById('edit-subjectName').value,
                course_code: document.getElementById('edit-courseCode').value,
                color: document.getElementById('edit-roomColor').value
            });
            alert("✅ บันทึกข้อมูลวิชาแล้ว");
            loadClassData();
        }

        // *** ฟังก์ชันลบห้องเรียน ***
        async function deleteClass() {
            if (!confirm("⚠️ คำเตือน: คุณต้องการลบห้องเรียนนี้ใช่หรือไม่?\n\nข้อมูลการเช็คชื่อและรายชื่อนิสิตทั้งหมดจะหายไปถาวร!")) return;
            
            // Double Check (กันพลาด)
            const confirmName = prompt(`พิมพ์ชื่อวิชา "${classData.subject_name}" เพื่อยืนยันการลบ:`);
            if (confirmName !== classData.subject_name) return alert("ชื่อวิชาไม่ถูกต้อง ยกเลิกการลบ");

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'delete_class',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });

                if (res.data.status === 'success') {
                    alert("🗑️ ลบห้องเรียนเรียบร้อยแล้ว");
                    // กลับไปหน้ารายการ
                    window.location.href = './manage_class.php';
                } else {
                    alert("❌ ลบไม่สำเร็จ: " + res.data.message);
                }
            } catch (err) {
                alert("Server Error");
            }
        }

        async function updateAPI(dataToUpdate) {
            const payload = {
                action: 'update_class',
                line_id: (await liff.getProfile()).userId,
                class_id: CLASS_ID,
                ...dataToUpdate 
            };
            const res = await axios.post('../../api/teacher_api.php', payload);
            if(res.data.status === 'success') classData = { ...classData, ...dataToUpdate };
            else alert("บันทึกผิดพลาด: " + res.data.message);
        }

        // ======================= HELPERS =======================
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
        function startCheckin() { alert("เริ่มเช็คชื่อ..."); }
    </script>
</body>
</html>