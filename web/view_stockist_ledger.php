<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: stockist_ledgers.php");
    exit();
}

$stockist_id = $_GET['id'];

// Fetch stockist details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'Stockist'");
$stmt->execute([$stockist_id]);
$stockist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stockist) {
    echo "Stockist not found.";
    exit();
}

// Fetch master metrics
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_billed,
        COALESCE(SUM(amount_paid), 0) as total_paid
    FROM stockist_invoices 
    WHERE stockist_id = ?
");
$stmt->execute([$stockist_id]);
$metrics = $stmt->fetch(PDO::FETCH_ASSOC);
$balance_due = $metrics['total_billed'] - $metrics['total_paid'];

// Fetch all invoices
$stmt = $pdo->prepare("SELECT * FROM stockist_invoices WHERE stockist_id = ? ORDER BY created_at DESC");
$stmt->execute([$stockist_id]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all payments
$stmt = $pdo->prepare("
    SELECT p.*, i.invoice_number 
    FROM stockist_invoice_payments p
    JOIN stockist_invoices i ON p.invoice_id = i.id
    WHERE i.stockist_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$stockist_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'stockist_ledgers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger: <?php echo htmlspecialchars($stockist['name']); ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 flex justify-between items-center sticky top-0">
            <div class="flex items-center gap-4">
                <a href="stockist_ledgers.php" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($stockist['name']); ?></h1>
                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Customer Ledger</p>
                </div>
            </div>
            <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Ledger
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 pb-20">
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Billed</p>
                    <p class="text-3xl font-black text-gray-900">₹<?php echo number_format($metrics['total_billed'], 2); ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Paid</p>
                    <p class="text-3xl font-black text-green-600">₹<?php echo number_format($metrics['total_paid'], 2); ?></p>
                </div>
                <div class="bg-indigo-50 p-6 rounded-2xl shadow-sm border border-indigo-100">
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-1">Total Balance Due</p>
                    <p class="text-3xl font-black text-indigo-700">₹<?php echo number_format($balance_due, 2); ?></p>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 mb-6">
                <button onclick="switchTab('invoices')" id="btn-invoices" class="px-6 py-3 font-bold text-sm border-b-2 transition-colors border-indigo-600 text-indigo-600">All Invoices</button>
                <button onclick="switchTab('payments')" id="btn-payments" class="px-6 py-3 font-bold text-sm border-b-2 transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Payment History</button>
            </div>

            <!-- Invoices Tab -->
            <div id="tab-invoices" class="tab-content active bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Invoice #</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Total</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Paid</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Balance</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($invoices)): ?>
                                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No invoices found for this stockist.</td></tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): 
                                    $bal = $inv['total_amount'] - $inv['amount_paid'];
                                    $statusColor = 'bg-red-100 text-red-800';
                                    if ($inv['payment_status'] === 'Paid') $statusColor = 'bg-green-100 text-green-800';
                                    else if ($inv['payment_status'] === 'Partial') $statusColor = 'bg-yellow-100 text-yellow-800';
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('d M Y', strtotime($inv['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-indigo-600"><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-gray-900">₹<?php echo number_format($inv['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-green-600">₹<?php echo number_format($inv['amount_paid'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-red-600">₹<?php echo number_format($bal, 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $statusColor; ?>"><?php echo $inv['payment_status']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <a href="view_stockist_invoice.php?id=<?php echo $inv['id']; ?>" class="text-sm font-semibold text-indigo-600 hover:underline">View Invoice</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payments Tab -->
            <div id="tab-payments" class="tab-content bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Date & Time</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Invoice #</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Method</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Ref / Txn ID</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Remarks</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No payment history found for this stockist.</td></tr>
                            <?php else: ?>
                                <?php foreach ($payments as $pay): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-700"><?php echo date('d M Y, h:i A', strtotime($pay['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">
                                        <a href="view_stockist_invoice.php?id=<?php echo $pay['invoice_id']; ?>" class="hover:underline"><?php echo htmlspecialchars($pay['invoice_number']); ?></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($pay['payment_method'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono"><?php echo htmlspecialchars($pay['transaction_id'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($pay['remarks'] ?? '-')); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-black text-lg text-green-600">+ ₹<?php echo number_format($pay['amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        function toggleMobileSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }

        function switchTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            // Remove active styling from buttons
            document.getElementById('btn-invoices').className = "px-6 py-3 font-bold text-sm border-b-2 transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
            document.getElementById('btn-payments').className = "px-6 py-3 font-bold text-sm border-b-2 transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
            
            // Show selected tab
            document.getElementById('tab-' + tabId).classList.add('active');
            // Add active styling to button
            document.getElementById('btn-' + tabId).className = "px-6 py-3 font-bold text-sm border-b-2 transition-colors border-indigo-600 text-indigo-600";
        }
    </script>
</body>
</html>
