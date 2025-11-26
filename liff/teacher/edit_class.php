<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขห้องเรียน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        .color-swatch.selected { border: 3px solid #000; box-shadow: 0 0 0 2px #fff; transform: scale(1.1); }
    </style>
</head>
<body class="bg-gray-100 p-4">

    <div id="loading" class="text-center py-10">กำลังโหลด...</div>

    <div id="content" class="max-w-md mx-auto hidden pb-20">
        <h1 class="text-2xl font-bold mb-4 text-gray-800" id="classTitle"></h1>
        <p class="text-sm text-gray-500 mb-6">จัดการรายละเอียดและรายชื่อนิสิต</p>

        <div class="bg-white p-5 rounded-xl shadow-lg mb-6">
            <h2 class="text-lg font-bold mb-3 text-blue-600">ข้อมูลทั่วไป</h2>

            <div class="mb-3">
                <label class="text-xs text-gray-500">ชื่อวิชา</label>
                <input type="text" id="subjectName" class="w-full border bg-gray-50 p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="mb-3">
                <label class="text-xs text-gray-500 block mb-1">สีประจำวิชา (เลือกได้ 7 สี)</label>
                <div id="colorSelection" class="flex gap-2 justify-between p-2 bg-gray-50 rounded-lg border"></div>
                <input type="hidden" id="roomColor">
            </div>
            <div class="mb-3">
                <label class="text-xs text-gray-500">จำนวนที่รับ (คน)</label>
                <input type="number" id="studentLimit" class="w-full border bg-gray-50 p-2 rounded-lg text-center">
            </div>
            <div class="mb-4">
                <label class="text-xs text-gray-500">เช็คสายหลังเวลา</label>
                <input type="time" id="limitTime" class="w-full border bg-gray-50 p-2 rounded-lg">
            </div>
            
            <button onclick="updateClassDetails()" class="w-full bg-green-500 hover:bg-green-600 text-white p-3 rounded-lg font-bold">บันทึกรายละเอียด</button>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-lg mb-6">
            <h2 class="text-lg font-bold mb-3 text-blue-600">📍 กำหนดจุดเช็คชื่อ</h2>
            <div class="bg-blue-50 p-3 rounded-lg mb-3 border border-blue-100">
                <p class="text-xs font-bold text-blue-800 mb-2">พิกัดปัจจุบันที่ตั้งไว้:</p>
                <div class="flex items-center gap-2 mb-2">
                    <input type="text" id="lat" placeholder="Latitude" class="w-1/2 text-xs bg-white border p-1 rounded text-center" readonly>
                    <input type="text" id="lng" placeholder="Longitude" class="w-1/2 text-xs bg-white border p-1 rounded text-center" readonly>
                </div>
                <button onclick="getLocation()" class="w-full bg-blue-500 hover:bg-blue-600 text-white text-xs py-2 rounded-lg transition">
                    ดึงตำแหน่งปัจจุบัน
                </button>
            </div>
            <button onclick="updateLocation()" class="w-full bg-green-500 hover:bg-green-600 text-white p-3 rounded-lg font-bold">บันทึกพิกัด</button>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-lg">
            <h2 class="text-lg font-bold mb-3 text-blue-600">👥 รายชื่อนิสิต (<span id="memberCount">0</span> คน)</h2>
            <div class="flex gap-2 mb-4">
                <input type="text" id="studentCode" placeholder="รหัสนิสิต (เช่น 6601001)" class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                <button onclick="addStudent()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-bold text-sm">เพิ่ม</button>
            </div>
            
            <div id="studentList" class="space-y-2">
                <p class="text-center text-gray-400">ยังไม่มีนิสิตในห้อง</p>
            </div>
        </div>
        
    </div>

    <script>
        const LIFF_ID = "2008562649-bkoEQOMg"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        let currentClassData = null; // เก็บข้อมูลห้องเรียนที่ดึงมา
        
        const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];

        // ********************** LIFF INIT & LOAD DATA **********************
        async function main() {
            if (!CLASS_ID) {
                document.getElementById('loading').innerHTML = '<div class="text-red-500">Error: ไม่พบ Class ID</div>';
                return;
            }
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            
            loadClassData();
            renderColorSwatches();
        }
        main();

        async function loadClassData() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_class_details',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });
                
                if (res.data.status === 'success') {
                    currentClassData = res.data.class;
                    fillForm(currentClassData);
                    renderStudentList(currentClassData.members);
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('content').classList.remove('hidden');
                } else {
                    alert("Error: " + res.data.message);
                }
            } catch (err) {
                alert("ติดต่อ Server ไม่ได้: " + err.message);
                console.error(err);
            }
        }
        
        // ********************** FORM & UI MANAGEMENT **********************
        function fillForm(data) {
            document.getElementById('classTitle').innerText = data.subject_name;
            document.getElementById('subjectName').value = data.subject_name;
            document.getElementById('studentLimit').value = data.student_limit;
            document.getElementById('limitTime').value = data.checkin_limit_time ? data.checkin_limit_time.substring(0, 5) : '08:00';
            document.getElementById('lat').value = data.lat || '';
            document.getElementById('lng').value = data.lng || '';

            selectColor(data.room_color || COLORS[0], false); // ตั้งค่าสีเริ่มต้น
        }
        
        function renderColorSwatches() {
            const container = document.getElementById('colorSelection');
            container.innerHTML = '';
            COLORS.forEach(hex => {
                const swatch = document.createElement('div');
                swatch.className = `color-swatch w-6 h-6 rounded-full cursor-pointer transition duration-150`;
                swatch.style.backgroundColor = hex;
                swatch.onclick = () => selectColor(hex);
                container.appendChild(swatch);
            });
        }
        
        function selectColor(hex, updateUI = true) {
            document.getElementById('roomColor').value = hex;
            if (updateUI) {
                document.querySelectorAll('.color-swatch').forEach(swatch => {
                    swatch.classList.remove('selected');
                    if (swatch.style.backgroundColor === hexToRgb(hex)) {
                        swatch.classList.add('selected');
                    }
                });
            }
        }

        // ********************** GPS LOGIC **********************
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('lat').value = position.coords.latitude;
                    document.getElementById('lng').value = position.coords.longitude;
                    alert("✅ ได้พิกัดแล้ว!");
                }, function(error) {
                    alert("❌ ไม่สามารถดึงพิกัดได้: " + error.message);
                });
            } else {
                alert("Browser นี้ไม่รองรับ Geolocation");
            }
        }

        async function updateLocation() {
             const lat = document.getElementById('lat').value;
             const lng = document.getElementById('lng').value;
             
             if (!lat || !lng) return alert("กรุณากด 'ดึงตำแหน่งปัจจุบัน' ก่อนบันทึก");

             // ใช้ฟังก์ชัน updateClassDetails เพื่อส่ง Lat/Lng ไป API
             updateClassDetails(true);
        }

        // ********************** UPDATE & SAVE LOGIC **********************
        async function updateClassDetails(isLocationUpdate = false) {
            const name = document.getElementById('subjectName').value;
            const color = document.getElementById('roomColor').value;
            const limit = document.getElementById('studentLimit').value;
            const time = document.getElementById('limitTime').value;
            const lat = document.getElementById('lat').value;
            const lng = document.getElementById('lng').value;

            if (!name) return alert("กรุณากรอกชื่อวิชา");

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'update_class',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    name: name,
                    color: color,
                    limit: limit,
                    time: time,
                    lat: lat,
                    lng: lng
                });

                if (res.data.status === 'success') {
                    alert("✅ บันทึกข้อมูลสำเร็จ!");
                    if (!isLocationUpdate) loadClassData(); // โหลดข้อมูลใหม่ถ้าไม่ใช่แค่การอัปเดตพิกัด
                } else {
                    alert("❌ บันทึกไม่ได้: " + res.data.message);
                }
            } catch (err) {
                alert("Server Error: " + err.message);
            }
        }

        // ********************** STUDENT LIST MANAGEMENT **********************
        function renderStudentList(members) {
            const list = document.getElementById('studentList');
            document.getElementById('memberCount').innerText = members.length;
            list.innerHTML = '';
            
            if (members.length === 0) {
                list.innerHTML = '<p class="text-center text-gray-400 py-4">ยังไม่มีนิสิตในห้องนี้</p>';
                return;
            }

            members.forEach(m => {
                list.innerHTML += `
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border">
                        <div>
                            <p class="font-medium text-gray-800">${m.name}</p>
                            <p class="text-xs text-gray-500">รหัส: ${m.student_id}</p>
                        </div>
                        <button onclick="removeStudent(${m.id}, '${m.name}')" class="text-red-500 hover:text-red-700 font-bold text-sm">
                            ลบ
                        </button>
                    </div>
                `;
            });
        }
        
        async function addStudent() {
            const studentCode = document.getElementById('studentCode').value.trim();
            if (!studentCode) return alert("กรุณาใส่รหัสนิสิต");
            
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'add_member',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    student_code: studentCode 
                });

                if (res.data.status === 'success') {
                    alert(`✅ เพิ่ม ${studentCode} สำเร็จ!`);
                    document.getElementById('studentCode').value = '';
                    loadClassData(); // โหลดข้อมูลใหม่เพื่อแสดงรายชื่อ
                } else {
                    alert("❌ เพิ่มไม่ได้: " + res.data.message);
                }
            } catch (err) {
                alert("Server Error: " + err.message);
            }
        }
        
        async function removeStudent(studentId, studentName) {
            if (!confirm(`คุณต้องการลบ ${studentName} ออกจากห้องเรียนนี้จริงหรือ?`)) return;

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'remove_member',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    student_id_to_remove: studentId // studentId ที่มาจากตาราง users.id
                });

                if (res.data.status === 'success') {
                    alert(`✅ ลบ ${studentName} สำเร็จ!`);
                    loadClassData(); 
                } else {
                    alert("❌ ลบไม่ได้: " + res.data.message);
                }
            } catch (err) {
                alert("Server Error: " + err.message);
            }
        }
        
        // ********************** UTILITY **********************
        function hexToRgb(hex) {
            const bigint = parseInt(hex.slice(1), 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgb(${r}, ${g}, ${b})`;
        }
    </script>
</body>
</html>