<?php require_once '../../config/security.php'; checkLogin('admin'); ?>
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
                <button onclick="window.location.reload()" class="text-gray-400 hover:text-blue-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg></button>
                
                <button onclick="window.location.href='../settings.php'" class="bg-white p-2 rounded-full shadow-sm text-gray-600 hover:text-blue-600 transition">
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
            <button onclick="switchTab('settings')" id="tab-settings" class="tab-btn flex-1 py-3 text-xs font-bold whitespace-nowrap px-4">⚙️ ตั้งค่า</button>
        </div>
    </div>
    
    <div class="p-4">
        <div id="view-users" class="space-y-4">
             <div id="userList" class="space-y-3">Loading...</div>
        </div>
        </div>
    
    <?php include '../../assets/components/settings_modal.html'; ?>
    <script>
        const LIFF_ID = "2008573640-Xlr1jY4w"; 
        let allUsers = [];
        async function main() { await liff.init({ liffId: LIFF_ID }); if (!liff.isLoggedIn()) liff.login(); loadAll(); }
        main();
        // ... (Functions เดิมทั้งหมด: switchTab, loadUsers, etc.) ...
        function switchTab(t) { document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active')); document.querySelectorAll('[id^="view-"]').forEach(v => v.classList.add('hidden')); document.getElementById('tab-'+t).classList.add('active'); document.getElementById('view-'+t).classList.remove('hidden'); }
        function loadAll() { loadUsers(); loadReports(); }
        async function loadUsers() { try{const pf=await liff.getProfile();const res=await axios.post('../../api/admin_api.php',{action:'get_all_users',line_id:pf.userId});if(res.data.status==='success'){allUsers=res.data.users;filterUsers();}}catch(e){}}
        function filterUsers() { const list=document.getElementById('userList'); list.innerHTML=''; allUsers.forEach(u=>{list.innerHTML+=`<div>${u.name}</div>`;}); /* (Simplified for brevity) */ }
        // ...
    </script>
    <script src="../../assets/js/user_settings.js"></script>
</body>
</html>