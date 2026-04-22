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
    <title>Purchase History - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Purchase Ledger (History)</h1>
        </header>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Medicine</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Dealer</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Input Qty</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Purchase Rate</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Total Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $pdo->query("
                                SELECT pu.id, p.name as medicine_name, pu.dealer_name, pu.quantity, pu.purchase_rate, pu.created_at, 
                                       (pu.quantity * pu.purchase_rate) as total_value
                                FROM purchases pu
                                JOIN products p ON pu.product_id = p.id
                                ORDER BY pu.id DESC
                            ");
                            $history = $stmt->fetchAll();
                            
                            if(count($history) == 0) {
                                echo '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No purchase records found in the ledger.</td></tr>';
                            }
                            
                            foreach($history as $h) {
                                $date = date('d M Y, h:i A', strtotime($h['created_at']));
                                echo '<tr class="hover:bg-gray-50">';
                                echo '<td class="px-6 py-4 text-sm text-gray-500">'.$date.'</td>';
                                echo '<td class="px-6 py-4 font-bold text-indigo-700">'.htmlspecialchars($h['medicine_name']).'</td>';
                                echo '<td class="px-6 py-4 text-sm font-medium text-gray-700">'.htmlspecialchars($h['dealer_name']).'</td>';
                                echo '<td class="px-6 py-4 font-bold text-gray-900">'.$h['quantity'].' Units</td>';
                                echo '<td class="px-6 py-4 text-sm font-medium text-gray-600">₹'.number_format($h['purchase_rate'], 2).'</td>';
                                echo '<td class="px-6 py-4 font-bold text-gray-900 text-sm">₹'.number_format($h['total_value'], 2).'</td>';
                                echo '</tr>';
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Please establish the purchase table first by adding stock.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
