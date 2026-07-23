<?php
include 'db.php';

if (!isset($_GET['machine_id']) || !is_numeric($_GET['machine_id'])) {
    header("Location: 404.php");
    exit;
}

$machine_id = intval($_GET['machine_id']);

$sql = "
    SELECT 
        m.ip_address,
        m.hostname,
        r.repair_date,
        r.problem,
        r.solution,
        r.technician,
        r.note
    FROM repairs r
    JOIN machines m ON r.machine_id = m.machine_id
    WHERE m.machine_id = ?
    ORDER BY r.repair_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$result = $stmt->get_result();

/* ===== ตั้งค่า CSV ===== */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=repair_history.csv');
// บอก Excel ว่าเป็น UTF-8
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

fputcsv($output, [
    'IP Address',
    'Hostname',
    'วันที่ซ่อม',
    'ปัญหา',
    'วิธีแก้',
    'ช่าง',
    'หมายเหตุ'
]);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
