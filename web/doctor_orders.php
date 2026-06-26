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
    <title>Doctor Orders (Plan 2) - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <link rel="stylesheet" href="admin-style.css">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header
            class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <button onclick="toggleMobileSidebar()"
                    class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="min-w-0">
                    <div>
                        <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-800 truncate">Doctor Orders</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Plan 2 — MR submitted orders from doctors</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3 sm:space-x-4">
                <a href="create_doctor_order.php"
                    class="bg-teal-600 text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg text-sm font-semibold shadow hover:bg-teal-700 transition-colors whitespace-nowrap">
                    + <span class="hidden sm:inline">Create Direct Order</span>
                </a>
                <input type="text" id="searchInput" oninput="filterOrders()" placeholder="Search Doctor or Order #..."
                    class="hidden sm:block text-sm border border-gray-200 rounded-lg px-2 py-1.5 sm:px-3 sm:py-2 focus:ring-2 focus:ring-teal-100 outline-none w-32 sm:w-48 transition-all duration-300 focus:w-64">
                <!-- Status filter -->
                <select id="statusFilter" onchange="filterOrders()"
                    class="text-sm border border-gray-200 rounded-lg px-2 py-1.5 sm:px-3 sm:py-2 focus:ring-2 focus:ring-teal-100 outline-none">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Confirmed">Confirmed</option>
                    <option value="Dispatched">Dispatched</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <?php
                try {
                    $stats = $pdo->query("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status='Delivered' THEN 1 ELSE 0 END) as delivered,
                            SUM(total_amount) as revenue
                        FROM doctor_orders
                    ")->fetch();
                } catch (Exception $e) {
                    $stats = ['total' => 0, 'pending' => 0, 'delivered' => 0, 'revenue' => 0];
                }
                ?>
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                    <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></div>
                    <div class="text-xs text-gray-500 font-medium mt-1">Total Orders</div>
                </div>
                <div class="bg-amber-50 rounded-xl p-5 border border-amber-100 shadow-sm">
                    <div class="text-2xl font-bold text-amber-600"><?php echo $stats['pending']; ?></div>
                    <div class="text-xs text-amber-500 font-medium mt-1">Pending</div>
                </div>
                <div class="bg-green-50 rounded-xl p-5 border border-green-100 shadow-sm">
                    <div class="text-2xl font-bold text-green-600"><?php echo $stats['delivered']; ?></div>
                    <div class="text-xs text-green-500 font-medium mt-1">Delivered</div>
                </div>
                <div class="bg-teal-50 rounded-xl p-5 border border-teal-100 shadow-sm">
                    <div class="text-2xl font-bold text-teal-600">
                        ₹<?php echo number_format($stats['revenue'] ?? 0, 0); ?></div>
                    <div class="text-xs text-teal-500 font-medium mt-1">Total Revenue</div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="ordersTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Order</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Doctor</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">MR (Sales Rep)
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Items</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $orders = $pdo->query("
                                SELECT 
                                    o.id, o.status, o.notes, o.total_amount, o.created_at, o.status_remarks, o.courier_company, o.awb_no,
                                    o.payment_status, o.payment_method, o.stockist_id,
                                    d.name AS doctor_name, d.phone AS doctor_phone,
                                    mr.name AS mr_name,
                                    st.name AS stockist_name,
                                    (SELECT COUNT(*) FROM doctor_order_items WHERE order_id = o.id) AS item_count
                                FROM doctor_orders o
                                JOIN users d ON o.doctor_id = d.id
                                JOIN users mr ON o.mr_id = mr.id
                                LEFT JOIN users st ON o.stockist_id = st.id
                                ORDER BY o.created_at DESC
                            ")->fetchAll();

                                if (count($orders) === 0) {
                                    echo '<tr><td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        <p class="text-gray-400 text-sm font-medium">No doctor orders yet.</p>
                                        <p class="text-gray-300 text-xs mt-1">Orders placed by MRs via the mobile app will appear here.</p>
                                    </div>
                                </td></tr>';
                                }

                                foreach ($orders as $o) {
                                    $statusColors = [
                                        'Pending' => 'bg-amber-100 text-amber-700',
                                        'Confirmed' => 'bg-blue-100 text-blue-700',
                                        'Dispatched' => 'bg-purple-100 text-purple-700',
                                        'Delivered' => 'bg-green-100 text-green-700',
                                        'Cancelled' => 'bg-red-100 text-red-700',
                                    ];
                                    $sc = $statusColors[$o['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <tr class="hover:bg-gray-50 order-row" data-status="<?php echo $o['status']; ?>"
                                        data-orderid="<?php echo $o['id']; ?>"
                                        data-doctor="<?php echo htmlspecialchars($o['doctor_name']); ?>">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-sm text-gray-900">#DO-<?php echo $o['id']; ?></div>
                                            <div class="text-xs sm:text-[10px] text-gray-400 mt-0.5">
                                                <?php echo date('d M Y, h:i A', strtotime($o['created_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-800 text-sm">Dr.
                                                <?php echo htmlspecialchars($o['doctor_name']); ?></div>
                                            <div class="text-xs text-gray-400">
                                                <?php echo htmlspecialchars($o['doctor_phone']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <div
                                                    class="w-7 h-7 rounded-full bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-white text-xs font-bold">
                                                    <?php echo strtoupper(substr($o['mr_name'], 0, 1)); ?>
                                                </div>
                                                <span
                                                    class="text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($o['mr_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button onclick="viewItems(<?php echo $o['id']; ?>)"
                                                class="text-sm font-bold text-teal-600 hover:underline">
                                                <?php echo $o['item_count']; ?>
                                                item<?php echo $o['item_count'] != 1 ? 's' : ''; ?>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-gray-900 text-sm">
                                            ₹<?php echo number_format($o['total_amount'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="px-2.5 py-1 text-[11px] font-bold uppercase rounded-full <?php echo $sc; ?>">
                                                <?php echo $o['status']; ?>
                                            </span>
                                            <?php if ($o['payment_status'] === 'Paid'): ?>
                                                <span
                                                    class="ml-1 px-2 py-0.5 text-xs sm:text-[10px] font-bold uppercase rounded text-emerald-700 bg-emerald-100 border border-emerald-200">
                                                    Paid (<?php echo htmlspecialchars($o['payment_method']); ?>)
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($o['stockist_name']): ?>
                                                <div class="text-xs sm:text-[10px] text-teal-700 mt-1 font-bold">Assigned to:
                                                    <?php echo htmlspecialchars($o['stockist_name']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($o['status_remarks']): ?>
                                                <div class="text-xs sm:text-[10px] text-gray-500 mt-1 italic break-words w-48">
                                                    Remark: <?php echo htmlspecialchars($o['status_remarks']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($o['status'] === 'Dispatched' && $o['courier_company']): ?>
                                                <div class="text-xs sm:text-[10px] text-gray-500 mt-1">Courier:
                                                    <?php echo htmlspecialchars($o['courier_company']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($o['status'] === 'Dispatched' && $o['awb_no']): ?>
                                                <div class="text-xs sm:text-[10px] text-gray-500 mt-0.5">AWB:
                                                    <?php echo htmlspecialchars($o['awb_no']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($o['notes']): ?>
                                                <div class="text-xs sm:text-[10px] text-gray-400 mt-1 italic max-w-[120px] truncate"
                                                    title="<?php echo htmlspecialchars($o['notes']); ?>">Notes:
                                                    <?php echo htmlspecialchars($o['notes']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end space-x-2">
                                                <?php if (!$o['stockist_name'] && in_array($o['status'], ['Pending', 'Confirmed'])): ?>
                                                    <button onclick="openAssignModal(<?php echo $o['id']; ?>)"
                                                        class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition-colors">
                                                        Assign Stockist
                                                    </button>
                                                <?php endif; ?>
                                                <button
                                                    onclick="openStatusModal(<?php echo $o['id']; ?>, '<?php echo $o['status']; ?>', '<?php echo addslashes($o['status_remarks'] ?? ''); ?>', '<?php echo addslashes($o['courier_company'] ?? ''); ?>', '<?php echo addslashes($o['awb_no'] ?? ''); ?>', '<?php echo $o['payment_status'] ?? 'Pending'; ?>', '<?php echo addslashes($o['payment_method'] ?? ''); ?>')"
                                                    class="text-xs font-bold text-teal-700 bg-teal-50 px-3 py-1.5 rounded-lg hover:bg-teal-100 transition-colors">
                                                    Update
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="7" class="px-6 py-12 text-center text-red-500 text-sm">Database error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
                <button onclick="document.getElementById('itemsModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div id="itemsContainer" class="flex-1 overflow-y-auto space-y-3">
                <div class="text-center py-8 text-gray-400">Loading...</div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between font-bold text-gray-800">
                    <span>Total</span>
                    <span id="itemsTotal"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-5 text-gray-800">Update Order Status</h3>
            <form id="statusForm" class="space-y-4">
                <input type="hidden" name="order_id" id="modalOrderId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New Status</label>
                    <select name="status" id="modalStatus" onchange="toggleCourierFields()"
                        class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-teal-100 focus:border-teal-400 outline-none">
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Dispatched">Dispatched</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div id="courierFields" class="hidden space-y-4 pt-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Courier Company Name</label>
                        <input type="text" name="courier_company" id="modalCourier"
                            class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-teal-100 outline-none"
                            placeholder="e.g. DTDC, BlueDart">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">AWB No. (Tracking ID)</label>
                        <input type="text" name="awb_no" id="modalAwb"
                            class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-teal-100 outline-none"
                            placeholder="Tracking Number">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark (Optional)</label>
                    <input type="text" name="remarks" id="modalRemarks"
                        class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-teal-100 outline-none"
                        placeholder="Add any remark...">
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Status</label>
                    <select name="payment_status" id="modalPaymentStatus" onchange="togglePaymentFields()"
                        class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 outline-none">
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                    </select>
                </div>

                <div id="paymentFields" class="hidden pb-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Method</label>
                    <select name="payment_method" id="modalPaymentMethod"
                        class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-emerald-100 outline-none">
                        <option value="Cash">Cash</option>
                        <option value="Online">Online / UPI</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('statusModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-teal-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-teal-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Stockist Modal -->
    <div id="assignModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-5 text-gray-800">Assign Stockist</h3>
            <form id="assignForm" class="space-y-4">
                <input type="hidden" name="order_id" id="assignOrderId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Eligible Stockists</label>
                    <select name="stockist_id" id="assignStockistSelect"
                        class="w-full border border-gray-300 p-2.5 rounded-xl focus:ring-2 focus:ring-teal-100 focus:border-teal-400 outline-none"
                        required>
                        <option value="">Loading...</option>
                    </select>
                    <p class="text-xs sm:text-[10px] text-gray-400 mt-1">Only stockists with complete stock for this
                        order are shown.</p>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-teal-600 text-white font-bold rounded-xl text-sm shadow-md hover:bg-teal-700">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterOrders() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput') ? document.getElementById('searchInput').value.toLowerCase() : '';
            const rows = document.querySelectorAll('.order-row');

            rows.forEach(row => {
                const rowStatus = row.dataset.status;
                const docName = (row.dataset.doctor || '').toLowerCase();
                const orderId = (row.dataset.orderid || '').toLowerCase();

                const matchesStatus = !status || rowStatus === status;
                const matchesSearch = !search || docName.includes(search) || orderId.includes(search);

                if (matchesStatus && matchesSearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function toggleCourierFields() {
            const status = document.getElementById('modalStatus').value;
            const courierFields = document.getElementById('courierFields');
            if (status === 'Dispatched') {
                courierFields.classList.remove('hidden');
            } else {
                courierFields.classList.add('hidden');
            }
        }

        function togglePaymentFields() {
            const pStatus = document.getElementById('modalPaymentStatus').value;
            const pFields = document.getElementById('paymentFields');
            if (pStatus === 'Paid') {
                pFields.classList.remove('hidden');
            } else {
                pFields.classList.add('hidden');
            }
        }

        function openStatusModal(id, currentStatus, remarks, courier, awb, paymentStatus, paymentMethod) {
            document.getElementById('modalOrderId').value = id;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('modalRemarks').value = remarks || '';
            document.getElementById('modalCourier').value = courier || '';
            document.getElementById('modalAwb').value = awb || '';

            document.getElementById('modalPaymentStatus').value = paymentStatus || 'Pending';
            document.getElementById('modalPaymentMethod').value = paymentMethod || 'Cash';

            toggleCourierFields();
            togglePaymentFields();
            document.getElementById('statusModal').classList.remove('hidden');
        }

        async function viewItems(orderId) {
            const modal = document.getElementById('itemsModal');
            const container = document.getElementById('itemsContainer');
            const totalEl = document.getElementById('itemsTotal');
            modal.classList.remove('hidden');
            container.innerHTML = '<div class="text-center py-8 text-gray-400">Loading items...</div>';

            try {
                const res = await fetch(`api/mr.php?action=get_order_detail&order_id=${orderId}`);
                const result = await res.json();
                if (result.status === 'success' && result.data.length > 0) {
                    let grandTotal = 0;
                    container.innerHTML = result.data.map(item => {
                        grandTotal += parseFloat(item.line_total);
                        return `
                            <div class="flex justify-between items-center bg-gray-50 rounded-xl px-4 py-3">
                                <div>
                                    <div class="font-semibold text-gray-800 text-sm">${item.product_name}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">Qty: ${item.quantity} × ₹${parseFloat(item.unit_price).toFixed(2)}</div>
                                </div>
                                <div class="font-bold text-gray-800">₹${parseFloat(item.line_total).toFixed(2)}</div>
                            </div>`;
                    }).join('');
                    totalEl.textContent = `₹${grandTotal.toFixed(2)}`;
                } else {
                    container.innerHTML = '<div class="text-center py-8 text-gray-400">No items found.</div>';
                    totalEl.textContent = '';
                }
            } catch (e) {
                container.innerHTML = '<div class="text-center py-8 text-red-400">Error loading items.</div>';
            }
        }

        document.getElementById('statusForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.textContent = 'Saving...';
            btn.disabled = true;
            try {
                const formData = new FormData(this);
                const res = await fetch('api/mr.php?action=update_status', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                    btn.textContent = 'Save';
                    btn.disabled = false;
                }
            } catch (e) {
                alert('Connection error');
                btn.textContent = 'Save';
                btn.disabled = false;
            }
        });

        async function openAssignModal(orderId) {
            document.getElementById('assignOrderId').value = orderId;
            const select = document.getElementById('assignStockistSelect');
            select.innerHTML = '<option value="">Loading eligible stockists...</option>';
            document.getElementById('assignModal').classList.remove('hidden');

            try {
                const res = await fetch(`api/mr.php?action=get_eligible_stockists&order_id=${orderId}`);
                const result = await res.json();
                if (result.status === 'success') {
                    if (result.data.length === 0) {
                        select.innerHTML = '<option value="">No stockists have enough stock</option>';
                    } else {
                        select.innerHTML = '<option value="">-- Select a Stockist --</option>' +
                            result.data.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
                    }
                } else {
                    select.innerHTML = '<option value="">Error loading stockists</option>';
                }
            } catch (e) {
                select.innerHTML = '<option value="">Connection error</option>';
            }
        }

        document.getElementById('assignForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.textContent = 'Assigning...';
            btn.disabled = true;
            try {
                const formData = new FormData(this);
                const res = await fetch('api/mr.php?action=assign_stockist', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                    btn.textContent = 'Assign';
                    btn.disabled = false;
                }
            } catch (e) {
                alert('Connection error');
                btn.textContent = 'Assign';
                btn.disabled = false;
            }
        });
    </script>
</body>

</html>