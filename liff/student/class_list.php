<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ห้องเรียนของฉัน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen pb-20">

    <div class="bg-white p-4 shadow-sm sticky top-0 z-10">
        <h1 class="text-xl font-bold text-gray-800">📚 ห้องเรียนของฉัน</h1>
        <p class="text-xs text-gray-500" id="studentName">กำลังโหลด...</p>
    </div>

    <div id="classList" class="p-4 space-y-4">
        <div class="text-center mt-10 text-gray-400">
            <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            กำลังโหลดข้อมูล...
        </div>
    </div>

    <button onclick="document.getElementById('joinModal').classList.remove('hidden')" 
            class="fixed bottom-6 right-6 bg-blue-600 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-3xl font-bold hover:bg-blue-700 transition transform hover:scale-110 active:scale-95 z-20">
        +
    </button>

    <div id="joinModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform transition-all scale-100">
            <h2 class="text-lg font-bold mb-2 text-gray-800 text-center">เข้าร่วมห้องเรียนใหม่</h2>
            <p class="text-xs text-gray-500 text-center mb-4">กรอกรหัส 6 หลักที่ได้รับจากอาจารย์</p>
            
            <input type="text" id="inputClassCode" maxlength="6" placeholder="XXXXXX" 
                   class="w-full text-center text-3xl font-mono font-bold tracking-widest border-2 border-blue-100 bg-blue-50 p-3 rounded-xl focus:border-blue-500 focus:outline-none mb-4 uppercase text-gray-700">

            <div class="flex gap-3">
                <button onclick="document.getElementById('joinModal').classList.add('hidden')" class="flex-1 py-3 text-gray-500 bg-gray-100 rounded-xl font-bold hover:bg-gray-200 transition">ยกเลิก</button>
                <button onclick="joinClass()" class="flex-1 py-3 text-white bg-blue-600 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition">เข้าร่วม</button>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008562649-LEXWJgaD"; 

        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) {
                    liff.login();
                } else {
                    const profile = await liff.getProfile();
                    document.getElementById('studentName').innerText = "สวัสดี, " + profile.displayName;
                    loadMyClasses();
                }
            } catch (err) {
                alert("LIFF Init Failed: " + err.message);
            }
        }
        main();

        // -----------------------------------------------------------
        // 1. โหลดรายการวิชา (My Classes)
        // -----------------------------------------------------------
        async function loadMyClasses() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', {
                    action: 'get_my_classes',
                    line_id: profile.userId
                });

                const list = document.getElementById('classList');
                list.innerHTML = '';

                if (res.data.classes.length === 0) {
                    list.innerHTML = `
                        <div class="text-center py-10 opacity-50">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" class="w-24 mx-auto mb-4 grayscale">
                            <p>คุณยังไม่มีห้องเรียน</p>
                            <p class="text-sm">กดปุ่ม + มุมขวาล่างเพื่อกรอกรหัสเข้าห้อง</p>
                        </div>`;
                    return;
                }

                res.data.classes.forEach(c => {
                    // คำนวณสีตัวอักษรให้ตัดกับพื้นหลัง
                    const textColor = isDarkColor(c.room_color) ? 'text-white' : 'text-gray-800';
                    const subText = isDarkColor(c.room_color) ? 'text-white/80' : 'text-gray-500';

                    list.innerHTML += `
                        <div style="background-color: ${c.room_color || '#fff'};" 
                             class="p-4 rounded-2xl shadow-md transition relative overflow-hidden mb-4 border border-black/5">
                            
                            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>

                            <div class="relative z-10 flex justify-between items-start">
                                <div onclick="goToCheckin(${c.id})" class="flex-1 cursor-pointer active:opacity-70">
                                    <span class="text-[10px] font-bold uppercase tracking-wider ${subText} border border-white/20 px-2 py-0.5 rounded-full inline-block mb-1">
                                        ${c.course_code}
                                    </span>
                                    <h3 class="text-xl font-bold ${textColor} leading-tight mb-1 truncate pr-2">${c.subject_name}</h3>
                                    <p class="text-sm ${subText}">👨‍🏫 อ.${c.teacher_name}</p>
                                </div>

                                <button onclick="goToHistory(${c.id})" 
                                    class="ml-2 bg-white/20 hover:bg-white/30 backdrop-blur-md p-2 rounded-xl text-[10px] font-bold ${textColor} border border-white/30 flex flex-col items-center justify-center w-14 h-14 transition shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    ประวัติ
                                </button>
                            </div>
                        </div>
                    `;
                });

            } catch (err) {
                console.error(err);
                document.getElementById('classList').innerHTML = `<p class="text-center text-red-500">โหลดข้อมูลไม่สำเร็จ</p>`;
            }
        }

        // -----------------------------------------------------------
        // 2. เข้าร่วมห้องเรียน (Join Class)
        // -----------------------------------------------------------
        async function joinClass() {
            const code = document.getElementById('inputClassCode').value;
            if (code.length < 6) return alert("กรุณากรอกรหัส 6 หลัก");

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/student_api.php', {
                    action: 'join_class',
                    line_id: profile.userId,
                    class_code: code
                });

                if (res.data.status === 'success') {
                    alert(`✅ เข้าร่วมวิชา "${res.data.subject_name}" สำเร็จ!`);
                    document.getElementById('joinModal').classList.add('hidden');
                    document.getElementById('inputClassCode').value = '';
                    loadMyClasses(); // รีโหลดรายการทันที
                } else {
                    alert("❌ " + res.data.message);
                }
            } catch (err) {
                alert("Server Error: ติดต่อเซิร์ฟเวอร์ไม่ได้");
            }
        }

        // -----------------------------------------------------------
        // Navigation Helpers
        // -----------------------------------------------------------
        function goToCheckin(classId) {
            window.location.href = './checkin.php?class_id=' + classId;
        }

        function goToHistory(classId) {
            event.stopPropagation(); // ป้องกันไม่ให้ Event คลิกทะลุไปโดน goToCheckin
            window.location.href = './history.php?class_id=' + classId;
        }

        function isDarkColor(hex) {
            if(!hex) return false;
            const r = parseInt(hex.substr(1, 2), 16);
            const g = parseInt(hex.substr(3, 2), 16);
            const b = parseInt(hex.substr(5, 2), 16);
            return (0.2126 * r + 0.7152 * g + 0.0722 * b) < 128; 
        }
    </script>
</body>
</html>