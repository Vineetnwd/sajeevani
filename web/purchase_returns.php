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
    <title>Purchase Returns - <?php echo APP_NAME; ?></title>
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
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Purchase Returns</h1>
                </div>
            </div>
            <a href="create_purchase_return.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition-colors flex items-center shrink-0">
                <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                <span class="hidden sm:inline">New Return</span>
                <span class="sm:hidden">Add</span>
            </a>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 pb-20">
            <?php
            $search = $_GET['search'] ?? '';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 15;
            $offset = ($page - 1) * $limit;
            
            $where_sql = "";
            $params = [];
            $countParams = [];
            
            if ($search !== '') {
                $where_sql = "WHERE p.name LIKE ? OR pr.dealer_name LIKE ? OR pr.reason LIKE ?";
                $params = ["%$search%", "%$search%", "%$search%"];
                $countParams = $params;
            }
            ?>
            
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-4 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <form method="GET" action="" class="flex w-full sm:w-auto items-center">
                    <div class="relative flex items-center w-full sm:w-96">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Medicine, Dealer or Reason..." class="w-full border border-gray-300 rounded-l-lg px-4 py-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                        <button type="submit" class="bg-orange-500 text-white px-5 py-2 rounded-r-lg text-sm font-semibold shadow-sm hover:bg-orange-600 border border-orange-500 transition-colors">Search</button>
                    </div>
                    <?php if($search): ?>
                        <a href="purchase_returns.php" class="ml-4 text-sm text-gray-500 hover:text-gray-700 underline whitespace-nowrap">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-4">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Date & Time</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Medicine</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Return To (Dealer)</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Return Qty</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Return Rate</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Total Value</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            // Count total
                            $countStmt = $pdo->prepare("SELECT count(*) FROM purchase_returns pr JOIN products p ON pr.product_id = p.id $where_sql");
                            $countStmt->execute($countParams);
                            $total_rows = $countStmt->fetchColumn();
                            $total_pages = ceil($total_rows / $limit);

                            // Fetch data
                            $stmt = $pdo->prepare("
                                SELECT pr.id, p.name as medicine_name, pr.dealer_name, pr.quantity, pr.return_rate, pr.reason, pr.created_at, 
                                       (pr.quantity * pr.return_rate) as total_value
                                FROM purchase_returns pr
                                JOIN products p ON pr.product_id = p.id
                                $where_sql
                                ORDER BY pr.id DESC
                                LIMIT $limit OFFSET $offset
                            ");
                            $stmt->execute($params);
                            $history = $stmt->fetchAll();

                            if (count($history) == 0) {
                                echo '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-400">No purchase returns found.</td></tr>';
                            }

                            foreach ($history as $h) {
                                $date = date('d M Y, h:i A', strtotime($h['created_at']));
                                
                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">' . $date . '</td>';
                                echo '<td class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap">' . htmlspecialchars($h['medicine_name']) . '</td>';
                                echo '<td class="px-6 py-4 text-sm font-medium text-gray-700 whitespace-nowrap">' . htmlspecialchars($h['dealer_name']) . '</td>';
                                echo '<td class="px-6 py-4 font-bold text-red-600 whitespace-nowrap">-' . $h['quantity'] . ' Units</td>';
                                echo '<td class="px-6 py-4 text-sm font-medium text-gray-600 whitespace-nowrap">₹' . number_format($h['return_rate'], 2) . '</td>';
                                echo '<td class="px-6 py-4 font-bold text-orange-600 text-sm whitespace-nowrap">₹' . number_format($h['total_value'], 2) . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-gray-500">' . htmlspecialchars($h['reason']) . '</td>';
                                echo '</tr>';
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-400">Database Error: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if (isset($total_pages) && $total_pages > 1): ?>
            <div class="flex items-center justify-between bg-white px-4 py-3 sm:px-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex flex-1 justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
                    <?php else: ?>
                        <span class="relative inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">Previous</span>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
                    <?php else: ?>
                        <span class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">Next</span>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-bold"><?php echo $offset + 1; ?></span> to <span class="font-bold"><?php echo min($offset + $limit, $total_rows); ?></span> of <span class="font-bold"><?php echo $total_rows; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                                </a>
                            <?php else: ?>
                                <span class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-200 cursor-not-allowed">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                                </span>
                            <?php endif; ?>
                            
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0 bg-gray-50">
                                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                            </span>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                                </a>
                            <?php else: ?>
                                <span class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-200 cursor-not-allowed">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                                </span>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>
