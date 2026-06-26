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
$reason = $_POST['reason'] ?? '';

$products = $_POST['products'] ?? [];
$quantities = $_POST['quantities'] ?? [];
$rates = $_POST['rates'] ?? [];

if (empty($dealer_name) || empty($products)) {
    echo json_encode(['status' => 'error', 'message' => 'Dealer and at least one returned item are required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $insertReturn = $pdo->prepare("
        INSERT INTO purchase_returns (product_id, dealer_name, quantity, return_rate, reason) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // For a purchase return, we are returning stock back to the dealer. So we DEDUCT stock.
    $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

    for ($i = 0; $i < count($products); $i++) {
        $product_id = (int)$products[$i];
        $qty = (int)$quantities[$i];
        $rate = (float)$rates[$i];

        if ($product_id > 0 && $qty > 0) {
            // Insert into ledger
            $insertReturn->execute([$product_id, $dealer_name, $qty, $rate, $reason]);
            
            // Deduct master stock
            $updateStock->execute([$qty, $product_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Purchase Return saved successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
