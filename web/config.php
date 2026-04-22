<?php
// web/config.php
session_start();

$is_local = in_array($_SERVER['HTTP_HOST'], ['localhost', 'localhost:8000', '::1']);

if ($is_local) {
    // Local Database Credentials
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'sanjeevni');
} else {
    // Live Database Credentials (Update these with your hosting details)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u960515621_sanjeevani');
    define('DB_PASS', 'Sanjeevani@2026');
    define('DB_NAME', 'u960515621_sanjeevani');
}

// Global Application Setting
define('APP_NAME', 'Sanjeevani Life Ayurvedic Platform');

date_default_timezone_set('Asia/Kolkata');

try {
    // Creating PDO connection for secure interactions
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);

    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Old-school MySQLi connection for legacy compatibility, in case you prefer it
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(["status" => "error", "message" => "MySQLi Connection failed: " . $conn->connect_error]));
    }
    // Global Auto-Migration (Ensures new columns exist across local and live environments)
    // 1. Add doctor_response to consultations if missing
    $colCheck = $pdo->query("SHOW COLUMNS FROM consultations LIKE 'doctor_response'");
    if (!$colCheck->fetch()) {
        $pdo->exec("ALTER TABLE consultations ADD COLUMN doctor_response TEXT DEFAULT NULL AFTER status");
    }
    // 2. Add status_remarks to orders if missing
    $colCheck = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status_remarks'");
    if (!$colCheck->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN status_remarks TEXT DEFAULT NULL AFTER status");
    }
    // 3. Ensure purchases table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `purchases` ( `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `dealer_name` varchar(150), `quantity` int(11) NOT NULL, `purchase_rate` decimal(10,2) NOT NULL, `created_at` timestamp DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // 4. Ensure dealers table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `dealers` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(150) NOT NULL, `phone` varchar(20), `created_at` timestamp DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch (PDOException $e) {
    // Return structured JSON error if connection fails (good for both API and Web)
    die(json_encode([
        "status" => "error",
        "message" => "Database Connection Failed",
        "debug" => $is_local ? $e->getMessage() : "Check Live DB Credentials"
    ]));
}

// Utility function to respond safely via API
function jsonResponse($status, $message, $data = null)
{
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}
?>