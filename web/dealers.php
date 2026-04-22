<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$pdo->exec("CREATE TABLE IF NOT EXISTS `dealers` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(150) NOT NULL, `phone` varchar(20), `created_at` timestamp DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dealers & Vendors - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Dealers & Vendors</h1>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm">+ Add Dealer</button>
        </header>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Dealer Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Contact Number</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT * FROM dealers ORDER BY name ASC");
                            $dealers = $stmt->fetchAll();
                            
                            if(count($dealers) == 0) {
                                echo '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">No dealers have been added yet.</td></tr>';
                            }
                            
                            foreach($dealers as $d) {
                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 font-bold text-gray-900">'.htmlspecialchars($d['name']).'</td>';
                                echo '<td class="px-6 py-4 font-medium text-gray-600">'.htmlspecialchars($d['phone']).'</td>';
                                echo '<td class="px-6 py-4"><span class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs font-bold">Active</span></td>';
                                echo '</tr>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-4">Add a Dealer</h3>
            <form id="dealerForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company / Dealer Name</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                    <input type="text" name="phone" class="w-full border border-gray-300 p-2 rounded focus:ring focus:ring-indigo-100">
                </div>
                <div class="flex justify-end space-x-2 mt-4 pt-2">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-3 py-1.5 border rounded text-sm hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded text-sm shadow-sm">Save Dealer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('dealerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = "Saving...";
            try {
                const formData = new FormData(this);
                const response = await fetch('api/inventory.php?action=add_dealer', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                    btn.innerHTML = "Save Dealer";
                }
            } catch(e) {
                alert('Connection error');
                btn.innerHTML = "Save Dealer";
            }
        });
    </script>
</body>
</html>
