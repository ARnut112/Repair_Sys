<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

// department mapping
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
    die("ไม่พบ repair id");
}

$repair_id = intval($_GET['id']);

$sql = "
    SELECT r.*, m.hostname, m.ip_address, m.department
    FROM repairs r
    JOIN machines m ON r.machine_id = m.machine_id
    WHERE r.repair_id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $conn->error);
}

$stmt->bind_param("i", $repair_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูลการซ่อม");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>รายละเอียดการซ่อม</title>
<style>
:root{--bg:#f5f6f8;--card:#ffffff;--accent:#0b3d91;--muted:#6b6f76;--danger:#b02a37;--border:#e6e9ee;--shadow:0 2px 6px rgba(11,61,145,0.06)}
body{font-family:Arial, Helvetica, 'Noto Sans Thai', sans-serif;background:var(--bg);color:#1f2933;margin:0;padding:28px}
.container{max-width:820px;margin:0 auto}
.card{background:var(--card);padding:24px;border-radius:8px;border:1px solid var(--border);box-shadow:var(--shadow)}
.header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px}
.title{font-size:1.25rem;font-weight:700;color:#0b2340}
.meta{color:var(--muted);font-size:0.95rem;margin-top:6px}
.machine-info{border:1px solid var(--border);padding:12px;border-radius:6px;background:#fafbfc;margin-bottom:14px}
.dl{display:grid;grid-template-columns:120px 1fr;gap:8px;align-items:center}
.small{color:var(--muted);font-size:0.9rem}
.info-label{font-weight:600;color:#0b2340}
.section{background:#fafbfc;border:1px solid var(--border);padding:12px;border-radius:6px;margin-bottom:12px}
.section-title{font-weight:700;color:#0b2340;margin-bottom:8px}
.section-content{line-height:1.6;white-space:pre-wrap;word-break:break-word}
.actions{display:flex;gap:10px;margin-top:14px}
.btn{padding:10px 14px;border-radius:6px;border:1px solid transparent;cursor:pointer;font-weight:600;flex:1;text-align:center;text-decoration:none;display:inline-block}
.btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
.btn-secondary{background:#fff;color:#0b2340;border-color:var(--border)}
.btn-ghost{background:transparent;color:var(--accent);border:0;flex:0}
@media (max-width:600px){.header{flex-direction:column}.actions{flex-direction:column}}
</style>
</head>
<body>
<div class="container">
<div class="card">
<div class="header">
<div>
<div class="title">รายละเอียดการซ่อม</div>
<div class="meta">ดูข้อมูลเพิ่มเติมเกี่ยวกับการซ่อม</div>
</div>
<div>
<a class="btn btn-ghost" href="repairs.php?id=<?= $data['machine_id'] ?>">กลับไปยังประวัติการซ่อม</a>
</div>
</div>

<div class="machine-info" role="group" aria-label="ข้อมูลเครื่อง">
<div class="dl"><div class="small">ชื่อเครื่อง</div><div><?= htmlspecialchars($data['hostname']) ?></div></div>
<div class="dl" style="margin-top:6px"><div class="small">IP</div><div><?= htmlspecialchars($data['ip_address']) ?></div></div>
<div class="dl" style="margin-top:6px"><div class="small">แผนก</div><div><?= htmlspecialchars(getDepartmentName($data['department'])) ?></div></div>
<div class="dl" style="margin-top:6px"><div class="small">วันที่ซ่อม</div><div><?= htmlspecialchars($data['repair_date']) ?></div></div>
<div class="dl" style="margin-top:6px"><div class="small">ผู้ดำเนินการ</div><div><?= htmlspecialchars($data['technician']) ?></div></div>
</div>

<div class="section">
<div class="section-title">❌ ปัญหาที่พบ</div>
<div class="section-content"><?= htmlspecialchars($data['problem']) ?></div>
</div>

<div class="section">
<div class="section-title">✅ วิธีแก้ไข</div>
<div class="section-content"><?= htmlspecialchars($data['solution']) ?></div>
</div>

<div class="section">
<div class="section-title">🔧 วิธีการดำเนินการ</div>
<div class="section-content"><?= htmlspecialchars($data['method'] ?? '-') ?></div>
</div>

<?php if (!empty($data['note'])): ?>
<div class="section">
<div class="section-title">📝 หมายเหตุ</div>
<div class="section-content"><?= htmlspecialchars($data['note']) ?></div>
</div>
<?php endif; ?>

<div class="actions">
<a class="btn btn-primary" href="edit_repair.php?id=<?= $data['repair_id'] ?>">✏️ แก้ไข</a>
<button class="btn btn-secondary" onclick="history.back()">⬅️ ยกเลิก</button>
</div>

</div>
</div>
</body>
</html>
