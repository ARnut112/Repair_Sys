<?php
 $csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTSjf_ppDd3pJ3KrHeP99nI0J-l8jne8GyawbZfj42M5DP8xdh4dg7ifxeW4iirvQbIM99DhNDXaDYA/pub?gid=1095863488&single=true&output=csv";
function getDataFromCsv($url) {
    $data = '';
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
        
        $data = curl_exec($ch);
        
        if (curl_errno($ch)) {
            // ถ้า cURL ล้มเหลว จะลองวิธีที่ 2
            // echo "cURL Error: " . curl_error($ch); // ใช้สำหรับ Debug
            $data = false;
        }
        curl_close($ch);
    }

    // วิธีที่ 2: ใช้ file_get_contents (ถ้า cURL ไม่ทำงาน)
    if ($data === false || empty($data)) {
        if (ini_get('allow_url_fopen')) {
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "User-Agent: Mozilla/5.0\r\n" // ปลอมตัวเป็น Browser เผื่อ Google บล็อก bot
                ]
            ];
            $context = stream_context_create($opts);
            $data = @file_get_contents($url, false, $context);
        }
    }

    // ถ้าดึงไม่ได้จริงๆ
    if ($data === false || empty($data)) {
        return []; // คืนค่า Array ว่าง
    }

    // แปลง String เป็น Array
    $rows = explode("\n", $data);
    $csvData = [];
    foreach ($rows as $row) {
        if (!empty(trim($row))) {
            $csvData[] = str_getcsv($row);
        }
    }
    return $csvData;
}

 $allData = getDataFromCsv($csvUrl);
 $headers = array_shift($allData); // ตัดหัวตารางออก

// ตัวแปรสำหรับ Filter
 $filterDevice = isset($_GET['device']) ? $_GET['device'] : '';
 $filterType = isset($_GET['type']) ? $_GET['type'] : '';
 $searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';

// เก็บค่า Unique สำหรับทำ Dropdown Filter
 $allDevices = [];
 $allTypes = [];

// ตัวแปรสำหรับสรุปผล
 $stats = [
    'total_records' => 0,
    'total_minutes' => 0,
    'by_device' => [],
    'by_type' => [],
    'filtered_logs' => []
];

// 3. ประมวลผลและกรองข้อมูล
foreach ($allData as $row) {
    // ตรวจสอบ Index ข้อมูล (0:วันที่, 1:หัวข้อ, 2:ประเภท, 3:อุปกรณ์, 4:เวลา)
    // ป้องกัน Error หากข้อมูลไม่ครบ
    $date = isset($row[0]) ? $row[0] : '-';
    $topic = isset($row[1]) ? $row[1] : '-';
    $type = isset($row[2]) ? $row[2] : '-';
    $device = isset($row[3]) ? $row[3] : '-';
    $duration_str = isset($row[4]) ? $row[4] : '0 นาที';

    // เก็บค่าลงใน Array สำหรับ Dropdown (ก่อนกรอง)
    if (!empty($device) && !in_array($device, $allDevices)) $allDevices[] = $device;
    if (!empty($type) && !in_array($type, $allTypes)) $allTypes[] = $type;

    // --- Logic การกรองข้อมูล ---
    $passFilter = true;

    if (!empty($filterDevice) && $device != $filterDevice) {
        $passFilter = false;
    }
    if (!empty($filterType) && $type != $filterType) {
        $passFilter = false;
    }
    if (!empty($searchKeyword)) {
        // ค้นหาในหัวข้อหรืออุปกรณ์
        if (stripos($topic, $searchKeyword) === false && stripos($device, $searchKeyword) === false) {
            $passFilter = false;
        }
    }

    // ถ้าผ่านการกรอง ค่อยนำไปคำนวณสถิติ
    if ($passFilter) {
        // แปลงเวลา
        $minutes = 0;
        if (preg_match('/(\d+)/', $duration_str, $matches)) {
            $minutes = intval($matches[1]);
        }

        $record = [
            'date' => $date,
            'topic' => $topic,
            'type' => $type,
            'device' => $device,
            'duration_str' => $duration_str,
            'minutes' => $minutes
        ];

        $stats['filtered_logs'][] = $record;
        $stats['total_records']++;
        $stats['total_minutes'] += $minutes;

        // นับสถิติกราฟ
        if (!isset($stats['by_device'][$device])) $stats['by_device'][$device] = 0;
        $stats['by_device'][$device]++;

        if (!isset($stats['by_type'][$type])) $stats['by_type'][$type] = 0;
        $stats['by_type'][$type]++;
    }
}

