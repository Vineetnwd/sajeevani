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
            <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Medicines</h1>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-green-600 text-white rounded-lg">+ Add</button>
    </div>
</header>
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Product Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Package</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Price</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Stock</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
                            $products = $stmt->fetchAll();
                            foreach($products as $p) {
                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 font-bold whitespace-nowrap">'.htmlspecialchars($p['name']).'</td>';
                                echo '<td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">'.htmlspecialchars($p['package_qty'] ?? '').'</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap">₹'.htmlspecialchars($p['price']).'</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap">'.$p['stock_quantity'].'</td>';
                                echo '<td class="px-6 py-4 text-right whitespace-nowrap"><button onclick="openEditModal('.$p['id'].', \''.htmlspecialchars($p['name'], ENT_QUOTES).'\', \''.htmlspecialchars($p['package_qty'] ?? '', ENT_QUOTES).'\', \''.htmlspecialchars($p['price'], ENT_QUOTES).'\')" class="text-sm px-4 py-1.5 border border-indigo-200 text-indigo-700 hover:bg-indigo-50 font-medium rounded shadow-sm whitespace-nowrap">Edit</button></td>';
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
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-4">Add Medicine</h3>
            <form id="productForm" class="space-y-4">
                <input type="text" name="name" placeholder="Medicine Name" required class="w-full border p-2 rounded focus:ring focus:ring-green-100">
                <input type="text" name="package_qty" placeholder="Package Qty (e.g. 10 Strips)" class="w-full border p-2 rounded focus:ring focus:ring-green-100">
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" step="0.01" name="price" placeholder="Price (₹)" required class="w-full border p-2 rounded focus:ring focus:ring-green-100">
                    <input type="number" name="quantity" placeholder="Initial Qty (Optional)" class="w-full border p-2 rounded focus:ring focus:ring-green-100">
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-3 py-1 border rounded">Cancel</button>
                    <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-4">Edit Medicine</h3>
            <form id="editForm" class="space-y-4">
                <input type="hidden" name="product_id" id="editProductId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="editProductName" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Package Qty</label>
                    <input type="text" name="package_qty" id="editProductPackage" placeholder="e.g. 10 Strips" class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (₹)</label>
                    <input type="number" step="0.01" name="price" id="editProductPrice" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                </div>
                <div class="flex justify-end space-x-2 mt-4 pt-2">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-3 py-1.5 border rounded text-sm hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded text-sm shadow-sm">Save Changes</button>
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

        function openEditModal(id, name, package_qty, price) {
            document.getElementById('editProductId').value = id;
            document.getElementById('editProductName').value = name;
            document.getElementById('editProductPackage').value = package_qty;
            document.getElementById('editProductPrice').value = price;
            document.getElementById('editModal').classList.remove('hidden');
        }

        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const response = await fetch('api/inventory.php?action=edit_product', { method: 'POST', body: formData });
            if ((await response.json()).status === 'success') window.location.reload();
        });
    </script>
</body>
</html>
