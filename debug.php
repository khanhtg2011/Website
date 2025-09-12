<?php
session_start();
require __DIR__ . "/config.php";

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

$debugFile = __DIR__ . '/upload_debug.log';
$debugData = [];

if (file_exists($debugFile)) {
    $lines = file($debugFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach (array_reverse($lines) as $line) {
        $data = json_decode($line, true);
        if ($data) {
            $debugData[] = $data;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Debug Info</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .slow { color: red; }
        .normal { color: green; }
    </style>
</head>
<body>
    <h1>Upload Performance Debug</h1>
    <p><a href="/">← Back to Gallery</a></p>

    <?php if (empty($debugData)): ?>
        <p>No debug data available. Upload some images first.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Total Time</th>
                    <th>DB Connect</th>
                    <th>File Move</th>
                    <th>Thumbnail</th>
                    <th>Metadata</th>
                    <th>JSON Save</th>
                    <th>DB Insert</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($debugData as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['filename']); ?></td>
                    <td class="<?php echo $entry['total_time'] > 10 ? 'slow' : 'normal'; ?>">
                        <?php echo $entry['total_time']; ?>s
                    </td>
                    <td class="<?php echo $entry['db_connect_time'] > 1 ? 'slow' : 'normal'; ?>">
                        <?php echo $entry['db_connect_time']; ?>s
                    </td>
                    <td><?php echo $entry['file_move_time']; ?>s</td>
                    <td><?php echo $entry['thumbnail_time']; ?>s</td>
                    <td><?php echo $entry['metadata_time']; ?>s</td>
                    <td><?php echo $entry['json_save_time']; ?>s</td>
                    <td><?php echo $entry['db_insert_time']; ?>s</td>
                    <td><?php echo $entry['timestamp']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Performance Analysis</h2>
    <ul>
        <li><strong>Red values</strong> indicate potential bottlenecks</li>
        <li><strong>DB Connect > 1s</strong>: Database connection is slow</li>
        <li><strong>Total Time > 10s</strong>: Upload is taking too long</li>
        <li><strong>Thumbnail > 2s</strong>: Image processing is slow</li>
    </ul>
</body>
</html>