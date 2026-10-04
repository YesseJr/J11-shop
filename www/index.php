<?php
declare(strict_types=1);

// ============================================================================
//  J11 TECHNOLOGIES  —  LARAGON CONTROL DECK
//  Local development environment dashboard with live system telemetry.
// ============================================================================

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

function execAllowed(): bool
{
    if (!function_exists('exec')) {
        return false;
    }
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    return !in_array('exec', $disabled, true);
}

function safeExec(string $cmd): ?array
{
    if (!execAllowed()) {
        return null;
    }
    $output = [];
    $returnCode = 0;
    // Redirect stderr so noisy tools don't pollute output
    @exec($cmd . ' 2>NUL', $output, $returnCode);
    return $output ?: null;
}

function wmicValue(array $lines, string $key): ?string
{
    foreach ($lines as $line) {
        if (stripos($line, $key . '=') === 0) {
            $val = trim(substr($line, strlen($key) + 1));
            return $val === '' ? null : $val;
        }
    }
    return null;
}

function getOsDetails(): array
{
    $family = PHP_OS_FAMILY;
    $details = [
        'family'  => $family,
        'name'    => php_uname('s'),
        'version' => null,
        'build'   => null,
        'arch'    => php_uname('m'),
        'exact'   => null,
    ];

    if ($family === 'Windows') {
        $out = safeExec('wmic os get Caption,Version,BuildNumber,OSArchitecture /value');
        if ($out) {
            $details['name']    = wmicValue($out, 'Caption') ?? $details['name'];
            $details['version'] = wmicValue($out, 'Version');
            $details['build']   = wmicValue($out, 'BuildNumber');
            $details['arch']    = wmicValue($out, 'OSArchitecture') ?? $details['arch'];
        }
        if ($details['version']) {
            $details['exact'] = $details['version'] . ($details['build'] ? " (Build {$details['build']})" : '');
        }
    } elseif (is_readable('/etc/os-release')) {
        $parsed = @parse_ini_file('/etc/os-release');
        if ($parsed && !empty($parsed['PRETTY_NAME'])) {
            $details['name'] = $parsed['PRETTY_NAME'];
        }
        $details['version'] = php_uname('r');
        $details['exact']   = php_uname('r');
    } else {
        $details['version'] = php_uname('r');
        $details['exact']   = php_uname('r');
    }

    return $details;
}

function getCpuUsage(): ?int
{
    if (PHP_OS_FAMILY === 'Windows') {
        $out = safeExec('wmic cpu get loadpercentage /value');
        if ($out) {
            $v = wmicValue($out, 'LoadPercentage');
            if ($v !== null) {
                return (int) $v;
            }
        }
        return null;
    }
    if (function_exists('sys_getloadavg')) {
        $load  = sys_getloadavg();
        $nproc = safeExec('nproc');
        $cores = $nproc ? max(1, (int) $nproc[0]) : 1;
        if ($load && $cores > 0) {
            return (int) min(100, round(($load[0] / $cores) * 100));
        }
    }
    return null;
}

function getMemoryUsage(): ?array
{
    if (PHP_OS_FAMILY === 'Windows') {
        $out = safeExec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /value');
        if ($out) {
            $free  = wmicValue($out, 'FreePhysicalMemory');
            $total = wmicValue($out, 'TotalVisibleMemorySize');
            if ($free !== null && $total !== null && (float) $total > 0) {
                $freeF  = (float) $free;
                $totalF = (float) $total;
                return [
                    'total_gb' => round($totalF / 1048576, 1),
                    'free_gb'  => round($freeF / 1048576, 1),
                    'used_pct' => (int) round((($totalF - $freeF) / $totalF) * 100),
                ];
            }
        }
        return null;
    }
    if (is_readable('/proc/meminfo')) {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $mt);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $ma);
        if ($mt && $ma) {
            $total = (float) $mt[1];
            $avail = (float) $ma[1];
            return [
                'total_gb' => round($total / 1048576, 1),
                'free_gb'  => round($avail / 1048576, 1),
                'used_pct' => (int) round((($total - $avail) / $total) * 100),
            ];
        }
    }
    return null;
}

function getUptime(): ?string
{
    if (PHP_OS_FAMILY === 'Windows') {
        $out = safeExec('wmic OS get LastBootUpTime /value');
        if ($out) {
            $raw = wmicValue($out, 'LastBootUpTime');
            if ($raw) {
                $stamp = substr($raw, 0, 14);
                $boot  = DateTime::createFromFormat('YmdHis', $stamp);
                if ($boot) {
                    $diff = (new DateTime())->diff($boot);
                    return trim(sprintf('%dd %dh %dm', $diff->days, $diff->h, $diff->i));
                }
            }
        }
        return null;
    }
    if (is_readable('/proc/uptime')) {
        $seconds = (int) floatval(explode(' ', file_get_contents('/proc/uptime'))[0]);
        $d  = intdiv($seconds, 86400);
        $hh = intdiv($seconds % 86400, 3600);
        $m  = intdiv($seconds % 3600, 60);
        return "{$d}d {$hh}h {$m}m";
    }
    return null;
}

