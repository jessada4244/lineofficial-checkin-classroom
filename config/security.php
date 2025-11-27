<?php
// config/security.php
session_start();

function checkLogin($requiredRole = null) {
    // 1. เช็คว่ามี Session หรือไม่
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        // ถ้าไม่มี ให้ดีดกลับไปหน้า Login
        // ใช้ JavaScript Redirect เพราะบางที Header PHP อาจทำงานไม่ทันใน LIFF iframe
        echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = '../login.php';</script>";
        exit;
    }

    // 2. เช็ค Role (ถ้ามีการระบุ Role ที่ต้องการ)
    if ($requiredRole !== null && $_SESSION['role'] !== $requiredRole) {
        echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.history.back();</script>";
        exit;
    }
}
?>