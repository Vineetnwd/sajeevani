<?php
require_once 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch actual statistics
$stats = [
    'leads' => 0,
    'consultations' => 0,
    'orders' => 0,
    'doctors' => 0,
    'low_stock' => 0
];

try {
    $stats['leads'] = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $stats['consultations'] = $pdo->query("SELECT COUNT(*) FROM consultations WHERE status='Pending'")->fetchColumn();
    $stats['orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['doctors'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='Doctor'")->fetchColumn();
    $stats['low_stock'] = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 10")->fetchColumn();

    // Fetch recent activity (last 5 patients)
    $recent_patients = $pdo->query("SELECT name, phone, created_at FROM patients ORDER BY created_at DESC LIMIT 5")->fetchAll();
    // Fetch recent orders
    $recent_orders = $pdo->query("SELECT o.id, p.name as patient_name, o.status, o.total_amount, o.created_at FROM orders o JOIN patients p ON o.patient_id = p.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

    // Fetch Chart Data: Leads last 7 days
    $leads_chart_labels = [];
    $leads_chart_data_raw = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $leads_chart_labels[] = date('M d', strtotime("-$i days"));
        $leads_chart_data_raw[$date] = 0;
    }
    $leadsQuery = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM patients WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
    while ($row = $leadsQuery->fetch()) {
        if (isset($leads_chart_data_raw[$row['date']])) {
            $leads_chart_data_raw[$row['date']] = $row['count'];
        }
    }
    $leads_chart_data = array_values($leads_chart_data_raw);

    // Fetch Chart Data: Orders by status
    $order_status_labels = ['Created', 'Packing', 'Dispatched', 'Delivered', 'Cancelled'];
    $order_status_data = [0, 0, 0, 0, 0];
    $statusQuery = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    while ($row = $statusQuery->fetch()) {
        $idx = array_search($row['status'], $order_status_labels);
        if ($idx !== false) {
            $order_status_data[$idx] = $row['count'];
        }
    }

    // --- NEW REPORTS DATA ---
    // Financial Stats
    $stats['revenue'] = $pdo->query("SELECT (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'Cancelled') + (SELECT COALESCE(SUM(total_amount),0) FROM doctor_orders WHERE status != 'Cancelled')")->fetchColumn();
    $stats['expense'] = $pdo->query("SELECT COALESCE(SUM(quantity * purchase_rate),0) FROM purchases")->fetchColumn();
    $stats['doc_orders'] = $pdo->query("SELECT COUNT(*) FROM doctor_orders WHERE status='Pending'")->fetchColumn();

    // Chart Data: Revenue last 7 days
    $revenue_chart_labels = [];
    $revenue_chart_data_raw = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $revenue_chart_labels[] = date('M d', strtotime("-$i days"));
        $revenue_chart_data_raw[$date] = 0;
    }
    
    $revQuery1 = $pdo->query("SELECT DATE(created_at) as date, SUM(total_amount) as total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status != 'Cancelled' GROUP BY DATE(created_at)");
    while ($row = $revQuery1->fetch()) {
        if (isset($revenue_chart_data_raw[$row['date']])) {
            $revenue_chart_data_raw[$row['date']] += (float)$row['total'];
        }
    }
    $revQuery2 = $pdo->query("SELECT DATE(created_at) as date, SUM(total_amount) as total FROM doctor_orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status != 'Cancelled' GROUP BY DATE(created_at)");
    while ($row = $revQuery2->fetch()) {
        if (isset($revenue_chart_data_raw[$row['date']])) {
            $revenue_chart_data_raw[$row['date']] += (float)$row['total'];
        }
    }
    $revenue_chart_data = array_values($revenue_chart_data_raw);

    // Recent Purchases
    $recent_purchases = $pdo->query("SELECT p.name as product_name, pu.dealer_name, pu.quantity, pu.purchase_rate, pu.created_at FROM purchases pu JOIN products p ON pu.product_id = p.id ORDER BY pu.created_at DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    // Silently handle if tables don't exist yet
    $recent_patients = [];
    $recent_orders = [];
    $leads_chart_labels = [];
    $leads_chart_data = [];
    $order_status_labels = [];
    $order_status_data = [];
    $revenue_chart_labels = [];
    $revenue_chart_data = [];
    $recent_purchases = [];
    $stats['revenue'] = 0;
    $stats['expense'] = 0;
    $stats['doc_orders'] = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(249, 250, 251, 1) 100%);
        }
    </style>
    <link rel="stylesheet" href="admin-style.css">
</head>

<body class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Top header -->
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
        <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <div class="min-w-0">
            <div class="min-w-0 min-w-0">
                <h1 class="text-lg sm:text-xl sm:text-lg sm:text-xl sm:text-2xl font-extrabold text-gray-800 tracking-tight truncate">Overview</h1>
                <p class="text-xs sm:text-sm text-gray-500 font-medium mt-0.5 truncate">Welcome back, <span
                        class="text-green-600"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>!</p>
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center px-3 py-1.5 bg-green-50 rounded-full border border-green-100">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse mr-2"></span>
                    <span class="text-xs font-bold text-green-700 uppercase tracking-wider">System Online</span>
                </div>
            </div>
    </div>
