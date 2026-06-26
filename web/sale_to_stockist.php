<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $invoice_id = $_POST['invoice_id'];
    $payment_amount = floatval($_POST['payment_amount']);
    
    $payment_method = $_POST['payment_method'] ?? null;
    $transaction_id = $_POST['transaction_id'] ?? null;
    $payment_remarks = $_POST['payment_remarks'] ?? null;
    
    // Fetch current details
    $stmt = $pdo->prepare("SELECT total_amount, amount_paid FROM stockist_invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($inv) {
        $new_amount = $inv['amount_paid'] + $payment_amount;
        $status = 'Due';
        if ($new_amount >= $inv['total_amount']) {
            $status = 'Paid';
            $new_amount = $inv['total_amount']; // Cap at total to prevent overpayment logic breaking
        } elseif ($new_amount > 0) {
            $status = 'Partial';
        }
        
        $update = $pdo->prepare("UPDATE stockist_invoices SET amount_paid = ?, payment_status = ?, payment_method = ?, transaction_id = ?, payment_remarks = ? WHERE id = ?");
        $update->execute([$new_amount, $status, $payment_method, $transaction_id, $payment_remarks, $invoice_id]);
        
        // Log individual payment history
        $log_payment = $pdo->prepare("INSERT INTO stockist_invoice_payments (invoice_id, amount, payment_method, transaction_id, remarks) VALUES (?, ?, ?, ?, ?)");
        $log_payment->execute([$invoice_id, $payment_amount, $payment_method, $transaction_id, $payment_remarks]);
        
        header("Location: sale_to_stockist.php?payment_success=1");
        exit;
    }
}

// Ensure the necessary tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS `stockist_invoices` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `stockist_id` int(11) NOT NULL,
    `invoice_number` varchar(50) NOT NULL,
    `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS `stockist_invoice_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `invoice_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL,
    `unit_price` decimal(10,2) NOT NULL,
    `total_price` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `stockist_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS `stockist_invoice_payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `invoice_id` int(11) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `payment_method` varchar(50) DEFAULT NULL,
    `transaction_id` varchar(100) DEFAULT NULL,
    `remarks` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `stockist_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Update existing schema for payment tracking
try {
    $pdo->exec("ALTER TABLE `stockist_invoices` ADD COLUMN `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`");
} catch (Exception $e) { }

try {
    $pdo->exec("ALTER TABLE `stockist_invoices` ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `payment_status`");
} catch (Exception $e) { }

try {
    $pdo->exec("ALTER TABLE `stockist_invoices` ADD COLUMN `transaction_id` VARCHAR(100) DEFAULT NULL AFTER `payment_method`");
} catch (Exception $e) { }

