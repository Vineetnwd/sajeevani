<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$pdo->exec("CREATE TABLE IF NOT EXISTS `dealers` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(150) NOT NULL, `phone` varchar(20), `created_at` timestamp DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$dealersList = $pdo->query("SELECT * FROM dealers ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Stock Ledger</h1>
        </header>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Medicine Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Current Stock</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Pricing & P/L</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        // First auto-create purchases table quietly if it's missing just for safety before executing the SELECT.
                        $pdo->exec("CREATE TABLE IF NOT EXISTS `purchases` ( `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `dealer_name` varchar(150), `quantity` int(11) NOT NULL, `purchase_rate` decimal(10,2) NOT NULL, `created_at` timestamp DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                        
                        try {
                            $stmt = $pdo->query("
                                SELECT p.id, p.name, p.price as sell_price, p.stock_quantity,
                                       (SELECT purchase_rate FROM purchases pr WHERE pr.product_id = p.id ORDER BY pr.id DESC LIMIT 1) as buy_price
                                FROM products p 
                                ORDER BY p.stock_quantity ASC
                            ");
                            $products = $stmt->fetchAll();
                            
                            if(count($products) == 0) {
                                echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No medicines available in the system yet.</td></tr>';
                            }

                            foreach($products as $p) {
                                $stockStatus = ($p['stock_quantity'] <= 10) ? '<span class="text-red-600 bg-red-50 px-2 py-1 rounded font-bold text-xs">Low Stock</span>' : '<span class="text-green-600 bg-green-50 px-2 py-1 rounded font-bold text-xs">In Stock</span>';
                                
                                $buyPrice = $p['buy_price'] ? (float)$p['buy_price'] : 0.00;
                                $sellPrice = (float)$p['sell_price'];
                                $margin = $sellPrice - $buyPrice;
                                $marginColor = ($margin > 0) ? 'text-green-600' : (($margin < 0) ? 'text-red-500' : 'text-gray-500');
                                
                                $plHtml = '<div class="text-xs text-gray-500">Buy: <span class="font-medium text-gray-900">₹'.$buyPrice.'</span> • Sell: <span class="font-medium text-gray-900">₹'.$sellPrice.'</span></div>';
                                $plHtml .= '<div class="text-xs font-bold mt-0.5 '.$marginColor.'">Margin (P/L): ₹'.$margin.'</div>';

                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 font-bold text-sm text-gray-900">'.htmlspecialchars($p['name']).'</td>';
                                echo '<td class="px-6 py-4 text-sm font-medium">'.$p['stock_quantity'].' Units</td>';
                                echo '<td class="px-6 py-4">'.$plHtml.'</td>';
                                echo '<td class="px-6 py-4">'.$stockStatus.'</td>';
                                echo '<td class="px-6 py-4 text-right"><button onclick="openStockModal('.$p['id'].', \''.htmlspecialchars($p['name'], ENT_QUOTES).'\')" class="text-sm px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded shadow-sm">+ Add Stock</button></td>';
                                echo '</tr>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Stock Update Modal -->
    <div id="stockModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-1">Add Stock</h3>
            <p id="modalMedName" class="text-sm text-gray-500 mb-4"></p>
            <form id="stockForm" class="space-y-4">
                <input type="hidden" name="product_id" id="stockProductId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dealer / Vendor Name</label>
                    <select name="dealer_name" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                        <option value="">-- Select a Dealer --</option>
                        <?php foreach($dealersList as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['name']); ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Rate (₹)</label>
                        <input type="number" step="0.01" name="purchase_rate" placeholder="Per unit" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (Units)</label>
                        <input type="number" name="stock_amount" min="1" placeholder="e.g. 50" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-4 pt-4 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('stockModal').classList.add('hidden')" class="px-4 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm">Update Ledger</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStockModal(id, name) {
            document.getElementById('stockProductId').value = id;
            document.getElementById('modalMedName').innerText = "Medicine: " + name;
            document.getElementById('stockModal').classList.remove('hidden');
        }

        document.getElementById('stockForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = "Updating...";
            try {
                const formData = new FormData(this);
                const response = await fetch('api/inventory.php?action=add_stock', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                    btn.innerHTML = "Update Ledger";
                }
            } catch(e) {
                alert('Connection error');
            }
        });
    </script>
</body>
</html>