// คำนวณเฉลี่ย
 $avgTime = $stats['total_records'] > 0 ? round($stats['total_minutes'] / $stats['total_records'], 2) : 0;

// ฟังก์ชันคิดเปอร์เซ็นต์
function calculatePercent($value, $total) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100);
}

// 4. ส่วน Export Excel (CSV)
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_export.csv"');
    
    $output = fopen('php://output', 'w');
    // เขียน Header (ถ้ามีภาษาไทย อาจต้องใช้ fwrite พิมพ์ BOM \xEF\xBB\xBF ก่อน เพื่อให้อ่านภาษาไทยได้ใน Excel)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); 
    fputcsv($output, ['วันที่', 'หัวข้อ', 'ประเภท', 'อุปกรณ์', 'เวลา']);
    
    foreach ($stats['filtered_logs'] as $row) {
        fputcsv($output, [$row['date'], $row['topic'], $row['type'], $row['device'], $row['duration_str']]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - รายงานการใช้งาน</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-dark: #1e3a8a;
            --bg-light: #f5f7fa;
            --bg-white: #ffffff;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --danger: #dc2626;
            --warning: #f59e0b;
            --success: #10b981;
            --info: #0ea5e9;
            --radius: 8px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * { 
            box-sizing: border-box; 
            margin: 0;
            padding: 0;
        }
        
        body { 
            font-family: 'Prompt', 'Sarabun', sans-serif; 
            background: var(--bg-light); 
            color: var(--text-dark); 
            line-height: 1.6;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            z-index: 100;
        }

        .sidebar-header {
            padding: 28px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .sidebar-nav {
            padding: 12px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 3px solid transparent;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-left-color: #ffffff;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: var(--bg-light);
        }

        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 30px;
        }

        /* Header Section */
        header { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white; 
            padding: 30px; 
            border-radius: 10px; 
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 20px;
        }
        
        .header-title h1 { 
            margin: 0; 
            font-size: 28px; 
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .header-title p { 
            margin: 8px 0 0; 
            opacity: 0.95; 
            font-size: 14px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Button Styles */
        .btn { 
            padding: 10px 18px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600; 
            border: none; 
            cursor: pointer; 
            transition: all 0.3s ease;
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            font-size: 14px;
            white-space: nowrap;
        }
        
        .btn-light { 
            background: rgba(255, 255, 255, 0.95); 
            color: var(--primary);
            border: 1px solid #fff;
        }
        
        .btn-light:hover { 
            background: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-export { 
            background: var(--success); 
            color: white; 
            border: none;
        }
        
        .btn-export:hover { 
            background: #229954;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .btn-clear {
            background: transparent;
            color: #e74c3c;
            text-decoration: underline;
            font-size: 13px;
            padding: 8px 12px;
            margin-left: 10px;
        }
        
        .btn-clear:hover {
            color: #c0392b;
            background: rgba(231, 76, 60, 0.05);
        }

        /* Filter Bar */
        .filter-bar { 
            background: var(--bg-white); 
            padding: 20px; 
            border-radius: 10px; 
            margin-bottom: 25px; 
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        
        .filter-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 15px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .form-group { 
            display: flex; 
            flex-direction: column; 
            gap: 6px;
        }
        
        .form-group label { 
            font-size: 13px; 
            font-weight: 700; 
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .form-control { 
            padding: 10px 12px; 
            border: 1px solid var(--border-color); 
            border-radius: 6px; 
            font-family: inherit;
            font-size: 14px;
            min-width: 160px;
            background: white;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
        
        .filter-btn-group {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }
        
        /* Cards */
        .summary-cards { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px; 
            margin-bottom: 30px;
        }
        
        .card { 
            background: var(--bg-white); 
            padding: 22px; 
            border-radius: 10px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 4px solid var(--primary);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        .card.export-card {
            border-top-color: var(--success);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .card h3 { 
            margin: 0 0 12px; 
            font-size: 13px; 
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .card .number { 
            font-size: 32px; 
            font-weight: bold; 
            color: var(--primary);
        }
        
        .card .sub-text { 
            font-size: 12px; 
            color: var(--text-muted); 
            margin-top: 6px;
        }

        /* Grid & Charts */
        .dashboard-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px; 
            margin-bottom: 30px;
        }
        
        .chart-box { 
            background: var(--bg-white); 
            padding: 22px; 
            border-radius: 10px; 
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        
        .chart-box h3 { 
            margin-top: 0; 
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px; 
            font-size: 16px;
            color: var(--primary);
            font-weight: 700;
        }
        
        .bar-chart-row { 
            margin-bottom: 16px; 
            display: flex; 
            align-items: center;
            gap: 10px;
        }
        
        .bar-label { 
            width: 140px; 
            font-size: 13px; 
            text-align: right;
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
            color: var(--text-muted);
        }
        
        .bar-track { 
            flex-grow: 1; 
            background: var(--bg-light); 
            height: 24px; 
            border-radius: 12px; 
            overflow: hidden;
        }
        
        .bar-fill { 
            height: 100%; 
            background: linear-gradient(90deg, var(--primary-light), var(--primary));
            width: 0%; 
            transition: width 0.6s ease;
        }
        
        .bar-value { 
            width: 45px; 
            text-align: right;
            font-size: 13px; 
            font-weight: 700;
            color: var(--primary);
        }

        /* Table */
        .table-container { 
            background: var(--bg-white); 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }
        
        .table-container h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--primary);
            font-size: 16px;
            font-weight: 700;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse;
        }
        
        th, td { 
            padding: 14px 16px; 
            text-align: left; 
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }
        
        th { 
            background: var(--bg-light); 
            color: var(--primary); 
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.3px;
        }
        
        tr:hover { 
            background: var(--bg-light);
            transition: background 0.2s;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .badge { 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            background: rgba(30, 64, 175, 0.1);
            color: var(--primary);
            font-weight: 700;
            display: inline-block;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }

            .main-content {
                margin-left: 240px;
            }

            .container {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }

            .main-content {
                margin-left: 0;
            }

            header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .header-title h1 {
                font-size: 24px;
            }

            .filter-controls {
                flex-direction: column;
            }

            .form-control {
                min-width: 100%;
            }

            .filter-btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px 12px;
            }

            .container {
                padding: 12px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item active">
            <i class="fas fa-chart-bar"></i> รายงาน
        </a>
        <a href="Full_repair.php" class="nav-item">
            <i class="fas fa-list"></i> รายการทั้งหมด
        </a>
        <hr style="border: 1px solid rgba(255,255,255,0.1); margin: 15px 0;">
        <a href="/repair_system/index.php" class="nav-item">
            <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
        </a>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content">
    <div class="container">
        <header>
            <div class="header-title">
                <h1><i class="fas fa-chart-bar"></i> รายงานการใช้งานอุปกรณ์</h1>
                <p>ระบบวิเคราะห์และรายงานสรุปผล</p>
            </div>
        </header>

        <!-- Filter Bar -->
        <form method="GET" action="" class="filter-bar">
            <p class="filter-title"><i class="fas fa-filter"></i> ตัวกรองข้อมูล</p>
            <div class="filter-controls">
                <div class="form-group">
                    <label><i class="fas fa-search"></i> ค้นหา (หัวข้อ/อุปกรณ์)</label>
                    <input type="text" name="search" class="form-control" placeholder="พิมพ์คำค้นหา..." value="<?php echo htmlspecialchars($searchKeyword); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-laptop"></i> อุปกรณ์</label>
                    <select name="device" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($allDevices as $dev): ?>
                            <option value="<?php echo htmlspecialchars($dev); ?>" <?php echo ($filterDevice == $dev) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dev); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> ประเภท</label>
                    <select name="type" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($allTypes as $typ): ?>
                            <option value="<?php echo htmlspecialchars($typ); ?>" <?php echo ($filterType == $typ) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($typ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-btn-group">
                    <button type="submit" class="btn btn-light" style="background:var(--primary-light); color:white;">
                        <i class="fas fa-check"></i> ตกลง
                    </button>
                    <a href="?" class="btn-clear"><i class="fas fa-times"></i> ล้างค่า</a>
                </div>
            </div>
        </form>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="card">
                <h3><i class="fas fa-list"></i> รายการที่พบ</h3>
                <div class="number"><?php echo number_format($stats['total_records']); ?></div>
                <div class="sub-text">รายการ (ตามการค้นหา)</div>
            </div>
            <div class="card">
                <h3><i class="fas fa-clock"></i> เวลารวม</h3>
                <div class="number"><?php echo number_format($stats['total_minutes']); ?></div>
                <div class="sub-text">นาที</div>
            </div>
            <div class="card">
                <h3><i class="fas fa-chart-line"></i> เวลาเฉลี่ย</h3>
                <div class="number"><?php echo number_format($avgTime, 1); ?></div>
                <div class="sub-text">นาที / รายการ</div>
            </div>
            <div class="card export-card">
                <h3><i class="fas fa-download"></i> ส่งออกข้อมูล</h3>
                <a href="?export=csv&device=<?php echo htmlspecialchars($filterDevice);?>&type=<?php echo htmlspecialchars($filterType);?>&search=<?php echo htmlspecialchars($searchKeyword);?>" class="btn btn-export" style="justify-content: center; width: fit-content;">
                    <i class="fas fa-file-excel"></i> ดาวน์โหลด Excel
                </a>
            </div>
        </div>

        <!-- Charts -->
        <div class="dashboard-grid">
            <div class="chart-box">
                <h3><i class="fas fa-box"></i> สถิติตามอุปกรณ์</h3>
                <?php 
                arsort($stats['by_device']);
                if (empty($stats['by_device'])) echo "<p style='color:#999;font-size:13px; text-align:center; padding:20px 0;'>ไม่มีข้อมูล</p>";
                foreach ($stats['by_device'] as $device => $count) : 
                    $percent = calculatePercent($count, $stats['total_records']);
                ?>
                <div class="bar-chart-row">
                    <div class="bar-label" title="<?php echo htmlspecialchars($device); ?>"><?php echo htmlspecialchars($device); ?></div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                    <div class="bar-value"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="chart-box">
                <h3><i class="fas fa-tag"></i> สถิติตามประเภท</h3>
                <?php 
                arsort($stats['by_type']);
                if (empty($stats['by_type'])) echo "<p style='color:#999;font-size:13px; text-align:center; padding:20px 0;'>ไม่มีข้อมูล</p>";
                foreach ($stats['by_type'] as $type => $count) : 
                    $percent = calculatePercent($count, $stats['total_records']);
                ?>
                <div class="bar-chart-row">
                    <div class="bar-label" title="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?php echo $percent; ?>%; background: linear-gradient(90deg, #9c27b0, #673ab7);"></div>
                    </div>
                    <div class="bar-value"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            <h3><i class="fas fa-table"></i> รายการละเอียด (Top 50 รายการล่าสุด)</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;"><i class="fas fa-calendar"></i> วัน/เดือน/ปี</th>
                        <th style="width: 20%;"><i class="fas fa-heading"></i> หัวข้อ</th>
                        <th style="width: 15%;"><i class="fas fa-label"></i> ประเภท</th>
                        <th style="width: 30%;"><i class="fas fa-laptop"></i> อุปกรณ์</th>
                        <th style="width: 15%;"><i class="fas fa-hourglass"></i> เวลา</th>
                        <th style="width: 10%; text-align:center;"><i class="fas fa-number"></i> ลำดับ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // ฟังก์ชันแปลงวว/เดือน/ปี เป็นตัวเลขเพื่อเปรียบเทียบ
                    $parseDate = function($dateStr) {
                        if (empty($dateStr)) return 0;
                        $parts = explode("/", trim($dateStr));
                        if (count($parts) != 3) return 0;
                        
                        $day = intval($parts[0]);
                        $month = intval($parts[1]);
                        $year = intval($parts[2]);
                        
                        // แปลง 259 เป็น 2025, 260 เป็น 2026 เป็นต้น (กรณี Buddhist calendar)
                        if ($year > 100 && $year < 200) {
                            $year += 1900;
                        }
                        
                        return mktime(0, 0, 0, $month, $day, $year);
                    };
                    
                    // แสดงแค่ 50 รายการล่าสุดเพื่อไม่ให้หน้าเว็บช้า
                    // เรียงลำดับตามวันที่ล่าสุดอยู่ด้านบน
                    usort($stats['filtered_logs'], function($a, $b) use ($parseDate) {
                        $dateA = $parseDate($a['date']);
                        $dateB = $parseDate($b['date']);
                        return $dateB <=> $dateA; // ล่าสุดอยู่ด้านบน
                    });
                    
                    $displayLogs = array_slice($stats['filtered_logs'], 0, 50); 
                    $rowNum = 1;
                    
                    if (empty($displayLogs)) {
                        echo "<tr><td colspan='6' style='text-align:center; padding:40px 20px; color:var(--text-muted);'>ไม่พบข้อมูลที่ตรงกับเงื่อนไข</td></tr>";
                    } else {
                        foreach ($displayLogs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['date']); ?></td>
                            <td><?php echo htmlspecialchars($log['topic']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($log['type']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['device']); ?></td>
                            <td><?php echo htmlspecialchars($log['duration_str']); ?></td>
                            <td style="text-align:center; color:var(--text-muted);"><?php echo $rowNum++; ?></td>
                        </tr>
                        <?php endforeach;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>