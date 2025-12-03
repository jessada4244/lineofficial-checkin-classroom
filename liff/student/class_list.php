<?php
// เพิ่ม Header บังคับไม่ให้ Browser จำ Cache หน้าเว็บ (แก้ปัญหา Back แล้ว Error)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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
    
    <script>
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</head>
<body class="bg-gray-200 min-h-screen pb-80"> 

    <div class="bg-white p-4 shadow-sm sticky top-0 z-10 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-800">📚 ห้องเรียนของฉัน</h1>
            <p class="text-xs text-gray-500" id="studentName">กำลังโหลด...</p>
        </div>
        
        <div class="flex gap-3">
            <button onclick="window.location.reload()" class="text-gray-400 hover:text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
            <button onclick="window.location.href='../settings.php'" class="text-gray-400 hover:text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </button>
        </div>
    </div>

    <div class="p-4 bg-white mb-2 shadow-sm pb-6 rounded-b-3xl space-y-3">
        <button onclick="document.getElementById('joinModal').classList.remove('hidden')" 
                class="w-full bg-gray-900 hover:bg-black text-white py-3.5 rounded-xl font-bold text-lg shadow-lg flex items-center justify-center gap-2 transition transform active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            เข้าร่วมห้องเรียนใหม่
        </button>
    </div>

    <div class="text-sm font-bold px-4 pt-2 text-gray-400 uppercase tracking-wider">รายการวิชาที่ลงทะเบียน</div>
    <div id="classList" class="px-4 pt-4 pb-20">
        <div class="text-center mt-10 text-gray-400">กำลังโหลดรายวิชา...</div>
    </div>

    <div class="fixed bottom-0 left-0 w-full bg-white p-4 shadow-[0_-5px_15px_rgba(0,0,0,0.05)] border-t border-gray-100 z-30 space-y-3 rounded-t-2xl">
        <button onclick="scanQR()" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl shadow-lg flex items-center justify-center gap-3 active:scale-95 transition">
            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
            <span class="text-2xl font-bold">สแกน QR เช็คชื่อ</span>
        </button>
        <button onclick="openManualCheckin()" class="w-full bg-white border-2 border-blue-400 text-indigo-600 py-3 rounded-xl hover:bg-indigo-50 transition flex items-center justify-center gap-2 active:scale-95">
            <span class="font-bold">กรอกรหัสเช็คชื่อ (ออนไลน์)</span>
        </button>
        <p id="gpsStatus" class="text-center text-xs text-gray-400 mt-1">กำลังโหลด... กรุณารอสักครู่</p>
    </div>

    <div id="joinModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h2 class="text-lg font-bold mb-2 text-gray-800 text-center">เข้าร่วมห้องเรียนใหม่</h2>
            <p class="text-center text-xs text-gray-400 mb-4">กรอกรหัสวิชา 6 หลักที่ได้รับจากอาจารย์</p>
            <input type="text" id="inputClassCode" maxlength="6" placeholder="รหัสวิชา (เช่น AB1234)" class="w-full text-center text-3xl font-mono font-bold border-2 border-gray-200 bg-gray-50 p-3 rounded-xl mb-6 uppercase focus:ring-2 focus:ring-gray-800 outline-none">
            <div class="flex gap-3">
                <button onclick="document.getElementById('joinModal').classList.add('hidden')" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 rounded-xl font-bold text-gray-600 transition">ยกเลิก</button>
                <button onclick="joinClass()" class="flex-1 py-3 text-white bg-gray-900 hover:bg-black rounded-xl font-bold transition shadow-lg">เข้าร่วม</button>
            </div>
        </div>
    </div>

    <div id="checkinModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
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

    <script>
        const LIFF_ID = "2008573640-jb4bpE5J"; 
        
        let userLat = 0, userLng = 0;
        let myClasses = [];

        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) {
                    liff.login();
                } else {
                    const profile = await liff.getProfile();
                    document.getElementById('studentName').innerText = "สวัสดี, " + profile.displayName;
                    loadMyClasses();
                    initGPS();
                }
            } catch (err) { 
                alert("LIFF Init Failed:\n" + err.message); 
            }
        }
        main();

        function initGPS() { 
            if (navigator.geolocation) { 
                navigator.geolocation.watchPosition(
                    (pos) => { 
                        userLat = pos.coords.latitude; 
                        userLng = pos.coords.longitude; 
                        document.getElementById('gpsStatus').innerHTML = `<span class="text-green-600">✅ พร้อมใช้งาน</span>`; 
                    }, 
                    (err) => { 
                        document.getElementById('gpsStatus').innerHTML = `<span class="text-red-500">❌ ไม่พบตำแหน่ง GPS (กรุณาเปิด GPS)</span>`; 
                    }, 
                    { enableHighAccuracy: true }
                ); 
            } else {
                document.getElementById('gpsStatus').innerText = "❌ กรุณาเปิด GPS";
            }
        }

        async function loadMyClasses() {
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', { action: 'get_my_classes', line_id: pf.userId });
                const list = document.getElementById('classList'); 
                list.innerHTML = ''; 
                myClasses = res.data.classes;
                
                if (res.data.classes.length === 0) { 
                    list.innerHTML = `<div class="bg-white p-10 rounded-2xl shadow-sm text-center border border-dashed border-gray-300">
                        <p class="text-gray-300 text-5xl mb-3">📂</p>
                        <p class="text-gray-400">ยังไม่ได้ลงทะเบียนวิชาเรียน</p>
                        <p class="text-xs text-gray-300 mt-2">กดปุ่มเข้าร่วมด้านบนเพื่อเริ่มเรียน</p>
                    </div>`; 
                    return; 
                }
                
                res.data.classes.forEach(c => {
                    const isDark = isDarkColor(c.room_color);
                    const iconColor = isDark ? 'text-white opacity-80' : 'text-gray-600 opacity-60';

                    // ดีไซน์ใหม่: Header Banner สีเต็มพื้นที่ + รหัสวิชาพื้นหลังขาว ตัวหนังสือสีดำ
                    list.innerHTML += `
                        <div onclick="goToHistory(${c.id})" class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4 overflow-hidden cursor-pointer hover:shadow-md transition transform duration-200 active:scale-95 group">
                            
                            <div class="px-5 py-3 flex justify-between items-center" style="background-color: ${c.room_color};">
                                <span class="text-xs font-bold px-3 py-1 rounded-lg bg-white shadow-sm text-gray-800">
                                    ${c.course_code}
                                </span>
                                <div class="${iconColor} group-hover:opacity-100 transition">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </div>

                            <div class="p-5 pt-4">
                                <h3 class="text-lg font-bold text-gray-800 mb-1 leading-snug line-clamp-2 group-hover:text-blue-600 transition">
                                    ${c.subject_name}
                                </h3>
                                
                                <div class="mt-4 pt-3 border-t border-gray-50 flex justify-between items-end">
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-bold mb-0.5 uppercase tracking-wide">ผู้สอน</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-bold text-gray-700">อ.${c.teacher_name}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-lg group-hover:bg-blue-50 group-hover:text-blue-600 transition">
                                        <span class="w-2 h-2 rounded-full" style="background-color:${c.room_color}"></span>
                                        <span class="font-medium text-black">ประวัติเข้าเรียน</span>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });
            } catch (e) { 
                console.error(e);
            }
        }

        async function scanQR() { 
            if (!userLat) return alert("ระบบยังไม่พร้อม กรุณารอสักครู่..."); 
            if (!liff.isInClient()) return alert("ฟังก์ชันสแกนใช้ได้เฉพาะบนแอป LINE ในมือถือเท่านั้น"); 
            try { 
                const result = await liff.scanCodeV2(); 
                if (result.value) { 
                    try {
                        const data = JSON.parse(result.value);
                        if(data.class_id && data.token) {
                            submitCheckin(data.class_id, data.token, 'scan'); 
                        } else {
                            alert("❌ QR Code ไม่ถูกต้อง (ไม่ใช่ของระบบนี้)");
                        }
                    } catch(e) { alert("❌ รูปแบบข้อมูล QR Code ไม่ถูกต้อง"); }
                } 
            } catch (err) { alert("เปิดกล้องไม่ได้: " + err.message); } 
        }

        function openManualCheckin() { 
            const s = document.getElementById('selectClassCheckin'); 
            s.innerHTML='<option value="">-- เลือกวิชา --</option>'; 
            myClasses.forEach(c=>{
                s.innerHTML+=`<option value="${c.id}">${c.course_code} ${c.subject_name}</option>`;
            }); 
            document.getElementById('checkinModal').classList.remove('hidden'); 
        }

        async function submitManualCheckin() { 
            const cid = document.getElementById('selectClassCheckin').value; 
            const t = document.getElementById('inputCheckinToken').value; 
            if(!cid || !t) return alert("กรุณากรอกข้อมูลให้ครบ"); 
            
            submitCheckin(cid, t, 'manual'); 
            document.getElementById('checkinModal').classList.add('hidden'); 
        }

        async function submitCheckin(classId, token, type) { 
            try { 
                const pf = await liff.getProfile(); 
                const res = await axios.post('../../api/student_api.php', {
                    action: 'check_in_qr',
                    line_id: pf.userId,
                    class_id: classId,
                    qr_token: token,
                    submission_type: type,
                    lat: userLat,
                    lng: userLng
                }); 
                
                if(res.data.status === 'success') {
                    alert(`✅ เช็คชื่อสำเร็จ!\n----------------\nวิชา: ${res.data.subject_name}\nสถานะ: ${res.data.checkin_status}\nเวลา: ${res.data.time}\nระยะทาง: ${res.data.distance}`); 
                } else {
                    alert("❌ " + res.data.message); 
                }
            } catch(err) { alert("Server Error"); } 
        }

        async function joinClass() { 
            const c = document.getElementById('inputClassCode').value; 
            if(!c) return alert("กรุณากรอกรหัสวิชา"); 
            
            try {
                const pf = await liff.getProfile(); 
                const r = await axios.post('../../api/student_api.php', {
                    action: 'join_class',
                    line_id: pf.userId,
                    class_code: c
                }); 
                
                if(r.data.status === 'success') {
                    alert("✅ เข้าร่วมวิชา '" + r.data.subject_name + "' สำเร็จ!");
                    loadMyClasses();
                    document.getElementById('joinModal').classList.add('hidden');
                    document.getElementById('inputClassCode').value = '';
                } else {
                    alert("❌ " + r.data.message);
                }
            } catch(e) { alert("Server Error"); } 
        }

        function goToHistory(id) { window.location.href = './history.php?class_id=' + id; }

        function isDarkColor(hex) { 
            if(!hex) return false; 
            const r = parseInt(hex.substr(1,2),16);
            const g = parseInt(hex.substr(3,2),16);
            const b = parseInt(hex.substr(5,2),16); 
            return (0.2126*r + 0.7152*g + 0.0722*b) < 128; 
        }
    </script>
</body>
</html>