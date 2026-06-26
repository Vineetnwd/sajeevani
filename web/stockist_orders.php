<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Stockist') {
    header("Location: index.php");
    exit();
}

$stockist_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
        <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <div class="min-w-0">
            <div>
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-800 truncate">My Assigned Orders</h1>
                <p class="text-xs text-gray-400 mt-0.5">Orders assigned to you for dispatch</p>
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        <div class="flex items-center space-x-3">
                <select id="statusFilter" onchange="filterStatus(this.value)" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-100 outline-none">
                    <option value="">All Status</option>
                    <option value="Confirmed">Confirmed</option>
                    <option value="Dispatched">Dispatched</option>
                    <option value="Delivered">Delivered</option>
                </select>
            </div>
    </div>
</header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200" id="ordersTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Order</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Items</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $orders = $pdo->prepare("
                                SELECT 
                                    o.id, o.status, o.created_at, o.status_remarks, o.courier_company, o.awb_no,
                                    d.name AS doctor_name, d.phone AS doctor_phone,
                                    (SELECT COUNT(*) FROM doctor_order_items WHERE order_id = o.id) AS item_count
                                FROM doctor_orders o
                                JOIN users d ON o.doctor_id = d.id
                                WHERE o.stockist_id = ?
                                ORDER BY o.created_at DESC
                            ");
                            $orders->execute([$stockist_id]);
                            $orderList = $orders->fetchAll();

                            if (count($orderList) === 0) {
                                echo '<tr><td colspan="5" class="px-6 py-16 text-center text-gray-400 font-medium">No orders assigned to you yet.</td></tr>';
                            }

                            foreach ($orderList as $o) {
                                $statusColors = [
                                    'Pending'   => 'bg-amber-100 text-amber-700',
                                    'Confirmed' => 'bg-blue-100 text-blue-700',
                                    'Dispatched'=> 'bg-indigo-100 text-indigo-700',
                                    'Delivered' => 'bg-green-100 text-green-700',
                                    'Cancelled' => 'bg-red-100 text-red-700',
                                ];
                                $sc = $statusColors[$o['status']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <tr class="hover:bg-gray-50 order-row" data-status="<?php echo $o['status']; ?>">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-sm text-gray-900">#DO-<?php echo $o['id']; ?></div>
                                        <div class="text-xs sm:text-[10px] text-gray-400 mt-0.5"><?php echo date('d M Y, h:i A', strtotime($o['created_at'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-800 text-sm">Dr. <?php echo htmlspecialchars($o['doctor_name']); ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($o['doctor_phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button onclick="viewItems(<?php echo $o['id']; ?>)" class="text-sm font-bold text-indigo-600 hover:underline">
                                            <?php echo $o['item_count']; ?> item(s)
                                        </button>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-full <?php echo $sc; ?>">
                                            <?php echo $o['status']; ?>
                                        </span>
                                        <?php if ($o['status'] === 'Dispatched' && $o['courier_company']): ?>
                                        <div class="text-xs sm:text-[10px] text-gray-500 mt-1">Courier: <?php echo htmlspecialchars($o['courier_company']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($o['status'] === 'Dispatched' && $o['awb_no']): ?>
                                        <div class="text-xs sm:text-[10px] text-gray-500 mt-0.5">AWB: <?php echo htmlspecialchars($o['awb_no']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <?php if (in_array($o['status'], ['Pending', 'Confirmed'])): ?>
                                        <button onclick="openDispatchModal(<?php echo $o['id']; ?>)"
                                            class="text-xs font-bold text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors">
                                            Mark Dispatched
                                        </button>
                                        <?php else: ?>
                                        <span class="text-xs text-gray-400 font-medium">Updated</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="5" class="px-6 py-12 text-center text-red-500 text-sm">Database error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
</div>
            </div>
        </div>
    </main>

    <!-- Items Detail Modal -->
    <div id="itemsModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-[480px] max-h-[80vh] flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Order Items</h3>
                <button onclick="document.getElementById('itemsModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div id="itemsContainer" class="flex-1 overflow-y-auto space-y-3">
                <div class="text-center py-8 text-gray-400">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Dispatch Modal -->
    <div id="dispatchModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-5 text-gray-800">Dispatch Order</h3>
            <form id="dispatchForm" class="space-y-4">
                <input type="hidden" name="order_id" id="dispatchOrderId">
                <input type="hidden" name="status" value="Dispatched">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Courier Company Name</label>
                    <input type="text" name="courier_company" class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-100 outline-none" placeholder="e.g. DTDC, BlueDart" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">AWB No. (Tracking ID)</label>
                    <input type="text" name="awb_no" class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-100 outline-none" placeholder="Tracking Number" required>
                </div>

                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('dispatchModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-indigo-700">Dispatch</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterStatus(status) {
            const rows = document.querySelectorAll('.order-row');
            rows.forEach(row => {
                if (!status || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function openDispatchModal(id) {
            document.getElementById('dispatchOrderId').value = id;
            document.getElementById('dispatchModal').classList.remove('hidden');
        }

        async function viewItems(orderId) {
            const modal = document.getElementById('itemsModal');
            const container = document.getElementById('itemsContainer');
            modal.classList.remove('hidden');
            container.innerHTML = '<div class="text-center py-8 text-gray-400">Loading items...</div>';

            try {
                const res = await fetch(`api/mr.php?action=get_order_detail&order_id=${orderId}`);
                const result = await res.json();
                if (result.status === 'success' && result.data.length > 0) {
                    container.innerHTML = result.data.map(item => `
                        <div class="flex justify-between items-center bg-gray-50 rounded-xl px-4 py-3">
                            <div>
                                <div class="font-semibold text-gray-800 text-sm">${item.product_name}</div>
                                <div class="text-xs text-gray-400 mt-0.5">Qty: ${item.quantity}</div>
                            </div>
                        </div>`).join('');
                } else {
                    container.innerHTML = '<div class="text-center py-8 text-gray-400">No items found.</div>';
                }
            } catch (e) {
                container.innerHTML = '<div class="text-center py-8 text-red-400">Error loading items.</div>';
            }
        }

        document.getElementById('dispatchForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.textContent = 'Dispatching...';
            btn.disabled = true;
            try {
                const formData = new FormData(this);
                const res = await fetch('api/mr.php?action=update_status', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                    btn.textContent = 'Dispatch';
                    btn.disabled = false;
                }
            } catch (e) {
                alert('Connection error');
                btn.textContent = 'Dispatch';
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
