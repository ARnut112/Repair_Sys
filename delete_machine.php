<?php
include 'db.php';

if (!isset($_GET['id'])) {
    die("ไม่พบเครื่อง");
}

$id = $_GET['id'];

// ลบประวัติการซ่อมก่อน (สำคัญ)
$conn->query("DELETE FROM repairs WHERE machine_id = $id");

// ลบเครื่อง
$conn->query("DELETE FROM machines WHERE machine_id = $id");

// กลับหน้า index
header("Location: index.php");
exit;
