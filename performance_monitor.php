<?php
// performance_monitor.php - Performance monitoring and analytics
class PerformanceMonitor {

    private static $metrics = [];

    /**
     * Track Core Web Vitals and custom metrics
     */
    public static function trackMetric($name, $value, $metadata = []) {
        $timestamp = microtime(true);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $url = $_SERVER['REQUEST_URI'] ?? '';

        self::$metrics[] = [
            'name' => $name,
            'value' => $value,
            'timestamp' => $timestamp,
            'user_agent' => $userAgent,
            'ip' => $ip,
            'url' => $url,
            'metadata' => $metadata
        ];
    }

    /**
     * Get performance statistics
     */
    public static function getStats($timeframe = 3600) {
        $cutoff = time() - $timeframe;
        $filtered = array_filter(self::$metrics, function($metric) use ($cutoff) {
            return $metric['timestamp'] >= $cutoff;
        });

        $stats = [
            'total_requests' => count($filtered),
            'core_web_vitals' => [],
            'custom_metrics' => [],
            'performance_score' => 0
        ];

        foreach ($filtered as $metric) {
            if (in_array($metric['name'], ['LCP', 'FID', 'CLS', 'FCP', 'TTFB'])) {
                if (!isset($stats['core_web_vitals'][$metric['name']])) {
                    $stats['core_web_vitals'][$metric['name']] = [];
                }
                $stats['core_web_vitals'][$metric['name']][] = $metric['value'];
            } else {
                if (!isset($stats['custom_metrics'][$metric['name']])) {
                    $stats['custom_metrics'][$metric['name']] = [];
                }
                $stats['custom_metrics'][$metric['name']][] = $metric['value'];
            }
        }

        // Calculate averages for Core Web Vitals
        foreach ($stats['core_web_vitals'] as $vital => $values) {
            $stats['core_web_vitals'][$vital . '_avg'] = array_sum($values) / count($values);
            $stats['core_web_vitals'][$vital . '_count'] = count($values);
        }

        // Calculate performance score based on Core Web Vitals
        $stats['performance_score'] = self::calculatePerformanceScore($stats['core_web_vitals']);

        return $stats;
    }

    /**
     * Calculate performance score based on Core Web Vitals
     */
    private static function calculatePerformanceScore($vitals) {
        $score = 0;
        $totalWeight = 0;

        // LCP (Largest Contentful Paint) - 25% weight
        if (isset($vitals['LCP_avg'])) {
            $lcp = $vitals['LCP_avg'];
            if ($lcp <= 2500) $score += 25; // Good
            elseif ($lcp <= 4000) $score += 15; // Needs improvement
            // else: 0 for poor
            $totalWeight += 25;
        }

        // FID (First Input Delay) - 10% weight
        if (isset($vitals['FID_avg'])) {
            $fid = $vitals['FID_avg'];
            if ($fid <= 100) $score += 10; // Good
            elseif ($fid <= 300) $score += 5; // Needs improvement
            // else: 0 for poor
            $totalWeight += 10;
        }

        // CLS (Cumulative Layout Shift) - 15% weight
        if (isset($vitals['CLS_avg'])) {
            $cls = $vitals['CLS_avg'];
            if ($cls <= 0.1) $score += 15; // Good
            elseif ($cls <= 0.25) $score += 7.5; // Needs improvement
            // else: 0 for poor
            $totalWeight += 15;
        }

        // FCP (First Contentful Paint) - 10% weight
        if (isset($vitals['FCP_avg'])) {
            $fcp = $vitals['FCP_avg'];
            if ($fcp <= 1800) $score += 10; // Good
            elseif ($fcp <= 3000) $score += 5; // Needs improvement
            // else: 0 for poor
            $totalWeight += 10;
        }

        // TTFB (Time to First Byte) - 5% weight
        if (isset($vitals['TTFB_avg'])) {
            $ttfb = $vitals['TTFB_avg'];
            if ($ttfb <= 800) $score += 5; // Good
            elseif ($ttfb <= 1800) $score += 2.5; // Needs improvement
            // else: 0 for poor
            $totalWeight += 5;
        }

        return $totalWeight > 0 ? round(($score / $totalWeight) * 100) : 0;
    }

