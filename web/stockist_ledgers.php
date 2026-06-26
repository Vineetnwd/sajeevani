<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$currentPage = 'stockist_ledgers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockist Ledgers - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Master Stockist Ledgers</h1>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <?php $search = trim($_GET['search'] ?? ''); ?>
            
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-4 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <form method="GET" action="" class="flex w-full sm:w-auto gap-3 items-center">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Name or Phone..." class="w-full sm:w-64 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm hover:bg-teal-700">Search</button>
                    <?php if($search): ?>
                        <a href="stockist_ledgers.php" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Stockist Details</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Total Billed</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Total Paid</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Balance Due</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $where_clause = "u.role = 'Stockist'";
                                $params = [];
                                
                                if ($search !== '') {
                                    $where_clause .= " AND (u.name LIKE ? OR u.phone LIKE ?)";
                                    $params[] = "%$search%";
                                    $params[] = "%$search%";
                                }

                                $stmt = $pdo->prepare("
                                    SELECT 
                                        u.id, 
                                        u.name, 
                                        u.phone, 
                                        COALESCE(SUM(i.total_amount), 0) as total_billed,
                                        COALESCE(SUM(i.amount_paid), 0) as total_paid
                                    FROM users u
                                    LEFT JOIN stockist_invoices i ON u.id = i.stockist_id
                                    WHERE $where_clause
                                    GROUP BY u.id, u.name, u.phone
                                    ORDER BY u.name ASC
                                ");
                                $stmt->execute($params);
                                $stockists = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($stockists) == 0) {
                                    echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No stockists found.</td></tr>';
                                }

                                foreach ($stockists as $s) {
                                    $balance = $s['total_billed'] - $s['total_paid'];
                                    
                                    echo '<tr class="hover:bg-gray-50">';
                                    echo '<td class="px-6 py-4 whitespace-nowrap">';
                                    echo '<div class="font-bold text-gray-900">' . htmlspecialchars($s['name']) . '</div>';
                                    echo '<div class="text-sm text-gray-500">' . htmlspecialchars($s['phone']) . '</div>';
                                    echo '</td>';
                                    echo '<td class="px-6 py-4 text-right whitespace-nowrap text-gray-600 font-semibold">₹' . number_format($s['total_billed'], 2) . '</td>';
                                    echo '<td class="px-6 py-4 text-right whitespace-nowrap text-green-600 font-semibold">₹' . number_format($s['total_paid'], 2) . '</td>';
                                    
                                    $balanceClass = $balance > 0 ? 'text-red-600 font-black' : 'text-gray-900 font-bold';
                                    echo '<td class="px-6 py-4 text-right whitespace-nowrap text-lg ' . $balanceClass . '">₹' . number_format($balance, 2) . '</td>';
                                    
                                    echo '<td class="px-6 py-4 text-right whitespace-nowrap">';
                                    echo '<a href="view_stockist_ledger.php?id=' . $s['id'] . '" class="inline-flex items-center text-sm font-bold text-teal-600 hover:text-teal-900 bg-teal-50 px-4 py-2 rounded-lg border border-teal-200 transition-colors shadow-sm">View Ledger &rarr;</a>';
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

    <script>
        function toggleMobileSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }
    </script>
</body>
</html>
