<?php
require_once '../config.php';

$action = $_GET['action'] ?? '';

if ($action === 'add_product') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $name = $_POST['name'] ?? '';
    $desc = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = 0; // Stock maintained separately

    if (empty($name) || empty($price)) {
        jsonResponse('error', 'Name and Price are mandatory');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock_quantity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $desc, $price, $stock]);
        jsonResponse('success', 'Medicine added successfully');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to add medicine', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'add_dealer') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (empty($name)) {
        jsonResponse('error', 'Dealer Name is mandatory');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `dealers` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(150) NOT NULL, `phone` varchar(20), `created_at` timestamp DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    try {
        $stmt = $pdo->prepare("INSERT INTO dealers (name, phone) VALUES (?, ?)");
        $stmt->execute([$name, $phone]);
        jsonResponse('success', 'Dealer added successfully');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to add dealer');
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
    
    if($product_id == 0 || $amount <= 0) {
        jsonResponse('error', 'Valid Medicine and Stock Amount required.');
    }
    
    // Auto-create purchases ledger table if not exists (for easy deployment)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `purchases` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `product_id` int(11) NOT NULL,
      `dealer_name` varchar(150),
      `quantity` int(11) NOT NULL,
      `purchase_rate` decimal(10,2) NOT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("INSERT INTO purchases (product_id, dealer_name, quantity, purchase_rate) VALUES (?, ?, ?, ?)")
            ->execute([$product_id, $dealer, $amount, $rate]);
            
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmt->execute([$amount, $product_id]);
        
        $pdo->commit();
        jsonResponse('success', 'Stock updated successfully');
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonResponse('error', 'Failed to update stock ledger');
    }
}

if ($action === 'edit_price') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }
    
    $product_id = $_POST['product_id'] ?? 0;
    $price = (float)($_POST['price'] ?? 0);
    
    if ($product_id == 0 || $price < 0) {
        jsonResponse('error', 'Valid product ID and price are required');
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET price = ? WHERE id = ?");
        $stmt->execute([$price, $product_id]);
        jsonResponse('success', 'Price updated successfully');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to update price');
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
