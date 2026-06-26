<?php
require_once 'config.php';
try {
    $pdo->exec("ALTER TABLE `stockist_invoices` ADD COLUMN `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`");
    $pdo->exec("ALTER TABLE `stockist_invoices` ADD COLUMN `payment_status` ENUM('Due', 'Partial', 'Paid') NOT NULL DEFAULT 'Due' AFTER `amount_paid`");
    echo "DB Updated!";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage();
}
?>
