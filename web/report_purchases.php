<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$date_from = $_GET['date_from'] ?? date('Y-m-01'); // Default to 1st of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Default to today
$dealer = trim($_GET['dealer'] ?? '');

$where_clauses = [];
$params = [];

if (!empty($date_from)) {
    $where_clauses[] = "DATE(pu.created_at) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $where_clauses[] = "DATE(pu.created_at) <= ?";
    $params[] = $date_to;
}
if (!empty($dealer)) {
    $where_clauses[] = "pu.dealer_name LIKE ?";
    $params[] = "%$dealer%";
}

$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Calculate total for summary card
$totalParams = $params;
$totalStmt = $pdo->prepare("SELECT SUM(quantity * purchase_rate) as total_purchase FROM purchases pu $where_sql");
$totalStmt->execute($totalParams);
$total_purchase_amount = $totalStmt->fetchColumn() ?: 0.00;

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Purchase_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Total Purchases for Period', $total_purchase_amount]);
    fputcsv($output, []); 
    fputcsv($output, ['Date', 'Invoice No.', 'Medicine', 'Dealer', 'Qty', 'Rate', 'Total Value']);
    
    $stmt = $pdo->prepare("SELECT pu.*, p.name as product_name FROM purchases pu JOIN products p ON pu.product_id = p.id $where_sql ORDER BY pu.created_at DESC");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            date('d M Y', strtotime($row['created_at'])),
            $row['purchase_invoice_no'] ?? 'N/A',
            $row['product_name'],
            $row['dealer_name'],
            $row['quantity'],
            $row['purchase_rate'],
            $row['quantity'] * $row['purchase_rate']
        ]);
    }
    fclose($output);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Report - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; }
        }
    </style>
    <link rel="stylesheet" href="admin-style.css">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="no-print bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Purchase Report</h1>
                </div>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="hidden sm:inline">Export CSV</span>
                </a>
                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span class="hidden sm:inline">Print Report</span>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 pb-20">
            <!-- Filter Bar -->
            <div class="no-print bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 mb-6">
                <form method="GET" action="" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">From Date</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">To Date</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Dealer (Optional)</label>
                        <input type="text" name="dealer" placeholder="e.g. Acme Corp" value="<?php echo htmlspecialchars($dealer); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div class="w-full sm:w-auto flex gap-2">
                        <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-teal-700 transition-colors">Generate</button>
                        <a href="report_purchases.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-200 transition-colors">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Summary Card -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Purchases</p>
                        <h3 class="text-2xl font-black text-gray-800">₹<?php echo number_format($total_purchase_amount, 2); ?></h3>
                        <p class="text-[10px] text-gray-400 font-semibold mt-1">For selected period</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                    </div>
                </div>
            </div>

            <div class="hidden print-only mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Purchase Report</h2>
                <p class="text-gray-600 text-sm">Period: <?php echo date('d M Y', strtotime($date_from)); ?> to <?php echo date('d M Y', strtotime($date_to)); ?></p>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden mb-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Invoice No.</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Medicine</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Dealer</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Qty</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Rate</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Total Value</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT pu.*, p.name as product_name FROM purchases pu JOIN products p ON pu.product_id = p.id $where_sql ORDER BY pu.created_at DESC");
                                $stmt->execute($params);
                                
                                $has_records = false;
                                while ($row = $stmt->fetch()) {
                                    $has_records = true;
                                    $total_val = $row['quantity'] * $row['purchase_rate'];
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-teal-600">
                                            <?php echo htmlspecialchars($row['purchase_invoice_no'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($row['product_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($row['dealer_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                            <?php echo number_format($row['quantity']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            ₹<?php echo number_format($row['purchase_rate'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-gray-900 text-right">
                                            ₹<?php echo number_format($total_val, 2); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                
                                if (!$has_records) {
                                    echo '<tr><td colspan="7" class="px-6 py-12 text-center text-gray-500 text-sm">No purchases found for the selected period.</td></tr>';
                                }
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="7" class="px-6 py-12 text-center text-red-500 text-sm">Error loading data.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
