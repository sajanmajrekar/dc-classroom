<?php ob_start();
date_default_timezone_set("Asia/Kolkata");

$isLocalHost = in_array($_SERVER['HTTP_HOST'] ?? '', array('localhost', '127.0.0.1'), true)
    || stripos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
    || stripos(__DIR__, 'xampp') !== false;

if ($isLocalHost) {
    define('DB_SERVER', '127.0.0.1');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_DATABASE', 'classroom'); // Note: changed from hrcrm to classroom
    define('DB_PORT', 3307);
} else {
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'digiplmk_hrcrm');
    define('DB_PASSWORD', 's5U@[c;?7B=k');
    define('DB_DATABASE', 'digiplmk_hrcrm');
    define('DB_PORT', 3306);
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$connect = $conn;

// Ensure underlying PDO endpoints continue to work smoothly by wrapping via the newly provided credentials.
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_DATABASE . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}
?>
