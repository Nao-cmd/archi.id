<?php
// Database Configuration
define('DB_HOST', 'sql311.infinityfree.com');
define('DB_USER', 'if0_40643216');
define('DB_PASS', 'Sawir200605266');
define('DB_NAME', 'if0_40643216_db_archiid');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
