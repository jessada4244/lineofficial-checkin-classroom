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

    <div class="p-4 max-w-7xl mx-auto">
        
        <div id="view-users">
            <div class="bg-white p-4 rounded-xl shadow-sm mb-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-gray-500 mb-1">ค้นหา</label>
                    <input type="text" id="searchInput" onkeyup="filterUsers()" placeholder="ไอดี, ชื่อ, เบอร์..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="w-32">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Role</label>
                    <select id="roleFilter" onchange="filterUsers()" class="w-full border rounded-lg px-2 py-2 text-sm outline-none">
                        <option value="all">ทั้งหมด</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Status</label>
                    <select id="statusFilter" onchange="filterUsers()" class="w-full border rounded-lg px-2 py-2 text-sm outline-none">
                        <option value="all">ทั้งหมด</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden overflow-x-auto">
                <table class="min-w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 uppercase font-bold text-xs">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Edu-ID</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Fullname</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Line</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Manage</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody" class="divide-y divide-gray-100">
                        <tr><td colspan="9" class="text-center py-10 text-gray-400">กำลังโหลดข้อมูล...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="view-broadcast" class="hidden space-y-4 max-w-lg mx-auto">
            <div class="bg-white p-5 rounded-2xl shadow-sm">
                <h2 class="font-bold text-gray-800 mb-4 flex gap-2 items-center">
                    <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg> 
                    ส่งประกาศ (Broadcast)
                </h2>
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

        <div id="view-reports" class="hidden space-y-4 max-w-lg mx-auto">
            <h2 class="font-bold text-gray-800 mb-2">📬 เรื่องร้องเรียน / ติดต่อ</h2>
            <div id="reportList" class="space-y-3">
                <p class="text-center text-gray-400 mt-5">กำลังโหลด...</p>
            </div>
        </div>

        <div id="view-settings" class="hidden space-y-4 max-w-lg mx-auto">
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
                <div><label class="text-xs text-gray-500 font-bold">ชื่อ-นามสกุล</label><input id="edit-name" class="w-full border p-2 rounded bg-gray-50"></div>
                <div><label class="text-xs text-gray-500 font-bold">Username</label><input id="edit-username" class="w-full border p-2 rounded bg-gray-50"></div>
                <div><label class="text-xs text-gray-500 font-bold">สถานะ (Role)</label><select id="edit-role" class="w-full border p-2 rounded bg-gray-50"><option value="student">นิสิต</option><option value="teacher">อาจารย์</option><option value="admin">Admin</option></select></div>
                <div><label class="text-xs text-gray-500 font-bold">Edu ID (รหัสนิสิต/อาจารย์)</label><input id="edit-eduId" class="w-full border p-2 rounded bg-gray-50"></div>
                <div><label class="text-xs text-gray-500 font-bold">เบอร์โทร</label><input id="edit-phone" class="w-full border p-2 rounded bg-gray-50"></div>
            </div>
            <div class="flex gap-2 mt-4">
                <button onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 py-2 bg-gray-200 rounded text-gray-600 font-bold">ยกเลิก</button>
                <button onclick="saveUser()" class="flex-1 py-2 bg-blue-600 text-white rounded font-bold">บันทึก</button>
            </div>
        </div>
    </div>

    <div id="replyModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-sm p-6 shadow-2xl">
            <h3 class="font-bold text-lg mb-2">💬 ตอบกลับข้อความ</h3>
            <p id="reply-to-name" class="text-xs text-gray-500 mb-3"></p>
            <input type="hidden" id="reply-report-id"><input type="hidden" id="reply-target-id">
            <textarea id="reply-msg" rows="4" class="w-full border p-2 rounded bg-gray-50 mb-4" placeholder="พิมพ์ข้อความตอบกลับ..."></textarea>
            <div class="flex gap-2">
                <button onclick="document.getElementById('replyModal').classList.add('hidden')" class="flex-1 py-2 bg-gray-200 rounded font-bold text-gray-600">ยกเลิก</button>
                <button onclick="sendReply()" class="flex-1 py-2 bg-green-600 text-white rounded font-bold">ส่งคำตอบ</button>
            </div>
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

        // --- USERS MANAGEMENT (TABLE LOGIC) ---
        async function loadUsers() {
            try {
                const pf = await liff.getProfile();
                const res = await axios.post('../../api/admin_api.php', { action: 'get_all_users', line_id: pf.userId });
                if(res.data.status==='success') {
                    allUsers = res.data.users;
                    filterUsers(); // Render table
                }
            } catch(e) { console.error(e); }
        }

        function filterUsers() {
            const txt = document.getElementById('searchInput').value.toLowerCase();
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '';

            const filtered = allUsers.filter(u => {
                const matchTxt = (u.name+u.username+(u.edu_id||'')+(u.phone||'')).toLowerCase().includes(txt);
                const matchRole = role === 'all' || u.role === role;
                const matchStatus = status === 'all' || (status === 'active' ? u.active == 1 : u.active == 0);
                return matchTxt && matchRole && matchStatus;
            });
            
            if(filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-6 text-gray-400">ไม่พบข้อมูล</td></tr>';
                return;
            }

            filtered.forEach(u => {
                const isActive = u.active == 1;
                const statusClass = isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                const statusText = isActive ? 'Active' : 'Inactive';
                
                let roleClass = 'bg-gray-100 text-gray-600';
                if(u.role === 'admin') roleClass = 'bg-purple-100 text-purple-700';
                if(u.role === 'teacher') roleClass = 'bg-blue-100 text-blue-700';
                if(u.role === 'student') roleClass = 'bg-orange-100 text-orange-700';

                const uidDisplay = u.line_user_id ? `<span class="text-xs font-mono text-gray-500 truncate max-w-[80px] inline-block" title="${u.line_user_id}">${u.line_user_id.substring(0,8)}...</span>` : '<span class="text-gray-300">-</span>';

                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50 transition border-b border-gray-50 last:border-0">
                        <td class="px-4 py-3 font-mono text-gray-400 text-xs">#${u.id}</td>
                        <td class="px-4 py-3 font-bold text-gray-700">${u.edu_id || '-'}</td>
                        <td class="px-4 py-3 text-gray-600">${u.username}</td>
                        <td class="px-4 py-3 font-bold text-gray-800">${u.name}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">${u.phone || '-'}</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase ${roleClass}">${u.role}</span></td>
                        <td class="px-4 py-3">${uidDisplay}</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 rounded text-[10px] font-bold cursor-pointer hover:opacity-80 ${statusClass}" onclick="toggleStatus(${u.id})">${statusText}</span></td>
                        <td class="px-4 py-3 text-center flex justify-center gap-2">
                            <button onclick="openEdit(${u.id})" class="bg-blue-50 text-blue-500 p-1.5 rounded hover:bg-blue-100 transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                            <button onclick="delUser(${u.id})" class="bg-red-50 text-red-500 p-1.5 rounded hover:bg-red-100 transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </td>
                    </tr>`;
            });
        }
        
        async function toggleStatus(id) { if(confirm("เปลี่ยนสถานะ Active/Inactive?")) { await apiCall('toggle_status',{user_id:id}); loadUsers(); }}
        async function delUser(id) { if(confirm("ยืนยันการลบผู้ใช้ถาวร? ข้อมูลการเข้าเรียนจะหายไปด้วย")) { await apiCall('delete_user',{user_id:id}); loadUsers(); }}
        
        function openEdit(id) {
            const u = allUsers.find(x=>x.id===id);
            document.getElementById('edit-id').value=u.id; 
            document.getElementById('edit-name').value=u.name;
            document.getElementById('edit-username').value=u.username; 
            document.getElementById('edit-role').value=u.role;
            document.getElementById('edit-eduId').value=u.edu_id||''; // ใช้ edu_id ตาม DB ใหม่
            document.getElementById('edit-phone').value=u.phone||'';
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        async function saveUser() {
            await apiCall('update_user', {
                user_id: document.getElementById('edit-id').value, 
                name: document.getElementById('edit-name').value,
                username: document.getElementById('edit-username').value, 
                role: document.getElementById('edit-role').value,
                edu_id: document.getElementById('edit-eduId').value, // ส่ง edu_id
                phone: document.getElementById('edit-phone').value
            });
            document.getElementById('editModal').classList.add('hidden'); 
            loadUsers();
        }

        // --- BROADCAST & REPORTS ---
        async function sendBroadcast() {
            const msg = document.getElementById('bc-msg').value;
            if(!msg || !confirm("ยืนยันการส่งประกาศ?")) return;
            const res = await apiCall('broadcast', { target_role: document.getElementById('bc-target').value, message: msg });
            if(res.status==='success') { alert(`ส่งสำเร็จ (${res.count} คน)`); document.getElementById('bc-msg').value=''; }
            else { alert(res.message); }
        }

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