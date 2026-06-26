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
            <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Orders</h1>
        </div>
    </div>

</header>
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Order ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Patient Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Action</th>
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
                                    case 'Dispatched': $statusColor = "teal"; break;
                                    case 'Delivered': $statusColor = "green"; break;
                                    case 'Cancelled': $statusColor = "red"; break;
                                    case 'Undelivered': $statusColor = "orange"; break;
                                }
                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 font-bold text-sm text-gray-900 flex flex-col whitespace-nowrap">#ORD-'.$o['id'].'<span class="text-xs sm:text-[10px] text-gray-400 font-normal">'.date('d M, h:i A', strtotime($o['created_at'])).'</span></td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-700">'.htmlspecialchars($o['name']).'</div>
                                        <div class="text-xs sm:text-[10px] text-gray-500 italic truncate max-w-xs">'.htmlspecialchars($o['status_remarks'] ?? '').'</div>
                                      </td>';
                                echo '<td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">₹'.$o['total_amount'].'</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col items-start space-y-1">
                                            <span class="px-2.5 py-1 text-xs sm:text-[10px] font-bold uppercase rounded-full bg-'.$statusColor.'-100 text-'.$statusColor.'-700">'.$o['status'].'</span>
                                            <button onclick="viewHistory('.$o['id'].')" class="text-xs sm:text-[10px] text-teal-600 hover:underline font-medium">View History</button>
                                        </div>
                                      </td>';
                                echo '<td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex justify-end items-center space-x-2">
                                            <button onclick="openStatusModal('.$o['id'].', \''.$o['status'].'\', \''.htmlspecialchars($o['status_remarks'] ?? '', ENT_QUOTES).'\')" class="text-xs font-bold text-teal-700 bg-teal-50 px-3 py-1.5 rounded-lg hover:bg-teal-100 transition-colors">Update</button>
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
        </div>
    </main>

    <!-- History Modal -->
    <div id="historyModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-[450px] transform transition-all flex flex-col max-h-[80vh]">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Order Timeline
            </h3>
            <div id="historyContainer" class="flex-1 overflow-y-auto space-y-4 py-4 pr-2">
                <!-- Loaded via JS -->
                <div class="text-center py-10 text-gray-400">Loading history...</div>
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="document.getElementById('historyModal').classList.add('hidden')" class="px-6 py-2 bg-gray-100 text-gray-600 font-bold rounded-xl text-sm hover:bg-gray-200 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96 transform transition-all">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Update Order Status
            </h3>
            <form id="statusForm" class="space-y-4">
                <input type="hidden" name="order_id" id="modalOrderId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select New Status</label>
                    <select name="status" id="modalStatusSelect" class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-teal-100 focus:border-teal-400 outline-none transition-all">
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
                    <textarea name="remarks" id="modalRemarksText" rows="3" placeholder="e.g. Out for delivery, Customer not available..." class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-teal-100 focus:border-teal-400 outline-none transition-all resize-none text-sm"></textarea>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('statusModal').classList.add('hidden')" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-teal-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-teal-700 transition-colors">Save Update</button>
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

        async function viewHistory(id) {
            const modal = document.getElementById('historyModal');
            const container = document.getElementById('historyContainer');
            modal.classList.remove('hidden');
            container.innerHTML = '<div class="text-center py-10 text-gray-400">Loading timeline...</div>';
            
            try {
                const response = await fetch(`api/orders.php?action=get_history&order_id=${id}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    if (result.data.length === 0) {
                        container.innerHTML = '<div class="text-center py-10 text-gray-400">No history found for this order.</div>';
                        return;
                    }
                    
                    container.innerHTML = result.data.map(h => `
                        <div class="relative pl-6 border-l-2 border-teal-100 pb-2">
                            <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-white border-2 border-teal-500"></div>
                            <div class="flex justify-between items-start">
                                <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded bg-teal-50 text-teal-700">${h.status}</span>
                                <span class="text-xs sm:text-[10px] text-gray-400 font-medium">${new Date(h.created_at).toLocaleString()}</span>
                            </div>
                            <p class="text-sm text-gray-700 mt-2 font-medium">${h.remarks || '<span class="text-gray-300 italic text-xs font-normal">No remark provided</span>'}</p>
                            <div class="text-xs sm:text-[10px] text-gray-400 mt-1">Updated by: ${h.updater_name || 'System'}</div>
                        </div>
                    `).join('');
                }
            } catch (e) {
                container.innerHTML = '<div class="text-center py-10 text-red-500">Error loading timeline.</div>';
            }
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
