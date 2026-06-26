<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$where_clauses_date = [];
$params = [];

if (!empty($date_from)) {
    $where_clauses_date[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $where_clauses_date[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_sql = $where_clauses_date ? "WHERE " . implode(" AND ", $where_clauses_date) : "";
$where_sql_sales = $where_clauses_date ? "WHERE status != 'Cancelled' AND " . implode(" AND ", $where_clauses_date) : "WHERE status != 'Cancelled'";

// Calculate Patient Orders Revenue
$stmtPO = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders $where_sql_sales");
$stmtPO->execute($params);
$revenue_patient = $stmtPO->fetchColumn();

// Calculate Doctor Orders Revenue
$stmtDO = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM doctor_orders $where_sql_sales");
$stmtDO->execute($params);
$revenue_doctor = $stmtDO->fetchColumn();

$total_revenue = $revenue_patient + $revenue_doctor;

// Calculate Total Purchases (COGS)
$stmtPurch = $pdo->prepare("SELECT COALESCE(SUM(quantity * purchase_rate), 0) FROM purchases $where_sql");
$stmtPurch->execute($params);
$total_purchases = $stmtPurch->fetchColumn();

// Gross Profit
$gross_profit = $total_revenue - $total_purchases;
$margin = $total_revenue > 0 ? ($gross_profit / $total_revenue) * 100 : 0;

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Profit_Loss_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Profit & Loss Report']);
    fputcsv($output, ['Period', date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to))]);
    fputcsv($output, []); 
    
    fputcsv($output, ['Category', 'Amount']);
    fputcsv($output, ['Gross Revenue (In)', $total_revenue]);
    fputcsv($output, ['  - Patient Sales', $revenue_patient]);
    fputcsv($output, ['  - B2B Sales', $revenue_doctor]);
    fputcsv($output, []); 
    fputcsv($output, ['Purchases / COGS (Out)', $total_purchases]);
    fputcsv($output, []); 
    fputcsv($output, ['Net Profit / Loss', $gross_profit]);
    fputcsv($output, ['Profit Margin %', number_format($margin, 1) . '%']);
    
    fclose($output);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Report - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Profit & Loss Report</h1>
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
                    <div class="w-full sm:w-auto flex gap-2">
                        <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-teal-700 transition-colors">Generate</button>
                        <a href="report_pnl.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-200 transition-colors">Clear</a>
                    </div>
                </form>
            </div>

            <div class="hidden print-only mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Profit & Loss Report</h2>
                <p class="text-gray-600 text-sm">Period: <?php echo date('d M Y', strtotime($date_from)); ?> to <?php echo date('d M Y', strtotime($date_to)); ?></p>
            </div>

            <!-- P&L Dashboard Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Summary Stats Column -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Total Revenue -->
                    <div class="bg-white p-6 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 relative overflow-hidden">
                        <div class="absolute right-0 top-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 opacity-50 z-0"></div>
                        <div class="relative z-10">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Gross Revenue (In)</p>
                            <h3 class="text-3xl font-black text-gray-800 mb-2">₹<?php echo number_format($total_revenue, 2); ?></h3>
                            <div class="flex gap-4 text-xs font-semibold text-gray-500">
                                <span>Patient: ₹<?php echo number_format($revenue_patient); ?></span>
                                <span>B2B: ₹<?php echo number_format($revenue_doctor); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Purchases -->
                    <div class="bg-white p-6 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 relative overflow-hidden">
                        <div class="absolute right-0 top-0 w-24 h-24 bg-red-50 rounded-bl-full -mr-4 -mt-4 opacity-50 z-0"></div>
                        <div class="relative z-10">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Purchases/COGS (Out)</p>
                            <h3 class="text-3xl font-black text-gray-800">₹<?php echo number_format($total_purchases, 2); ?></h3>
                        </div>
                    </div>

                    <!-- Gross Profit -->
                    <div class="bg-gradient-to-br <?php echo $gross_profit >= 0 ? 'from-teal-600 to-blue-700' : 'from-red-600 to-rose-700'; ?> p-6 rounded-xl shadow-lg border border-transparent relative overflow-hidden text-white">
                        <div class="absolute right-0 top-0 w-32 h-32 bg-white rounded-bl-full -mr-4 -mt-4 opacity-10 z-0"></div>
                        <div class="relative z-10">
                            <p class="text-xs font-bold text-white/70 uppercase tracking-wider mb-1">Net Profit/Loss</p>
                            <h3 class="text-4xl font-black mb-2">₹<?php echo number_format($gross_profit, 2); ?></h3>
                            <p class="text-sm font-semibold text-white/90">
                                <?php echo number_format($margin, 1); ?>% Profit Margin
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Chart Column -->
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex flex-col">
                    <h3 class="text-sm font-bold text-gray-800 mb-6 uppercase tracking-wider">Revenue vs Expenses Comparison</h3>
                    <div class="relative flex-1 w-full min-h-[300px]">
                        <canvas id="pnlChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('pnlChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Gross Revenue', 'Purchases (COGS)', 'Net Profit'],
                datasets: [{
                    label: 'Amount (₹)',
                    data: [
                        <?php echo $total_revenue; ?>, 
                        <?php echo $total_purchases; ?>, 
                        <?php echo $gross_profit; ?>
                    ],
                    backgroundColor: [
                        '#10b981', // green for revenue
                        '#ef4444', // red for expenses
                        '<?php echo $gross_profit >= 0 ? '#0d9488' : '#ef4444'; ?>' // teal for positive profit, red for loss
                    ],
                    borderRadius: 6,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(value) { return '₹' + value; } }
                    }
                }
            }
        });
    </script>
</body>
</html>
