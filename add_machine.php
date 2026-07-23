<?php
include 'db.php';

$errors = [];
$success = false;
$insert_id = null;

// Default IP (fix localhost IPv6)
$ip = $_SERVER['REMOTE_ADDR'] === '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];

// initialize form values
$hostname = '';
$asset = '';
$department = '';
$location = '';
$custom_dept = '';

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

$loc_options = [
    'loc1' => 'อาคารผู้ป่วยนอก',
    'loc2' => 'อาคารทันตกรรม',
    'loc3' => 'อาคารผู้ป่วยใน',
    'loc4' => 'อาคารกายภาพบำบัด',
    'loc5' => 'อาคารพัสดุและบริหาร',
    'loc6' => 'อาคารพระธรรมมุนี94ปี(มินิธัญลักษณ์และยาเสพติด)'
];

if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    $hostname = trim($_POST['hostname'] ?? '');
    $ip = trim($_POST['ip'] ?? $ip);
    $asset = trim($_POST['asset'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $location = trim($_POST['location'] ?? '');

    $custom_dept_post = trim($_POST['custom_department'] ?? '');
    if ($department === 'other' && $custom_dept_post !== '') {
        $department = $custom_dept_post;
    }

    if ($hostname === '') {
        $errors[] = 'กรุณากรอก Hostname';
    }
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        $errors[] = 'กรุณากรอก IP ที่ถูกต้อง';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO machines (hostname, ip_address, asset_number, department, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $hostname, $ip, $asset, $department, $location);
        if ($stmt->execute()) {
            $success = true;
            $insert_id = $conn->insert_id;
            // clear form values after success
            $hostname = $asset = $department = $location = '';
        } else {
            $errors[] = 'เกิดข้อผิดพลาดในการบันทึก';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>เพิ่มเครื่องคอมพิวเตอร์</title>
<style>
:root{--bg:#f4f6fb;--card:#ffffff;--accent:#0d6efd;--muted:#6c757d;--success:#198754;--danger:#dc3545;--shadow:0 6px 18px rgba(13,110,253,0.06)}
*{box-sizing:border-box}
body{font-family:Inter,Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:var(--bg);color:#222;margin:0;padding:28px}
.container{max-width:820px;margin:0 auto}
.card{background:var(--card);padding:24px;border-radius:12px;box-shadow:var(--shadow);border:1px solid rgba(15,23,42,0.04)}
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.title{font-size:1.4rem;font-weight:700;display:flex;align-items:center;gap:10px}
.title svg{width:28px;height:28px}
.subtitle{color:var(--muted);font-size:0.95rem}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:12px}
.form-group{margin-bottom:6px}
label{display:block;font-weight:600;margin-bottom:8px;color:#222}
input[type=text], select{width:100%;padding:12px;border:1px solid #e6e9ef;border-radius:10px;font-size:0.95rem;background:#fff}
select:focus, input[type=text]:focus{outline:none;border-color:var(--accent);box-shadow:0 6px 18px rgba(13,110,253,0.06)}
small{color:var(--muted);display:block;margin-top:6px}
.actions{display:flex;gap:10px;margin-top:14px}
.btn{padding:10px 14px;border-radius:10px;border:0;cursor:pointer;font-weight:700;display:inline-flex;align-items:center;gap:8px}
.btn svg{height:16px;width:16px}
.btn-primary{background:var(--accent);color:#fff}
.btn-secondary{background:#eef2ff;color:var(--accent);border:1px solid rgba(13,110,253,0.08)}
.btn-ghost{background:transparent;color:var(--accent);border:0}
.alert{padding:14px;border-radius:10px;margin-bottom:14px;font-size:0.98rem}
.alert-error{background:#fff6f6;border:1px solid rgba(220,53,69,0.12);color:var(--danger)}
.alert-success{background:#f6fffa;border:1px solid rgba(25,135,84,0.08);color:var(--success)}
.inline-error{color:var(--danger);font-size:0.875rem;margin-top:6px;display:none}
.success-actions{display:flex;gap:8px;margin-top:10px}
@media (max-width:720px){.form-row{grid-template-columns:1fr}.header{flex-direction:column;align-items:flex-start;gap:8px}}
</style>
</head>
<body>
<div class="container">
<div class="card">
<div class="header">
<div>
<div class="title">
<!-- Simple icon -->
<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2l4 4-4 4-4-4 4-4z" stroke="#0d6efd" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
เพิ่มเครื่องคอมพิวเตอร์
</div>
<div class="subtitle">เพิ่มข้อมูลเครื่องในระบบ — กรอกข้อมูลให้ครบถ้วน</div>
</div>
<div class="small">ตรวจสอบข้อมูลให้ถูกต้องก่อนบันทึก</div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <strong>พบข้อผิดพลาด:</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success" role="status">
        <strong>✔ บันทึกเรียบร้อย</strong>
        <div style="margin-top:8px">
            <a class="btn btn-secondary" href="index.php">กลับหน้าแรก</a>
            <a class="btn btn-secondary" href="repairs.php?id=<?= (int)$insert_id ?>">เพิ่มรายการซ่อม</a>
            <a class="btn btn-secondary" href="add_repair.php?id=<?= (int)$insert_id ?>">เพิ่มการแจ้งซ่อม</a>
        </div>
    </div>
<?php endif; ?>

<form id="machineForm" method="post" novalidate>
    <div class="form-row">
        <div class="form-group">
            <label for="hostname">Hostname <span style="color:var(--danger)">*</span></label>
            <input id="hostname" type="text" name="hostname" required value="<?= htmlspecialchars($hostname ?: '') ?>" autofocus>
            <div id="err-hostname" class="inline-error">กรุณากรอก Hostname</div>
            <small>เช่น: PC-001</small>
        </div>

        <div class="form-group">
            <label for="ip">IP เครื่อง <span style="color:var(--danger)">*</span></label>
            <input id="ip" type="text" name="ip" inputmode="numeric" required pattern="\b(?:(?:2[0-4]\d|25[0-5]|[01]?\d?\d)\.){3}(?:2[0-4]\d|25[0-5]|[01]?\d?\d)\b" placeholder="192.168.1.50" value="<?= htmlspecialchars($ip) ?>">
            <div id="err-ip" class="inline-error">กรุณากรอก IP ที่ถูกต้อง (ตัวอย่าง 192.168.1.50)</div>
            <small>รับเฉพาะ IPv4</small>
        </div>
    </div>

    <div class="form-group">
        <label for="asset">เลขครุภัณฑ์</label>
        <input id="asset" type="text" name="asset" value="<?= htmlspecialchars($asset) ?>">
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="department">แผนก</label>
            <select id="department" name="department">
                <option value="" <?= $department === '' ? 'selected' : '' ?>>-- เลือกแผนก --</option>
                <?php foreach ($dept_options as $key => $name): ?>
                    <option value="<?= $key ?>" <?= $department === $key ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
                <option value="other" <?= $department === 'other' ? 'selected' : '' ?>>อื่นๆ</option>
            </select>
            <input id="customDepartment" type="text" name="custom_department" style="display:none;margin-top:8px;width:100%;padding:12px;border:1px solid #e6e9ef;border-radius:10px;font-size:0.95rem;background:#fff" placeholder="กรุณากรอกชื่อแผนก" value="<?= htmlspecialchars($custom_dept) ?>">
            <small>แก้ไขชื่อแผนกในตัวเลือกได้ตามต้องการ</small>
        </div>
        <div class="form-group">
            <label for="location">สถานที่</label>
            <select id="location" name="location">
                <option value="" <?= $location === '' ? 'selected' : '' ?>>-- เลือกสถานที่ --</option>
                <option value="loc1" <?= $location === 'loc1' ? 'selected' : '' ?>>อาคารผู้ป่วยนอก</option>
                <option value="loc2" <?= $location === 'loc2' ? 'selected' : '' ?>>อาคารทันตกรรม</option>
                <option value="loc3" <?= $location === 'loc3' ? 'selected' : '' ?>>อาคารผู้ป่วยใน</option>
                <option value="loc4" <?= $location === 'loc4' ? 'selected' : '' ?>>อาคารกายภาพบำบัด</option>
                <option value="loc5" <?= $location === 'loc5' ? 'selected' : '' ?>>อาคารพัสดุและบริหาร</option>
                <option value="loc6" <?= $location === 'loc6' ? 'selected' : '' ?>>อาคารพระธรรมมุนี94ปี(มินิธัญลักษณ์และยาเสพติด)</option>
            </select>
            <small>แก้ไขชื่อสถานที่ในตัวเลือกได้ตามต้องการ</small>
        </div>
    </div>

    <div class="actions">
        <button id="submitBtn" class="btn btn-primary" type="submit"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 5l7 7-7 7" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>บันทึก</button>
        <a class="btn btn-secondary" href="index.php">ยกเลิก</a>
    </div>
</form>

</div>
</div>

<script>
(function(){
    const form = document.getElementById('machineForm');
    const hostname = document.getElementById('hostname');
    const ip = document.getElementById('ip');
    const submitBtn = document.getElementById('submitBtn');
    const errHostname = document.getElementById('err-hostname');
    const errIp = document.getElementById('err-ip');
    const department = document.getElementById('department');
    const customDepartment = document.getElementById('customDepartment');
    const ipv4Regex = /^(?:(?:2[0-4]\d|25[0-5]|[01]?\d?\d)\.){3}(?:2[0-4]\d|25[0-5]|[01]?\d?\d)$/;

    function showError(el){ el.style.display='block'; }
    function hideError(el){ el.style.display='none'; }

    hostname.addEventListener('input', () => hideError(errHostname));
    ip.addEventListener('input', () => hideError(errIp));

    department.addEventListener('change', function() {
        if (this.value === 'other') {
            customDepartment.style.display = 'block';
            customDepartment.required = true;
        } else {
            customDepartment.style.display = 'none';
            customDepartment.required = false;
            customDepartment.value = '';
        }
    });

    if (department.value === 'other') {
        customDepartment.style.display = 'block';
        customDepartment.required = true;
    }

    form.addEventListener('submit', function(e){
        let hasError = false;
        if (!hostname.value.trim()){
            showError(errHostname); hasError = true;
        }
        if (!ipv4Regex.test(ip.value.trim())){
            showError(errIp); hasError = true;
        }
        if (hasError){
            e.preventDefault();
            const firstError = document.querySelector('.inline-error[style*="display:block"]');
            if (firstError){ firstError.scrollIntoView({behavior:'smooth',block:'center'}); }
            return false;
        }
        // disable submit to prevent double-submit
        submitBtn.disabled = true;
        submitBtn.style.opacity = 0.9;
    });
})();
</script>
</body>
</html>
