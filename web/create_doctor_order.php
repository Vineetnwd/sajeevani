<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Admin'])) {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_order') {
    $doctor_id = $_POST['doctor_id'] ?? 0;
    $stockist_id = $_POST['stockist_id'] ?? 0;
    $notes = trim($_POST['notes'] ?? '');
    
    // Process items
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];
    
    if (empty($doctor_id) || empty($stockist_id) || empty($product_ids)) {
        $error = "Doctor, Stockist, and at least one item are required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $total_amount = 0;
            $items_to_insert = [];
            
            // Check stock and prepare items
            foreach ($product_ids as $index => $pid) {
                $qty = (int)($quantities[$index] ?? 1);
                $price = (float)($prices[$index] ?? 0);
                
                if ($pid > 0 && $qty > 0) {
                    // Check stock at stockist
                    $chk = $pdo->prepare("SELECT quantity FROM stockist_inventory WHERE stockist_id = ? AND product_id = ?");
                    $chk->execute([$stockist_id, $pid]);
                    $inv = $chk->fetch();
                    
                    if (!$inv || $inv['quantity'] < $qty) {
                        throw new Exception("Insufficient stock at the selected stockist for one or more items.");
                    }
                    
                    $items_to_insert[] = [
                        'product_id' => $pid,
                        'quantity' => $qty,
                        'unit_price' => $price
                    ];
                    $total_amount += ($qty * $price);
                }
            }
            
            if (empty($items_to_insert)) {
                throw new Exception("No valid items provided.");
            }
            
            // Insert order
            $stmt = $pdo->prepare("INSERT INTO doctor_orders (mr_id, doctor_id, stockist_id, status, notes, total_amount) VALUES (?, ?, ?, 'Confirmed', ?, ?)");
            // Using Admin ID as MR ID for now, since Admin is creating it
            $stmt->execute([$_SESSION['user_id'], $doctor_id, $stockist_id, $notes, $total_amount]);
            $order_id = $pdo->lastInsertId();
            
            // Insert items
            $itemStmt = $pdo->prepare("INSERT INTO doctor_order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            foreach ($items_to_insert as $item) {
                $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['unit_price']]);
            }
            
            $pdo->commit();
            $success = "Order #DO-$order_id created and assigned successfully!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Doctor Order - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Create Direct Doctor Order</h1>
                <a href="doctor_orders.php" class="text-sm text-indigo-600 hover:underline">&larr; Back to Orders</a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-4xl mx-auto">
                <form method="POST" id="orderForm">
                    <input type="hidden" name="action" value="create_order">
                    
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Doctor</label>
                            <select name="doctor_id" required class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-100 outline-none">
                                <option value="">-- Choose Doctor --</option>
                                <?php
                                $doctors = $pdo->query("SELECT id, name, phone FROM users WHERE role = 'Doctor' AND status = 'Active'")->fetchAll();
                                foreach ($doctors as $d) {
                                    echo '<option value="'.$d['id'].'">Dr. '.htmlspecialchars($d['name']).' ('.$d['phone'].')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Stockist</label>
                            <select name="stockist_id" required class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-100 outline-none">
                                <option value="">-- Choose Stockist --</option>
                                <?php
                                $stockists = $pdo->query("SELECT id, name FROM users WHERE role = 'Stockist' AND status = 'Active'")->fetchAll();
                                foreach ($stockists as $s) {
                                    echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['name']).'</option>';
                                }
                                ?>
                            </select>
                            <p class="text-[10px] text-gray-500 mt-1">Ensure stockist has enough inventory for the items below.</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <input type="text" name="notes" placeholder="Optional notes..." class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-100 outline-none">
                    </div>

                    <div class="mb-6">
                        <h3 class="font-bold text-gray-800 mb-3 border-b pb-2">Order Items</h3>
                        <div id="itemsContainer" class="space-y-3">
                            <!-- Item Row -->
                            <div class="item-row flex space-x-3 items-end">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Product</label>
                                    <select name="product_id[]" required onchange="updatePrice(this)" class="product-select w-full border border-gray-300 p-2 rounded-lg focus:ring focus:ring-indigo-100 text-sm">
                                        <option value="" data-price="0">-- Select --</option>
                                        <?php
                                        $products = $pdo->query("SELECT id, name, price FROM products ORDER BY name ASC")->fetchAll();
                                        foreach ($products as $p) {
                                            echo '<option value="'.$p['id'].'" data-price="'.$p['price'].'">'.htmlspecialchars($p['name']).' (₹'.$p['price'].')</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="w-24">
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Qty</label>
                                    <input type="number" name="quantity[]" min="1" value="1" required class="w-full border border-gray-300 p-2 rounded-lg text-sm text-center">
                                </div>
                                <div class="w-32">
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Price (₹)</label>
                                    <input type="number" name="price[]" step="0.01" min="0" required class="price-input w-full border border-gray-300 p-2 rounded-lg text-sm bg-gray-50" readonly>
                                </div>
                                <button type="button" onclick="removeItem(this)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        <button type="button" onclick="addItem()" class="mt-4 px-4 py-2 bg-indigo-50 text-indigo-700 text-sm font-bold rounded-lg hover:bg-indigo-100 transition-colors">
                            + Add Another Item
                        </button>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-md hover:bg-indigo-700 transition-colors">
                            Create Direct Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function updatePrice(selectElement) {
            const priceInput = selectElement.closest('.item-row').querySelector('.price-input');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            priceInput.value = selectedOption.dataset.price;
        }

        function addItem() {
            const container = document.getElementById('itemsContainer');
            const firstRow = container.querySelector('.item-row');
            const newRow = firstRow.cloneNode(true);
            
            newRow.querySelector('select').value = '';
            newRow.querySelector('input[type="number"]').value = '1';
            newRow.querySelector('.price-input').value = '';
            
            container.appendChild(newRow);
        }

        function removeItem(button) {
            const container = document.getElementById('itemsContainer');
            if (container.querySelectorAll('.item-row').length > 1) {
                button.closest('.item-row').remove();
            } else {
                alert('You must have at least one item.');
            }
        }
    </script>
</body>
</html>
