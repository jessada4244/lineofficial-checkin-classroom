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
        /* Toggle Switch Style */
        .toggle-checkbox:checked { right: 0; border-color: #68D391; }
        .toggle-checkbox:checked + .toggle-label { background-color: #68D391; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen pb-20">

    <div class="bg-white shadow-sm sticky top-0 z-20">
        <div class="px-4 py-3 flex justify-between items-center">
            <h1 class="text-lg font-bold text-gray-800">🛠️ Admin Dashboard</h1>
            <div class="flex gap-2">
                <button onclick="loadUsers()" class="text-gray-400 hover:text-blue-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg></button>
                <a href="../logout.php" onclick="return confirm('ออกจากระบบ?')" class="text-red-400 hover:text-red-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg></a>
            </div>
        </div>
        <div class="flex border-t border-gray-100">
            <button onclick="switchTab('users')" id="tab-users" class="tab-btn active flex-1 py-3 text-sm text-gray-500">ผู้ใช้งาน</button>
            <button onclick="switchTab('settings')" id="tab-settings" class="tab-btn flex-1 py-3 text-sm text-gray-500">ตั้งค่า</button>
        </div>
    </div>

    <div class="p-4">
        <div id="view-users" class="space-y-4">
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-white p-2 rounded-lg text-center shadow-sm"><p class="text-[10px] text-gray-400">ทั้งหมด</p><p id="statTotal" class="font-bold text-blue-600">-</p></div>
                <div class="bg-white p-2 rounded-lg text-center shadow-sm"><p class="text-[10px] text-gray-400">อาจารย์</p><p id="statTeacher" class="font-bold text-green-600">-</p></div>
                <div class="bg-white p-2 rounded-lg text-center shadow-sm"><p class="text-[10px] text-gray-400">นิสิต</p><p id="statStudent" class="font-bold text-purple-600">-</p></div>
            </div>

            <div class="flex gap-2">
                <input type="text" id="searchInput" onkeyup="filterUsers()" placeholder="🔍 ชื่อ, User, ID..." class="flex-1 bg-white border border-gray-200 rounded-lg p-2 text-sm focus:outline-none focus:border-blue-500">
                <select id="roleFilter" onchange="filterUsers()" class="bg-white border border-gray-200 rounded-lg p-2 text-sm focus:outline-none">
                    <option value="all">ทั้งหมด</option>
                    <option value="teacher">อาจารย์</option>
                    <option value="student">นิสิต</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div id="userList" class="space-y-3 pb-20"></div>
        </div>

        <div id="view-settings" class="hidden">
            <a href="../../setup/rich_menu.php" target="_blank" class="block w-full bg-gray-800 text-white text-center py-4 rounded-xl shadow-md">
                📱 อัปเดต Rich Menu
            </a>
        </div>
    </div>

    <div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-2xl">
            <h2 class="text-lg font-bold mb-4">แก้ไขข้อมูลผู้ใช้</h2>
            <input type="hidden" id="edit-id">
            
            <div class="space-y-3">
                <div><label class="text-xs text-gray-500">ชื่อ-สกุล</label><input type="text" id="edit-name" class="w-full border p-2 rounded"></div>
                <div><label class="text-xs text-gray-500">Username</label><input type="text" id="edit-username" class="w-full border p-2 rounded"></div>
                <div><label class="text-xs text-gray-500">สิทธิ์ (Role)</label>
                    <select id="edit-role" class="w-full border p-2 rounded">
                        <option value="student">นิสิต</option>
                        <option value="teacher">อาจารย์</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div><label class="text-xs text-gray-500">รหัสนิสิต (ถ้ามี)</label><input type="text" id="edit-studentId" class="w-full border p-2 rounded"></div>
            </div>

            <div class="flex gap-2 mt-6">
                <button onclick="closeEditModal()" class="flex-1 py-2 bg-gray-200 rounded-lg">ยกเลิก</button>
                <button onclick="saveUser()" class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-bold">บันทึก</button>
            </div>
        </div>
    </div>

    <script>
        const LIFF_ID = "2008573640-Xlr1jY4w"; 
        let allUsers = [];

        async function main() {
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            loadUsers();
        }
        main();

        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            document.getElementById('view-users').classList.toggle('hidden', tab !== 'users');
            document.getElementById('view-settings').classList.toggle('hidden', tab !== 'settings');
        }

        async function loadUsers() {
            try {
                const profile = await liff.getProfile();
                const res = await axios.post('../../api/admin_api.php', { action: 'get_all_users', line_id: profile.userId });
                if (res.data.status === 'success') {
                    allUsers = res.data.users;
                    document.getElementById('statTotal').innerText = res.data.stats.total;
                    document.getElementById('statTeacher').innerText = res.data.stats.teacher;
                    document.getElementById('statStudent').innerText = res.data.stats.student;
                    filterUsers();
                }
            } catch (e) { console.error(e); }
        }

        function filterUsers() {
            const txt = document.getElementById('searchInput').value.toLowerCase();
            const role = document.getElementById('roleFilter').value;
            
            const filtered = allUsers.filter(u => {
                const matchTxt = u.name.toLowerCase().includes(txt) || u.username.toLowerCase().includes(txt) || (u.student_id && u.student_id.includes(txt));
                const matchRole = role === 'all' || u.role === role;
                return matchTxt && matchRole;
            });
            renderList(filtered);
        }

        function renderList(users) {
            const list = document.getElementById('userList');
            list.innerHTML = '';
            users.forEach(u => {
                const isActive = u.active == 1;
                const roleColor = u.role==='admin'?'gray':(u.role==='teacher'?'green':'purple');
                const badge = `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-${roleColor}-100 text-${roleColor}-700 uppercase">${u.role}</span>`;
                const uidDisplay = u.line_user_id ? `<p class="text-[10px] text-gray-400 truncate mt-1">UID: ${u.line_user_id}</p>` : '';

                list.innerHTML += `
                    <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100 relative ${isActive?'':'opacity-60 bg-red-50'}">
                        <div class="flex justify-between items-start">
                            <div class="w-2/3">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-800">${u.name}</span> ${badge}
                                </div>
                                <p class="text-xs text-gray-500">User: ${u.username} ${u.student_id ? '| ID: '+u.student_id : ''}</p>
                                ${uidDisplay}
                            </div>
                            <div class="flex flex-col gap-2 items-end">
                                <button onclick="toggleStatus(${u.id})" class="text-[10px] font-bold px-2 py-1 rounded border ${isActive ? 'border-green-200 text-green-600 bg-green-50' : 'border-red-200 text-red-600 bg-red-100'}">
                                    ${isActive ? 'Active' : 'Banned'}
                                </button>
                                <div class="flex gap-2">
                                    <button onclick="openEdit(${u.id})" class="text-blue-500 bg-blue-50 p-1.5 rounded hover:bg-blue-100">✏️</button>
                                    ${u.role !== 'admin' ? `<button onclick="deleteUser(${u.id})" class="text-red-500 bg-red-50 p-1.5 rounded hover:bg-red-100">🗑️</button>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // --- Actions ---

        async function toggleStatus(id) {
            if(!confirm("ต้องการเปลี่ยนสถานะการเข้าใช้งาน?")) return;
            const profile = await liff.getProfile();
            await axios.post('../../api/admin_api.php', { action: 'toggle_status', line_id: profile.userId, user_id: id });
            loadUsers();
        }

        async function deleteUser(id) {
            if(!confirm("ยืนยันการลบผู้ใช้? ข้อมูลทั้งหมดจะหายไป")) return;
            const profile = await liff.getProfile();
            await axios.post('../../api/admin_api.php', { action: 'delete_user', line_id: profile.userId, user_id: id });
            loadUsers();
        }

        // --- Edit Modal Logic ---

        function openEdit(id) {
            const user = allUsers.find(u => u.id === id);
            document.getElementById('edit-id').value = user.id;
            document.getElementById('edit-name').value = user.name;
            document.getElementById('edit-username').value = user.username;
            document.getElementById('edit-role').value = user.role;
            document.getElementById('edit-studentId').value = user.student_id || '';
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        async function saveUser() {
            const id = document.getElementById('edit-id').value;
            const profile = await liff.getProfile();
            
            await axios.post('../../api/admin_api.php', {
                action: 'update_user',
                line_id: profile.userId,
                user_id: id,
                name: document.getElementById('edit-name').value,
                username: document.getElementById('edit-username').value,
                role: document.getElementById('edit-role').value,
                student_id: document.getElementById('edit-studentId').value
            });
            
            closeEditModal();
            loadUsers();
            alert("บันทึกข้อมูลสำเร็จ");
        }
    </script>
</body>
</html>