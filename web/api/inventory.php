<?php
require_once '../config.php';

$action = $_GET['action'] ?? '';

if ($action === 'add_product') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $name = $_POST['name'] ?? '';
    $package_qty = $_POST['package_qty'] ?? '';
    $desc = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = (int)($_POST['quantity'] ?? 0);

    if (empty($name) || empty($price)) {
        jsonResponse('error', 'Name and Price are mandatory');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, package_qty, description, price, stock_quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $package_qty, $desc, $price, $stock]);
        jsonResponse('success', 'Product added successfully');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to add product', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'add_dealer') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $gst_no = $_POST['gst_no'] ?? '';
    $state_id = !empty($_POST['state_id']) ? $_POST['state_id'] : null;
    $district_id = !empty($_POST['district_id']) ? $_POST['district_id'] : null;
    $block_id = !empty($_POST['block_id']) ? $_POST['block_id'] : null;

    if (empty($name)) {
        jsonResponse('error', 'Dealer Name is mandatory');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO dealers (name, phone, email, address, gst_no, state_id, district_id, block_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $address, $gst_no, $state_id, $district_id, $block_id]);
        jsonResponse('success', 'Dealer added successfully');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to add dealer');
    }
}

if ($action === 'edit_dealer') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $dealer_id = $_POST['dealer_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $gst_no = $_POST['gst_no'] ?? '';
    $state_id = !empty($_POST['state_id']) ? $_POST['state_id'] : null;
    $district_id = !empty($_POST['district_id']) ? $_POST['district_id'] : null;
    $block_id = !empty($_POST['block_id']) ? $_POST['block_id'] : null;

    if ($dealer_id == 0 || empty($name)) {
        jsonResponse('error', 'Valid dealer ID and Name are required');
    }

    try {
        $stmt = $pdo->prepare("UPDATE dealers SET name=?, phone=?, email=?, address=?, gst_no=?, state_id=?, district_id=?, block_id=? WHERE id=?");
        $stmt->execute([$name, $phone, $email, $address, $gst_no, $state_id, $district_id, $block_id, $dealer_id]);
        jsonResponse('success', 'Dealer updated successfully');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to update dealer');
    }
}

if ($action === 'add_stock') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }
    
    $product_id = $_POST['product_id'] ?? 0;
    $amount = (int)($_POST['stock_amount'] ?? 0);
    $dealer = $_POST['dealer_name'] ?? 'Unknown Dealer';
    $rate = (float)($_POST['purchase_rate'] ?? 0);
    $invoice_no = $_POST['purchase_invoice'] ?? 'N/A';
    
    if($product_id == 0 || $amount <= 0) {
        jsonResponse('error', 'Valid Product and Stock Amount required.');
    }
    
    // Auto-create purchases ledger table if not exists (for easy deployment)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `purchases` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `product_id` int(11) NOT NULL,
      `dealer_name` varchar(150),
      `purchase_invoice_no` varchar(100) DEFAULT 'N/A',
      `quantity` int(11) NOT NULL,
      `purchase_rate` decimal(10,2) NOT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    try {
        $pdo->exec("ALTER TABLE `purchases` ADD COLUMN `purchase_invoice_no` varchar(100) DEFAULT 'N/A' AFTER `dealer_name`");
    } catch (PDOException $e) {
        // Column likely already exists
    }
    
    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("INSERT INTO purchases (product_id, dealer_name, purchase_invoice_no, quantity, purchase_rate) VALUES (?, ?, ?, ?, ?)")
            ->execute([$product_id, $dealer, $invoice_no, $amount, $rate]);
            
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmt->execute([$amount, $product_id]);
        
        $pdo->commit();
        jsonResponse('success', 'Stock updated successfully');
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonResponse('error', 'Failed to update stock ledger');
    }
}

if ($action === 'edit_product') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }
    
    $product_id = $_POST['product_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $package_qty = $_POST['package_qty'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    
    if ($product_id == 0 || empty($name) || $price < 0) {
        jsonResponse('error', 'Valid product ID, name and price are required');
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, package_qty = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $package_qty, $price, $product_id]);
        jsonResponse('success', 'Product updated successfully');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to update product');
    }
}

if ($action === 'get_products') {
    try {
        $stmt = $pdo->query("SELECT id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name ASC");
        $products = $stmt->fetchAll();
        jsonResponse('success', 'Fetched products', $products);
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch products');
    }
}

jsonResponse('error', 'Invalid API Action');
?>
