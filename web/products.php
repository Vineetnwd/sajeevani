<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Medicines</h1>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 bg-green-600 text-white rounded-lg">+ Add</button>
        </header>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
                            $products = $stmt->fetchAll();
                            foreach($products as $p) {
                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 font-bold">'.htmlspecialchars($p['name']).'</td>';
                                echo '<td class="px-6 py-4">₹'.htmlspecialchars($p['price']).'</td>';
                                echo '<td class="px-6 py-4">'.$p['stock_quantity'].'</td>';
                                echo '<td class="px-6 py-4 text-right"><button onclick="openEditPriceModal('.$p['id'].', \''.htmlspecialchars($p['price']).'\')" class="text-sm px-4 py-1.5 border border-indigo-200 text-indigo-700 hover:bg-indigo-50 font-medium rounded shadow-sm">Edit Price</button></td>';
                                echo '</tr>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-4">Add Medicine</h3>
            <form id="productForm" class="space-y-4">
                <input type="text" name="name" placeholder="Medicine Name" required class="w-full border p-2 rounded focus:ring focus:ring-green-100">
                <input type="number" step="0.01" name="price" placeholder="Price (₹)" required class="w-full border p-2 rounded focus:ring focus:ring-green-100">
                <div class="bg-gray-50 p-2 text-xs text-gray-500 rounded">Note: Stock is maintained separately in Stock Management. Initial stock will be 0.</div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-3 py-1 border rounded">Cancel</button>
                    <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="editPriceModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-4">Edit Selling Price</h3>
            <form id="editPriceForm" class="space-y-4">
                <input type="hidden" name="product_id" id="editProductId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Selling Price (₹)</label>
                    <input type="number" step="0.01" name="price" id="editProductPrice" placeholder="Selling Price (₹)" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-green-100">
                </div>
                <div class="flex justify-end space-x-2 mt-4 pt-2">
                    <button type="button" onclick="document.getElementById('editPriceModal').classList.add('hidden')" class="px-3 py-1.5 border rounded text-sm hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded text-sm shadow-sm">Update Price</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('productForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const response = await fetch('api/inventory.php?action=add_product', { method: 'POST', body: formData });
            if ((await response.json()).status === 'success') window.location.reload();
        });

        function openEditPriceModal(id, currentPrice) {
            document.getElementById('editProductId').value = id;
            document.getElementById('editProductPrice').value = currentPrice;
            document.getElementById('editPriceModal').classList.remove('hidden');
        }

        document.getElementById('editPriceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const response = await fetch('api/inventory.php?action=edit_price', { method: 'POST', body: formData });
            if ((await response.json()).status === 'success') window.location.reload();
        });
    </script>
</body>
</html>
