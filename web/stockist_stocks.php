<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$stockist_id = $_GET['id'] ?? 0;
if (!$stockist_id) {
    echo "Invalid stockist ID.";
    exit;
}

// Fetch stockist details
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'Stockist'");
$stmt->execute([$stockist_id]);
$stockist = $stmt->fetch();

if (!$stockist) {
    echo "Stockist not found.";
    exit;
}

// Ensure the table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS `stockist_inventory` ( 
    `id` int(11) NOT NULL AUTO_INCREMENT, 
    `stockist_id` int(11) NOT NULL, 
    `product_id` int(11) NOT NULL, 
    `quantity` int(11) DEFAULT 0, 
    `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
    PRIMARY KEY (`id`), 
    UNIQUE KEY `stockist_product` (`stockist_id`, `product_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Handle Add Stock to Stockist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $product_id = $_POST['product_id'] ?? 0;
    $amount = (int)($_POST['stock_amount'] ?? 0);
    
    if ($product_id > 0 && $amount > 0) {
        try {
            // Check if admin has enough stock
            $checkStmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $checkStmt->execute([$product_id]);
            $productData = $checkStmt->fetch();

            if ($productData && $productData['stock_quantity'] >= $amount) {
                $pdo->beginTransaction();

                // Deduct from main stock
                $deductStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $deductStmt->execute([$amount, $product_id]);

                // Upsert stockist inventory
                $stmt = $pdo->prepare("INSERT INTO stockist_inventory (stockist_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                $stmt->execute([$stockist_id, $product_id, $amount, $amount]);
                
                $pdo->commit();
                $success = "Stock added successfully to stockist.";
            } else {
                $error = "Insufficient stock in main inventory. Current available stock: " . ($productData['stock_quantity'] ?? 0);
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to update stock: " . $e->getMessage();
        }
    } else {
        $error = "Valid product and amount are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockist Inventory - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
        <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <div class="min-w-0">
            <div>
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-800 truncate">Inventory: <?php echo htmlspecialchars($stockist['name']); ?></h1>
                <a href="stockists.php" class="text-sm text-indigo-600 hover:underline">&larr; Back to Stockists</a>
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        <button onclick="document.getElementById('stockModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow hover:bg-indigo-700">+ Assign Stock</button>
    </div>
</header>
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Medicine Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Stockist Quantity</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT p.name, si.quantity, si.last_updated 
                                FROM stockist_inventory si
                                JOIN products p ON si.product_id = p.id
                                WHERE si.stockist_id = ?
                                ORDER BY p.name ASC
                            ");
                            $stmt->execute([$stockist_id]);
                            $inventory = $stmt->fetchAll();
                            
                            if(count($inventory) == 0) {
                                echo '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">No stock assigned to this stockist yet.</td></tr>';
                            }

                            foreach($inventory as $inv) {
                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 font-bold text-sm text-gray-900">'.htmlspecialchars($inv['name']).'</td>';
                                echo '<td class="px-6 py-4 text-sm font-medium">'.$inv['quantity'].' Units</td>';
                                echo '<td class="px-6 py-4 text-sm text-gray-500">'.date('d M Y, h:i A', strtotime($inv['last_updated'])).'</td>';
                                echo '</tr>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </tbody>
                </table>
</div>
            </div>
        </div>
    </main>

    <!-- Assign Stock Modal -->
    <div id="stockModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-4">Assign Stock to <?php echo htmlspecialchars($stockist['name']); ?></h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_stock">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                    <select name="product_id" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                        <option value="">-- Select a Medicine --</option>
                        <?php 
                        $products = $pdo->query("SELECT id, name, stock_quantity FROM products ORDER BY name ASC")->fetchAll();
                        foreach($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (Avail: <?php echo $p['stock_quantity']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (Units)</label>
                    <input type="number" name="stock_amount" min="1" placeholder="e.g. 50" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                </div>
                <div class="flex justify-end space-x-2 mt-4 pt-4 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('stockModal').classList.add('hidden')" class="px-4 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm">Assign Stock</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
