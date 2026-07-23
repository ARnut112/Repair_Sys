<?php
include 'db.php';

// ค่าค้นหา
$search = $_GET['search'] ?? "";

// Department mapping
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

// Function to get department name
function getDepartmentName($dept) {
    global $dept_options;
    return isset($dept_options[$dept]) ? $dept_options[$dept] : $dept;
}

// ดึงข้อมูล + นับจำนวนครั้งที่ซ่อม
$sql = "
SELECT 
    m.*,
    COUNT(r.repair_id) AS repair_count
FROM machines m
LEFT JOIN repairs r ON m.machine_id = r.machine_id
WHERE 
    m.hostname LIKE ? OR
    m.ip_address LIKE ? OR
    m.department LIKE ?
GROUP BY m.machine_id
ORDER BY m.department, m.hostname
";

$stmt = $conn->prepare($sql);
$keyword = "%$search%";
$stmt->bind_param("sss", $keyword, $keyword, $keyword);
$stmt->execute();
$machines = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบจัดการประวัติการซ่อมคอมพิวเตอร์</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-tools"></i>
                <span>ระบบจัดการซ่อม</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item active">
                <i class="fas fa-list"></i> รายการเครื่อง
            </a>
            <a href="Sheet_Json/index.php" class="nav-item">
                <i class="fas fa-list"></i> ดูรายการซ่อม
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="site-header">
            <div class="header-content">
                <div>
                    <h1>จัดการข้อมูลเครื่องคอมพิวเตอร์</h1>
                    <p class="header-subtitle">ตรวจสอบและบันทึกประวัติการซ่อมบำรุง</p>
                </div>
                <div class="header-info">
                    <div class="info-box">
                        <span class="info-label">จำนวนเครื่องทั้งหมด</span>
                        <span class="info-value"><?php echo $machines->num_rows; ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Container with Toolbar -->
        <div class="container">
            <!-- Toolbar -->
            <div class="toolbar">
                <form method="get" class="search-form">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input 
                            id="search" 
                            type="text" 
                            name="search" 
                            placeholder="ค้นหา hostname, IP Address หรือแผนก..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    <button type="submit" class="btn btn-search">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                    <?php if ($search): ?>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> ล้าง
                    </a>
                    <?php endif; ?>
                </form>
                <a href="add_machine.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> เพิ่มเครื่องใหม่
                </a>
            </div>

            <!-- Table Section -->
            <div class="table-container">
                <div class="table-header">
                    <h2><i class="fas fa-server"></i> รายการเครื่อง</h2>
                    <span class="record-count"><?php echo $machines->num_rows; ?> รายการ</span>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="col-hostname">
                                    <i class="fas fa-desktop"></i> Hostname
                                </th>
                                <th class="col-ip">
                                    <i class="fas fa-network-wired"></i> IP Address
                                </th>
                                <th class="col-dept">
                                    <i class="fas fa-building"></i> แผนก
                                </th>
                                <th class="col-count" style="text-align:center;">
                                    <i class="fas fa-wrench"></i> จำนวนครั้งซ่อม
                                </th>
                                <th class="col-actions">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($machines->num_rows == 0): ?>
                            <tr class="empty-row">
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>ไม่พบข้อมูลเครื่องคอมพิวเตอร์</p>
                                        <a href="add_machine.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus"></i> เพิ่มเครื่องใหม่
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>

                            <?php while ($row = $machines->fetch_assoc()): ?>
                            <tr class="data-row">
                                <td class="col-hostname">
                                    <strong><?php echo htmlspecialchars($row['hostname']); ?></strong>
                                </td>
                                <td class="col-ip">
                                    <code><?php echo htmlspecialchars($row['ip_address']); ?></code>
                                </td>
                                <td class="col-dept">
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars(getDepartmentName($row['department'])); ?>
                                    </span>
                                </td>
                                <td class="col-count" style="text-align:center;">
                                    <span class="repair-badge">
                                        <?php echo $row['repair_count']; ?> ครั้ง
                                    </span>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        <a href="repairs.php?id=<?php echo $row['machine_id']; ?>" 
                                           class="btn btn-sm btn-info" title="ดูประวัติ">
                                            <i class="fas fa-history"></i> ประวัติ
                                        </a>
                                        <a href="edit_machine.php?id=<?php echo $row['machine_id']; ?>" 
                                           class="btn btn-sm btn-warning" title="แก้ไข">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>
                                        <a href="delete_machine.php?id=<?php echo $row['machine_id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('⚠️ ต้องการลบเครื่องและประวัติการซ่อมทั้งหมด ใช่หรือไม่?');"
                                           title="ลบ">
                                            <i class="fas fa-trash"></i> ลบ
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <footer class="site-footer">
                <p>&copy; <?php echo date('Y'); ?> <strong>ระบบจัดการประวัติการซ่อมคอมพิวเตอร์</strong></p>
                <p class="footer-note">สำหรับการสอบถามติดต่อ ศูนย์บริการเทคนิก</p>
            </footer>
        </div>
    </div>
</div>

</body>
</html>
