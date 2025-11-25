<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องเรียน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50 p-4">

    <div class="max-w-md mx-auto">
        <h1 class="text-xl font-bold mb-4">รายวิชาของฉัน</h1>
        
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                class="w-full bg-blue-600 text-white p-3 rounded-lg mb-6 shadow flex justify-center items-center">
            + สร้างห้องเรียนใหม่
        </button>

        <div id="classList" class="space-y-3">
            <p class="text-center text-gray-500">กำลังโหลด...</p>
        </div>
    </div>

    <div id="createModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm">
            <h2 class="text-lg font-bold mb-4">สร้างห้องเรียนใหม่</h2>
            
            <input type="text" id="subjectName" placeholder="ชื่อวิชา (เช่น CS101)" class="w-full border p-2 mb-3 rounded">
            <input type="time" id="limitTime" class="w-full border p-2 mb-3 rounded" value="09:00">
            <p class="text-xs text-gray-500 mb-3">*กำหนดเวลาสาย (เช่น 09:00)</p>

            <div class="bg-blue-50 p-3 rounded mb-4">
                <p class="text-sm font-semibold mb-1">พิกัดห้องเรียน:</p>
                <div class="flex items-center gap-2">
                    <input type="text" id="lat" placeholder="Lat" class="w-1/2 text-xs border p-1 rounded" readonly>
                    <input type="text" id="lng" placeholder="Lng" class="w-1/2 text-xs border p-1 rounded" readonly>
                </div>
                <button onclick="getLocation()" class="mt-2 w-full bg-blue-500 text-white text-sm py-1 rounded">
                    📍 ใช้ตำแหน่งปัจจุบันของฉัน
                </button>
            </div>

            <div class="flex justify-end gap-2">
                <button onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 text-gray-600">ยกเลิก</button>
                <button onclick="createClass()" class="px-4 py-2 bg-green-600 text-white rounded">บันทึก</button>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "ใส่_TEACHER_LIFF_ID_ของคุณ"; // LIFF ID สำหรับ Teacher App

        async function main() {
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            
            // โหลดรายการห้องเรียน
            loadClasses();
        }
        main();

        // 1. ฟังก์ชันดึง GPS (HTML5 Geolocation)
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('lat').value = position.coords.latitude;
                    document.getElementById('lng').value = position.coords.longitude;
                    alert("ดึงพิกัดสำเร็จ!");
                }, function(error) {
                    alert("Error: " + error.message);
                });
            } else {
                alert("Browser ไม่รองรับ Geolocation");
            }
        }

        // 2. ฟังก์ชันโหลดรายการวิชา
        async function loadClasses() {
            const profile = await liff.getProfile();
            const res = await axios.post('../../api/teacher_api.php', {
                action: 'get_classes',
                line_id: profile.userId
            });
            
            const list = document.getElementById('classList');
            list.innerHTML = '';

            if(res.data.classes.length === 0) {
                list.innerHTML = '<p class="text-center text-gray-400">ยังไม่มีวิชา</p>';
                return;
            }

            res.data.classes.forEach(c => {
                list.innerHTML += `
                    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold text-lg">${c.subject_name}</h3>
                                <p class="text-sm text-gray-500">สายหลัง: ${c.checkin_limit_time}</p>
                            </div>
                            <button onclick="showQR(${c.id})" class="text-blue-600 text-sm border border-blue-600 px-2 py-1 rounded">QR Code</button>
                        </div>
                    </div>
                `;
            });
        }

        // 3. ฟังก์ชันสร้างวิชา
        async function createClass() {
            const name = document.getElementById('subjectName').value;
            const time = document.getElementById('limitTime').value;
            const lat = document.getElementById('lat').value;
            const lng = document.getElementById('lng').value;
            
            if(!name || !lat) return alert("กรุณากรอกชื่อวิชาและระบุพิกัด");

            const profile = await liff.getProfile();
            
            const res = await axios.post('../../api/teacher_api.php', {
                action: 'create_class',
                line_id: profile.userId,
                name: name,
                time: time,
                lat: lat,
                lng: lng
            });

            if(res.data.status === 'success') {
                alert("สร้างวิชาสำเร็จ!");
                document.getElementById('createModal').classList.add('hidden');
                loadClasses();
            } else {
                alert("Error: " + res.data.message);
            }
        }
        
        function showQR(id) {
            // เดี๋ยวมาทำฟังก์ชันสร้าง QR Code กันต่อ
            alert("Class ID: " + id);
        }
    </script>
</body>
</html>