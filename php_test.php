<?php
// php_test.php - Test if PHP is working
echo "<h1>PHP Test</h1>";
echo "<p>If you can see this, PHP is working!</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>GD Extension: " . (function_exists('imagecreatefromjpeg') ? 'Available' : 'NOT Available') . "</p>";
echo "<p>Database: " . (extension_loaded('mysqli') ? 'MySQL Available' : 'MySQL NOT Available') . "</p>";
?>