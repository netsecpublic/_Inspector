<?php
// --- 1. SERVER SIDE (PHP 8.3) ---
$ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}

$city = "Unknown City";
$country = "Unknown Country";
$db_path = '/usr/share/GeoIP/GeoLite2-City.mmdb';

if (file_exists($db_path) && class_exists('MaxMind\Db\Reader')) {
    try {
        $reader = new MaxMind\Db\Reader($db_path);
        $record = $reader->get($ip);
        if ($record) {
            $city = $record['city']['names']['en'] ?? "Unknown City";
            $country = $record['country']['names']['en'] ?? "Unknown Country";
        }
        $reader->close();
    } catch (Exception $e) { }
}

// --- 2. LOGGING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if ($data) {
        $logFile = 'visitor_stats.csv';
        $isNew = !file_exists($logFile);
        $handle = fopen($logFile, 'a');

        if ($handle) {
            if ($isNew) {
                fputcsv($handle, ['Timestamp', 'IP', 'City', 'Country', 'OS', 'Platform', 'Browser', 'Vendor', 'Resolution', 'ColorDepth', 'Timezone', 'CPU_Cores', 'RAM', 'GPU', 'HWID', 'UserAgent']);
            }

            fputcsv($handle, [
                date('Y-m-d H:i:s'),
                $ip,
                $city,
                $country,
                $data['os'],
                $data['platform'],
                $data['browser'],
                $data['vendor'],
                $data['res'],
                $data['color'],
                $data['tz'],
                $data['cores'],
                $data['ram'],
                $data['gpu'],
                $data['hwid'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            fclose($handle);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cannot open file']);
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shield Node Inspector</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0b0e14; color: #cfd8dc; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; background: #151921; padding: 30px; border-radius: 12px; border: 1px solid #2d333b; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        h1 { color: #00d4ff; text-align: center; border-bottom: 1px solid #2d333b; padding-bottom: 15px; margin-top: 0; font-weight: 300; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; }
        .card { background: #0b0e14; padding: 20px; border-radius: 8px; border: 1px solid #2d333b; min-height: 100px; }
        .label { color: #00d4ff; font-size: 0.75rem; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 10px; }
        .value { font-family: 'Courier New', monospace; font-size: 1.1rem; color: #fff; word-break: break-all; display: block; }
        .sub { font-size: 0.85rem; color: #8b949e; margin-top: 5px; display: block; }
        .fp-box { background: #1c2128; padding: 20px; border: 1px dashed #00d4ff; text-align: center; color: #00d4ff; font-size: 1.6rem; border-radius: 8px; margin-top: 20px; }
        @media (max-width: 850px) { .grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 550px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-shield-alt"></i> NODE_INSPECTOR</h1>
    <div class="grid">
        <div class="card"><span class="label"><i class="fas fa-network-wired"></i> Connection</span><span class="value"><?php echo $ip; ?></span><span class="sub"><?php echo "$city, $country"; ?></span></div>
        <div class="card"><span class="label"><i class="fas fa-laptop"></i> Operating System</span><span class="value" id="os-name">...</span><span class="sub" id="os-platform">...</span></div>
        <div class="card"><span class="label"><i class="fas fa-browser"></i> Browser</span><span class="value" id="browser-name">...</span><span class="sub" id="browser-vendor">...</span></div>
        <div class="card"><span class="label"><i class="fas fa-desktop"></i> Display</span><span class="value" id="res-info">...</span><span class="sub" id="color-info">...</span></div>
        <div class="card"><span class="label"><i class="fas fa-clock"></i> Timezone</span><span class="value" id="tz-info">...</span><span class="sub" id="lang-info">...</span></div>
        <div class="card"><span class="label"><i class="fas fa-microchip"></i> Hardware</span><span class="value" id="cpu-cores">...</span><span class="sub" id="gpu-name">...</span><span class="sub" id="mem-est">...</span></div>
    </div>
    <div class="card" style="margin-top:20px; border-top: 3px solid #00d4ff;"><span class="label"><i class="fas fa-fingerprint"></i> Unique HWID (Canvas Fingerprint)</span><div id="fp-hash" class="fp-box">CALCULATING...</div></div>
</div>

<canvas id="fpCanvas" width="200" height="40" style="display:none;"></canvas>

<script>
const logData = {};
const ua = navigator.userAgent;
logData.os = ua.includes("Win") ? "Windows" : ua.includes("Mac") ? "MacOS" : ua.includes("Linux") ? "Linux" : "Other";
logData.platform = navigator.platform;
document.getElementById('os-name').innerText = logData.os;
document.getElementById('os-platform').innerText = logData.platform;

logData.browser = ua.includes("Chrome") ? "Chrome" : ua.includes("Firefox") ? "Firefox" : ua.includes("Safari") && !ua.includes("Chrome") ? "Safari" : "Other";
logData.vendor = navigator.vendor || "N/A";
document.getElementById('browser-name').innerText = logData.browser;
document.getElementById('browser-vendor').innerText = logData.vendor;

logData.res = (window.screen.width * window.devicePixelRatio) + "x" + (window.screen.height * window.devicePixelRatio);
logData.color = window.screen.colorDepth + "-bit";
document.getElementById('res-info').innerText = logData.res;
document.getElementById('color-info').innerText = logData.color + " | Ratio: " + window.devicePixelRatio;

logData.tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
document.getElementById('tz-info').innerText = logData.tz;
document.getElementById('lang-info').innerText = "Lang: " + (navigator.language || "Unknown");

logData.cores = navigator.hardwareConcurrency || "N/A";
logData.ram = navigator.deviceMemory || "Unknown";
document.getElementById('cpu-cores').innerText = logData.cores + " Logical Cores";
document.getElementById('mem-est').innerText = "Memory: ~" + logData.ram + " GB RAM";

const gl = document.createElement('canvas').getContext('webgl');
logData.gpu = "Restricted";
if (gl) {
    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
    logData.gpu = debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : "Restricted";
}
document.getElementById('gpu-name').innerText = "GPU: " + logData.gpu;

function generateFP() {
    const canvas = document.getElementById('fpCanvas');
    const ctx = canvas.getContext('2d');
    ctx.textBaseline = "top";
    ctx.font = "14px 'Arial'";
    ctx.fillStyle = "#f60"; ctx.fillRect(125,1,62,20);
    ctx.fillStyle = "#069"; ctx.fillText("Shield_Secure_Node", 2, 15);
    const b64 = canvas.toDataURL();
    let hash = 0;
    for (let i = 0; i < b64.length; i++) { hash = ((hash << 5) - hash) + b64.charCodeAt(i); hash |= 0; }
    logData.hwid = "SHIELD-ID-" + Math.abs(hash).toString(16).toUpperCase();
    document.getElementById('fp-hash').innerText = logData.hwid;

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(logData)
    }).then(res => res.json()).then(data => console.log("Log Status:", data))
    .catch(err => console.error("Log Error:", err));
}
generateFP();
</script>
</body>
</html>
