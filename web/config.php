<?php
// web/config.php
session_start();

// Force local environment for mobile testing
$is_local = true;

if ($is_local) {
    // Local Database Credentials
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'sanjeevni');
} else {
    // Live Database Credentials (Update these with your hosting details)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u673864504_praanved');
    define('DB_PASS', '@Pranveda_2001');
    define('DB_NAME', 'u673864504_praanved');
}

// Global Application Setting
define('APP_NAME', 'Praanveda Ayurshakti');

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

    // 1.5 Add doctor profile fields to users if missing
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'clinic_name'");
    if (!$colCheck->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER phone");
        $pdo->exec("ALTER TABLE users ADD COLUMN clinic_name VARCHAR(150) DEFAULT NULL AFTER email");
        $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL AFTER clinic_name");
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
    // 5. Ensure order_status_history table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_status_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `status` varchar(50) NOT NULL,
        `remarks` text DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // 6. Ensure doctor_orders table exists (Plan 2: MR -> Doctor -> Distributor flow)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `doctor_orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `mr_id` int(11) NOT NULL,
        `doctor_id` int(11) NOT NULL,
        `status` enum('Pending','Confirmed','Dispatched','Delivered','Cancelled') DEFAULT 'Pending',
        `notes` text DEFAULT NULL,
        `total_amount` decimal(10,2) DEFAULT 0.00,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`mr_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // 7. Add courier, remark and payment columns to doctor_orders if missing
    $colCheck = $pdo->query("SHOW COLUMNS FROM doctor_orders LIKE 'status_remarks'");
    if (!$colCheck->fetch()) {
        $pdo->exec("ALTER TABLE doctor_orders ADD COLUMN status_remarks TEXT DEFAULT NULL AFTER status");
        $pdo->exec("ALTER TABLE doctor_orders ADD COLUMN courier_company VARCHAR(100) DEFAULT NULL AFTER status_remarks");
        $pdo->exec("ALTER TABLE doctor_orders ADD COLUMN awb_no VARCHAR(100) DEFAULT NULL AFTER courier_company");
    }

    $colCheck = $pdo->query("SHOW COLUMNS FROM doctor_orders LIKE 'payment_status'");
    if (!$colCheck->fetch()) {
        $pdo->exec("ALTER TABLE doctor_orders ADD COLUMN payment_status ENUM('Pending','Paid') DEFAULT 'Pending' AFTER total_amount");
        $pdo->exec("ALTER TABLE doctor_orders ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER payment_status");
        $pdo->exec("ALTER TABLE doctor_orders ADD COLUMN payment_date TIMESTAMP NULL DEFAULT NULL AFTER payment_method");
    }

    $colCheck = $pdo->query("SHOW COLUMNS FROM doctor_orders LIKE 'stockist_id'");
    if (!$colCheck->fetch()) {
        $pdo->exec("ALTER TABLE doctor_orders ADD COLUMN stockist_id INT(11) DEFAULT NULL AFTER doctor_id");
    }

    // 8. Ensure doctor_order_items table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `doctor_order_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL DEFAULT 1,
        `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`order_id`) REFERENCES `doctor_orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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