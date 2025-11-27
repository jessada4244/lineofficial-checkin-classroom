<?php
require_once '../../config/security.php';
checkLogin('student'); // บังคับว่าเป็น teacher เท่านั้น
?>
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
        #map { z-index: 0; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

    <div id="loading" class="fixed inset-0 bg-white z-50 flex flex-col items-center justify-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-500 font-medium">กำลังระบุตำแหน่ง GPS...</p>
        <p class="text-xs text-gray-400 mt-2">กรุณากด "อนุญาต" หากมีการขอสิทธิ์เข้าถึงตำแหน่ง</p>
    </div>

    <div id="content" class="w-full max-w-sm bg-white rounded-3xl shadow-2xl overflow-hidden hidden transform transition-all">
        
        <div class="h-56 relative bg-gray-200">
            <div id="map" class="w-full h-full"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent z-[1] pointer-events-none"></div>
            
            <button onclick="window.history.back()" class="absolute top-4 left-4 z-[2] bg-white/80 backdrop-blur-md p-2 rounded-full shadow-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
        </div>

        <div class="px-6 pb-8 pt-2 text-center relative z-10">
            <h1 class="text-2xl font-bold text-gray-800 mb-1" id="pageTitle">พร้อมเช็คชื่อ</h1>
            <p class="text-sm text-gray-500 mb-6">กรุณาอยู่ในระยะ 50 เมตรจากจุดเช็คชื่อ</p>

            <div id="resultBox" class="hidden mb-6 p-5 rounded-2xl bg-gray-50 border border-gray-100 shadow-inner">
                <div id="resultIcon" class="text-5xl mb-3 animate-bounce"></div>
                <h2 id="resultTitle" class="font-bold text-xl mb-1"></h2>
                <p id="resultDesc" class="text-sm text-gray-600 leading-relaxed"></p>
            </div>

            <button id="btnCheckin" onclick="doCheckin()" disabled
                class="w-full bg-gray-300 text-white py-4 rounded-2xl font-bold text-lg shadow-lg transition-all transform active:scale-95 flex items-center justify-center gap-2">
                <span>กำลังหาระยะ...</span>
            </button>
            
        </div>
    </div>

    <script>
        // *** ใช้ LIFF ID เดียวกับ class_list.php ได้เลยครับ ***
        const LIFF_ID = "2008573640-jb4bpE5J"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        
        let map, userLat, userLng;

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบรหัสวิชา");
            
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                
                // ขอ GPS
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(onLocationFound, onLocationError, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    });
                } else {
                    alert("อุปกรณ์ไม่รองรับ GPS");
                }
            } catch (err) { alert("LIFF Error: " + err.message); }
        }
        main();

        function onLocationFound(pos) {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            
            // แสดงหน้าจอ
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('content').classList.remove('hidden');
            
            // ตั้งค่าปุ่มให้พร้อมกด
            const btn = document.getElementById('btnCheckin');
            btn.disabled = false;
            btn.className = "w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-bold text-lg shadow-lg shadow-blue-200 transition-all transform active:scale-95 flex items-center justify-center gap-2";
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg> กดเพื่อเช็คชื่อ`;

            initMap(userLat, userLng);
        }

        function onLocationError(err) {
            document.getElementById('loading').innerHTML = `
                <div class="text-red-500 text-center p-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <p class="font-bold">ไม่สามารถระบุตำแหน่งได้</p>
                    <p class="text-sm mt-1">กรุณาเปิด GPS และกดอนุญาตสิทธิ์</p>
                    <button onclick="location.reload()" class="mt-4 bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">ลองใหม่</button>
                </div>
            `;
        }

        function initMap(lat, lng) {
            map = L.map('map', { zoomControl: false, dragging: false, scrollWheelZoom: false }).setView([lat, lng], 17);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(map);
            
            // Marker ตัวเรา
            const icon = L.divIcon({
                className: 'custom-div-icon',
                html: "<div class='w-4 h-4 bg-blue-600 border-2 border-white rounded-full shadow-md pulse-ring'></div>",
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });
            L.marker([lat, lng], {icon: icon}).addTo(map);
        }

        async function doCheckin() {
            const btn = document.getElementById('btnCheckin');
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังตรวจสอบ...`;

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
                showResult('error', "เกิดข้อผิดพลาดในการเชื่อมต่อ Server");
            }
        }

        function showResult(type, arg1, dist, time) {
            const box = document.getElementById('resultBox');
            const icon = document.getElementById('resultIcon');
            const title = document.getElementById('resultTitle');
            const desc = document.getElementById('resultDesc');
            const btn = document.getElementById('btnCheckin');

            box.classList.remove('hidden');
            btn.classList.add('hidden'); // ซ่อนปุ่มเมื่อเสร็จ

            if (type === 'success') {
                const status = arg1; // present or late
                if (status === 'present') {
                    box.className = "mb-6 p-6 rounded-2xl bg-green-50 border border-green-200 text-center";
                    icon.innerText = "✅";
                    title.innerText = "เช็คชื่อสำเร็จ!";
                    title.className = "font-bold text-xl text-green-700";
                    desc.innerHTML = `เข้าเรียนทันเวลา <span class="font-bold">${time}</span><br>ระยะห่าง: ${dist} เมตร`;
                } else {
                    box.className = "mb-6 p-6 rounded-2xl bg-yellow-50 border border-yellow-200 text-center";
                    icon.innerText = "⚠️";
                    title.innerText = "เช็คชื่อสำเร็จ (สาย)";
                    title.className = "font-bold text-xl text-yellow-700";
                    desc.innerHTML = `เข้าเรียนสาย <span class="font-bold">${time}</span><br>ระยะห่าง: ${dist} เมตร`;
                }
            } else {
                // Error (นอกพื้นที่ หรือ เช็คไปแล้ว)
                box.className = "mb-6 p-6 rounded-2xl bg-red-50 border border-red-200 text-center";
                icon.innerText = "❌";
                title.innerText = "เช็คชื่อไม่สำเร็จ";
                title.className = "font-bold text-xl text-red-700";
                desc.innerText = arg1; // Error message
                
                // ปุ่มกลับมาให้ลองใหม่
                btn.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = "ลองใหม่อีกครั้ง";
                btn.className = "w-full bg-gray-800 text-white py-3 rounded-xl font-bold mt-4 shadow-lg";
            }
        }
    </script>
</body>
</html>