    /**
     * Log performance data to file
     */
    public static function logToFile($filename = 'performance.log') {
        $logData = json_encode([
            'timestamp' => time(),
            'metrics' => self::$metrics,
            'stats' => self::getStats()
        ]);

        file_put_contents($filename, $logData . PHP_EOL, FILE_APPEND);
    }

    /**
     * Generate performance report
     */
    public static function generateReport() {
        $stats = self::getStats();

        $report = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Performance Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .metric { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #007bff; }
        .score { font-size: 24px; font-weight: bold; color: #28a745; }
        .warning { border-left-color: #ffc107; }
        .danger { border-left-color: #dc3545; }
        .good { border-left-color: #28a745; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📊 Photo Gallery Performance Report</h1>
        <div class='metric'>
            <h3>Overall Performance Score</h3>
            <div class='score'>" . $stats['performance_score'] . "/100</div>
        </div>

        <h2>Core Web Vitals</h2>";

        foreach (['LCP', 'FID', 'CLS', 'FCP', 'TTFB'] as $vital) {
            if (isset($stats['core_web_vitals'][$vital . '_avg'])) {
                $avg = round($stats['core_web_vitals'][$vital . '_avg'], 2);
                $count = $stats['core_web_vitals'][$vital . '_count'];
                $class = 'good';

                // Determine status class
                switch ($vital) {
                    case 'LCP':
                        $class = $avg <= 2500 ? 'good' : ($avg <= 4000 ? 'warning' : 'danger');
                        break;
                    case 'FID':
                        $class = $avg <= 100 ? 'good' : ($avg <= 300 ? 'warning' : 'danger');
                        break;
                    case 'CLS':
                        $class = $avg <= 0.1 ? 'good' : ($avg <= 0.25 ? 'warning' : 'danger');
                        break;
                    case 'FCP':
                        $class = $avg <= 1800 ? 'good' : ($avg <= 3000 ? 'warning' : 'danger');
                        break;
                    case 'TTFB':
                        $class = $avg <= 800 ? 'good' : ($avg <= 1800 ? 'warning' : 'danger');
                        break;
                }

                $report .= "<div class='metric $class'>
                    <h4>$vital (Average)</h4>
                    <p><strong>{$avg}ms</strong> (from {$count} measurements)</p>
                </div>";
            }
        }

        $report .= "<h2>Custom Metrics</h2>";
        if (!empty($stats['custom_metrics'])) {
            $report .= "<table>
                <tr><th>Metric</th><th>Average</th><th>Count</th></tr>";

            foreach ($stats['custom_metrics'] as $name => $values) {
                $avg = round(array_sum($values) / count($values), 2);
                $count = count($values);
                $report .= "<tr><td>$name</td><td>{$avg}ms</td><td>$count</td></tr>";
            }

            $report .= "</table>";
        } else {
            $report .= "<p>No custom metrics recorded yet.</p>";
        }

        $report .= "<div class='metric'>
            <h3>Total Requests</h3>
            <p><strong>{$stats['total_requests']}</strong> in the last hour</p>
        </div>
    </div>
</body>
</html>";

        return $report;
    }
}

// Handle AJAX performance data collection
if (isset($_POST['performance_data'])) {
    $data = json_decode($_POST['performance_data'], true);

    if ($data) {
        foreach ($data as $metric) {
            PerformanceMonitor::trackMetric(
                $metric['name'],
                $metric['value'],
                $metric['metadata'] ?? []
            );
        }

        // Log to file periodically
        if (rand(1, 10) === 1) { // 10% chance to log
            PerformanceMonitor::logToFile();
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Handle report generation
if (isset($_GET['report'])) {
    echo PerformanceMonitor::generateReport();
    exit;
}
?>