</header>

        <!-- Dynamic Content Area -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6 bg-[#f8fafc]">

            <!-- Quick Stats Row 1 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Stat 1: Revenue -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Revenue</p>
                        <h3 class="text-2xl font-black text-gray-800">₹<?php echo number_format($stats['revenue']); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Stat 2: Expense -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Expense</p>
                        <h3 class="text-2xl font-black text-gray-800">₹<?php echo number_format($stats['expense']); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                    </div>
                </div>

                <!-- Stat 3: Total Orders -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Orders</p>
                        <h3 class="text-2xl font-black text-gray-800"><?php echo number_format($stats['orders']); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>

                <!-- Stat 4: Pending Consults -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-2">
                            Pending Consults
                            <?php if ($stats['consultations'] > 0): ?>
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <?php endif; ?>
                        </p>
                        <h3 class="text-2xl font-black text-gray-800"><?php echo number_format($stats['consultations']); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Row 2 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Stat 5: Pending Doctor Orders -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between hover:shadow-md transition-all <?php echo $stats['doc_orders'] > 0 ? 'border-amber-200 bg-amber-50/10' : ''; ?>">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-2">
                            Pending Doctor Orders
                            <?php if ($stats['doc_orders'] > 0): ?>
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <?php endif; ?>
                        </p>
                        <h3 class="text-2xl font-black <?php echo $stats['doc_orders'] > 0 ? 'text-amber-600' : 'text-gray-800'; ?>"><?php echo number_format($stats['doc_orders']); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full <?php echo $stats['doc_orders'] > 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-50 text-gray-500'; ?> flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002 2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>

                <!-- Stat 6: Active Doctors -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Active Doctors</p>
                        <h3 class="text-2xl font-black text-gray-800"><?php echo number_format($stats['doctors']); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Stat 7: Total Leads -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between hover:shadow-md transition-all">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Leads</p>
                        <h3 class="text-2xl font-black text-gray-800"><?php echo number_format($stats['leads']); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Stat 8: Low Stock -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex items-center justify-between hover:shadow-md transition-all <?php echo $stats['low_stock'] > 0 ? 'border-red-200 bg-red-50/10' : ''; ?>">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-2">
                            Low Stock Items
                            <?php if ($stats['low_stock'] > 0): ?>
                                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                            <?php endif; ?>
                        </p>
                        <h3 class="text-2xl font-black <?php echo $stats['low_stock'] > 0 ? 'text-red-600' : 'text-gray-800'; ?>"><?php echo number_format($stats['low_stock']); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full <?php echo $stats['low_stock'] > 0 ? 'bg-red-100 text-red-600' : 'bg-gray-50 text-gray-500'; ?> flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Bar Chart: Revenue -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-bold text-gray-800">Revenue (7 Days)</h3>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-green-600 bg-green-50 px-2.5 py-1 rounded-md border border-green-200">Sales</span>
                    </div>
                    <div class="relative flex-1 w-full min-h-[220px]">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Line Chart: Leads -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-bold text-gray-800">Leads Overview</h3>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 bg-gray-100 px-2.5 py-1 rounded-md border border-gray-200">Last 7 Days</span>
                    </div>
                    <div class="relative flex-1 w-full min-h-[220px]">
                        <canvas id="leadsChart"></canvas>
                    </div>
                </div>

                <!-- Doughnut Chart: Orders -->
                <div class="bg-white p-5 rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 flex flex-col">
                    <h3 class="text-sm font-bold text-gray-800 mb-4">Orders by Status</h3>
                    <div class="relative flex-1 w-full min-h-[220px] flex justify-center items-center">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Lists & Actions Section -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Recent Purchases -->
                <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden flex flex-col lg:col-span-1">
                    <div class="p-4 border-b border-gray-50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-800">Recent Purchases</h3>
                        <a href="purchases.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2 py-1 rounded">View All</a>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <?php if (empty($recent_purchases)): ?>
                            <div class="flex h-full items-center justify-center p-6 text-sm text-gray-400">No recent purchases</div>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-50">
                                <?php foreach ($recent_purchases as $rpur): ?>
                                    <li class="p-3 hover:bg-gray-50 flex items-center justify-between transition-colors">
                                        <div class="min-w-0 pr-2">
                                            <p class="text-sm font-bold text-gray-800 truncate"><?php echo htmlspecialchars($rpur['product_name']); ?></p>
                                            <p class="text-[11px] font-semibold text-gray-500 truncate">From: <?php echo htmlspecialchars($rpur['dealer_name']); ?></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-[11px] font-bold text-gray-900 mb-0.5">Qty: <?php echo htmlspecialchars($rpur['quantity']); ?></p>
                                            <p class="text-[10px] text-gray-400">₹<?php echo number_format($rpur['quantity'] * $rpur['purchase_rate'], 2); ?></p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Leads -->
                <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden flex flex-col lg:col-span-1">
                    <div class="p-4 border-b border-gray-50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-800">Recent Leads</h3>
                        <a href="leads.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2 py-1 rounded">View All</a>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <?php if (empty($recent_patients)): ?>
                            <div class="flex h-full items-center justify-center p-6 text-sm text-gray-400">No recent leads</div>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-50">
                                <?php foreach ($recent_patients as $rp): ?>
                                    <li class="p-3 hover:bg-gray-50 flex items-center justify-between transition-colors">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="w-8 h-8 shrink-0 rounded-full bg-gradient-to-br from-indigo-100 to-blue-100 text-indigo-700 font-bold flex items-center justify-center text-xs border border-indigo-200">
                                                <?php echo strtoupper(substr($rp['name'], 0, 1)); ?>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-gray-800 truncate"><?php echo htmlspecialchars($rp['name']); ?></p>
                                                <p class="text-[11px] font-semibold text-gray-500 truncate"><?php echo htmlspecialchars($rp['phone']); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden flex flex-col lg:col-span-1">
                    <div class="p-4 border-b border-gray-50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-800">Recent Orders</h3>
                        <a href="orders.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2 py-1 rounded">View All</a>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <?php if (empty($recent_orders)): ?>
                            <div class="flex h-full items-center justify-center p-6 text-sm text-gray-400">No recent orders</div>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-50">
                                <?php foreach ($recent_orders as $ro): 
                                    $statusColor = 'bg-gray-100 text-gray-600 border-gray-200';
                                    if ($ro['status'] === 'Delivered') $statusColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    if ($ro['status'] === 'Dispatched') $statusColor = 'bg-blue-50 text-blue-700 border-blue-200';
                                    if ($ro['status'] === 'Cancelled') $statusColor = 'bg-red-50 text-red-700 border-red-200';
                                    if ($ro['status'] === 'Packing') $statusColor = 'bg-amber-50 text-amber-700 border-amber-200';
                                ?>
                                    <li class="p-3 hover:bg-gray-50 flex justify-between items-center transition-colors">
                                        <div class="min-w-0 pr-2">
                                            <p class="text-sm font-bold text-gray-800 truncate">#ORD-<?php echo str_pad($ro['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                            <p class="text-[11px] font-semibold text-gray-500 truncate"><?php echo htmlspecialchars($ro['patient_name']); ?></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-sm font-black text-gray-900 leading-none mb-1">₹<?php echo number_format($ro['total_amount'], 2); ?></p>
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider border <?php echo $statusColor; ?>">
                                                <?php echo htmlspecialchars($ro['status']); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-gray-900 via-gray-800 to-indigo-900 rounded-xl shadow-lg p-6 text-white relative overflow-hidden flex flex-col justify-between group">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500 rounded-full opacity-20 blur-2xl group-hover:opacity-40 transition-opacity duration-500"></div>
                    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-blue-500 rounded-full opacity-20 blur-2xl group-hover:opacity-40 transition-opacity duration-500"></div>
                    
                    <div class="relative z-10">
                        <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center mb-4 border border-white/20 backdrop-blur-sm">
                            <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black mb-1">Quick Actions</h3>
                        <p class="text-gray-300 text-[11px] font-medium mb-6 leading-relaxed">Fast access to essential modules to streamline your daily workflow.</p>
                    </div>
                    
                    <div class="relative z-10 flex flex-col gap-2">
                        <a href="leads.php" class="w-full flex items-center justify-between px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/10 rounded-lg text-sm font-bold transition-all backdrop-blur-sm">
                            <span>Manage Leads</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="products.php" class="w-full flex items-center justify-between px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/10 rounded-lg text-sm font-bold transition-all backdrop-blur-sm">
                            <span>Check Inventory</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="orders.php" class="w-full flex items-center justify-between px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/10 rounded-lg text-sm font-bold transition-all backdrop-blur-sm">
                            <span>View Orders</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Revenue Chart (Bar)
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($revenue_chart_labels); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($revenue_chart_data); ?>,
                    backgroundColor: '#10b981', // emerald-500
                    borderRadius: 4,
                    barPercentage: 0.6
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

        // Leads Chart (Line)
        const leadsCtx = document.getElementById('leadsChart').getContext('2d');
        new Chart(leadsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($leads_chart_labels); ?>,
                datasets: [{
                    label: 'New Leads',
                    data: <?php echo json_encode($leads_chart_data); ?>,
                    borderColor: '#4f46e5', // indigo-600
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
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
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Orders Chart (Doughnut)
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($order_status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($order_status_data); ?>,
                    backgroundColor: [
                        '#9ca3af', // gray-400 (Created)
                        '#fbbf24', // amber-400 (Packing)
                        '#60a5fa', // blue-400 (Dispatched)
                        '#34d399', // emerald-400 (Delivered)
                        '#f87171'  // red-400 (Cancelled)
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { family: 'Inter', size: 12 }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>