function getDiskUsage(string $path): ?array
{
    $total = @disk_total_space($path);
    $free  = @disk_free_space($path);
    if ($total && $free) {
        return [
            'total_gb' => round($total / 1073741824, 1),
            'free_gb'  => round($free / 1073741824, 1),
            'used_pct' => (int) round((($total - $free) / $total) * 100),
        ];
    }
    return null;
}

function checkPort(string $host, int $port, float $timeout = 0.35): array
{
    $start = microtime(true);
    $conn  = @fsockopen($host, $port, $errno, $errstr, $timeout);
    $latency = (int) round((microtime(true) - $start) * 1000);
    if ($conn) {
        fclose($conn);
        return ['up' => true, 'latency' => $latency];
    }
    return ['up' => false, 'latency' => null];
}

function relativeTime(int $timestamp): string
{
    if ($timestamp <= 0) {
        return '';
    }
    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        $m = intdiv($diff, 60);
        return $m . 'm ago';
    }
    if ($diff < 86400) {
        $h = intdiv($diff, 3600);
        return $h . 'h ago';
    }
    if ($diff < 604800) {
        $d = intdiv($diff, 86400);
        return $d . 'd ago';
    }
    return date('M j, Y', $timestamp);
}

function scanProjects(string $docRoot): array
{
    $projects = [];
    if (!is_dir($docRoot)) {
        return $projects;
    }
    foreach (scandir($docRoot) as $entry) {
        if ($entry === '.' || $entry === '..' || $entry[0] === '.') {
            continue;
        }
        $full = $docRoot . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($full)) {
            continue;
        }
        $stack = [];
        if (file_exists($full . '/composer.json')) {
            $stack[] = 'PHP';
        }
        if (file_exists($full . '/artisan')) {
            $stack[] = 'Laravel';
        }
        if (file_exists($full . '/package.json')) {
            $stack[] = 'Node';
        }
        if (file_exists($full . '/wp-config.php') || is_dir($full . '/wp-content')) {
            $stack[] = 'WP';
        }
        if (file_exists($full . '/manage.py') || file_exists($full . '/requirements.txt')) {
            $stack[] = 'Python';
        }
        if (is_dir($full . '/.git')) {
            $stack[] = 'Git';
        }
        $projects[] = [
            'name'  => $entry,
            'stack' => $stack,
            'mtime' => @filemtime($full) ?: 0,
        ];
    }
    usort($projects, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $projects;
}

function computeHealth(?int $cpu, ?array $memory, ?array $disk): array
{
    $scores = array_values(array_filter(
        [$cpu, $memory['used_pct'] ?? null, $disk['used_pct'] ?? null],
        fn($v) => $v !== null
    ));
    if (!$scores) {
        return ['label' => 'UNKNOWN', 'class' => 'unknown', 'max' => null];
    }
    $max = max($scores);
    if ($max >= 90) {
        return ['label' => 'CRITICAL', 'class' => 'critical', 'max' => $max];
    }
    if ($max >= 75) {
        return ['label' => 'ELEVATED', 'class' => 'elevated', 'max' => $max];
    }
    if ($max >= 50) {
        return ['label' => 'NORMAL', 'class' => 'normal', 'max' => $max];
    }
    return ['label' => 'OPTIMAL', 'class' => 'optimal', 'max' => $max];
}

