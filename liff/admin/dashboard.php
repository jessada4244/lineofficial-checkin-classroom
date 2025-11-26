<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้ดูแลระบบ</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .tab-btn.active { border-bottom: 3px solid #2563EB; color: #2563EB; font-weight: bold; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-20">
        <div class="px-4 py-3 flex justify-between items-center">
            <h1 class="text-lg font-bold text-gray-800">🛠️ Admin Dashboard</h1>
            <button onclick="window.location.reload()" class="text-gray-400 hover:text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            </button>
        </div>
        <div class="flex border-t border-gray-100">
            <button onclick="switchTab('users')" id="tab-users" class="tab-btn active flex-1 py-3 text-sm text-gray-500">จัดการผู้ใช้</button>
            <button onclick="switchTab('broadcast')" id="tab-broadcast" class="tab-btn flex-1 py-3 text-sm text-gray-500">ส่งประกาศ</button>
        </div>
    </div>

    <div class="p-4 pb-20">
        
        <div id="view-users" class="space-y-4">
            <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="bg-white p-3 rounded-xl shadow-sm border border-blue-100 text-center">
                    <p class="text-xs text-gray-400">ทั้งหมด</p>
                    <p id="statTotal" class="text-xl font-bold text-blue-600">-</p>
                </div>
                <div class="bg-white p-3 rounded-xl shadow-sm border border-green-100 text-center">
                    <p class="text-xs text-gray-400">อาจารย์</p>
                    <p id="statTeacher" class="text-xl font-bold text-green-600">-</p>
                </div>
                <div class="bg-white p-3 rounded-xl shadow-sm border border-purple-100 text-center">
                    <p class="text-xs text-gray-400">นิสิต</p>
                    <p id="statStudent" class="text-xl font-bold text-purple-600">-</p>
                </div>
            </div>

            <input type="text" id="searchInput" onkeyup="filterUsers()" placeholder="🔍 ค้นหาชื่อ หรือ Username..." class="w-full bg-white border border-gray-200 rounded-lg p-2 text-sm focus:outline-none focus:border-blue-500">

            <div id="userList" class="space-y-2">
                <div class="text-center py-10 text-gray-400">กำลังโหลด...</div>
            </div>
        </div>

        <div id="view-broadcast" class="hidden space-y-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm">
                <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" /></svg>
                    ประกาศข่าวสาร (Broadcast)
                </h2>

                <label class="block text-xs font-bold text-gray-500 mb-2">ส่งถึงใคร?</label>
                <select id="targetRole" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-sm mb-4">
                    <option value="all">ส่งถึงทุกคน (All)</option>
                    <option value="student">เฉพาะนิสิต (Students)</option>
                    <option value="teacher">เฉพาะอาจารย์ (Teachers)</option>
                </select>

                <label class="block text-xs font-bold text-gray-500 mb-2">ข้อความ</label>
                <textarea id="msgContent" rows="4" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="พิมพ์ข้อความประกาศที่นี่..."></textarea>

                <button onclick="sendBroadcast()" id="btnSend" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition mt-2">
                    ส่งประกาศ
                </button>
            </div>
        </div>

    </div>

    <script>
        // ใช้ LIFF ID เดียวกับหน้าอื่น หรือสร้างใหม่ถ้าต้องการ
        const LIFF_ID = "2008573640-Xlr1jY4w"; 
        let allUsers = [];

        async function main() {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) liff.login();
                loadUsers();
            } catch (err) { alert("LIFF Init Failed"); }
        }
        main();

        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            
            if(tab === 'users') {
                document.getElementById('view-users').classList.remove('hidden');
                document.getElementById('view-broadcast').classList.add('hidden');
            } else {
                document.getElementById('view-users').classList.add('hidden');
                document.getElementById('view-broadcast').classList.remove('hidden');
            }
        }

        // ================= USERS LOGIC =================
        async function loadUsers() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/admin_api.php', {
                    action: 'get_all_users',
                    line_id: profile.userId
                });

                if (res.data.status === 'success') {
                    allUsers = res.data.users;
                    renderStats(res.data.stats);
                    renderUserList(allUsers);
                } else {
                    document.getElementById('userList').innerHTML = `<p class="text-center text-red-500 py-10">${res.data.message}</p>`;
                }
            } catch (err) { alert("Error Loading Users"); }
        }

        function renderStats(stats) {
            document.getElementById('statTotal').innerText = stats.total;
            document.getElementById('statTeacher').innerText = stats.teacher;
            document.getElementById('statStudent').innerText = stats.student;
        }

        function renderUserList(users) {
            const list = document.getElementById('userList');
            list.innerHTML = '';
            
            users.forEach(u => {
                const roleBadge = u.role === 'teacher' 
                    ? '<span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded font-bold">อาจารย์</span>'
                    : (u.role === 'admin' ? '<span class="bg-gray-800 text-white text-[10px] px-2 py-0.5 rounded font-bold">Admin</span>' 
                    : '<span class="bg-purple-100 text-purple-700 text-[10px] px-2 py-0.5 rounded font-bold">นิสิต</span>');
                
                const deleteBtn = u.role !== 'admin' 
                    ? `<button onclick="deleteUser(${u.id}, '${u.username}')" class="text-red-400 hover:text-red-600 p-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>` 
                    : '';

                list.innerHTML += `
                    <div class="bg-white p-3 rounded-xl border border-gray-100 flex justify-between items-center shadow-sm">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-bold text-gray-800 text-sm">${u.name}</span>
                                ${roleBadge}
                            </div>
                            <p class="text-xs text-gray-400">User: ${u.username} ${u.student_id ? '| ID: '+u.student_id : ''}</p>
                        </div>
                        ${deleteBtn}
                    </div>
                `;
            });
        }

        function filterUsers() {
            const txt = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allUsers.filter(u => 
                u.name.toLowerCase().includes(txt) || 
                u.username.toLowerCase().includes(txt) ||
                (u.student_id && u.student_id.includes(txt))
            );
            renderUserList(filtered);
        }

        async function deleteUser(id, name) {
            if(!confirm(`ต้องการลบผู้ใช้ "${name}" ใช่ไหม?`)) return;
            try {
                const profile = await liff.getProfile();
                await axios.post('../../api/admin_api.php', {
                    action: 'delete_user',
                    line_id: profile.userId,
                    user_id: id
                });
                loadUsers();
            } catch(e) { alert("Error"); }
        }

        // ================= BROADCAST LOGIC =================
        async function sendBroadcast() {
            const role = document.getElementById('targetRole').value;
            const msg = document.getElementById('msgContent').value;
            
            if(!msg) return alert("กรุณาพิมพ์ข้อความ");
            if(!confirm("ยืนยันการส่งข้อความถึง " + role + "?")) return;

            const btn = document.getElementById('btnSend');
            btn.disabled = true;
            btn.innerText = "กำลังส่ง...";

            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/admin_api.php', {
                    action: 'broadcast',
                    line_id: profile.userId,
                    target_role: role,
                    message: msg
                });

                if(res.data.status === 'success') {
                    alert(`✅ ส่งข้อความสำเร็จ (${res.data.count} คน)`);
                    document.getElementById('msgContent').value = '';
                } else {
                    alert("❌ " + res.data.message);
                }
            } catch(e) { alert("Error"); }
            
            btn.disabled = false;
            btn.innerText = "ส่งประกาศ";
        }
    </script>
</body>
</html>