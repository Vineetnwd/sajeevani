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
    <title>Orders - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4">
            <h1 class="text-xl font-bold text-gray-800">Orders</h1>
        </header>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Order ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Patient Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT o.id, o.status, o.status_remarks, o.total_amount, p.name, o.created_at FROM orders o JOIN patients p ON o.patient_id = p.id ORDER BY o.id DESC");
                            $orders = $stmt->fetchAll();
                            foreach($orders as $o) {
                                $statusColor = "gray";
                                switch($o['status']) {
                                    case 'Created': $statusColor = "blue"; break;
                                    case 'Packing': $statusColor = "yellow"; break;
                                    case 'Dispatched': $statusColor = "indigo"; break;
                                    case 'Delivered': $statusColor = "green"; break;
                                    case 'Cancelled': $statusColor = "red"; break;
                                    case 'Undelivered': $statusColor = "orange"; break;
                                }
                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 font-bold text-sm text-gray-900 flex flex-col">#ORD-'.$o['id'].'<span class="text-[10px] text-gray-400 font-normal">'.date('d M, h:i A', strtotime($o['created_at'])).'</span></td>';
                                echo '<td class="px-6 py-4">
                                        <div class="font-medium text-gray-700">'.htmlspecialchars($o['name']).'</div>
                                        <div class="text-[10px] text-gray-500 italic truncate max-w-xs">'.htmlspecialchars($o['status_remarks'] ?? '').'</div>
                                      </td>';
                                echo '<td class="px-6 py-4 font-bold text-gray-900">₹'.$o['total_amount'].'</td>';
                                echo '<td class="px-6 py-4"><span class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-full bg-'.$statusColor.'-100 text-'.$statusColor.'-700">'.$o['status'].'</span></td>';
                                echo '<td class="px-6 py-4 text-right">
                                        <div class="flex justify-end items-center space-x-2">
                                            <button onclick="openStatusModal('.$o['id'].', \''.$o['status'].'\', \''.htmlspecialchars($o['status_remarks'] ?? '', ENT_QUOTES).'\')" class="text-xs font-bold text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition-colors">Status</button>
                                            <button onclick="deleteOrder('.$o['id'].')" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Remove Order">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                      </td>';
                                echo '</tr>';
                            }
                            if(count($orders) === 0) {
                                echo '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic text-sm">No orders found in the system.</td></tr>';
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="5" class="px-6 py-12 text-center text-red-500 text-sm">Database error: '.htmlspecialchars($e->getMessage()).'</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96 transform transition-all">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Update Order Status
            </h3>
            <form id="statusForm" class="space-y-4">
                <input type="hidden" name="order_id" id="modalOrderId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select New Status</label>
                    <select name="status" id="modalStatusSelect" class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 outline-none transition-all">
                        <option value="Created">Created</option>
                        <option value="Packing">Packing</option>
                        <option value="Dispatched">Dispatched</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Undelivered">Undelivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remark / Note</label>
                    <textarea name="remarks" id="modalRemarksText" rows="3" placeholder="e.g. Out for delivery, Customer not available..." class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 outline-none transition-all resize-none text-sm"></textarea>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('statusModal').classList.add('hidden')" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-indigo-700 transition-colors">Save Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(id, currentStatus, currentRemarks) {
            document.getElementById('modalOrderId').value = id;
            document.getElementById('modalStatusSelect').value = currentStatus;
            document.getElementById('modalRemarksText').value = currentRemarks;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        async function deleteOrder(id) {
            if (!confirm('Are you sure you want to remove this order? This cannot be undone.')) return;
            
            try {
                const formData = new FormData();
                formData.append('order_id', id);
                
                const response = await fetch('api/orders.php?action=delete_order', { 
                    method: 'POST', 
                    body: formData 
                });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                }
            } catch (e) {
                alert('Connection error');
            }
        }

        document.getElementById('statusForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = "Updating...";
            btn.disabled = true;

            try {
                const formData = new FormData(this);
                const response = await fetch('api/orders.php?action=update_status', { 
                    method: 'POST', 
                    body: formData 
                });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (e) {
                alert('Connection error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