// ============================================================================
//  QUERY ROUTER (allow-list)
// ============================================================================
if (isset($_GET['q'])) {
    $query = $_GET['q'];

    if ($query === 'info') {
        if ($isLocal) {
            phpinfo();
            exit;
        }
        http_response_code(403);
        exit('Forbidden! phpinfo() is allowed on localhost only.');
    }

    if ($query === 'stats') {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $cpu    = getCpuUsage();
        $memory = getMemoryUsage();
        $disk   = getDiskUsage($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
        $health = computeHealth($cpu, $memory, $disk);
        echo json_encode([
            'cpu'         => $cpu,
            'memory'      => $memory,
            'disk'        => $disk,
            'uptime'      => getUptime(),
            'server_time' => date('H:i:s'),
            'health'      => $health,
            'mysql'       => checkPort('127.0.0.1', 3306),
            'redis'       => checkPort('127.0.0.1', 6379),
            'postgres'    => checkPort('127.0.0.1', 5432),
        ]);
        exit;
    }

    http_response_code(404);
    exit('Invalid query parameter.');
}

// ============================================================================
//  GATHER PAGE DATA
// ============================================================================
$computerName   = gethostname() ?: 'UNKNOWN-DEVICE';
$os             = getOsDetails();
$cpu            = getCpuUsage();
$memory         = getMemoryUsage();
$disk           = getDiskUsage($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
$uptime         = getUptime();
$phpVersion     = PHP_VERSION;
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$docRoot        = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
$localIp        = $_SERVER['SERVER_ADDR'] ?? (gethostbyname($computerName) ?: '127.0.0.1');
$extCount       = count(get_loaded_extensions());
$mysql          = checkPort('127.0.0.1', 3306);
$redis          = checkPort('127.0.0.1', 6379);
$postgres       = checkPort('127.0.0.1', 5432);
$projects       = scanProjects($docRoot);
$health         = computeHealth($cpu, $memory, $disk);

$isApache = stripos($serverSoftware, 'apache') !== false;
$isNginx  = stripos($serverSoftware, 'nginx') !== false;
$webServerName = $isApache ? 'Apache' : ($isNginx ? 'Nginx' : 'Web Server');

$diskLabel = PHP_OS_FAMILY === 'Windows'
    ? strtoupper(substr($docRoot, 0, 1)) . ':'
    : basename($docRoot);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="dark">
<title>J11 Technologies — Control Deck</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --bg: #070a0f;
        --bg-glow:
            radial-gradient(ellipse 80% 50% at 15% -20%, rgba(33, 199, 168, 0.12), transparent 50%),
            radial-gradient(ellipse 60% 40% at 90% 0%, rgba(255, 159, 67, 0.07), transparent 45%),
            radial-gradient(ellipse 40% 30% at 50% 100%, rgba(95, 184, 255, 0.04), transparent 50%);
        --panel: #0c1219;
        --panel-alt: #0a0f16;
        --panel-hover: #0f1620;
        --border: rgba(255, 255, 255, 0.06);
        --border-strong: rgba(255, 255, 255, 0.1);
        --primary: #21c7a8;
        --primary-dim: rgba(33, 199, 168, 0.12);
        --primary-glow: rgba(33, 199, 168, 0.25);
        --accent: #ff9f43;
        --accent-dim: rgba(255, 159, 67, 0.12);
        --danger: #ff5c5c;
        --danger-dim: rgba(255, 92, 92, 0.12);
        --info: #5fb8ff;
        --info-dim: rgba(95, 184, 255, 0.12);
        --text: #e8eef4;
        --muted: #6b7888;
        --mono: 'JetBrains Mono', ui-monospace, monospace;
        --sans: 'Inter', system-ui, sans-serif;
        --radius: 12px;
        --radius-sm: 8px;
        --shadow: 0 4px 24px rgba(0, 0, 0, 0.25);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        min-height: 100%;
        background: var(--bg);
        color: var(--text);
        font-family: var(--sans);
        -webkit-font-smoothing: antialiased;
    }

    body {
        background-image:
            var(--bg-glow),
            linear-gradient(rgba(255, 255, 255, 0.018) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.018) 1px, transparent 1px);
        background-size: auto, 48px 48px, 48px 48px;
        background-position: 0 0, -1px -1px, -1px -1px;
    }

    .wrap {
        max-width: 1100px;
        margin: 0 auto;
        padding: 24px 18px 48px;
    }

    /* ---------- Top bar ---------- */
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
        box-shadow: var(--shadow);
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .brand-mark {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: linear-gradient(135deg, var(--primary), #0f8f78);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--mono);
        font-weight: 700;
        color: #04140f;
        font-size: 14px;
        letter-spacing: -0.5px;
        box-shadow: 0 0 16px var(--primary-glow);
    }

    .brand-text b {
        font-size: 13.5px;
        letter-spacing: 0.3px;
        display: block;
        line-height: 1.2;
    }

    .brand-text span {
        font-size: 10px;
        color: var(--muted);
        font-family: var(--mono);
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .health-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: var(--mono);
        font-size: 11px;
        padding: 6px 12px;
        border-radius: 999px;
        background: var(--panel-alt);
        border: 1px solid var(--border);
        text-transform: uppercase;
        letter-spacing: 1.2px;
        transition: border-color 0.3s, background 0.3s;
    }

    .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--primary);
        position: relative;
        flex: none;
    }

    .dot::after {
        content: '';
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        background: inherit;
        opacity: 0.35;
        animation: pulse 2s ease-out infinite;
    }

    @keyframes pulse {
        0% { transform: scale(0.5); opacity: 0.5; }
        100% { transform: scale(2.4); opacity: 0; }
    }

    .health-optimal .dot { background: var(--primary); }
    .health-normal .dot { background: var(--info); }
    .health-elevated .dot { background: var(--accent); }
    .health-critical .dot { background: var(--danger); }
    .health-unknown .dot { background: var(--muted); }

    .health-optimal { border-color: rgba(33, 199, 168, 0.25); }
    .health-normal { border-color: rgba(95, 184, 255, 0.25); }
    .health-elevated { border-color: rgba(255, 159, 67, 0.3); }
    .health-critical { border-color: rgba(255, 92, 92, 0.35); }

    .clock-box { text-align: right; font-family: var(--mono); }
    #live-date { font-size: 10.5px; color: var(--muted); letter-spacing: 0.4px; }
    #live-time {
        font-size: 17px;
        font-weight: 700;
        color: var(--primary);
        letter-spacing: 1px;
        font-variant-numeric: tabular-nums;
    }

    /* ---------- Hero ---------- */
    .hero {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 22px 26px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--primary), transparent);
        opacity: 0.55;
    }

    .terminal-line {
        font-family: var(--mono);
        font-size: 12.5px;
        color: var(--muted);
        margin-bottom: 4px;
    }

    .terminal-line .prompt-user { color: var(--primary); }
    .terminal-line .prompt-path { color: var(--accent); }

    .hero h1 {
        font-family: var(--mono);
        font-size: clamp(22px, 3.8vw, 30px);
        margin: 4px 0 6px;
        font-weight: 700;
        letter-spacing: -0.6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .hero h1 .caret {
        display: inline-block;
        width: 9px;
        height: 0.95em;
        background: var(--primary);
        animation: blink 1.05s steps(1) infinite;
        border-radius: 1px;
    }

    @keyframes blink { 50% { opacity: 0; } }

    .hero p {
        color: var(--muted);
        font-size: 13px;
        line-height: 1.45;
    }

    .hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .chip {
        font-family: var(--mono);
        font-size: 10.5px;
        padding: 4px 10px;
        border-radius: 6px;
        background: var(--panel-alt);
        border: 1px solid var(--border);
        color: var(--muted);
        letter-spacing: 0.3px;
    }

    .chip strong { color: var(--text); font-weight: 600; }

    /* ---------- Section ---------- */
    .section-label {
        font-family: var(--mono);
        font-size: 10.5px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--accent);
        margin: 0 0 10px 2px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
        max-width: 80px;
    }

    /* ---------- Gauges ---------- */
    .gauge-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }

    .gauge-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 18px 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        box-shadow: var(--shadow);
        transition: border-color 0.2s;
    }

    .gauge-card:hover { border-color: var(--border-strong); }

    .g-label {
        font-family: var(--mono);
        font-size: 10.5px;
        letter-spacing: 1.5px;
        color: var(--muted);
        text-transform: uppercase;
    }

    .gauge-svg { width: 120px; height: 120px; }

    .gauge-track {
        fill: none;
        stroke: rgba(255, 255, 255, 0.05);
        stroke-width: 8.5;
    }

    .gauge-fill {
        fill: none;
        stroke: var(--primary);
        stroke-width: 8.5;
        stroke-linecap: round;
        stroke-dasharray: 339.292;
        stroke-dashoffset: 339.292;
        transform: rotate(-90deg);
        transform-origin: 64px 64px;
        transition: stroke-dashoffset 0.9s cubic-bezier(0.22, 1, 0.36, 1), stroke 0.35s ease;
    }

    .gauge-pct {
        font-family: var(--mono);
        font-size: 20px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        min-height: 1.2em;
    }

    .gauge-sub {
        font-size: 11px;
        color: var(--muted);
        text-align: center;
        line-height: 1.35;
        min-height: 2.7em;
    }

    /* ---------- Info grid ---------- */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 20px;
    }

    .panel {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: var(--shadow);
    }

    .panel-title {
        font-family: var(--mono);
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--accent);
        margin-bottom: 10px;
        font-weight: 700;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 7px 0;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }

    .info-item:last-child { border-bottom: none; }

    .info-label { color: var(--muted); flex-shrink: 0; }

    .info-value {
        font-weight: 600;
        font-family: var(--mono);
        font-size: 12px;
        text-align: right;
        word-break: break-all;
    }

    .info-value.accent { color: var(--primary); }

    .info-value a {
        color: var(--primary);
        text-decoration: none;
        background: var(--primary-dim);
        padding: 2px 8px;
        border-radius: 5px;
        font-size: 10.5px;
        margin-left: 4px;
        transition: background 0.15s;
    }

    .info-value a:hover { background: rgba(33, 199, 168, 0.28); }

    .copy-btn {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--muted);
        font-family: var(--mono);
        font-size: 9.5px;
        padding: 2px 6px;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 6px;
        transition: color 0.15s, border-color 0.15s, background 0.15s;
        vertical-align: middle;
    }

    .copy-btn:hover {
        color: var(--primary);
        border-color: var(--primary);
        background: var(--primary-dim);
    }

    /* ---------- Services ---------- */
    .service-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .service-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        box-shadow: var(--shadow);
        transition: border-color 0.2s;
    }

    .service-card:hover { border-color: var(--border-strong); }

    .service-name { font-size: 13px; font-weight: 700; }
    .service-meta {
        font-family: var(--mono);
        font-size: 10.5px;
        color: var(--muted);
        margin-top: 2px;
    }

    .status-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        font-family: var(--mono);
        font-size: 10.5px;
        padding: 4px 10px;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        flex-shrink: 0;
    }

    .status-up { background: var(--primary-dim); color: var(--primary); }
    .status-down { background: var(--danger-dim); color: var(--danger); }
    .status-up .dot { background: var(--primary); }
    .status-down .dot { background: var(--danger); }
    .status-down .dot::after { display: none; }
    .status-chip .dot { width: 6px; height: 6px; }

    /* ---------- Projects ---------- */
    .projects-panel {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
    }

    .project-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .project-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        background: var(--panel-alt);
        border: 1px solid var(--border);
        border-radius: 10px;
        text-decoration: none;
        color: var(--text);
        transition: border-color 0.2s, transform 0.15s, background 0.15s;
    }

    .project-item:hover {
        border-color: var(--primary);
        background: var(--panel-hover);
        transform: translateY(-1px);
    }

    .project-left {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .project-name {
        font-family: var(--mono);
        font-size: 12.5px;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .project-mtime {
        font-family: var(--mono);
        font-size: 10px;
        color: var(--muted);
    }

    .project-badges {
        display: flex;
        gap: 4px;
        flex: none;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .badge {
        font-size: 9px;
        font-family: var(--mono);
        padding: 2px 6px;
        border-radius: 4px;
        background: var(--accent-dim);
        color: var(--accent);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .badge.php { background: var(--primary-dim); color: var(--primary); }
    .badge.laravel { background: rgba(255, 45, 32, 0.12); color: #ff6b5e; }
    .badge.node { background: rgba(104, 160, 99, 0.15); color: #7bc47f; }
    .badge.git { background: var(--info-dim); color: var(--info); }
    .badge.wp { background: rgba(33, 117, 155, 0.15); color: #4db8e8; }
    .badge.python { background: rgba(55, 118, 171, 0.15); color: #5b9fd4; }

    .empty-note {
        color: var(--muted);
        font-size: 13px;
        font-family: var(--mono);
        padding: 8px 0;
    }

    /* ---------- Actions ---------- */
    .btn-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .btn {
        flex: 1;
        min-width: 140px;
        padding: 12px 18px;
        font-size: 12.5px;
        font-weight: 700;
        text-decoration: none;
        border-radius: 10px;
        text-align: center;
        font-family: var(--mono);
        letter-spacing: 0.2px;
        transition: transform 0.15s ease, opacity 0.15s ease, box-shadow 0.15s;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .btn-primary {
        background: var(--primary);
        color: #04140f;
        box-shadow: 0 2px 12px var(--primary-glow);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        opacity: 0.95;
        box-shadow: 0 4px 20px var(--primary-glow);
    }

    .btn-secondary {
        background: var(--panel-alt);
        color: var(--text);
        border-color: var(--border);
    }

    .btn-secondary:hover {
        transform: translateY(-2px);
        border-color: var(--primary);
        background: var(--panel-hover);
    }

    /* ---------- Footer ---------- */
    .footer {
        text-align: center;
        font-family: var(--mono);
        font-size: 10.5px;
        color: var(--muted);
        padding-top: 4px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .footer .sync { color: var(--primary); }

    /* ---------- Toast ---------- */
    .toast {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: var(--panel);
        border: 1px solid var(--border-strong);
        color: var(--text);
        font-family: var(--mono);
        font-size: 12px;
        padding: 10px 18px;
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s, transform 0.25s;
        z-index: 100;
        max-width: 90vw;
    }

    .toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .toast.success { border-color: rgba(33, 199, 168, 0.4); }
    .toast.error { border-color: rgba(255, 92, 92, 0.4); }

    /* ---------- Responsive ---------- */
    @media (max-width: 720px) {
        .info-grid { grid-template-columns: 1fr; }
        .gauge-grid { grid-template-columns: 1fr 1fr; }
        .project-list { grid-template-columns: 1fr; }
    }

    @media (max-width: 480px) {
        .gauge-grid { grid-template-columns: 1fr; }
        .wrap { padding: 16px 12px 40px; }
        .hero { padding: 18px 16px; }
    }

    /* Reduced motion */
    @media (prefers-reduced-motion: reduce) {
        .gauge-fill { transition: none; }
        .dot::after { animation: none; }
        .hero h1 .caret { animation: none; }
        .project-item, .btn { transition: none; }
    }
</style>
</head>
<body>
<div class="wrap">

    <!-- ================= TOP BAR ================= -->
    <div class="topbar">
        <div class="brand">
            <div class="brand-mark">J11</div>
            <div class="brand-text">
                <b>J11 Technologies</b>
                <span>Control Deck</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="health-pill health-<?= h($health['class']) ?>" id="health-pill">
                <span class="dot"></span>
                <span id="health-label">SYSTEM <?= h($health['label']) ?></span>
            </div>
            <div class="clock-box">
                <div id="live-date">—</div>
                <div id="live-time">00:00:00</div>
            </div>
        </div>
    </div>

    <!-- ================= HERO ================= -->
    <div class="hero">
        <div class="terminal-line">
            <span class="prompt-user">j11@<?= h($computerName) ?></span>:<span class="prompt-path">~/www</span>$ whoami
        </div>
        <h1><?= h($computerName) ?><span class="caret"></span></h1>
        <p>Local development environment · Dar es Salaam · live telemetry every 5s</p>
        <div class="hero-meta">
            <span class="chip"><strong>PHP</strong> <?= h($phpVersion) ?></span>
            <span class="chip"><strong><?= h($webServerName) ?></strong></span>
            <span class="chip" id="uptime-chip"><strong>Uptime</strong> <?= h($uptime ?? 'N/A') ?></span>
            <span class="chip"><strong>IP</strong> <?= h($localIp) ?></span>
        </div>
    </div>

    <!-- ================= GAUGES ================= -->
    <div class="section-label">Live Resource Monitor</div>
    <div class="gauge-grid">
        <div class="gauge-card">
            <div class="g-label">CPU Load</div>
            <svg class="gauge-svg" viewBox="0 0 128 128" id="gauge-cpu" data-pct="<?= $cpu !== null ? (int) $cpu : '' ?>" aria-hidden="true">
                <circle class="gauge-track" cx="64" cy="64" r="54"></circle>
                <circle class="gauge-fill" cx="64" cy="64" r="54"></circle>
            </svg>
            <div class="gauge-pct" id="cpu-pct-label"><?= $cpu !== null ? h($cpu) . '%' : 'N/A' ?></div>
            <div class="gauge-sub"><?= $cpu !== null ? 'Processor utilization' : 'exec() disabled on this host' ?></div>
        </div>
        <div class="gauge-card">
            <div class="g-label">Memory</div>
            <svg class="gauge-svg" viewBox="0 0 128 128" id="gauge-ram" data-pct="<?= $memory['used_pct'] ?? '' ?>" aria-hidden="true">
                <circle class="gauge-track" cx="64" cy="64" r="54"></circle>
                <circle class="gauge-fill" cx="64" cy="64" r="54"></circle>
            </svg>
            <div class="gauge-pct" id="ram-pct-label"><?= isset($memory['used_pct']) ? h($memory['used_pct']) . '%' : 'N/A' ?></div>
            <div class="gauge-sub" id="ram-sub"><?= isset($memory['free_gb']) ? h($memory['free_gb']) . ' GB free of ' . h($memory['total_gb']) . ' GB' : 'Unavailable on this host' ?></div>
        </div>
        <div class="gauge-card">
            <div class="g-label">Disk (<?= h($diskLabel) ?>)</div>
            <svg class="gauge-svg" viewBox="0 0 128 128" id="gauge-disk" data-pct="<?= $disk['used_pct'] ?? '' ?>" aria-hidden="true">
                <circle class="gauge-track" cx="64" cy="64" r="54"></circle>
                <circle class="gauge-fill" cx="64" cy="64" r="54"></circle>
            </svg>
            <div class="gauge-pct" id="disk-pct-label"><?= isset($disk['used_pct']) ? h($disk['used_pct']) . '%' : 'N/A' ?></div>
            <div class="gauge-sub" id="disk-sub"><?= isset($disk['free_gb']) ? h($disk['free_gb']) . ' GB free of ' . h($disk['total_gb']) . ' GB' : 'Unavailable' ?></div>
        </div>
    </div>

    <!-- ================= INFO GRID ================= -->
    <div class="info-grid">
        <div class="panel">
            <div class="panel-title">Host Machine</div>
            <div class="info-item">
                <span class="info-label">Computer Name</span>
                <span class="info-value accent">
                    <?= h($computerName) ?>
                    <button type="button" class="copy-btn" data-copy="<?= h($computerName) ?>" title="Copy">copy</button>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Operating System</span>
                <span class="info-value"><?= h($os['name']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Version</span>
                <span class="info-value"><?= h($os['exact'] ?? 'Unknown') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Architecture</span>
                <span class="info-value"><?= h($os['arch']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Uptime</span>
                <span class="info-value" id="uptime-value"><?= h($uptime ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Local IP</span>
                <span class="info-value">
                    <?= h($localIp) ?>
                    <button type="button" class="copy-btn" data-copy="<?= h($localIp) ?>" title="Copy">copy</button>
                </span>
            </div>
        </div>
        <div class="panel">
            <div class="panel-title">Server Environment</div>
            <?php if ($isLocal): ?>
                <div class="info-item">
                    <span class="info-label">Server Software</span>
                    <span class="info-value"><?= h($serverSoftware) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">PHP Version</span>
                    <span class="info-value">
                        <?= h($phpVersion) ?>
                        <a href="/?q=info">info()</a>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Loaded Extensions</span>
                    <span class="info-value"><?= h($extCount) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Document Root</span>
                    <span class="info-value" style="font-size:11px;" id="docroot-value"><?= h($docRoot) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Last Sync</span>
                    <span class="info-value sync" id="last-sync">just now</span>
                </div>
            <?php else: ?>
                <div class="info-item">
                    <span class="info-label">Server Status</span>
                    <span class="info-value accent">Reachable on network</span>
                </div>
                <div class="info-item">
                    <span class="info-label">PHP Engine</span>
                    <span class="info-value accent">Active</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Note</span>
                    <span class="info-value" style="color:var(--muted);font-size:11px;">Full details on localhost only</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================= SERVICES ================= -->
    <div class="section-label">Services</div>
    <div class="service-grid">
        <div class="service-card">
            <div>
                <div class="service-name"><?= h($webServerName) ?></div>
                <div class="service-meta">Web server</div>
            </div>
            <div class="status-chip status-up"><span class="dot"></span>Online</div>
        </div>
        <div class="service-card" id="svc-mysql">
            <div>
                <div class="service-name">MySQL / MariaDB</div>
                <div class="service-meta" id="mysql-meta"><?= $mysql['up'] ? h($mysql['latency']) . 'ms · port 3306' : 'port 3306' ?></div>
            </div>
            <div class="status-chip <?= $mysql['up'] ? 'status-up' : 'status-down' ?>" id="mysql-status">
                <span class="dot"></span><?= $mysql['up'] ? 'Online' : 'Offline' ?>
            </div>
        </div>
        <div class="service-card" id="svc-redis">
            <div>
                <div class="service-name">Redis</div>
                <div class="service-meta" id="redis-meta"><?= $redis['up'] ? h($redis['latency']) . 'ms · port 6379' : 'port 6379' ?></div>
            </div>
            <div class="status-chip <?= $redis['up'] ? 'status-up' : 'status-down' ?>" id="redis-status">
                <span class="dot"></span><?= $redis['up'] ? 'Online' : 'Offline' ?>
            </div>
        </div>
        <div class="service-card" id="svc-postgres">
            <div>
                <div class="service-name">PostgreSQL</div>
                <div class="service-meta" id="postgres-meta"><?= $postgres['up'] ? h($postgres['latency']) . 'ms · port 5432' : 'port 5432' ?></div>
            </div>
            <div class="status-chip <?= $postgres['up'] ? 'status-up' : 'status-down' ?>" id="postgres-status">
                <span class="dot"></span><?= $postgres['up'] ? 'Online' : 'Offline' ?>
            </div>
        </div>
        <div class="service-card">
            <div>
                <div class="service-name">PHP Engine</div>
                <div class="service-meta">v<?= h($phpVersion) ?></div>
            </div>
            <div class="status-chip status-up"><span class="dot"></span>Active</div>
        </div>
    </div>

    <!-- ================= PROJECTS ================= -->
    <div class="projects-panel">
        <div class="panel-title">Projects in <?= h(basename($docRoot)) ?> (<?= count($projects) ?>)</div>
        <?php if ($projects): ?>
            <div class="project-list">
                <?php foreach ($projects as $p): ?>
                    <a class="project-item" href="/<?= rawurlencode($p['name']) ?>/" title="Open <?= h($p['name']) ?>">
                        <span class="project-left">
                            <span class="project-name">📁 <?= h($p['name']) ?></span>
                            <?php if ($p['mtime']): ?>
                                <span class="project-mtime"><?= h(relativeTime($p['mtime'])) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="project-badges">
                            <?php foreach ($p['stack'] as $tag): ?>
                                <span class="badge <?= h(strtolower($tag)) ?>"><?= h($tag) ?></span>
                            <?php endforeach; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-note">No project folders detected in the document root yet.</div>
        <?php endif; ?>
    </div>

    <!-- ================= ACTIONS ================= -->
    <div class="btn-group">
        <a href="https://laragon.org/docs" target="_blank" rel="noopener" class="btn btn-primary">Laragon Docs</a>
        <a href="/phpmyadmin" class="btn btn-secondary">phpMyAdmin</a>
        <button type="button" class="btn btn-secondary" id="copy-root-btn">Copy Document Root</button>
        <button type="button" class="btn btn-secondary" id="refresh-btn" title="Refresh now (R)">Refresh Now</button>
    </div>

    <div class="footer">
        <div>&copy; <?= date('Y') ?> J11 Technologies. All Rights Reserved.</div>
        <div>Telemetry every 5s · pauses when tab is hidden · <span class="sync" id="sync-status">connected</span></div>
    </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
const CIRCUMFERENCE = 339.292;

function setGauge(id, pct) {
    const svg = document.getElementById(id);
    if (!svg) return;
    const fill = svg.querySelector('.gauge-fill');
    if (pct === null || pct === undefined || pct === '' || isNaN(Number(pct))) {
        fill.style.stroke = 'rgba(255,255,255,0.05)';
        fill.style.strokeDashoffset = CIRCUMFERENCE;
        return;
    }
    pct = Math.max(0, Math.min(100, Number(pct)));
    const offset = CIRCUMFERENCE * (1 - pct / 100);
    fill.style.strokeDashoffset = offset;
    let color = 'var(--primary)';
    if (pct >= 90) color = 'var(--danger)';
    else if (pct >= 75) color = 'var(--accent)';
    fill.style.stroke = color;
}

function initGauges() {
    ['gauge-cpu', 'gauge-ram', 'gauge-disk'].forEach(id => {
        const svg = document.getElementById(id);
        if (!svg) return;
        const pct = svg.dataset.pct;
        requestAnimationFrame(() => setGauge(id, pct === '' ? null : pct));
    });
}

function updateClock() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    const ss = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('live-time').textContent = `${hh}:${mm}:${ss}`;
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    document.getElementById('live-date').textContent =
        `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

let lastSyncSeconds = 0;
function tickSyncLabel() {
    lastSyncSeconds++;
    const el = document.getElementById('last-sync');
    if (el) el.textContent = lastSyncSeconds <= 1 ? 'just now' : `${lastSyncSeconds}s ago`;
}

function setServiceStatus(prefix, data) {
    const statusEl = document.getElementById(prefix + '-status');
    const metaEl = document.getElementById(prefix + '-meta');
    if (!statusEl) return;
    if (data && data.up) {
        statusEl.className = 'status-chip status-up';
        statusEl.innerHTML = '<span class="dot"></span>Online';
        if (metaEl) {
            const port = prefix === 'mysql' ? '3306' : (prefix === 'redis' ? '6379' : '5432');
            metaEl.textContent = `${data.latency}ms · port ${port}`;
        }
    } else {
        statusEl.className = 'status-chip status-down';
        statusEl.innerHTML = '<span class="dot"></span>Offline';
        if (metaEl) {
            const port = prefix === 'mysql' ? '3306' : (prefix === 'redis' ? '6379' : '5432');
            metaEl.textContent = `port ${port}`;
        }
    }
}

function updateHealth(health) {
    if (!health) return;
    const pill = document.getElementById('health-pill');
    const label = document.getElementById('health-label');
    if (pill) {
        pill.className = 'health-pill health-' + (health.class || 'unknown');
    }
    if (label) {
        label.textContent = 'SYSTEM ' + (health.label || 'UNKNOWN');
    }
}

async function refreshStats() {
    if (document.hidden) return;
    try {
        const res = await fetch('/?q=stats', { cache: 'no-store' });
        if (!res.ok) throw new Error('bad response');
        const data = await res.json();

        setGauge('gauge-cpu', data.cpu);
        document.getElementById('cpu-pct-label').textContent =
            data.cpu !== null && data.cpu !== undefined ? `${data.cpu}%` : 'N/A';

        if (data.memory) {
            setGauge('gauge-ram', data.memory.used_pct);
            document.getElementById('ram-pct-label').textContent = `${data.memory.used_pct}%`;
            document.getElementById('ram-sub').textContent =
                `${data.memory.free_gb} GB free of ${data.memory.total_gb} GB`;
        }

        if (data.disk) {
            setGauge('gauge-disk', data.disk.used_pct);
            document.getElementById('disk-pct-label').textContent = `${data.disk.used_pct}%`;
            document.getElementById('disk-sub').textContent =
                `${data.disk.free_gb} GB free of ${data.disk.total_gb} GB`;
        }

        if (data.uptime) {
            const up = document.getElementById('uptime-value');
            if (up) up.textContent = data.uptime;
            const chip = document.getElementById('uptime-chip');
            if (chip) chip.innerHTML = `<strong>Uptime</strong> ${data.uptime}`;
        }

        if (data.health) updateHealth(data.health);
        if (data.mysql) setServiceStatus('mysql', data.mysql);
        if (data.redis) setServiceStatus('redis', data.redis);
        if (data.postgres) setServiceStatus('postgres', data.postgres);

        const sync = document.getElementById('sync-status');
        if (sync) {
            sync.textContent = 'connected';
            sync.style.color = 'var(--primary)';
        }
        lastSyncSeconds = 0;
    } catch (e) {
        const sync = document.getElementById('sync-status');
        if (sync) {
            sync.textContent = 'connection lost — retrying';
            sync.style.color = 'var(--danger)';
        }
    }
}

function showToast(msg, type = 'success') {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.className = 'toast show ' + type;
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => {
        el.classList.remove('show');
    }, 2200);
}

async function copyText(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copied to clipboard');
        return true;
    } catch {
        showToast('Copy failed', 'error');
        return false;
    }
}

document.getElementById('copy-root-btn')?.addEventListener('click', () => {
    const path = document.getElementById('docroot-value')?.textContent?.trim();
    if (path) copyText(path);
});

document.getElementById('refresh-btn')?.addEventListener('click', () => {
    refreshStats();
    showToast('Refreshing…');
});

document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => copyText(btn.dataset.copy));
});

document.addEventListener('keydown', (e) => {
    if (e.target.matches('input, textarea, select')) return;
    if (e.key === 'r' || e.key === 'R') {
        e.preventDefault();
        refreshStats();
        showToast('Refreshing…');
    }
});

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) refreshStats();
});

initGauges();
updateClock();
setInterval(updateClock, 1000);
setInterval(tickSyncLabel, 1000);
setInterval(refreshStats, 5000);
</script>
</body>
</html>
