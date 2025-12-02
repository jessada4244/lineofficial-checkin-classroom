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
        
        <button onclick="window.location.href='../settings.php'" class="bg-white p-2 rounded-full shadow-sm text-gray-600 hover:text-blue-600 transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
</button>
    </div>

    <div class="p-4 bg-white mb-2 shadow-sm pb-6 rounded-b-3xl space-y-3">
        <button onclick="scanQR()" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-xl shadow-lg flex items-center justify-center gap-2">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
            <span class="font-bold">สแกน QR (GPS)</span>
        </button>
        <button onclick="openManualCheckin()" class="w-full bg-white border-2 border-indigo-100 text-indigo-600 py-3 rounded-xl hover:bg-indigo-50 transition flex items-center justify-center gap-2">
            <span class="font-bold">กรอกรหัสเช็คชื่อ (ออนไลน์)</span>
        </button>
        <p id="gpsStatus" class="text-center text-xs text-gray-400 mt-1">📍 กำลังค้นหาพิกัด GPS...</p>
    </div>

    <div id="classList" class="px-4 space-y-4 pb-20">
        <div class="text-center mt-10 text-gray-400">กำลังโหลดรายวิชา...</div>
    </div>

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
            <input type="number" id="inputCheckinToken" placeholder="000000" class="w-full text-center text-3xl font-mono font-bold border-2 border-blue-100 bg-blue-50 p-3 rounded-xl mb-4 text-blue-800 outline-none">
            <div class="flex gap-3">
                <button onclick="document.getElementById('checkinModal').classList.add('hidden')" class="flex-1 py-3 bg-gray-100 rounded-xl font-bold text-gray-600">ยกเลิก</button>
                <button onclick="submitManualCheckin()" class="flex-1 py-3 text-white bg-blue-600 rounded-xl font-bold shadow-lg shadow-blue-200">เช็คชื่อ</button>
            </div>
        </div>
    </div>
    
    <button onclick="document.getElementById('joinModal').classList.remove('hidden')" class="fixed bottom-6 right-6 bg-gray-800 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-3xl font-bold hover:bg-black transition transform hover:scale-110 active:scale-95 z-20">+</button>


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

        // (คงฟังก์ชันเดิมทั้งหมดไว้: initGPS, loadMyClasses, scanQR, submitCheckin, etc.)
        function initGPS() { if (navigator.geolocation) { navigator.geolocation.watchPosition((pos) => { userLat = pos.coords.latitude; userLng = pos.coords.longitude; document.getElementById('gpsStatus').innerHTML = `<span class="text-green-600">✅ GPS พร้อมใช้งาน</span>`; }, (err) => { document.getElementById('gpsStatus').innerHTML = `<span class="text-red-500">❌ ไม่พบตำแหน่ง GPS</span>`; }, { enableHighAccuracy: true }); } }
        async function loadMyClasses() {
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', { action: 'get_my_classes', line_id: pf.userId });
                const list = document.getElementById('classList'); list.innerHTML = ''; myClasses = res.data.classes;
                if (res.data.classes.length === 0) { list.innerHTML = `<p class="text-center text-gray-400 mt-10">ยังไม่มีรายวิชา</p>`; return; }
                res.data.classes.forEach(c => {
                    const isDark = isDarkColor(c.room_color);
                    list.innerHTML += `<div onclick="goToHistory(${c.id})" class="p-4 rounded-2xl shadow-md mb-4 cursor-pointer relative overflow-hidden" style="background-color: ${c.room_color};"><h3 class="text-xl font-bold ${isDark?'text-white':'text-gray-800'}">${c.subject_name}</h3><p class="text-sm ${isDark?'text-white/80':'text-gray-500'}">${c.course_code} | อ.${c.teacher_name}</p></div>`;
                });
            } catch (e) {}
        }
        async function scanQR() { if (!userLat) return alert("❌ รอ GPS สักครู่"); if (!liff.isInClient()) return alert("ใช้ใน LINE เท่านั้น"); try { const result = await liff.scanCodeV2(); if (result.value) { const data = JSON.parse(result.value); submitCheckin(data.class_id, data.token, 'scan'); } } catch (err) { alert("เปิดกล้องไม่ได้"); } }
        function openManualCheckin() { const s = document.getElementById('selectClassCheckin'); s.innerHTML='<option value="">-- เลือกวิชา --</option>'; myClasses.forEach(c=>{s.innerHTML+=`<option value="${c.id}">${c.course_code}</option>`;}); document.getElementById('checkinModal').classList.remove('hidden'); }
        async function submitManualCheckin() { const cid=document.getElementById('selectClassCheckin').value; const t=document.getElementById('inputCheckinToken').value; if(!cid||!t)return; submitCheckin(cid,t,'manual'); document.getElementById('checkinModal').classList.add('hidden'); }
        async function submitCheckin(classId, token, type) { try { const pf=await liff.getProfile(); const res=await axios.post('../../api/student_api.php',{action:'check_in_qr',line_id:pf.userId,class_id:classId,qr_token:token,submission_type:type,lat:userLat,lng:userLng}); if(res.data.status==='success') alert(`✅ เช็คชื่อสำเร็จ!\nวิชา: ${res.data.subject_name}\nสถานะ: ${res.data.checkin_status}\nระยะ: ${res.data.distance}`); else alert("❌ "+res.data.message); } catch(err){alert("Error");} }
        async function joinClass() { const c=document.getElementById('inputClassCode').value; if(!c)return; try{const pf=await liff.getProfile(); const r=await axios.post('../../api/student_api.php',{action:'join_class',line_id:pf.userId,class_code:c}); if(r.data.status==='success'){alert("สำเร็จ");loadMyClasses();document.getElementById('joinModal').classList.add('hidden');}else alert(r.data.message);}catch(e){} }
        function goToHistory(id) { window.location.href = './history.php?class_id=' + id; }
        function isDarkColor(hex) { if(!hex)return false; const r=parseInt(hex.substr(1,2),16),g=parseInt(hex.substr(3,2),16),b=parseInt(hex.substr(5,2),16); return (0.2126*r+0.7152*g+0.0722*b)<128; }
    </script>
   
</body>
</html>