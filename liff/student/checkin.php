<?php
require_once '../../config/security.php';
checkLogin('student'); 
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
        .pulse-ring { border: 3px solid #fff; -webkit-border-radius: 30px; height: 18px; width: 18px; position: absolute; -webkit-animation: pulsate 1s ease-out; -webkit-animation-iteration-count: infinite; opacity: 0.0; }
        @-webkit-keyframes pulsate { 0% {-webkit-transform: scale(0.1, 0.1); opacity: 0.0;} 50% {opacity: 1.0;} 100% {-webkit-transform: scale(1.2, 1.2); opacity: 0.0;} }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

    <div id="loading" class="fixed inset-0 bg-white z-50 flex flex-col items-center justify-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-500 font-medium">กำลังระบุตำแหน่ง GPS...</p>
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

            <div id="actionButtons">
                <button onclick="scanQR()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-bold text-lg shadow-lg shadow-blue-200 mb-3 flex items-center justify-center gap-2 transition-all transform active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                    สแกน QR Code
                </button>

                <button id="btnCheckin" onclick="doCheckin()" disabled class="w-full bg-gray-100 text-gray-400 py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                    <span>📍 เช็คชื่อด้วย GPS เท่านั้น</span>
                </button>
            </div>
            
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-jb4bpE5J"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');
        let map, userLat, userLng;

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบรหัสวิชา");
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(onLocationFound, onLocationError, {
                        enableHighAccuracy: true, timeout: 10000, maximumAge: 0
                    });
                } else { alert("อุปกรณ์ไม่รองรับ GPS"); }
            } catch (err) { alert("LIFF Error: " + err.message); }
        }
        main();

        function onLocationFound(pos) {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('content').classList.remove('hidden');
            
            const btn = document.getElementById('btnCheckin');
            btn.disabled = false;
            btn.classList.remove('bg-gray-100', 'text-gray-400');
            btn.classList.add('bg-white', 'text-gray-600', 'border', 'border-gray-200', 'hover:bg-gray-50');

            initMap(userLat, userLng);
        }

        function onLocationError(err) {
            document.getElementById('loading').innerHTML = `<p class="text-red-500 font-bold p-10 text-center">ไม่สามารถระบุตำแหน่ง GPS ได้</p>`;
        }

        function initMap(lat, lng) {
            map = L.map('map', { zoomControl: false, dragging: false, scrollWheelZoom: false }).setView([lat, lng], 17);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(map);
            const icon = L.divIcon({ className: 'custom-div-icon', html: "<div class='w-4 h-4 bg-blue-600 border-2 border-white rounded-full shadow-md pulse-ring'></div>", iconSize: [20, 20], iconAnchor: [10, 10] });
            L.marker([lat, lng], {icon: icon}).addTo(map);
        }

        async function scanQR() {
            if (!liff.isInClient()) return alert('ฟังก์ชันสแกนใช้ได้เฉพาะบนแอป LINE ในมือถือเท่านั้น');
            try {
                const result = await liff.scanCodeV2();
                if (result.value) {
                    try {
                        const data = JSON.parse(result.value);
                        if (data.class_id != CLASS_ID) return alert("❌ คุณสแกนผิดวิชา!");
                        if (!data.token) return alert("❌ QR Code ไม่ถูกต้อง");
                        submitCheckin(data.token);
                    } catch (e) { alert("รูปแบบ QR Code ไม่ถูกต้อง"); }
                }
            } catch (err) { alert("เกิดข้อผิดพลาดในการเปิดกล้อง"); }
        }

        async function submitCheckin(qrToken) {
            setLoading(true);
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', {
                    action: 'check_in_qr', // Action สำหรับ QR
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    lat: userLat,
                    lng: userLng,
                    qr_token: qrToken
                });
                if (res.data.status === 'success') showResult('success', res.data.checkin_status, res.data.distance, res.data.time);
                else showResult('error', res.data.message);
            } catch (err) { showResult('error', "Server Error"); }
            setLoading(false);
        }

        async function doCheckin() {
            setLoading(true);
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', {
                    action: 'check_in', // Action เดิม (GPS)
                    line_id: profile.userId,
                    class_id: CLASS_ID,
                    lat: userLat,
                    lng: userLng
                });
                if (res.data.status === 'success') showResult('success', res.data.checkin_status, res.data.distance, res.data.time);
                else showResult('error', res.data.message);
            } catch (err) { showResult('error', "Server Error"); }
            setLoading(false);
        }

        function setLoading(isLoading) {
            document.getElementById('actionButtons').innerHTML = isLoading ? `<p class="text-center text-blue-500 py-4">กำลังตรวจสอบ...</p>` : '';
        }

        function showResult(type, status, dist, time) {
            const box = document.getElementById('resultBox');
            const icon = document.getElementById('resultIcon');
            const title = document.getElementById('resultTitle');
            const desc = document.getElementById('resultDesc');
            document.getElementById('actionButtons').classList.add('hidden');
            box.classList.remove('hidden');
            
            if (type === 'success') {
                if (status === 'present') {
                    box.className = "mb-6 p-6 rounded-2xl bg-green-50 border border-green-200 text-center";
                    icon.innerText = "✅"; title.innerText = "เช็คชื่อสำเร็จ!"; title.className = "font-bold text-xl text-green-700";
                    desc.innerHTML = `เข้าเรียนทันเวลา <span class="font-bold">${time}</span><br>ระยะห่าง: ${dist} เมตร`;
                } else {
                    box.className = "mb-6 p-6 rounded-2xl bg-yellow-50 border border-yellow-200 text-center";
                    icon.innerText = "⚠️"; title.innerText = "เช็คชื่อสำเร็จ (สาย)"; title.className = "font-bold text-xl text-yellow-700";
                    desc.innerHTML = `เข้าเรียนสาย <span class="font-bold">${time}</span><br>ระยะห่าง: ${dist} เมตร`;
                }
            } else {
                box.className = "mb-6 p-6 rounded-2xl bg-red-50 border border-red-200 text-center";
                icon.innerText = "❌"; title.innerText = "เช็คชื่อไม่สำเร็จ"; title.className = "font-bold text-xl text-red-700";
                desc.innerText = status; 
                document.getElementById('actionButtons').classList.remove('hidden'); // ให้กดใหม่ได้
                document.getElementById('actionButtons').innerHTML = `
                    <button onclick="scanQR()" class="w-full bg-blue-600 text-white py-3 rounded-xl mb-2">ลองสแกนใหม่</button>
                    <button onclick="doCheckin()" class="w-full bg-gray-200 text-gray-600 py-3 rounded-xl">ลอง GPS ใหม่</button>`;
            }
        }
    </script>
</body>
</html>