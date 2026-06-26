<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$dealer_name = $_POST['dealer_name'] ?? '';
$invoice_no = $_POST['invoice_no'] ?? '';
$invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');

$products = $_POST['products'] ?? [];
$quantities = $_POST['quantities'] ?? [];
$rates = $_POST['rates'] ?? [];

if (empty($dealer_name) || empty($invoice_no) || empty($products)) {
    echo json_encode(['status' => 'error', 'message' => 'Dealer, Invoice No, and at least one item are required.']);
    exit;
}

// Convert date to timestamp for created_at
$created_at = $invoice_date . ' ' . date('H:i:s');

try {
    $pdo->beginTransaction();

    $insertPurchase = $pdo->prepare("
        INSERT INTO purchases (product_id, dealer_name, purchase_invoice_no, quantity, purchase_rate, created_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");

    for ($i = 0; $i < count($products); $i++) {
        $product_id = (int)$products[$i];
        $qty = (int)$quantities[$i];
        $rate = (float)$rates[$i];

        if ($product_id > 0 && $qty > 0 && $rate > 0) {
            // Insert into ledger
            $insertPurchase->execute([$product_id, $dealer_name, $invoice_no, $qty, $rate, $created_at]);
            
            // Update master stock
            $updateStock->execute([$qty, $product_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Purchase Invoice saved successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
