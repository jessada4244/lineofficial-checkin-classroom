<?php 
require_once '../../config/security.php'; 
checkLogin('admin'); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .tab-btn.active { border-bottom: 3px solid #2563EB; color: #2563EB; font-weight: bold; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen pb-24">

    <div class="bg-white shadow-sm sticky top-0 z-20">
        <div class="px-4 py-3 flex justify-between items-center">
            <h1 class="text-lg font-bold text-gray-800">🛠️ Admin Panel</h1>
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
        <div class="flex border-t border-gray-100 overflow-x-auto">
            <button onclick="switchTab('users')" id="tab-users" class="tab-btn active flex-1 py-3 text-xs font-bold whitespace-nowrap px-4">👥 ผู้ใช้</button>
            <button onclick="switchTab('broadcast')" id="tab-broadcast" class="tab-btn flex-1 py-3 text-xs font-bold whitespace-nowrap px-4">📢 ประกาศ</button>
            <button onclick="switchTab('reports')" id="tab-reports" class="tab-btn flex-1 py-3 text-xs font-bold whitespace-nowrap px-4">📬 กล่องข้อความ</button>
            <button onclick="switchTab('settings')" id="tab-settings" class="tab-btn flex-1 py-3 text-xs font-bold whitespace-nowrap px-4">⚙️ ระบบ</button>
        </div>
    </div>

    <div class="p-4">
        
        <div id="view-users" class="space-y-4">
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-white p-2 rounded text-center shadow-sm border-b-2 border-blue-500"><p class="text-[10px] text-gray-400">ทั้งหมด</p><p id="statTotal" class="font-bold text-blue-600">-</p></div>
                <div class="bg-white p-2 rounded text-center shadow-sm border-b-2 border-green-500"><p class="text-[10px] text-gray-400">อาจารย์</p><p id="statTeacher" class="font-bold text-green-600">-</p></div>
                <div class="bg-white p-2 rounded text-center shadow-sm border-b-2 border-purple-500"><p class="text-[10px] text-gray-400">นิสิต</p><p id="statStudent" class="font-bold text-purple-600">-</p></div>
            </div>
            <div class="flex gap-2">
                <input type="text" id="searchInput" onkeyup="filterUsers()" placeholder="🔍 ค้นหา..." class="flex-1 bg-white border rounded p-2 text-sm">
                <select id="roleFilter" onchange="filterUsers()" class="bg-white border rounded p-2 text-sm"><option value="all">ทุกสิทธิ์</option><option value="teacher">อาจารย์</option><option value="student">นิสิต</option><option value="admin">Admin</option></select>
            </div>
            <div id="userList" class="space-y-3">
                <div class="text-center text-gray-400 mt-10">กำลังโหลดข้อมูล...</div>
            </div>
        </div>

        <div id="view-broadcast" class="hidden space-y-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm">
                <h2 class="font-bold text-gray-800 mb-4 flex gap-2"><svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg> ส่งประกาศ (Broadcast)</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-gray-500">ส่งถึง</label>
                        <select id="bc-target" class="w-full border rounded p-2 bg-gray-50 text-sm">
                            <option value="all">ทุกคน (All Users)</option>
                            <option value="student">เฉพาะนิสิต</option>
                            <option value="teacher">เฉพาะอาจารย์</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">ข้อความ</label>
                        <textarea id="bc-msg" rows="4" class="w-full border rounded p-2 bg-gray-50 text-sm" placeholder="พิมพ์ข้อความ..."></textarea>
                    </div>
                    <button onclick="sendBroadcast()" class="w-full bg-blue-600 text-white font-bold py-3 rounded shadow hover:bg-blue-700">ส่งข้อความ</button>
                </div>
            </div>
        </div>

        <div id="view-reports" class="hidden space-y-4">
            <h2 class="font-bold text-gray-800 mb-2">📬 เรื่องร้องเรียน / ติดต่อ</h2>
            <div id="reportList" class="space-y-3">Loading...</div>
        </div>

        <div id="view-settings" class="hidden space-y-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm space-y-4">
                <h2 class="font-bold text-gray-800">⚙️ ตั้งค่าระบบ</h2>
                <a href="../../setup/rich_menu.php" target="_blank" class="block w-full bg-gray-800 text-white text-center py-6 rounded-xl shadow-lg hover:bg-black transition">
                    <div class="text-3xl mb-2">📱</div>
                    <div class="font-bold text-lg">อัปเดต Rich Menu</div>
                    <div class="text-xs text-gray-400 mt-1">กดเพื่อรีเซ็ตเมนูไลน์ของทุกคนใหม่</div>
                </a>
                <p class="text-xs text-gray-400 text-center">ใช้เมื่อมีการเปลี่ยนรูปเมนูหรือเปลี่ยนลิงก์</p>
            </div>
        </div>
    </div>

    <div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-sm p-6 shadow-2xl">
            <h3 class="font-bold text-lg mb-4">✏️ แก้ไขข้อมูล</h3>
            <input type="hidden" id="edit-id">
            <div class="space-y-3 text-sm">
                <div><label class="text-xs text-gray-500">ชื่อ-นามสกุล</label><input id="edit-name" class="w-full border p-2 rounded bg-gray-50"></div>
                <div><label class="text-xs text-gray-500">Username</label><input id="edit-username" class="w-full border p-2 rounded bg-gray-50"></div>
                <div><label class="text-xs text-gray-500">สถานะ</label><select id="edit-role" class="w-full border p-2 rounded bg-gray-50"><option value="student">นิสิต</option><option value="teacher">อาจารย์</option><option value="admin">Admin</option></select></div>
                <div><label class="text-xs text-gray-500">รหัสนิสิต</label><input id="edit-stdId" class="w-full border p-2 rounded bg-gray-50"></div>
            </div>
            <div class="flex gap-2 mt-4"><button onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 py-2 bg-gray-200 rounded">ยกเลิก</button><button onclick="saveUser()" class="flex-1 py-2 bg-blue-600 text-white rounded">บันทึก</button></div>
        </div>
    </div>

    <div id="replyModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-sm p-6 shadow-2xl">
            <h3 class="font-bold text-lg mb-2">💬 ตอบกลับข้อความ</h3>
            <p id="reply-to-name" class="text-xs text-gray-500 mb-3"></p>
            <input type="hidden" id="reply-report-id"><input type="hidden" id="reply-target-id">
            <textarea id="reply-msg" rows="4" class="w-full border p-2 rounded bg-gray-50 mb-4" placeholder="พิมพ์ข้อความตอบกลับ..."></textarea>
            <div class="flex gap-2"><button onclick="document.getElementById('replyModal').classList.add('hidden')" class="flex-1 py-2 bg-gray-200 rounded">ยกเลิก</button><button onclick="sendReply()" class="flex-1 py-2 bg-green-600 text-white rounded">ส่งคำตอบ</button></div>
        </div>
    </div>

    <script>
        // LIFF ID หน้า Admin
        const LIFF_ID = "2008573640-Xlr1jY4w"; 
        let allUsers = [];

        async function main() { 
            try {
                await liff.init({ liffId: LIFF_ID }); 
                if (!liff.isLoggedIn()) liff.login(); 
                loadAll();
            } catch (err) { console.error(err); }
        }
        main();

        function switchTab(t) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('[id^="view-"]').forEach(v => v.classList.add('hidden'));
            document.getElementById('tab-'+t).classList.add('active');
            document.getElementById('view-'+t).classList.remove('hidden');
        }

        function loadAll() { loadUsers(); loadReports(); }

        // --- USERS ---
        async function loadUsers() {
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/admin_api.php', { action: 'get_all_users', line_id: pf.userId });
                if(res.data.status==='success') {
                    allUsers = res.data.users;
                    document.getElementById('statTotal').innerText = res.data.stats.total;
                    document.getElementById('statTeacher').innerText = res.data.stats.teacher;
                    document.getElementById('statStudent').innerText = res.data.stats.student;
                    filterUsers();
                }
            } catch(e){}
        }

        function filterUsers() {
            const txt = document.getElementById('searchInput').value.toLowerCase();
            const role = document.getElementById('roleFilter').value;
            const list = document.getElementById('userList');
            list.innerHTML = '';

            const filtered = allUsers.filter(u => (u.name.toLowerCase().includes(txt)||u.username.toLowerCase().includes(txt)) && (role==='all'||u.role===role));
            
            if(filtered.length === 0) {
                list.innerHTML = '<p class="text-center text-gray-400 mt-5">ไม่พบข้อมูล</p>';
                return;
            }

            filtered.forEach(u => {
                const isActive = u.active==1;
                list.innerHTML += `
                <div class="bg-white p-3 rounded-lg shadow-sm border ${isActive?'border-gray-100':'border-red-200 bg-red-50'} flex justify-between items-center">
                    <div>
                        <div class="font-bold text-sm text-gray-800">${u.name} <span class="text-[10px] bg-gray-100 text-gray-500 px-1 rounded uppercase">${u.role}</span></div>
                        <div class="text-xs text-gray-500">${u.username} ${u.student_id?'| '+u.student_id:''}</div>
                        ${u.line_user_id ? `<div class="text-[10px] text-green-600 mt-0.5">🟢 ผูกไลน์แล้ว</div>` : '<div class="text-[10px] text-gray-300">⚪ ยังไม่ผูกไลน์</div>'}
                    </div>
                    <div class="flex flex-col gap-1 items-end">
                        <button onclick="toggleActive(${u.id})" class="text-[10px] px-2 py-0.5 rounded border min-w-[50px] ${isActive?'text-green-600 border-green-200 hover:bg-green-50':'text-red-600 border-red-200 bg-white hover:bg-red-50'}">${isActive?'Active':'Banned'}</button>
                        <div class="flex gap-1 mt-1">
                            <button onclick="openEdit(${u.id})" class="bg-blue-50 text-blue-500 p-1.5 rounded hover:bg-blue-100 transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                            ${u.role!=='admin'?`<button onclick="delUser(${u.id})" class="bg-red-50 text-red-500 p-1.5 rounded hover:bg-red-100 transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>`:''}
                        </div>
                    </div>
                </div>`;
            });
        }
        
        async function toggleActive(id) { if(confirm("เปลี่ยนสถานะ?")) { await apiCall('toggle_status',{user_id:id}); loadUsers(); }}
        async function delUser(id) { if(confirm("ยืนยันการลบผู้ใช้ถาวร?")) { await apiCall('delete_user',{user_id:id}); loadUsers(); }}
        
        function openEdit(id) {
            const u = allUsers.find(x=>x.id===id);
            document.getElementById('edit-id').value=u.id; document.getElementById('edit-name').value=u.name;
            document.getElementById('edit-username').value=u.username; document.getElementById('edit-role').value=u.role;
            document.getElementById('edit-stdId').value=u.student_id||'';
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        async function saveUser() {
            await apiCall('update_user', {
                user_id: document.getElementById('edit-id').value, name: document.getElementById('edit-name').value,
                username: document.getElementById('edit-username').value, role: document.getElementById('edit-role').value,
                student_id: document.getElementById('edit-stdId').value
            });
            document.getElementById('editModal').classList.add('hidden'); loadUsers();
        }

        // --- BROADCAST ---
        async function sendBroadcast() {
            const msg = document.getElementById('bc-msg').value;
            if(!msg || !confirm("ยืนยันการส่งประกาศ?")) return;
            const res = await apiCall('broadcast', { target_role: document.getElementById('bc-target').value, message: msg });
            if(res.status==='success') { alert(`ส่งสำเร็จ (${res.count} คน)`); document.getElementById('bc-msg').value=''; }
            else { alert(res.message); }
        }

        // --- REPORTS ---
        async function loadReports() {
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/admin_api.php', { action: 'get_reports', line_id: pf.userId });
                const list = document.getElementById('reportList'); list.innerHTML = '';
                if(res.data.reports.length === 0) { list.innerHTML = '<p class="text-center text-gray-400 mt-5">ไม่มีข้อความใหม่</p>'; return; }
                
                res.data.reports.forEach(r => {
                    const isRep = r.status==='replied';
                    list.innerHTML += `
                    <div class="bg-white p-4 rounded-xl shadow-sm border ${isRep?'border-gray-100 opacity-60':'border-blue-200'}">
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-bold bg-gray-100 px-2 py-0.5 rounded text-gray-600">${r.topic}</span>
                            <span class="text-[10px] text-gray-400">${r.created_at}</span>
                        </div>
                        <p class="text-sm text-gray-800 mb-2">${r.message}</p>
                        <div class="flex justify-between items-center border-t pt-2 mt-2">
                            <div class="text-xs text-gray-500">
                                จาก: <span class="font-bold">${r.sender_name}</span> <br>
                                โทร: ${r.phone}
                            </div>
                            ${!isRep ? `<button onclick="openReply(${r.id}, '${r.sender_line_id}', '${r.sender_name}')" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-full shadow hover:bg-blue-700">ตอบกลับ</button>` : '<span class="text-xs text-green-600 font-bold">✓ ตอบแล้ว</span>'}
                        </div>
                    </div>`;
                });
            } catch(e){}
        }

        function openReply(rid, uid, name) {
            document.getElementById('reply-report-id').value = rid; document.getElementById('reply-target-id').value = uid;
            document.getElementById('reply-to-name').innerText = 'ถึง: ' + name;
            document.getElementById('replyModal').classList.remove('hidden');
        }
        
        async function sendReply() {
            const msg = document.getElementById('reply-msg').value;
            if(!msg) return;
            await apiCall('reply_report', { 
                report_id: document.getElementById('reply-report-id').value, 
                target_line_id: document.getElementById('reply-target-id').value, 
                message: msg 
            });
            document.getElementById('replyModal').classList.add('hidden'); document.getElementById('reply-msg').value='';
            loadReports(); alert("ส่งคำตอบแล้ว");
        }

        async function apiCall(act, data={}) {
            const pf = await liff.getProfile();
            const res = await axios.post('../../api/admin_api.php', { ...data, action: act, line_id: pf.userId });
            return res.data;
        }
    </script>
</body>
</html>