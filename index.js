require('dotenv').config();
const express = require('express');
const mysql = require('mysql2');
const line = require('@line/bot-sdk');
const path = require('path');
const bodyParser = require('body-parser');

const app = express();

// --- 1. Middleware ---
app.use(express.static('public')); 
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// --- 2. Database Config ---
const db = mysql.createConnection({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME
});
db.connect((err) => console.log(err ? '❌ DB Error' : '✅ DB Connected'));

// --- 3. LINE Config ---
const lineConfig = {
    channelAccessToken: process.env.CHANNEL_ACCESS_TOKEN,
    channelSecret: process.env.CHANNEL_SECRET
};
const client = new line.Client(lineConfig);

// ================= ROUTES ================= //

// 1. API: ลงทะเบียน
app.post('/api/register', (req, res) => {
    const { lineUserId, fullName, role, academicId } = req.body;
    if (!lineUserId || !fullName || !academicId) return res.status(400).json({ success: false });

    const sql = `INSERT INTO users (line_user_id, full_name, role, academic_id) VALUES (?, ?, ?, ?)`;
    db.query(sql, [lineUserId, fullName, role, academicId], (err, result) => {
        if (err && err.code === 'ER_DUP_ENTRY') return res.json({ success: false, message: 'ลงทะเบียนไปแล้ว' });
        if (err) return res.json({ success: false, message: 'DB Error' });
        
        // Push Message บอก
        client.pushMessage(lineUserId, { type: 'text', text: `ยินดีต้อนรับคุณ ${fullName} ✅` }).catch(e=>{});
        res.json({ success: true });
    });
});

// 2. API: ดึงรายวิชาทั้งหมด
app.get('/api/courses', (req, res) => {
    db.query('SELECT * FROM courses', (err, results) => {
        if (err) return res.json([]);
        res.json(results);
    });
});

// 3. API: สร้าง Session (เปิดคลาส) *** สำคัญ ***
app.post('/api/create-session', (req, res) => {
    const { courseId } = req.body;
    const qrToken = `CLASS_${courseId}_${Date.now()}`; 

    const sql = `INSERT INTO class_sessions (course_id, start_time, qr_token, is_active) VALUES (?, NOW(), ?, 1)`;
    db.query(sql, [courseId, qrToken], (err, result) => {
        if (err) return res.json({ success: false, message: 'DB Error' });
        
        // ต้องส่ง insertId กลับไป เพื่อให้ Frontend เอาไปดึงรายชื่อถูกห้อง
        res.json({ 
            success: true, 
            sessionId: result.insertId, 
            qrToken: qrToken 
        });
    });
});

// 4. API: นิสิตเช็คชื่อ
app.post('/api/check-in', (req, res) => {
    const { lineUserId, qrToken } = req.body;

    // หา Session
    const sqlSession = `SELECT id, course_id FROM class_sessions WHERE qr_token = ? AND is_active = 1`;
    db.query(sqlSession, [qrToken], (err, sessions) => {
        if (err || sessions.length === 0) return res.json({ success: false, message: 'QR Code ไม่ถูกต้อง/หมดอายุ' });
        const sessionId = sessions[0].id;

        // หา User
        db.query(`SELECT id, full_name FROM users WHERE line_user_id = ?`, [lineUserId], (err, users) => {
            if (users.length === 0) return res.json({ success: false, message: 'ยังไม่ลงทะเบียน' });
            const studentId = users[0].id;
            const studentName = users[0].full_name;

            // เช็คซ้ำ
            db.query(`SELECT id FROM attendance_logs WHERE session_id = ? AND student_id = ?`, [sessionId, studentId], (err, logs) => {
                if (logs.length > 0) return res.json({ success: false, message: 'เช็คชื่อไปแล้ว' });

                // บันทึก
                db.query(`INSERT INTO attendance_logs (session_id, student_id, status) VALUES (?, ?, 'present')`, 
                [sessionId, studentId], (err, result) => {
                    if (err) return res.json({ success: false });
                    console.log(`✅ ${studentName} เช็คชื่อสำเร็จ`);
                    res.json({ success: true, studentName: studentName });
                });
            });
        });
    });
});

// 5. API: ดึงรายชื่อคนเข้าเรียน (Real-time) *** สำคัญ ***
app.get('/api/session-logs/:sessionId', (req, res) => {
    const sessionId = req.params.sessionId;
    const sql = `
        SELECT users.full_name, users.academic_id, attendance_logs.check_in_time 
        FROM attendance_logs 
        JOIN users ON attendance_logs.student_id = users.id 
        WHERE attendance_logs.session_id = ? 
        ORDER BY attendance_logs.check_in_time DESC
    `;
    db.query(sql, [sessionId], (err, results) => {
        if (err) return res.json({ success: false });
        res.json({ success: true, logs: results });
    });
});

// --- Routes หน้าเว็บ ---
app.get('/register', (req, res) => res.sendFile(path.join(__dirname, 'public', 'register.html')));
app.get('/teacher', (req, res) => res.sendFile(path.join(__dirname, 'public', 'teacher.html')));
app.get('/student', (req, res) => res.sendFile(path.join(__dirname, 'public', 'student.html')));

// --- Webhook ---
app.post('/webhook', line.middleware(lineConfig), (req, res) => {
    res.json({ status: 'ok' }); // ตอบ OK ไปก่อน ง่ายๆ
});

// --- Start Server ---
const port = process.env.PORT || 3000;
app.listen(port, () => {
    console.log(`🚀 Server running on port ${port}`);
});