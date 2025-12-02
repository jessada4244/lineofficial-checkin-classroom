
async function openSettings() {
    document.getElementById('settingsModal').classList.remove('hidden');
    
    // โหลดข้อมูล
    try {
        const profile = await liff.getProfile();
        // Set Image
        if(profile.pictureUrl) document.getElementById('set-img').src = profile.pictureUrl;
        
        // Call API
        // Note: path อาจต้องปรับขึ้นลงตามความลึกของไฟล์ที่เรียกใช้ 
        // ปกติเราเรียกจาก liff/role/xxx.php ซึ่งอยู่ลึก 2 ชั้นจาก root
        // ดังนั้น api จะอยู่ที่ ../../api/user_api.php
        const res = await axios.post('../../api/user_setting.php', {
            action: 'get_profile',
            line_id: profile.userId
        });

        if (res.data.status === 'success') {
            const u = res.data.data;
            document.getElementById('set-name').innerText = u.name;
            document.getElementById('set-username').innerText = u.username;
            document.getElementById('set-role').innerText = u.role;
            document.getElementById('set-phone').innerText = u.phone || '-';
            
            // Show ID based on role
            if (u.role === 'student') {
                document.getElementById('lbl-id').innerText = 'รหัสนิสิต';
                document.getElementById('set-id').innerText = u.student_id;
            } else {
                document.getElementById('lbl-id').innerText = 'User ID';
                document.getElementById('set-id').innerText = u.id; // หรือจะใช้ username ก็ได้
            }

            // Pre-fill Edit Form
            document.getElementById('ep-name').value = u.name;
            document.getElementById('ep-phone').value = u.phone || '';
        }
    } catch (e) {
        console.error(e);
        alert("โหลดข้อมูลไม่สำเร็จ");
    }
}

function closeSettings() {
    document.getElementById('settingsModal').classList.add('hidden');
}

function openEditProfile() {
    document.getElementById('editProfileModal').classList.remove('hidden');
}

function openChangePass() {
    document.getElementById('changePassModal').classList.remove('hidden');
}

async function saveProfile() {
    const name = document.getElementById('ep-name').value;
    const phone = document.getElementById('ep-phone').value;
    
    if(!name) return alert("กรุณากรอกชื่อ");
    
    try {
        const profile = await liff.getProfile();
        const res = await axios.post('../../api/user_setting.php', {
            action: 'update_profile',
            line_id: profile.userId,
            name: name,
            phone: phone
        });
        
        if(res.data.status === 'success') {
            alert("บันทึกข้อมูลเรียบร้อย");
            document.getElementById('editProfileModal').classList.add('hidden');
            openSettings(); // Reload info
            // Optional: Reload parent page info if needed
             if(document.getElementById('studentName')) document.getElementById('studentName').innerText = "สวัสดี, " + name; // For student page
        } else {
            alert(res.data.message);
        }
    } catch(e) { alert("Server Error"); }
}

async function savePassword() {
    const oldP = document.getElementById('cp-old').value;
    const newP = document.getElementById('cp-new').value;
    const confP = document.getElementById('cp-conf').value;
    
    if(!oldP || !newP) return alert("กรอกข้อมูลให้ครบ");
    if(newP !== confP) return alert("รหัสผ่านใหม่ไม่ตรงกัน");
    
    try {
        const profile = await liff.getProfile();
        const res = await axios.post('../../api/user_setting.php', {
            action: 'change_password',
            line_id: profile.userId,
            old_pass: oldP,
            new_pass: newP
        });
        
        if(res.data.status === 'success') {
            alert("✅ เปลี่ยนรหัสผ่านสำเร็จ!");
            document.getElementById('changePassModal').classList.add('hidden');
            // Clear inputs
            document.getElementById('cp-old').value = '';
            document.getElementById('cp-new').value = '';
            document.getElementById('cp-conf').value = '';
        } else {
            alert("❌ " + res.data.message);
        }
    } catch(e) { alert("Server Error"); }
}