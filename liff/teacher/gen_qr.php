<?php
require_once '../../config/security.php';
checkLogin('teacher'); // บังคับว่าเป็น teacher เท่านั้น
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code เช็คชื่อ</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap'); body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gray-800 h-screen flex flex-col items-center justify-center p-4">

    <div class="bg-white p-8 rounded-3xl shadow-2xl text-center max-w-sm w-full">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">สแกนเพื่อเช็คชื่อ</h1>
        <p class="text-gray-500 mb-6" id="className">กำลังโหลด...</p>
        
        <div id="qrcode" class="flex justify-center mb-6 border-4 border-gray-100 p-2 rounded-lg inline-block"></div>
        
        <p class="text-xs text-red-500 mt-2">* QR Code นี้จะเปลี่ยนทุกครั้งที่รีเฟรชหน้าจอ</p>
        
        <button onclick="window.history.back()" class="mt-6 w-full py-3 bg-gray-100 rounded-xl font-bold text-gray-600 hover:bg-gray-200 transition">ย้อนกลับ</button>
    </div>

    <script>
        // *** ใช้ LIFF ID เดียวกับ manage_class.php ***
        const LIFF_ID = "2008573640-qQxJWXLz"; 
        const CLASS_ID = (new URLSearchParams(window.location.search)).get('class_id');

        async function main() {
            if (!CLASS_ID) return alert("ไม่พบรหัสวิชา");
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) liff.login();
            generateQR();
        }
        main();

        async function generateQR() {
            try {
                const profile = await liff.getProfile();
                
                // เรียก API สร้าง Token ใหม่
                const res = await axios.post('../../api/teacher_api.php', {
                    action: 'generate_qr',
                    line_id: profile.userId,
                    class_id: CLASS_ID
                });

                if (res.data.status === 'success') {
                    document.getElementById('className').innerText = res.data.subject_name;
                    
                    // ข้อมูลใน QR เป็น JSON String
                    const qrData = JSON.stringify({
                        class_id: CLASS_ID,
                        token: res.data.qr_token
                    });

                    // สร้าง QR Code
                    document.getElementById('qrcode').innerHTML = "";
                    new QRCode(document.getElementById("qrcode"), {
                        text: qrData,
                        width: 220,
                        height: 220,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });
                } else {
                    alert("ไม่สามารถสร้าง QR ได้: " + res.data.message);
                }
            } catch (err) {
                console.error(err);
                alert("Server Error");
            }
        }
    </script>
</body>
</html>