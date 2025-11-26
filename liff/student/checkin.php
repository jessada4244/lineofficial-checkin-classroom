<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เช็คชื่อเข้าเรียน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

    <div id="loading" class="fixed inset-0 bg-white z-50 flex flex-col items-center justify-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-500">กำลังเตรียมระบบเช็คชื่อ...</p>
    </div>

    <div id="content" class="w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden hidden">
        
        <div class="h-48 relative bg-gray-200">
            <div id="map" class="w-full h-full z-0"></div>
            <div class="absolute inset-0 pointer-events-none z-10 flex items-center justify-center">
                <div class="w-4 h-4 bg-blue-500 rounded-full ring-4 ring-white shadow-lg animate-pulse"></div>
            </div>
        </div>

        <div class="p-6 text-center">
            <h1 class="text-xl font-bold text-gray-800 mb-1" id="subjectName">กำลังระบุตำแหน่ง...</h1>
            <p class="text-sm text-gray-500 mb-6" id="statusText">กรุณาอยู่ในพื้นที่ห้องเรียน</p>

            <div id="resultBox" class="hidden mb-6 p-4 rounded-xl bg-gray-50 border border-gray-100">
                <div id="resultIcon" class="text-4xl mb-2"></div>
                <h2 id="resultTitle" class="font-bold text-lg"></h2>
                <p id="resultDesc" class="text-sm text-gray-500"></p>
            </div>

            <button id="btnCheckin" onclick="doCheckin()" disabled
                class="w-full bg-gray-300 text-white py-4 rounded-xl font-bold text-lg shadow-md transition-all">
                ระบุตำแหน่ง...
            </button>
            
            <button onclick="window.history.back()" class="mt-4 text-gray-400 text-sm">ย้อนกลับ</button>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008562649-LEXWJgaD"; // ใช้ ID เดียวกับ class_list ได้เลย
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        
        let map, userLat, userLng;

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบรหัสวิชา");
            
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            
            // เริ่มต้นขอ GPS
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(onLocationFound, onLocationError, {
                    enableHighAccuracy: true, // ขอความแม่นยำสูง
                    timeout: 5000,
                    maximumAge: 0
                });
            } else {
                alert("อุปกรณ์ของคุณไม่รองรับ GPS");
            }
        }
        main();

        function onLocationFound(pos) {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            
            // แสดงหน้าจอ
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('content').classList.remove('hidden');
            document.getElementById('subjectName').innerText = "พร้อมเช็คชื่อ";
            
            // เปิดปุ่ม
            const btn = document.getElementById('btnCheckin');
            btn.disabled = false;
            btn.classList.remove('bg-gray-300');
            btn.classList.add('bg-blue-600', 'hover:bg-blue-700', 'active:scale-95');
            btn.innerText = "📍 กดเพื่อเช็คชื่อ";

            // Show Map
            initMap(userLat, userLng);
        }

        function onLocationError(err) {
            document.getElementById('loading').classList.add('hidden');
            alert("ไม่สามารถระบุตำแหน่งได้: " + err.message + "\nกรุณาเปิด GPS และลองใหม่");
        }

        function initMap(lat, lng) {
            map = L.map('map', { zoomControl: false, dragging: false, scrollWheelZoom: false }).setView([lat, lng], 17);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(map);
        }

        async function doCheckin() {
            const btn = document.getElementById('btnCheckin');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-3 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังตรวจสอบ...';

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', {
                    action: 'check_in',
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    lat: userLat,
                    lng: userLng
                });

                if (res.data.status === 'success') {
                    showResult('success', res.data.checkin_status, res.data.distance, res.data.time);
                } else {
                    showResult('error', res.data.message);
                }
            } catch (err) {
                showResult('error', "Server Error");
            }
        }

        function showResult(type, arg1, dist, time) {
            const box = document.getElementById('resultBox');
            const icon = document.getElementById('resultIcon');
            const title = document.getElementById('resultTitle');
            const desc = document.getElementById('resultDesc');
            const btn = document.getElementById('btnCheckin');

            box.classList.remove('hidden');
            btn.classList.add('hidden'); // ซ่อนปุ่มเช็คชื่อไปเลย

            if (type === 'success') {
                const status = arg1; // present or late
                if (status === 'present') {
                    box.className = "mb-6 p-6 rounded-xl bg-green-50 border border-green-200";
                    icon.innerText = "✅";
                    title.innerText = "เช็คชื่อสำเร็จ!";
                    title.className = "font-bold text-lg text-green-700";
                    desc.innerText = `เข้าเรียนทันเวลา (${time})\nระยะห่าง: ${dist} ม.`;
                } else {
                    box.className = "mb-6 p-6 rounded-xl bg-yellow-50 border border-yellow-200";
                    icon.innerText = "⚠️";
                    title.innerText = "เช็คชื่อสำเร็จ (สาย)";
                    title.className = "font-bold text-lg text-yellow-700";
                    desc.innerText = `เข้าเรียนสาย (${time})\nระยะห่าง: ${dist} ม.`;
                }
            } else {
                // Error (นอกพื้นที่ หรือ เช็คไปแล้ว)
                box.className = "mb-6 p-6 rounded-xl bg-red-50 border border-red-200";
                icon.innerText = "❌";
                title.innerText = "เช็คชื่อไม่สำเร็จ";
                title.className = "font-bold text-lg text-red-700";
                desc.innerText = arg1; // Error message
                
                // ถ้าผิดพลาด ให้ปุ่มกลับมาเพื่อให้ลองใหม่
                btn.classList.remove('hidden');
                btn.disabled = false;
                btn.innerText = "ลองใหม่อีกครั้ง";
                btn.classList.remove('bg-blue-600');
                btn.classList.add('bg-gray-800');
            }
        }
    </script>
</body>
</html>