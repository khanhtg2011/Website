<?php
// check_activity.php
session_start();
$timeout = 900; // 15 phút
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    echo "logout";
} else {
    $_SESSION['last_activity'] = time();
    echo "active";
}
?>