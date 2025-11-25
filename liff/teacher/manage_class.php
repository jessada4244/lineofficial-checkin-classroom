<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องเรียน</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        /* สไตล์สำหรับปุ่มสีที่ถูกเลือก */
        .color-swatch.selected {
            border: 3px solid #000;
            box-shadow: 0 0 0 2px #fff;
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-gray-100 p-4">

    <div class="max-w-md mx-auto pb-20">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">รายวิชาของฉัน</h1>
        
        <button onclick="document.getElementById('createModal').classList.remove('hidden'); selectColor('#3B82F6')" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-xl shadow-lg flex justify-center items-center font-bold mb-6 transition">
            + สร้างห้องเรียนใหม่
        </button>

        <div id="classList" class="space-y-4">
            <p class="text-center text-gray-500 mt-10">กำลังโหลดข้อมูล...</p>
        </div>
    </div>

    <div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h2 class="text-xl font-bold mb-4 text-gray-800">สร้างห้องเรียนใหม่</h2>
            
            <div class="mb-3">
                <label class="text-xs text-gray-500">ชื่อวิชา</label>
                <input type="text" id="subjectName" placeholder="เช่น Programming 1" class="w-full border bg-gray-50 p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="text-xs text-gray-500 block mb-1">สีประจำวิชา (เลือกได้ 7 สี)</label>
                <div id="colorSelection" class="flex gap-2 justify-between p-2 bg-gray-50 rounded-lg border">
                    </div>
                <input type="hidden" id="roomColor" value="#3B82F6">
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500">จำนวนที่รับ (คน)</label>
                <input type="number" id="studentLimit" value="40" class="w-full border bg-gray-50 p-2 rounded-lg text-center focus:ring-2 focus:ring-blue-500 outline-none">
            </div>


            <div class="flex justify-end gap-2 pt-2 border-t">
                <button onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">ยกเลิก</button>
                <button onclick="createClass()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg shadow">บันทึก</button>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008562649-bkoEQOMg"; 
        const COLORS = [
            '#3B82F6',
            '#10B981', 
            '#F59E0B', 
            '#EF4444', 
            '#8B5CF6', 
            '#EC4899', 
            '#06B6D4'  
        ];
        
        async function main() {
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            
            // สร้างปุ่มสีเมื่อ DOM พร้อม
            renderColorSwatches(); 
            loadClasses();
        }
        main();
        
        // **********************************************
        // ฟังก์ชันจัดการสี
        // **********************************************
        function renderColorSwatches() {
            const container = document.getElementById('colorSelection');
            container.innerHTML = '';
            COLORS.forEach(hex => {
                const isDefault = hex === document.getElementById('roomColor').value;
                const swatch = document.createElement('div');
                swatch.className = `color-swatch w-6 h-6 rounded-full cursor-pointer transition duration-150 ${isDefault ? 'selected' : ''}`;
                swatch.style.backgroundColor = hex;
                swatch.onclick = () => selectColor(hex);
                container.appendChild(swatch);
            });
        }

        function selectColor(hex) {
            document.getElementById('roomColor').value = hex;
            document.querySelectorAll('.color-swatch').forEach(swatch => {
                swatch.classList.remove('selected');
                if (swatch.style.backgroundColor === hexToRgb(hex)) {
                    swatch.classList.add('selected');
                }
            });
        }
        
        function hexToRgb(hex) {
            // แปลง Hex เป็น RGB string เพื่อเปรียบเทียบกับ .style.backgroundColor
            const bigint = parseInt(hex.slice(1), 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgb(${r}, ${g}, ${b})`;
        }


        // **********************************************
        // ฟังก์ชันโหลดและแสดงผล (ปรับปุ่มจัดการ)
        // **********************************************
        async function loadClasses() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'get_classes',
                    line_id: profile.userId
                });
                
                const list = document.getElementById('classList');
                list.innerHTML = '';

                if(res.data.classes.length === 0) {
                    list.innerHTML = '<div class="text-center py-10 bg-white rounded-xl shadow-sm"><p class="text-gray-400">ยังไม่มีรายวิชา</p></div>';
                    return;
                }

                res.data.classes.forEach(c => {
                    const textColor = isDarkColor(c.room_color) ? 'text-white' : 'text-gray-800';
                    const subTextColor = isDarkColor(c.room_color) ? 'text-gray-200' : 'text-gray-500';
                    
                    // ปรับสไตล์ปุ่มจัดการให้สวยงาม
                    const manageButton = `<button onclick="alert('ไปหน้าแก้ไขห้อง ID: ${c.id}')" 
                        class="text-xs font-medium bg-white/70 hover:bg-white text-gray-800 px-3 py-1 rounded-full shadow-sm transition backdrop-blur-sm">
                        ⚙️ จัดการ
                    </button>`;

                    list.innerHTML += `
                        <div style="background-color: ${c.room_color};" class="p-5 rounded-2xl shadow-md transition transform active:scale-95">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-xl ${textColor}">${c.subject_name}</h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs ${subTextColor}">👥 รับ ${c.student_limit} คน</span>
                                        ${c.checkin_limit_time ? `<span class="text-xs ${subTextColor}">⏰ สายหลัง ${c.checkin_limit_time.substring(0,5)}</span>` : `<span class="text-xs ${subTextColor} font-medium">ยังไม่กำหนดเวลา/พิกัด</span>`}
                                    </div>
                                </div>
                                ${manageButton}
                            </div>
                        </div>
                    `;
                });
            } catch (err) {
                console.error(err);
            }
        }

        async function createClass() {
            const name = document.getElementById('subjectName').value;
            const color = document.getElementById('roomColor').value; // อ่านค่าจาก hidden input
            const limit = document.getElementById('studentLimit').value;
            
            if(!name) return alert("กรุณาระบุชื่อวิชา");

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'create_class',
                    line_id: profile.userId,
                    name: name,
                    color: color,
                    limit: limit
                });

                if(res.data.status === 'success') {
                    alert("✅ สร้างห้องเรียนสำเร็จ!");
                    document.getElementById('createModal').classList.add('hidden');
                    loadClasses();
                } else {
                    alert("❌ บันทึกไม่ได้: " + res.data.message);
                }
            } catch (err) {
                alert("Server Error: " + err.message);
            }
        }

        // Helper Function: เช็คสีเข้ม/อ่อน
        function isDarkColor(hex) {
            if(!hex) return false;
            const r = parseInt(hex.substr(1, 2), 16);
            const g = parseInt(hex.substr(3, 2), 16);
            const b = parseInt(hex.substr(5, 2), 16);
            const luma = 0.2126 * r + 0.7152 * g + 0.0722 * b; 
            return luma < 128; 
        }
    </script>
</body>
</html>