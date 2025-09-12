<?php
// Simple test script to create sample photos without GD
require __DIR__ . "/config.php";

// Create uploads directory if it doesn't exist
if (!is_dir(__DIR__ . "/uploads")) {
    mkdir(__DIR__ . "/uploads", 0755, true);
}

// Create some sample photo entries in database
$samplePhotos = [
    [
        'filename' => 'sample1.jpg',
        'uploader' => 'Test User',
        'ip_address' => '127.0.0.1'
    ],
    [
        'filename' => 'sample2.jpg',
        'uploader' => 'Test User',
        'ip_address' => '127.0.0.1'
    ],
    [
        'filename' => 'sample3.jpg',
        'uploader' => 'Test User',
        'ip_address' => '127.0.0.1'
    ]
];

// Create sample files (just empty files for testing)
foreach ($samplePhotos as $photo) {
    $filepath = __DIR__ . "/uploads/" . $photo['filename'];
    if (!file_exists($filepath)) {
        file_put_contents($filepath, ''); // Create empty file

        // Create metadata file
        $metadata = [
            'IFD0' => ['Make' => 'Test Camera', 'Model' => 'Test Model'],
            'EXIF' => [
                'FNumber' => '2.8',
                'FocalLength' => '50',
                'ISOSpeedRatings' => '100',
                'ExposureTime' => '0.01'
            ],
            'filesize' => 0,
            'uploaded_at' => time()
        ];
        file_put_contents($filepath . ".json", json_encode($metadata));
    }

    // Insert into database
    if ($useSQLite) {
        $stmt = $conn->prepare("INSERT OR IGNORE INTO photos (filename, uploader, ip_address, uploaded_at) VALUES (?, ?, ?, datetime('now'))");
        $stmt->execute([$photo['filename'], $photo['uploader'], $photo['ip_address']]);
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO photos (filename, uploader, ip_address) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $photo['filename'], $photo['uploader'], $photo['ip_address']);
        $stmt->execute();
        $stmt->close();
    }
}

echo "✅ Sample photos created successfully!<br>";
echo "📁 Created files: sample1.jpg, sample2.jpg, sample3.jpg<br>";
echo "<br><a href='/'>← Back to Gallery</a>";
?>