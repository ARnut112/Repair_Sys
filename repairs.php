<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

// department mapping (shared logic)
$dept_options = [
    'dept1' => 'IT',
    'dept2' => 'งานประกัน',
    'dept3' => 'Lab',
    'dept4' => 'X-ray',
    'dept5' => 'Supplier',
    'dept6' => 'กายภาพ แพทย์แผนไทย และแผนจีน',
    'dept7' => 'งานการพยาบาลผู้คลอด',
    'dept8' => 'งานการพยาบาลผู้ป่วยนอก',
    'dept9' => 'งานการพยาบาลผู้ป่วยใน',
    'dept10' => 'งานการพยาบาลผู้ป่วยอุบัติเหตุฉุกเฉินและนิติเวช',
    'dept11' => 'ทันตกรรม',
    'dept12' => 'ห้องยา',
    'dept13' => 'ห้องเวช',
    'dept14' => 'ยาเสพติดและมินิธัญลักษณ์',
    'dept15' => 'บริหาร'
];

function getDepartmentName($dept) {
    global $dept_options;
    return isset($dept_options[$dept]) ? $dept_options[$dept] : $dept;
}

if (!isset($_GET['id'])) {
    die("ไม่พบ machine id");
}

$machine_id = intval($_GET['id']);

/* ===== โหลดข้อมูลเครื่อง ===== */
$m = $conn->prepare("SELECT * FROM machines WHERE machine_id = ?");
if (!$m) {
    die("SQL machines error: " . $conn->error);
}
$m->bind_param("i", $machine_id);
$m->execute();
$machine = $m->get_result()->fetch_assoc();

if (!$machine) {
    die("ไม่พบเครื่องในระบบ");
}

/* ===== โหลดประวัติการซ่อม ===== */
$r = $conn->prepare("
    SELECT repair_id, repair_date, problem, solution, method, technician, note
    FROM repairs
    WHERE machine_id = ?
    ORDER BY repair_date DESC
");
if (!$r) {
    die("SQL repairs error: " . $conn->error);
}
$r->bind_param("i", $machine_id);
$r->execute();
$repairs = $r->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ประวัติการซ่อม</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#f4f6fb;--card:#fff;--accent:#0d6efd;--muted:#6c757d;--danger:#dc3545;--shadow:0 8px 24px rgba(13,110,253,0.06)}
*{box-sizing:border-box}
body{font-family:Inter,Segoe UI,Arial,sans-serif;background:var(--bg);color:#222;margin:0;padding:28px}
.container{max-width:1000px;margin:0 auto}
.site-header{margin-bottom:18px}
.site-header h1{margin:0;font-size:1.5rem}
.site-header .muted{color:var(--muted);margin-top:6px}
.toolbar{display:flex;align-items:center;gap:12px;background:transparent;margin-bottom:18px;padding:10px}
.toolbar .muted{color:var(--muted)}
.btn{padding:10px 14px;border-radius:10px;border:0;cursor:pointer;font-weight:700;background:transparent}
.btn-primary{background:var(--accent);color:#fff}
.btn-secondary{background:#eef2ff;color:var(--accent);border:1px solid rgba(13,110,253,0.08)}
.btn-danger{background:#fff6f6;color:var(--danger);border:1px solid rgba(220,53,69,0.12)}
hr{border:0;border-top:1px solid rgba(0,0,0,0.06);margin:12px 0}
.table-wrap{display:grid;grid-template-columns:1fr;gap:14px}
.repair-card{background:var(--card);padding:16px;border-radius:10px;box-shadow:var(--shadow);border:1px solid rgba(15,23,42,0.03)}
.repair-card .meta{color:var(--muted);font-size:0.95rem;margin-bottom:8px}
.repair-card p{margin:8px 0}
.repair-card .actions{display:flex;gap:8px;margin-top:10px}
.muted{color:var(--muted)}
.site-footer{margin-top:22px;color:var(--muted);text-align:center}
@media (min-width:720px){.table-wrap{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<div class="container">
<header class="site-header">
    <h1>ประวัติการซ่อม</h1>
    <p class="muted">จัดการและดูประวัติการซ่อมของเครื่อง</p>
</header>

<div class="toolbar">
    <div style="flex:1;">
        <div class="muted"><strong>Hostname:</strong> <?= htmlspecialchars($machine['hostname']) ?></div>
        <div class="muted"><strong>IP:</strong> <?= htmlspecialchars($machine['ip_address']) ?> &nbsp; <strong>แผนก:</strong> <?= htmlspecialchars(getDepartmentName($machine['department'])) ?></div>
    </div>

    <div style="display:flex;gap:8px;align-items:center;">
        <a href="add_repair.php?id=<?= $machine_id ?>" class="btn btn-primary">➕ เพิ่มประวัติการซ่อม</a>
        <a href="export.php?machine_id=<?= $machine_id ?>" class="btn btn-secondary">📄 Export CSV</a>
        <button onclick="location.href='index.php'" class="btn">↩️ กลับหน้าหลัก</button>
    </div>
</div>

<hr>

<div class="table-wrap">
<?php if ($repairs->num_rows == 0): ?>
    <div class="repair-card muted">ยังไม่มีประวัติการซ่อม</div>
<?php endif; ?>

<?php while ($row = $repairs->fetch_assoc()): ?>
    <div class="repair-card">
        <div class="meta">📅 <?= htmlspecialchars($row['repair_date']) ?> — 👤 <?= htmlspecialchars($row['technician']) ?: 'ไม่ระบุ' ?></div>
        <p><strong>ปัญหา:</strong><br><?= nl2br(htmlspecialchars($row['problem'])) ?></p>
        <p><strong>วิธีแก้:</strong><br><?= nl2br(htmlspecialchars($row['solution'])) ?></p>
        <?php if (!empty($row['method'])): ?><p><strong>วิธีซ่อม:</strong> <?= htmlspecialchars($row['method']) ?></p><?php endif; ?>
        <?php if (!empty($row['note'])): ?><p class="muted"><strong>หมายเหตุ:</strong> <?= nl2br(htmlspecialchars($row['note'])) ?></p><?php endif; ?>
        <div class="actions">
            <a href="view_repair.php?id=<?= $row['repair_id'] ?>" class="btn">👁 ดูรายละเอียด</a>
            <a href="edit_repair.php?id=<?= $row['repair_id'] ?>" class="btn btn-secondary">✏️ แก้ไข</a>
        </div>
    </div>
<?php endwhile; ?>
</div>

<footer class="site-footer">
    <p class="muted">&copy; <?php echo date('Y'); ?> ระบบเช็คประวัติการซ่อม</p>
</footer>
</div> <!-- .container -->

</body>
</html>
