<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

// department map (keep synchronized with other pages)
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

// ดึงข้อมูลเครื่องเพื่อแสดงหัวเรื่อง
$stmt = $conn->prepare("SELECT hostname, ip_address, department FROM machines WHERE machine_id = ?");
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$machine = $stmt->get_result()->fetch_assoc();
if (!$machine) {
    die("ไม่พบข้อมูลเครื่อง");
}

$errors = [];
$success = false;
$insert_id = null;

// ค่าเริ่มต้น
$repair_date = date('Y-m-d');
$problem = $solution = $method = $technician = $note = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repair_date = trim($_POST['repair_date'] ?? $repair_date);
    $problem = trim($_POST['problem'] ?? '');
    $solution = trim($_POST['solution'] ?? '');
    $method = trim($_POST['method'] ?? '');
    $technician = trim($_POST['technician'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($repair_date === '') {
        $errors[] = 'กรุณาเลือกวันที่ซ่อม';
    }
    if ($problem === '') {
        $errors[] = 'กรุณากรอกปัญหาที่พบ';
    }

    if (empty($errors)) {
        $sql = "INSERT INTO repairs (machine_id, repair_date, problem, solution, method, technician, note) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = 'SQL error: ' . $conn->error;
        } else {
            $stmt->bind_param(
                "issssss",
                $machine_id,
                $repair_date,
                $problem,
                $solution,
                $method,
                $technician,
                $note
            );
            if ($stmt->execute()) {
                $insert_id = $conn->insert_id;
                // เปลี่ยนเสน่ห์: ส่งกลับไปยังหน้าประวัติการซ่อมหลังบันทึกสำเร็จ
                header('Location: repairs.php?id=' . $machine_id);
                exit;
            } else {
                $errors[] = 'เกิดข้อผิดพลาดในการบันทึก';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>บันทึกประวัติการซ่อม</title>
<style>
:root{--bg:#f5f6f8;--card:#ffffff;--accent:#0b3d91;--muted:#6b6f76;--success:#197b30;--danger:#b02a37;--border:#e6e9ee;--shadow:0 2px 6px rgba(11,61,145,0.06)}
body{font-family:Arial, Helvetica, 'Noto Sans Thai', sans-serif;background:var(--bg);color:#1f2933;margin:0;padding:28px}
.container{max-width:820px;margin:0 auto}
.card{background:var(--card);padding:24px;border-radius:8px;border:1px solid var(--border);box-shadow:var(--shadow)}
.header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px}
.title{font-size:1.25rem;font-weight:700;color:#0b2340}
.meta{color:var(--muted);font-size:0.95rem;margin-top:6px}
.machine-info{border:1px solid var(--border);padding:12px;border-radius:6px;background:#fafbfc;margin-bottom:14px}
.dl{display:grid;grid-template-columns:120px 1fr;gap:8px;align-items:center}
.form-group{margin-bottom:12px}
label{display:block;font-weight:600;margin-bottom:6px;color:#0b2340}
input[type=date],input[type=text],textarea{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;background:#fff;color:#0b2340}
textarea{min-height:120px}
.small{color:var(--muted);font-size:0.9rem}
.actions{display:flex;gap:10px;margin-top:14px}
.btn{padding:10px 14px;border-radius:6px;border:1px solid transparent;cursor:pointer;font-weight:600}
.btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
.btn-secondary{background:#fff;color:#0b2340;border-color:var(--border)}
.btn-ghost{background:transparent;color:var(--accent);border:0}
.alert{padding:12px;border-radius:6px;margin-bottom:12px;font-size:0.95rem}
.alert-error{background:#fff5f6;border:1px solid rgba(176,42,55,0.08);color:var(--danger)}
.alert-success{background:#f5fff6;border:1px solid rgba(25,123,48,0.08);color:var(--success)}
@media (max-width:600px){.header{flex-direction:column}}
</style>
</head>
<body>
<div class="container">
<div class="card">
<div class="header">
<div>
<div class="title">บันทึกประวัติการซ่อม</div>
<div class="meta">กรุณากรอกข้อมูลการซ่อมของอุปกรณ์</div>
</div>
<div>
<a class="btn btn-ghost" href="repairs.php?id=<?= $machine_id ?>">กลับไปยังประวัติการซ่อม</a>
</div>
</div>

<div class="machine-info" role="group" aria-label="ข้อมูลเครื่อง">
<div class="dl"><div class="small">ชื่อเครื่อง</div><div><?= htmlspecialchars($machine['hostname']) ?></div></div>
<div class="dl" style="margin-top:6px"><div class="small">IP</div><div><?= htmlspecialchars($machine['ip_address']) ?></div></div>
<div class="dl" style="margin-top:6px"><div class="small">แผนก</div><div><?= htmlspecialchars(getDepartmentName($machine['department'])) ?></div></div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <strong>กรุณาตรวจสอบข้อมูลต่อไปนี้:</strong>
        <ul style="margin:8px 0 0 18px;">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success" role="status">
        บันทึกข้อมูลเรียบร้อยแล้ว
        <div style="margin-top:8px">
            <a class="btn btn-secondary" href="repairs.php?id=<?= $machine_id ?>">ไปที่ประวัติการซ่อม</a>
            <?php if ($insert_id): ?>
                <a class="btn btn-primary" href="view_repair.php?id=<?= $insert_id ?>">ดูรายละเอียด</a>
                <a class="btn btn-ghost" href="add_repair.php?id=<?= $machine_id ?>">บันทึกประวัติเพิ่มเติม</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<form method="post" novalidate>
    <div class="form-group">
        <label for="repair_date">วันที่ซ่อม <span class="small" style="color:var(--danger)">* จำเป็น</span></label>
        <input id="repair_date" type="date" name="repair_date" required value="<?= htmlspecialchars($repair_date) ?>">
    </div>

    <div class="form-group">
        <label for="problem">รายละเอียดปัญหา <span class="small" style="color:var(--danger)">* จำเป็น</span></label>
        <textarea id="problem" name="problem" required placeholder="โปรดระบุปัญหาอย่างละเอียดเพื่อการวิเคราะห์ที่ถูกต้อง"><?= htmlspecialchars($problem) ?></textarea>
        <div class="small" style="margin-top:6px">ข้อควรระบุ: อาการ, ข้อความผิดพลาด, เงื่อนไขที่เกิดขึ้น</div>
    </div>

    <div class="form-group">
        <label for="solution">สรุปวิธีแก้</label>
        <textarea id="solution" name="solution" placeholder="สรุปขั้นตอนหรือการดำเนินการที่ใช้แก้ปัญหา"><?= htmlspecialchars($solution) ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
            <label for="method">วิธีการดำเนินการ</label>
            <input id="method" type="text" name="method" value="<?= htmlspecialchars($method) ?>" placeholder="เช่น เปลี่ยนฮาร์ดแวร์/ติดตั้งซอฟต์แวร์">
        </div>
        <div class="form-group">
            <label for="technician">ผู้ดำเนินการ</label>
            <input id="technician" type="text" name="technician" value="<?= htmlspecialchars($technician) ?>" placeholder="ชื่อผู้ดำเนินการ" >
        </div>
    </div>

    <div class="form-group">
        <label for="note">หมายเหตุเพิ่มเติม</label>
        <textarea id="note" name="note" placeholder="หมายเหตุอื่นๆ ที่จำเป็น"><?= htmlspecialchars($note) ?></textarea>
    </div>

    <div class="actions">
        <button class="btn btn-primary" type="submit">บันทึก</button>
        <button class="btn btn-secondary" type="button" onclick="history.back()">ยกเลิก</button>
    </div>
</form>

</div>
</div>
</body>
</html>