try {
    $pdo->exec("ALTER TABLE `stockist_invoices` ADD COLUMN `payment_remarks` TEXT DEFAULT NULL AFTER `transaction_id`");
} catch (Exception $e) { }


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale to Stockists - <?php echo APP_NAME; ?></title>
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
                    <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Invoices Ledger (Stockists)</h1>
                </div>
            </div>
            <div class="flex items-center space-x-3 sm:space-x-4">
                <a href="create_stockist_invoice.php" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow hover:bg-teal-700">+ New Sale / Invoice</a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">Invoice generated successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['payment_success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">Payment recorded successfully!</div>
            <?php endif; ?>

            <?php
            $search = $_GET['search'] ?? '';
            $status_filter = $_GET['status'] ?? '';
            ?>
            
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-4 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <form method="GET" action="" class="flex w-full sm:w-auto gap-3 items-center">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Invoice or Name..." class="w-full sm:w-64 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">All Statuses</option>
                        <option value="Due" <?php if($status_filter == 'Due') echo 'selected'; ?>>Due</option>
                        <option value="Partial" <?php if($status_filter == 'Partial') echo 'selected'; ?>>Partial</option>
                        <option value="Paid" <?php if($status_filter == 'Paid') echo 'selected'; ?>>Paid</option>
                    </select>
                    <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm hover:bg-teal-700">Filter</button>
                    <?php if($search || $status_filter): ?>
                        <a href="sale_to_stockist.php" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Invoice Number</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Stockist Name</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Total Amount</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $where_clauses = [];
                                $params = [];

                                if ($search !== '') {
                                    $where_clauses[] = "(s.invoice_number LIKE ? OR u.name LIKE ?)";
                                    $params[] = "%$search%";
                                    $params[] = "%$search%";
                                }

                                if ($status_filter !== '') {
                                    $where_clauses[] = "s.payment_status = ?";
                                    $params[] = $status_filter;
                                }

                                $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

                                $stmt = $pdo->prepare("
                                    SELECT s.id, s.invoice_number, s.created_at, s.total_amount, s.amount_paid, s.payment_status, u.name as stockist_name
                                    FROM stockist_invoices s
                                    JOIN users u ON s.stockist_id = u.id
                                    $where_sql
                                    ORDER BY s.id DESC
                                ");
                                $stmt->execute($params);
                                $history = $stmt->fetchAll();

                                if (count($history) == 0) {
                                    echo '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No stock invoices found.</td></tr>';
                                }

                                foreach ($history as $h) {
                                    $date = date('d M Y, h:i A', strtotime($h['created_at']));
                                    $statusColor = 'bg-red-100 text-red-800';
                                    if ($h['payment_status'] === 'Paid') $statusColor = 'bg-green-100 text-green-800';
                                    else if ($h['payment_status'] === 'Partial') $statusColor = 'bg-yellow-100 text-yellow-800';
                                    $balance = $h['total_amount'] - $h['amount_paid'];

                                    echo '<tr class="hover:bg-gray-50">';
                                    echo '<td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">' . $date . '</td>';
                                    echo '<td class="px-6 py-4 font-bold text-teal-700 whitespace-nowrap">' . htmlspecialchars($h['invoice_number']) . '</td>';
                                    echo '<td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">' . htmlspecialchars($h['stockist_name']) . '</td>';
                                    echo '<td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">₹' . number_format($h['total_amount'], 2) . '<br><span class="text-xs font-normal text-gray-500">Paid: ₹'.number_format($h['amount_paid'],2).'</span></td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap"><span class="px-3 py-1 rounded-full text-xs font-bold ' . $statusColor . '">' . $h['payment_status'] . '</span></td>';
                                    
                                    echo '<td class="px-6 py-4 text-right whitespace-nowrap space-x-2">';
                                    if ($h['payment_status'] !== 'Paid') {
                                        echo '<button onclick="openPaymentModal('.$h['id'].', \''.htmlspecialchars($h['invoice_number']).'\', '.$balance.')" class="text-sm font-semibold text-green-600 hover:text-green-900 bg-green-50 px-3 py-1.5 rounded-lg border border-green-200 shadow-sm">Record Payment</button>';
                                    }
                                    echo '<a href="view_stockist_invoice.php?id=' . $h['id'] . '" class="text-sm font-semibold text-teal-600 hover:text-teal-900 bg-teal-50 px-3 py-1.5 rounded-lg border border-teal-200 shadow-sm">View Invoice</a>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="5" class="px-6 py-8 text-center text-red-500">Database error: ' . $e->getMessage() . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-900">Record Payment</h3>
                <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="record_payment">
                <input type="hidden" name="invoice_id" id="modal_invoice_id">
                <div class="p-6 space-y-6">
                    <div class="flex justify-between items-center bg-teal-50/50 p-4 rounded-xl border border-teal-100">
                        <div>
                            <p class="text-xs text-teal-500 font-semibold uppercase tracking-wider mb-1">Invoice Number</p>
                            <p class="font-black text-teal-700 text-lg" id="modal_invoice_no">INV-XXXX</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Balance Due</p>
                            <p class="font-black text-gray-900 text-2xl" id="modal_balance">₹0.00</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Amount Received</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-bold text-lg">₹</span>
                            </div>
                            <input type="number" name="payment_amount" id="payment_amount" step="0.01" min="0.01" required class="w-full pl-9 pr-4 border border-gray-300 rounded-xl py-3 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors outline-none font-bold text-gray-900 text-xl shadow-sm">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Method</label>
                            <select name="payment_method" required class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none text-gray-700">
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ref / Transaction ID</label>
                            <input type="text" name="transaction_id" placeholder="Optional" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none text-gray-700">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks / Notes</label>
                        <textarea name="payment_remarks" rows="2" placeholder="Any payment notes..." class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none text-gray-700"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" onclick="closePaymentModal()" class="px-4 py-2 text-gray-700 font-semibold hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700">Save Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMobileSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }
        function openPaymentModal(id, invNo, balance) {
            document.getElementById('modal_invoice_id').value = id;
            document.getElementById('modal_invoice_no').innerText = invNo;
            document.getElementById('modal_balance').innerText = '₹' + parseFloat(balance).toFixed(2);
            document.getElementById('payment_amount').max = balance;
            document.getElementById('payment_amount').value = balance; // Default to full balance
            document.getElementById('paymentModal').classList.remove('hidden');
        }
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }
    </script>
</body>
</html>
