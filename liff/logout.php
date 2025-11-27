<?php
session_start();
// ล้างตัวแปร Session ทั้งหมด
session_unset();
// ทำลาย Session
session_destroy();

// ส่งกลับไปหน้า Login
header("Location: login.php");
exit;
?>