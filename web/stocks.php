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
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .ts-wrapper { padding: 0 !important; border: none !important; }
        .ts-control { border: 1px solid #d1d5db !important; border-radius: 0.5rem !important; padding: 0.625rem 0.75rem !important; font-size: 0.875rem !important; box-shadow: none !important; background-color: white !important; min-height: 42px !important; display: flex; align-items: center; }
        .ts-control.focus { border-color: #5eead4 !important; box-shadow: 0 0 0 2px #ccfbf1 !important; outline: none !important; }
        .ts-dropdown { border-radius: 0.5rem !important; border: 1px solid #e5e7eb !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important; font-size: 0.875rem !important; overflow: hidden; margin-top: 4px; }
        .ts-dropdown .option { padding: 0.5rem 1rem !important; transition: background-color 0.1s ease; }
        .ts-dropdown .active { background-color: #f3f4f6 !important; color: #111827 !important; }
    </style>
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
            <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Stock Ledger</h1>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        <input type="text" id="searchInput" oninput="filterStocks()" placeholder="Search Products..." class="hidden sm:block text-sm border border-gray-200 rounded-lg px-2 py-1.5 sm:px-3 sm:py-2 focus:ring-2 focus:ring-teal-100 outline-none w-32 sm:w-48 transition-all duration-300 focus:w-64">
    </div>
</header>
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Product Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Current Stock</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Pricing & P/L</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Action</th>
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

                            if (count($products) == 0) {
                                echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No products available in the system yet.</td></tr>';
                            }

                            foreach ($products as $p) {
                                $stockStatus = ($p['stock_quantity'] <= 10) ? '<span class="text-red-600 bg-red-50 px-2 py-1 rounded font-bold text-xs">Low Stock</span>' : '<span class="text-teal-600 bg-teal-50 px-2 py-1 rounded font-bold text-xs">In Stock</span>';

                                $buyPrice = $p['buy_price'] ? (float) $p['buy_price'] : 0.00;
                                $sellPrice = (float) $p['sell_price'];
                                $margin = $sellPrice - $buyPrice;
                                $marginColor = ($margin > 0) ? 'text-teal-600' : (($margin < 0) ? 'text-red-500' : 'text-gray-500');

                                $plHtml = '<div class="text-xs text-gray-500">Buy: <span class="font-medium text-gray-900">₹' . $buyPrice . '</span> • Sell: <span class="font-medium text-gray-900">₹' . $sellPrice . '</span></div>';
                                $plHtml .= '<div class="text-xs font-bold mt-0.5 ' . $marginColor . '">Margin (P/L): ₹' . $margin . '</div>';

                                echo '<tr class="hover:bg-gray-50 stock-row" data-name="' . htmlspecialchars($p['name']) . '">';
                                echo '<td class="px-6 py-4 font-bold text-sm text-gray-900 whitespace-nowrap">' . htmlspecialchars($p['name']) . '</td>';
                                echo '<td class="px-6 py-4 text-sm font-medium whitespace-nowrap">' . $p['stock_quantity'] . ' Units</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap">' . $plHtml . '</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap">' . $stockStatus . '</td>';
                                echo '<td class="px-6 py-4 text-right whitespace-nowrap"><button onclick="openStockModal(' . $p['id'] . ', \'' . htmlspecialchars($p['name'], ENT_QUOTES) . '\')" class="text-sm px-4 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded shadow-sm whitespace-nowrap">+ Add Stock</button></td>';
                                echo '</tr>';
                            }
                        } catch (PDOException $e) {
                        }
                        ?>
                    </tbody>
                </table>
</div>
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
                    <select name="dealer_name" required
                        class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-teal-100">
                        <option value="">-- Select a Dealer --</option>
                        <?php foreach ($dealersList as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['name']); ?>">
                                <?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Invoice No.</label>
                    <input type="text" name="purchase_invoice" placeholder="e.g. INV-99283" required
                        class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-teal-100">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Rate (₹)</label>
                        <input type="number" step="0.01" name="purchase_rate" placeholder="Per unit" required
                            class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-teal-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (Units)</label>
                        <input type="number" name="stock_amount" min="1" placeholder="e.g. 50" required
                            class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-teal-100">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-4 pt-4 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('stockModal').classList.add('hidden')"
                        class="px-4 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-teal-600 hover:bg-teal-700 text-white rounded text-sm">Update
                        Ledger</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterStocks() {
            const search = document.getElementById('searchInput') ? document.getElementById('searchInput').value.toLowerCase() : '';
            const rows = document.querySelectorAll('.stock-row');
            
            rows.forEach(row => {
                const name = (row.dataset.name || '').toLowerCase();
                if (!search || name.includes(search)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function openStockModal(id, name) {
            document.getElementById('stockProductId').value = id;
            document.getElementById('modalMedName').innerText = "Product: " + name;
            document.getElementById('stockModal').classList.remove('hidden');
        }

        document.getElementById('stockForm').addEventListener('submit', async function (e) {
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
            } catch (e) {
                alert('Connection error');
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tsConfig = {
                create: false,
                sortField: { field: "text", direction: "asc" }
            };
            document.querySelectorAll('select').forEach((el) => {
                new TomSelect(el, tsConfig);
            });
        });
    </script>
</body>
</html>