<?php
session_start();
date_default_timezone_set('Asia/Tehran');

$db_file = __DIR__ . '/wireguard_data.json';

function loadData() {
    global $db_file;
    if (file_exists($db_file)) {
        $data = json_decode(file_get_contents($db_file), true);
        if ($data === null) {
            return ['limit' => 10, 'used' => 0, 'configs' => [], 'sessions' => []];
        }
        return $data;
    }
    return ['limit' => 10, 'used' => 0, 'configs' => [], 'sessions' => []];
}

function saveData($data) {
    global $db_file;
    file_put_contents($db_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generateRandomName($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $name = '';
    for ($i = 0; $i < $length; $i++) {
        $name .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $name;
}

function detectDevice() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    if (preg_match('/iPhone/i', $user_agent)) return 'iPhone';
    if (preg_match('/iPad/i', $user_agent)) return 'iPad';
    if (preg_match('/Android/i', $user_agent)) return 'Android';
    if (preg_match('/Macintosh/i', $user_agent)) return 'Mac';
    if (preg_match('/Windows/i', $user_agent)) return 'Windows';
    if (preg_match('/Linux/i', $user_agent)) return 'Linux';
    
    return 'Unknown';
}

if (isset($_GET['create'])) {
    $count = intval($_GET['create']);
    if ($count <= 0) {
        die('تعداد نامعتبر است!');
    }
    
    $data = loadData();
    
    if ($data['used'] + $count > $data['limit']) {
        echo '<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حد مجاز تمام شده</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #d32f2f;
            margin-bottom: 20px;
            font-size: 28px;
        }
        p {
            color: #555;
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .telegram-link {
            display: inline-block;
            background: #0088cc;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .telegram-link:hover {
            background: #006699;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,136,204,0.4);
        }
        @media (max-width: 600px) {
            .container { padding: 30px 20px; }
            h1 { font-size: 24px; }
            p { font-size: 16px; }
            .icon { font-size: 60px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⛔</div>
        <h1>حد مجاز تمام شده است!</h1>
        <p>متأسفانه حد مجاز ساخت کانفیگ شما تمام شده است.<br>برای شارژ مجدد با ادمین تماس بگیرید.</p>
        <a href="https://t.me/anishtayin" class="telegram-link" target="_blank">
            📱 تماس با ادمین
        </a>
    </div>
</body>
</html>';
        exit;
    }
    
    $session_id = session_id();
    $device = detectDevice();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $created_files = [];
    
    $config_dir = __DIR__ . '/configs';
    if (!is_dir($config_dir)) {
        mkdir($config_dir, 0755, true);
    }
    
    for ($i = 0; $i < $count; $i++) {
        $filename = generateRandomName(8) . '.conf';
        $filepath = $config_dir . '/' . $filename;
        
        $config_content = "
        [Interface]
PrivateKey = YoUW+9NC6jkKWgLw4Hhq8k9Y3GdhDasHSKMO/Q5wEmM=
Address = 188.115.92.28/32
MTU = 1299
DNS = 208.67.222.222, 208.67.220.220

[Peer]
PublicKey = Q/P8TOVc8Py9bSxnOxw4/JRa0WME185Yx2Pkfs8VfsA=
Endpoint = 188.115.213.109:51066
AllowedIPs = 172.16.0.2/32, 2606:4700:110:dfa9:91ac:c439:b397:8c12/128
";
        
        file_put_contents($filepath, $config_content);
        $created_files[] = $filename;
        
        $data['configs'][] = [
            'filename' => $filename,
            'created_at' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'device' => $device,
            'session_id' => $session_id
        ];
    }
    
    $data['used'] += $count;
    
    if (!isset($data['sessions'][$session_id])) {
        $data['sessions'][$session_id] = [
            'device' => $device,
            'ip' => $ip,
            'first_visit' => date('Y-m-d H:i:s'),
            'configs_count' => 0
        ];
    }
    $data['sessions'][$session_id]['configs_count'] += $count;
    $data['sessions'][$session_id]['last_visit'] = date('Y-m-d H:i:s');
    
    saveData($data);
    
    echo '<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دانلود کانفیگ‌ها</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
        }
        .success-icon {
            text-align: center;
            font-size: 80px;
            margin-bottom: 20px;
        }
        .config-list {
            margin-top: 30px;
        }
        .config-item {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        .config-item:hover {
            background: #e8f5e9;
            transform: translateX(-5px);
        }
        .config-name {
            font-weight: bold;
            color: #333;
            font-size: 18px;
        }
        .download-btn {
            background: #4CAF50;
            color: white;
            padding: 10px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        .download-btn:hover {
            background: #45a049;
            box-shadow: 0 5px 15px rgba(76,175,80,0.4);
        }
        .stats {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: center;
        }
        .stats p {
            color: #1976d2;
            font-size: 16px;
            margin: 5px 0;
        }
        @media (max-width: 600px) {
            .container { padding: 20px; }
            h1 { font-size: 24px; }
            .config-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .success-icon { font-size: 60px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✅</div>
        <h1>کانفیگ‌ها با موفقیت ساخته شد!</h1>
        
        <div class="config-list">';
    
    foreach ($created_files as $file) {
        echo '<div class="config-item">
                <span class="config-name">📄 ' . htmlspecialchars($file) . '</span>
                <a href="?download=' . urlencode($file) . '" class="download-btn">دانلود</a>
              </div>';
    }
    
    echo '</div>
        
        <div class="stats">
            <p><strong>تعداد ساخته شده:</strong> ' . $count . '</p>
            <p><strong>مانده از حد مجاز:</strong> ' . ($data['limit'] - $data['used']) . '</p>
        </div>
    </div>
</body>
</html>';
    exit;
}

if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = __DIR__ . '/configs/' . $filename;
    
    if (file_exists($filepath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        die('فایل یافت نشد!');
    }
}

if (isset($_GET['admin'])) {
    $data = loadData();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'increase':
                    $amount = intval($_POST['amount']);
                    $data['limit'] += $amount;
                    saveData($data);
                    break;
                case 'decrease':
                    $amount = intval($_POST['amount']);
                    $data['limit'] = max(0, $data['limit'] - $amount);
                    saveData($data);
                    break;
                case 'reset':
                    $data['used'] = 0;
                    saveData($data);
                    break;
            }
            header('Location: ?admin');
            exit;
        }
    }
    
    $unique_devices = [];
    $unique_ips = [];
    $unique_sessions = count($data['sessions']);
    
    foreach ($data['configs'] as $config) {
        if (!in_array($config['device'], $unique_devices)) {
            $unique_devices[] = $config['device'];
        }
        if (!in_array($config['ip'], $unique_ips)) {
            $unique_ips[] = $config['ip'];
        }
    }
    
    echo '<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت WireGuard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        .header h1 {
            color: #1e3c72;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .stat-card h3 {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .stat-card .value {
            color: #1e3c72;
            font-size: 36px;
            font-weight: bold;
        }
        .control-panel {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .control-panel h2 {
            color: #1e3c72;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .control-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .form-group {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
        }
        .form-group label {
            display: block;
            color: #333;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        .btn-success:hover {
            background: #45a049;
        }
        .btn-danger {
            background: #f44336;
            color: white;
        }
        .btn-danger:hover {
            background: #da190b;
        }
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        .btn-warning:hover {
            background: #e68900;
        }
        .logs-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .logs-section h2 {
            color: #1e3c72;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .log-item {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }
        .log-item .time {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .log-item .details {
            color: #333;
            font-size: 16px;
        }
        .devices-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .device-badge {
            background: #2196F3;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .header h1 { font-size: 24px; }
            .stat-card .value { font-size: 28px; }
            .control-form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎛️ پنل مدیریت WireGuard</h1>
            <p>مدیریت کامل کانفیگ‌های WireGuard</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📊</div>
                <h3>حد مجاز کل</h3>
                <div class="value">' . $data['limit'] . '</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">✅</div>
                <h3>استفاده شده</h3>
                <div class="value">' . $data['used'] . '</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">⏳</div>
                <h3>باقی‌مانده</h3>
                <div class="value">' . ($data['limit'] - $data['used']) . '</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">👥</div>
                <h3>کاربران منحصر به فرد</h3>
                <div class="value">' . $unique_sessions . '</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">📱</div>
                <h3>انواع دستگاه</h3>
                <div class="value">' . count($unique_devices) . '</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">🌐</div>
                <h3>IP های منحصر به فرد</h3>
                <div class="value">' . count($unique_ips) . '</div>
            </div>
        </div>
        
        <div class="control-panel">
            <h2>⚙️ تنظیمات حد مجاز</h2>
            <div class="control-form">
                <div class="form-group">
                    <label>افزایش حد مجاز</label>
                    <form method="POST">
                        <input type="hidden" name="action" value="increase">
                        <input type="number" name="amount" placeholder="تعداد افزایش" required min="1">
                        <button type="submit" class="btn btn-success">➕ افزایش</button>
                    </form>
                </div>
                
                <div class="form-group">
                    <label>کاهش حد مجاز</label>
                    <form method="POST">
                        <input type="hidden" name="action" value="decrease">
                        <input type="number" name="amount" placeholder="تعداد کاهش" required min="1">
                        <button type="submit" class="btn btn-danger">➖ کاهش</button>
                    </form>
                </div>
                
                <div class="form-group">
                    <label>ریست استفاده شده</label>
                    <form method="POST" onsubmit="return confirm(\'آیا مطمئن هستید؟\')">
                        <input type="hidden" name="action" value="reset">
                        <p style="color: #666; margin-bottom: 10px;">تعداد استفاده شده را صفر می‌کند</p>
                        <button type="submit" class="btn btn-warning">🔄 ریست</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="logs-section">
            <h2>📋 آمار دستگاه‌ها</h2>
            <div class="devices-list">';
    
    $device_counts = [];
    foreach ($data['configs'] as $config) {
        $device = $config['device'];
        if (!isset($device_counts[$device])) {
            $device_counts[$device] = 0;
        }
        $device_counts[$device]++;
    }
    
    foreach ($device_counts as $device => $count) {
        echo '<div class="device-badge">' . htmlspecialchars($device) . ': ' . $count . '</div>';
    }
    
    echo '</div>
        </div>
        
        <div class="logs-section">
            <h2>📜 آخرین فعالیت‌ها</h2>';
    
    $grouped_logs = [];
    foreach ($data['configs'] as $config) {
        $key = $config['session_id'] . '_' . $config['created_at'];
        if (!isset($grouped_logs[$key])) {
            $grouped_logs[$key] = [
                'time' => $config['created_at'],
                'device' => $config['device'],
                'ip' => $config['ip'],
                'files' => []
            ];
        }
        $grouped_logs[$key]['files'][] = $config['filename'];
    }
    
    $grouped_logs = array_reverse($grouped_logs);
    $shown = 0;
    foreach ($grouped_logs as $log) {
        if ($shown >= 20) break;
        echo '<div class="log-item">
                <div class="time">⏰ زمان: ' . htmlspecialchars($log['time']) . ' | 📱 دستگاه: ' . htmlspecialchars($log['device']) . ' | 🌐 IP: ' . htmlspecialchars($log['ip']) . '</div>
                <div class="details">📊 تعداد: ' . count($log['files']) . ' | 📁 فایل‌ها: (' . implode(', ', array_map('htmlspecialchars', $log['files'])) . ')</div>
              </div>';
        $shown++;
    }
    
    if (count($grouped_logs) == 0) {
        echo '<p style="text-align: center; color: #666;">هنوز هیچ فعالیتی ثبت نشده است.</p>';
    }
    
    echo '</div>
    </div>
</body>
</html>';
    exit;
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سرویس WireGuard Config</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
        }
        .info-box {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .info-box p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 10px;
        }
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
            font-size: 14px;
        }
        .btn-admin {
            display: block;
            background: #667eea;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn-admin:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        @media (max-width: 600px) {
            .container { padding: 30px 20px; }
            h1 { font-size: 24px; }
            .code { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 سرویس WireGuard Config</h1>
        
        <div class="info-box">
            <h3>📖 راهنمای استفاده</h3>
            <p>برای ساخت کانفیگ از لینک زیر استفاده کنید:</p>
            <div class="code">https://domain.com/conf.php?create=تعداد</div>
            <p><strong>مثال:</strong></p>
            <div class="code">https://domain.com/conf.php?create=5</div>
        </div>
        
        <div class="info-box">
            <h3>⚡ ویژگی‌ها</h3>
            <p>✅ ساخت خودکار فایل کانفیگ با نام تصادفی</p>
            <p>✅ محدودیت تعداد ساخت</p>
            <p>✅ پنل مدیریت کامل</p>
            <p>✅ آمار دقیق کاربران و دستگاه‌ها</p>
            <p>✅ طراحی ریسپانسیو برای همه دستگاه‌ها</p>
        </div>
        
        <a href="?admin" class="btn-admin">🎛️ ورود به پنل مدیریت</a>
    </div>
</body>
</html>
