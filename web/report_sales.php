<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'all'; // all, patient, doctor
$search = trim($_GET['search'] ?? '');

$where_clauses_o = [];
$params_o = [];
$where_clauses_do = [];
$params_do = [];

if (!empty($date_from)) {
    $where_clauses_o[] = "DATE(o.created_at) >= ?";
    $where_clauses_do[] = "DATE(do.created_at) >= ?";
    $params_o[] = $date_from;
    $params_do[] = $date_from;
}
if (!empty($date_to)) {
    $where_clauses_o[] = "DATE(o.created_at) <= ?";
    $where_clauses_do[] = "DATE(do.created_at) <= ?";
    $params_o[] = $date_to;
    $params_do[] = $date_to;
}
if (!empty($search)) {
    $where_clauses_o[] = "(o.id LIKE ? OR p.name LIKE ?)";
    $where_clauses_do[] = "(do.id LIKE ? OR u.name LIKE ?)";
    $params_o[] = "%$search%";
    $params_o[] = "%$search%";
    $params_do[] = "%$search%";
    $params_do[] = "%$search%";
}

$where_sql_o = $where_clauses_o ? "WHERE " . implode(" AND ", $where_clauses_o) : "";
$where_sql_do = $where_clauses_do ? "WHERE " . implode(" AND ", $where_clauses_do) : "";

// Construct UNION query
$query_parts = [];
$final_params = [];

if ($type === 'all' || $type === 'patient') {
    $query_parts[] = "SELECT 'Patient' as sale_type, o.id as order_id, o.created_at as sale_date, p.name as customer_name, o.status, o.total_amount 
                      FROM orders o JOIN patients p ON o.patient_id = p.id $where_sql_o";
    $final_params = array_merge($final_params, $params_o);
}
if ($type === 'all' || $type === 'doctor') {
    $query_parts[] = "SELECT 'Doctor' as sale_type, do.id as order_id, do.created_at as sale_date, u.name as customer_name, do.status, do.total_amount 
                      FROM doctor_orders do JOIN users u ON do.doctor_id = u.id $where_sql_do";
    $final_params = array_merge($final_params, $params_do);
}

$final_query = implode(" UNION ALL ", $query_parts) . " ORDER BY sale_date DESC";

// Calculate Total Revenue (excluding cancelled)
$total_revenue = 0;
$stmt_all = $pdo->prepare($final_query);
$stmt_all->execute($final_params);
$all_sales = $stmt_all->fetchAll();

foreach ($all_sales as $sale) {
    if ($sale['status'] !== 'Cancelled') {
        $total_revenue += $sale['total_amount'];
    }
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Sales_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Total Sales Revenue (Excl. Cancelled)', $total_revenue]);
    fputcsv($output, []); 
    fputcsv($output, ['Date', 'Order ID', 'Sale Type', 'Customer Name', 'Status', 'Amount']);
    
    foreach ($all_sales as $row) {
        fputcsv($output, [
            date('d M Y, h:i A', strtotime($row['sale_date'])),
            '#' . $row['order_id'],
            $row['sale_type'],
            $row['customer_name'] ?? 'Unknown',
            $row['status'],
            $row['total_amount']
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
    <title>Sales Report - <?php echo APP_NAME; ?></title>
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
                    <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Sales Report</h1>
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
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sale Type</label>
                        <select name="type" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Sales</option>
                            <option value="patient" <?php echo $type === 'patient' ? 'selected' : ''; ?>>Patient Sales Only</option>
                            <option value="doctor" <?php echo $type === 'doctor' ? 'selected' : ''; ?>>Doctor Sales Only</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Order ID or Customer..." class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>
                    <div class="w-full sm:w-auto flex gap-2">
                        <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-teal-700 transition-colors">Generate</button>
                        <a href="report_sales.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-200 transition-colors">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Summary Card -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Sales Revenue</p>
                        <h3 class="text-2xl font-black text-gray-800">₹<?php echo number_format($total_revenue, 2); ?></h3>
                        <p class="text-[10px] text-gray-400 font-semibold mt-1">Excludes cancelled orders</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="hidden print-only mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Sales Report</h2>
                <p class="text-gray-600 text-sm">Period: <?php echo date('d M Y', strtotime($date_from)); ?> to <?php echo date('d M Y', strtotime($date_to)); ?></p>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden mb-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Order ID</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Sale Type</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Customer Name</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            if (empty($all_sales)) {
                                echo '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500 text-sm">No sales found for the selected period.</td></tr>';
                            } else {
                                foreach ($all_sales as $row) {
                                    $statusColor = 'bg-gray-100 text-gray-800';
                                    if ($row['status'] === 'Delivered') $statusColor = 'bg-green-100 text-green-800';
                                    if ($row['status'] === 'Cancelled') $statusColor = 'bg-red-100 text-red-800';
                                    if ($row['status'] === 'Dispatched') $statusColor = 'bg-blue-100 text-blue-800';
                                    if ($row['status'] === 'Pending') $statusColor = 'bg-amber-100 text-amber-800';

                                    $typeColor = $row['sale_type'] === 'Patient' ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-purple-50 text-purple-700 border-purple-200';
                                    
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors <?php echo $row['status'] === 'Cancelled' ? 'opacity-50' : ''; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d M Y, h:i A', strtotime($row['sale_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                            #<?php echo htmlspecialchars($row['order_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?php echo $typeColor; ?>">
                                                <?php echo htmlspecialchars($row['sale_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($row['customer_name'] ?? 'Unknown'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-md <?php echo $statusColor; ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-gray-900 text-right">
                                            ₹<?php echo number_format($row['total_amount'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
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
