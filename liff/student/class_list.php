<?php
require_once '../../config/security.php';
checkLogin('student');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ห้องเรียนของฉัน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap'); body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen pb-24">

    <div class="bg-white p-4 shadow-sm sticky top-0 z-10 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-800">📚 ห้องเรียนของฉัน</h1>
            <p class="text-xs text-gray-500" id="studentName">กำลังโหลด...</p>
        </div>
        <a href="../logout.php" onclick="return confirm('ยืนยันการออกจากระบบ?')" class="bg-gray-100 p-2 rounded-full text-red-500 hover:bg-red-50 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
        </a>
    </div>

    <div class="p-4 bg-white mb-2 shadow-sm pb-6 rounded-b-3xl space-y-3">
        
        <button onclick="scanQR()" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-xl shadow-lg shadow-blue-200 transform active:scale-95 transition flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
            <span class="font-bold">สแกน QR (GPS)</span>
        </button>

        <button onclick="openManualCheckin()" class="w-full bg-white border-2 border-indigo-100 text-indigo-600 py-3 rounded-xl hover:bg-indigo-50 transition flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            <span class="font-bold">กรอกรหัสเช็คชื่อ (ออนไลน์)</span>
        </button>

        <p id="gpsStatus" class="text-center text-xs text-gray-400 mt-1">📍 กำลังค้นหาพิกัด GPS...</p>
    </div>

    <div id="classList" class="px-4 space-y-4 pb-20">
        <div class="text-center mt-10 text-gray-400">กำลังโหลดรายวิชา...</div>
    </div>

    <button onclick="document.getElementById('joinModal').classList.remove('hidden')" 
            class="fixed bottom-6 right-6 bg-gray-800 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-3xl font-bold hover:bg-black transition transform hover:scale-110 active:scale-95 z-20">
        +
    </button>

    <div id="joinModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h2 class="text-lg font-bold mb-2 text-gray-800 text-center">เข้าร่วมห้องเรียนใหม่</h2>
            <input type="text" id="inputClassCode" maxlength="6" placeholder="รหัสวิชา 6 หลัก" class="w-full text-center text-3xl font-mono font-bold border-2 border-gray-200 bg-gray-50 p-3 rounded-xl mb-4 uppercase">
            <div class="flex gap-3">
                <button onclick="document.getElementById('joinModal').classList.add('hidden')" class="flex-1 py-3 bg-gray-100 rounded-xl font-bold text-gray-600">ยกเลิก</button>
                <button onclick="joinClass()" class="flex-1 py-3 text-white bg-gray-800 rounded-xl font-bold">เข้าร่วม</button>
            </div>
        </div>
    </div>

    <div id="checkinModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h2 class="text-lg font-bold mb-1 text-gray-800 text-center">เช็คชื่อด้วยรหัส</h2>
            <p class="text-xs text-gray-400 text-center mb-4">สำหรับนิสิตที่เรียนออนไลน์ หรืออยู่นอกพื้นที่</p>
            
            <label class="text-xs font-bold text-gray-500 mb-1 block">1. เลือกวิชา</label>
            <select id="selectClassCheckin" class="w-full border border-gray-200 p-2 rounded-lg mb-3 bg-gray-50 outline-none text-sm"></select>
            
            <label class="text-xs font-bold text-gray-500 mb-1 block">2. กรอกรหัสตัวเลข (6 หลัก)</label>
            <input type="number" id="inputCheckinToken" placeholder="000000" class="w-full text-center text-3xl font-mono font-bold border-2 border-blue-100 bg-blue-50 p-3 rounded-xl mb-4 text-blue-800 outline-none focus:ring-2 focus:ring-blue-500">
            
            <div class="flex gap-3">
                <button onclick="document.getElementById('checkinModal').classList.add('hidden')" class="flex-1 py-3 bg-gray-100 rounded-xl font-bold text-gray-600">ยกเลิก</button>
                <button onclick="submitManualCheckin()" class="flex-1 py-3 text-white bg-blue-600 rounded-xl font-bold shadow-lg shadow-blue-200">เช็คชื่อ</button>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-jb4bpE5J"; 
        let userLat = 0, userLng = 0;
        let myClasses = [];

        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                else {
                    const profile = await liff.getProfile();
                    document.getElementById('studentName').innerText = "สวัสดี, " + profile.displayName;
                    loadMyClasses();
                    initGPS();
                }
            } catch (err) { alert("LIFF Init Failed"); }
        }
        main();

        function initGPS() {
            if (navigator.geolocation) {
                navigator.geolocation.watchPosition(
                    (pos) => {
                        userLat = pos.coords.latitude;
                        userLng = pos.coords.longitude;
                        document.getElementById('gpsStatus').innerHTML = `<span class="text-green-600">✅ GPS พร้อมใช้งาน</span>`;
                    },
                    (err) => {
                        document.getElementById('gpsStatus').innerHTML = `<span class="text-red-500">❌ ไม่พบตำแหน่ง GPS</span>`;
                    },
                    { enableHighAccuracy: true }
                );
            }
        }

        async function loadMyClasses() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', {
                    action: 'get_my_classes', line_id: profile.userId
                });
                const list = document.getElementById('classList');
                list.innerHTML = '';
                
                if (res.data.classes.length === 0) {
                    list.innerHTML = `<p class="text-center text-gray-400 mt-10">ยังไม่มีรายวิชา</p>`; return;
                }

                myClasses = res.data.classes; // เก็บไว้ใช้ใน Dropdown

                res.data.classes.forEach(c => {
                    list.innerHTML += `
                        <div onclick="goToHistory(${c.id})" class="p-4 rounded-2xl shadow-md mb-4 cursor-pointer active:opacity-80 transition relative overflow-hidden" style="background-color: ${c.room_color || '#fff'};">
                            <h3 class="text-xl font-bold ${isDark(c.room_color)?'text-white':'text-gray-800'}">${c.subject_name}</h3>
                            <p class="text-sm ${isDark(c.room_color)?'text-white/80':'text-gray-500'}">${c.course_code} | อ.${c.teacher_name}</p>
                            <div class="absolute top-4 right-4 bg-white/20 p-2 rounded-full">
                                <svg class="w-6 h-6 ${isDark(c.room_color)?'text-white':'text-gray-800'}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                    `;
                });
            } catch (e) {}
        }

        // --- Scan QR (GPS Check) ---
        async function scanQR() {
            if (!userLat || !userLng) return alert("❌ กรุณารอให้ GPS ระบุตำแหน่งได้ก่อน");
            if (!liff.isInClient()) return alert("ใช้ได้เฉพาะใน LINE บนมือถือ");
            try {
                const result = await liff.scanCodeV2();
                if (result.value) {
                    try {
                        const data = JSON.parse(result.value);
                        if (!data.token || !data.class_id) throw new Error();
                        submitCheckin(data.class_id, data.token, 'scan');
                    } catch(e) { alert("QR Code ไม่ถูกต้อง"); }
                }
            } catch (err) { alert("เปิดกล้องไม่ได้"); }
        }

        // --- Manual Checkin (No GPS Check) ---
        function openManualCheckin() {
            const select = document.getElementById('selectClassCheckin');
            select.innerHTML = '<option value="">-- เลือกวิชา --</option>';
            myClasses.forEach(c => {
                select.innerHTML += `<option value="${c.id}">${c.course_code} ${c.subject_name}</option>`;
            });
            document.getElementById('inputCheckinToken').value = '';
            document.getElementById('checkinModal').classList.remove('hidden');
        }

        async function submitManualCheckin() {
            const classId = document.getElementById('selectClassCheckin').value;
            const token = document.getElementById('inputCheckinToken').value;
            if(!classId || !token) return alert("กรุณาเลือกวิชาและกรอกรหัส");
            
            submitCheckin(classId, token, 'manual');
            document.getElementById('checkinModal').classList.add('hidden');
        }

        // --- Common Submit ---
        async function submitCheckin(classId, token, type) {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', {
                    action: 'check_in_qr',
                    line_id: profile.userId,
                    class_id: classId,
                    qr_token: token,
                    submission_type: type, // บอก API ว่าเป็น Scan หรือ Manual
                    lat: userLat,
                    lng: userLng
                });

                if (res.data.status === 'success') {
                    alert(`✅ เช็คชื่อสำเร็จ!\nวิชา: ${res.data.subject_name}\nสถานะ: ${res.data.checkin_status}\nระยะ: ${res.data.distance}`);
                } else {
                    alert("❌ " + res.data.message);
                }
            } catch (err) { alert("Server Error"); }
        }

        async function joinClass() { 
            const code = document.getElementById('inputClassCode').value;
            if(!code) return;
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', { action: 'join_class', line_id: profile.userId, class_code: code });
                if(res.data.status==='success') { alert(`เข้าร่วม ${res.data.subject_name} สำเร็จ`); loadMyClasses(); document.getElementById('joinModal').classList.add('hidden'); }
                else alert(res.data.message);
            } catch(e) { alert("Server Error"); }
        }

        function goToHistory(id) { window.location.href = './history.php?class_id=' + id; }
        function isDark(color) {
            const hex = color.replace('#', '');
            const r = parseInt(hex.substr(0, 2), 16);
            const g = parseInt(hex.substr(2, 2), 16);
            const b = parseInt(hex.substr(4, 2), 16);
            return ((r * 299) + (g * 587) + (b * 114)) / 1000 < 128;
        }
    </script>
</body>
</html>