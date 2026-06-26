<?php
require_once '../config.php';

// Accept JSON payload
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (!isset($_SESSION['user_id']) || !isset($data['action']) || $data['action'] !== 'process_invoice') {
    jsonResponse('error', 'Invalid or unauthorized request.');
}

$stockist_id = (int)($data['stockist_id'] ?? 0);
$items = $data['items'] ?? [];

if ($stockist_id <= 0 || empty($items)) {
    jsonResponse('error', 'Valid stockist and cart items are required.');
}

try {
    $pdo->beginTransaction();

    $grand_total = 0;
    
    // First Pass: Validate stock and calculate totals server-side to prevent tampering
    foreach ($items as $item) {
        $prod_id = (int)$item['product_id'];
        $qty = (int)$item['quantity'];

        if ($qty <= 0) {
            throw new Exception("Invalid quantity for one of the products.");
        }

        $checkStmt = $pdo->prepare("SELECT name, stock_quantity, price FROM products WHERE id = ? FOR UPDATE");
        $checkStmt->execute([$prod_id]);
        $prod = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            throw new Exception("Product ID $prod_id not found.");
        }

        if ($prod['stock_quantity'] < $qty) {
            throw new Exception("Insufficient stock for " . $prod['name'] . ". Available: " . $prod['stock_quantity']);
        }

        $grand_total += ($qty * (float)$prod['price']);
    }

    // Generate Invoice Number
    $invNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    // Insert Invoice Record
    $invStmt = $pdo->prepare("INSERT INTO stockist_invoices (stockist_id, invoice_number, subtotal, total_amount) VALUES (?, ?, ?, ?)");
    $invStmt->execute([$stockist_id, $invNumber, $grand_total, $grand_total]);
    $invoice_id = $pdo->lastInsertId();

    // Second Pass: Apply changes to inventory and record line items
    $deductStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
    $upsertInvStmt = $pdo->prepare("INSERT INTO stockist_inventory (stockist_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    $lineItemStmt = $pdo->prepare("INSERT INTO stockist_invoice_items (invoice_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");

    foreach ($items as $item) {
        $prod_id = (int)$item['product_id'];
        $qty = (int)$item['quantity'];
        
        // Fetch accurate price again to be fully safe
        $priceStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $priceStmt->execute([$prod_id]);
        $unit_price = (float)$priceStmt->fetchColumn();
        $line_total = $unit_price * $qty;

        // 1. Deduct main stock
        $deductStmt->execute([$qty, $prod_id]);

        // 2. Add to stockist inventory
        $upsertInvStmt->execute([$stockist_id, $prod_id, $qty, $qty]);

        // 3. Record invoice item
        $lineItemStmt->execute([$invoice_id, $prod_id, $qty, $unit_price, $line_total]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Invoice created', 'invoice_id' => $invoice_id]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse('error', $e->getMessage());
}

jsonResponse('error', 'Unknown error occurred.');
