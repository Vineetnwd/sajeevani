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

$stockist_id = $_POST['stockist_id'] ?? '';
$reason = $_POST['reason'] ?? '';

$products = $_POST['products'] ?? [];
$quantities = $_POST['quantities'] ?? [];
$rates = $_POST['rates'] ?? [];

if (empty($stockist_id) || empty($products)) {
    echo json_encode(['status' => 'error', 'message' => 'Stockist and at least one returned item are required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $insertReturn = $pdo->prepare("
        INSERT INTO sale_returns (product_id, stockist_id, quantity, return_rate, reason) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // For a sale return, the stockist returns goods to the company. So we ADD to our stock.
    $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
    
    // We also need to DEDUCT from the stockist's personal inventory
    $deductStockistInv = $pdo->prepare("UPDATE stockist_inventory SET quantity = GREATEST(0, quantity - ?) WHERE stockist_id = ? AND product_id = ?");

    for ($i = 0; $i < count($products); $i++) {
        $product_id = (int)$products[$i];
        $qty = (int)$quantities[$i];
        $rate = (float)$rates[$i];

        if ($product_id > 0 && $qty > 0) {
            // Insert into ledger
            $insertReturn->execute([$product_id, $stockist_id, $qty, $rate, $reason]);
            
            // Add back to master stock
            $updateStock->execute([$qty, $product_id]);
            
            // Deduct from stockist inventory
            $deductStockistInv->execute([$qty, $stockist_id, $product_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Sale Return saved successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
