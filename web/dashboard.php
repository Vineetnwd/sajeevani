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
} catch (PDOException $e) {
    // Silently handle if tables don't exist yet
    $recent_patients = [];
    $recent_orders = [];
    $leads_chart_labels = [];
    $leads_chart_data = [];
    $order_status_labels = [];
    $order_status_data = [];
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
</head>

<body class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Top header -->
        <header
            class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-8 py-5 flex justify-between items-center sticky top-0">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Overview</h1>
                <p class="text-sm text-gray-500 font-medium mt-0.5">Welcome back, <span
                        class="text-green-600"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>!</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center px-3 py-1.5 bg-green-50 rounded-full border border-green-100">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse mr-2"></span>
                    <span class="text-xs font-bold text-green-700 uppercase tracking-wider">System Online</span>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Area -->
        <div class="flex-1 overflow-y-auto p-8 space-y-8">

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <!-- Stat 1 -->
                <div
                    class="stat-card p-6 rounded-2xl shadow-sm border border-gray-200 relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-blue-50 rounded-full transition-transform group-hover:scale-125 duration-500 opacity-50">
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm font-semibold text-gray-500 mb-1 uppercase tracking-wider">Total Leads</div>
                        <div class="text-4xl font-extrabold text-gray-900"><?php echo number_format($stats['leads']); ?>
                        </div>
                    </div>
                </div>

                <!-- Stat 2 -->
                <div
                    class="stat-card p-6 rounded-2xl shadow-sm border border-gray-200 relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-amber-50 rounded-full transition-transform group-hover:scale-125 duration-500 opacity-50">
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <?php if ($stats['consultations'] > 0): ?>
                                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-full">Requires
                                    Action</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm font-semibold text-gray-500 mb-1 uppercase tracking-wider">Pending Consults
                        </div>
                        <div class="text-4xl font-extrabold text-gray-900">
                            <?php echo number_format($stats['consultations']); ?></div>
                    </div>
                </div>

                <!-- Stat 3 -->
                <div
                    class="stat-card p-6 rounded-2xl shadow-sm border border-gray-200 relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-emerald-50 rounded-full transition-transform group-hover:scale-125 duration-500 opacity-50">
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm font-semibold text-gray-500 mb-1 uppercase tracking-wider">Total Orders
                        </div>
                        <div class="text-4xl font-extrabold text-gray-900">
                            <?php echo number_format($stats['orders']); ?></div>
                    </div>
                </div>

                <!-- Stat 4 -->
                <div
                    class="stat-card p-6 rounded-2xl shadow-sm border border-gray-200 relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-red-50 rounded-full transition-transform group-hover:scale-125 duration-500 opacity-50">
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <?php if ($stats['low_stock'] > 0): ?>
                                <span
                                    class="text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded-full border border-red-100">Restock
                                    Needed</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm font-semibold text-gray-500 mb-1 uppercase tracking-wider">Low Stock Items
                        </div>
                        <div
                            class="text-4xl font-extrabold <?php echo $stats['low_stock'] > 0 ? 'text-red-600' : 'text-gray-900'; ?>">
                            <?php echo number_format($stats['low_stock']); ?></div>
                    </div>
                </div>

            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Line Chart: Leads over last 7 days -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Leads Overview (Last 7 Days)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="leadsChart"></canvas>
                    </div>
                </div>

                <!-- Doughnut Chart: Orders by Status -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Orders by Status</h3>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Recent Patients -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-800">Recent Leads</h3>
                        <a href="leads.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">View
                            All</a>
                    </div>
                    <div class="p-0 flex-1">
                        <?php if (empty($recent_patients)): ?>
                            <div class="text-center text-gray-500 py-12">
                                <p class="text-sm">No recent leads found.</p>
                            </div>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-100">
                                <?php foreach ($recent_patients as $rp): ?>
                                    <li class="px-6 py-4 hover:bg-gray-50 transition-colors flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gradient-to-r from-teal-400 to-blue-500 flex items-center justify-center text-white font-bold shadow-sm">
                                                <?php echo strtoupper(substr($rp['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">
                                                    <?php echo htmlspecialchars($rp['name']); ?></p>
                                                <p class="text-xs text-gray-500 font-medium">
                                                    <?php echo htmlspecialchars($rp['phone']); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-xs font-semibold text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">
                                            <?php echo date('d M, Y', strtotime($rp['created_at'])); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-800">Recent Orders</h3>
                        <a href="orders.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">View
                            All</a>
                    </div>
                    <div class="p-0 flex-1">
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center text-gray-500 py-12">
                                <p class="text-sm">No recent orders found.</p>
                            </div>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-100">
                                <?php foreach ($recent_orders as $ro):
                                    $statusColor = 'bg-gray-100 text-gray-800';
                                    if ($ro['status'] === 'Delivered')
                                        $statusColor = 'bg-green-100 text-green-800';
                                    if ($ro['status'] === 'Dispatched')
                                        $statusColor = 'bg-blue-100 text-blue-800';
                                    if ($ro['status'] === 'Cancelled')
                                        $statusColor = 'bg-red-100 text-red-800';
                                    if ($ro['status'] === 'Packing')
                                        $statusColor = 'bg-amber-100 text-amber-800';
                                    ?>
                                    <li class="px-6 py-4 hover:bg-gray-50 transition-colors flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">
                                                #ORD-<?php echo str_pad($ro['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                            <p class="text-xs text-gray-500 font-medium">
                                                <?php echo htmlspecialchars($ro['patient_name']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-gray-900 mb-1">
                                                ₹<?php echo number_format($ro['total_amount'], 2); ?></p>
                                            <span
                                                class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider <?php echo $statusColor; ?>">
                                                <?php echo htmlspecialchars($ro['status']); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Quick Actions -->
            <div class="bg-indigo-600 rounded-2xl shadow-lg p-8 text-white relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-64 h-64 bg-indigo-500 rounded-full opacity-50 blur-3xl translate-x-1/2 -translate-y-1/2">
                </div>
                <div class="relative z-10">
                    <h3 class="text-xl font-bold mb-2">Need to do something quickly?</h3>
                    <p class="text-indigo-100 text-sm mb-6 max-w-xl">Use these quick shortcuts to navigate to frequently
                        used areas of the system and manage your workflow efficiently.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="leads.php"
                            class="px-5 py-2.5 bg-white text-indigo-700 font-bold rounded-lg shadow-sm hover:shadow text-sm transition-all hover:bg-indigo-50">Manage
                            Leads</a>
                        <a href="products.php"
                            class="px-5 py-2.5 bg-indigo-700 text-white font-bold rounded-lg shadow-sm hover:shadow text-sm transition-all border border-indigo-500 hover:bg-indigo-800">Check
                            Inventory</a>
                        <a href="orders.php"
                            class="px-5 py-2.5 bg-indigo-700 text-white font-bold rounded-lg shadow-sm hover:shadow text-sm transition-all border border-indigo-500 hover:bg-indigo-800">View
                            Orders</a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
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