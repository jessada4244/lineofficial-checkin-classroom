<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.tailwindcss.com"></script> </head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-4 text-center">เข้าสู่ระบบ</h2>
        <div id="status" class="text-red-500 text-sm mb-4 text-center"></div>

        <form id="loginForm">
            <input type="text" id="username" placeholder="Username" class="w-full border p-2 mb-4 rounded" required>
            <input type="password" id="password" placeholder="Password" class="w-full border p-2 mb-4 rounded" required>
            <button type="submit" class="w-full bg-green-500 text-white p-2 rounded hover:bg-green-600">Login</button>
        </form>
    </div>

    <script>
        // *** ใส่ LIFF ID ของคุณตรงนี้ ***
        const MY_LIFF_ID = "2008562649-3z1WPZD2"; 

        async function main() {
            await liff.init({ liffId: MY_LIFF_ID });
            if (!liff.isLoggedIn()) {
                liff.login();
            }
        }
        main();

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            try {
                const profile = await liff.getProfile(); // ดึง UserID ไลน์
                const userId = profile.userId;

                document.getElementById('status').innerText = "กำลังตรวจสอบ...";

                // ส่งข้อมูลไปที่ Backend (api/login.php)
                // *** แก้ URL ให้ตรงกับ Ngrok ของคุณ ***
                const response = await fetch('../api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password, lineUserId: userId })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert('เข้าสู่ระบบสำเร็จ! (' + result.role + ')');
                    liff.closeWindow(); // ปิดหน้าต่าง LIFF
                } else {
                    document.getElementById('status').innerText = result.message;
                }

            } catch (err) {
                console.error(err);
                document.getElementById('status').innerText = "เกิดข้อผิดพลาด: " + err.message;
            }
        });
    </script>
</body>
</html>