<?php
$dir = __DIR__ . "/uploads/";
$total = @disk_total_space($dir);
$free  = @disk_free_space($dir);
$used  = ($total !== false && $free !== false) ? ($total - $free) : 0;

function formatSize($bytes) {
    $units = ["B","KB","MB","GB","TB"];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units)-1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . " " . $units[$i];
}

header("Content-Type: text/plain; charset=utf-8");
if ($total === false || $free === false) {
    echo "Không thể đọc dung lượng thư mục uploads/";
    exit;
}
echo "Đã dùng: " . formatSize($used) . " / Tổng: " . formatSize($total) .
     " (Còn lại: " . formatSize($free) . ")";
?>
