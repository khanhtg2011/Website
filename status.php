<?php
// status.php
// Trả về JSON status và append log CSV
header("Content-Type: application/json");
date_default_timezone_set("Asia/Ho_Chi_Minh");

$logDir = __DIR__ . "/logs";
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Basic info
$domain = $_SERVER['SERVER_NAME'] ?? 'unknown';
$php_version = phpversion();
$ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "Bật" : "Tắt";
$server = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

// Disk (MB)
$disk_total = @round(disk_total_space("/") / 1024 / 1024, 2);
$disk_free = @round(disk_free_space("/") / 1024 / 1024, 2);

// RAM & CPU: may not work on some shared hosts
$ram_total = null;
$ram_free = null;
$cpu_load = [0,0,0];
$cpu_usage = 0.0;

if (is_readable("/proc/meminfo")) {
    $meminfo = file_get_contents("/proc/meminfo");
    if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $m)) $ram_total = (int)$m[1];
    if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $m2)) $ram_free = (int)$m2[1];
}

if (function_exists('sys_getloadavg')) {
    $cpu_load = sys_getloadavg();
    $cpu_usage = round($cpu_load[0], 2);
}

// If values not available, set as N/A
$ram_total_str = $ram_total ? $ram_total . " kB" : "N/A";
$ram_free_str = $ram_free ? $ram_free . " kB" : "N/A";

// Databases & Cronjobs detection (best-effort, customize as needed)
$databases = 0;
// try to count mysql databases if CLI available (comment out if not allowed)
if (function_exists('shell_exec')) {
    // WARNING: requires mysql client and proper permissions; this is optional
    // $dbs = shell_exec("mysql -e 'SHOW DATABASES;' 2>/dev/null | wc -l");
    // if ($dbs !== null) $databases = max(0, (int)$dbs - 1);
    $databases = 0;
}

// cronjobs best-effort (most shared hosts don't allow global access)
$cronjobs = [];
// If you store crons in a specific file you can read them here.

$timestamp = date("Y-m-d H:i:s");

// Prepare log CSV
$logFile = $logDir . "/" . date("Y-m-d") . ".csv";
if (!file_exists($logFile)) {
    file_put_contents($logFile, "Timestamp,CPU(%),RAM(%)\n");
}

// Compute RAM percent if possible
$ram_percent = "N/A";
if ($ram_total && $ram_free) {
    $ram_percent = round((($ram_total - $ram_free) / $ram_total) * 100, 2);
}

// Append log (if numeric values exist)
$cpu_log_val = is_numeric($cpu_usage) ? $cpu_usage : 0;
$ram_log_val = is_numeric($ram_percent) ? $ram_percent : 0;
file_put_contents($logFile, "$timestamp,$cpu_log_val,$ram_log_val\n", FILE_APPEND | LOCK_EX);

// Output JSON
$out = [
    "domain" => $domain,
    "php_version" => $php_version,
    "ssl" => $ssl,
    "server" => $server,
    "disk_total" => ($disk_total !== null ? $disk_total . " MB" : "N/A"),
    "disk_free" => ($disk_free !== null ? $disk_free . " MB" : "N/A"),
    "ram_total" => $ram_total_str,
    "ram_free" => $ram_free_str,
    "cpu_load" => $cpu_load,
    "cpu_usage" => $cpu_usage,
    "databases" => $databases,
    "cronjobs" => $cronjobs,
    "timestamp" => $timestamp
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
