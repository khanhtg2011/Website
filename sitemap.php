<?php
// sitemap.php
require __DIR__ . "/config.php";

$result = $conn->query("SELECT filename, uploaded_at FROM photos");
$baseUrl = "https://your-domain.com"; // Thay bằng domain của bạn

header("Content-Type: application/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php while ($row = $result->fetch_assoc()): ?>
  <url>
    <loc><?php echo htmlspecialchars($baseUrl . "/uploads/" . $row['filename']); ?></loc>
    <lastmod><?php echo date("c", strtotime($row['uploaded_at'])); ?></lastmod>
    <image:image>
      <image:loc><?php echo htmlspecialchars($baseUrl . "/uploads/" . $row['filename']); ?></image:loc>
      <image:title><?php echo htmlspecialchars($row['filename']); ?></image:title>
    </image:image>
  </url>
<?php endwhile; ?>
</urlset>