<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$invoice_no = $_GET['invoice_no'] ?? '';
if (empty($invoice_no) || $invoice_no === 'N/A') {
    die("Invalid Invoice Number");
}

// Fetch all items under this invoice
$stmt = $pdo->prepare("
    SELECT pu.*, p.name as product_name, d.address as dealer_address, d.email as dealer_email 
    FROM purchases pu 
    JOIN products p ON pu.product_id = p.id 
    LEFT JOIN dealers d ON pu.dealer_name = d.name
    WHERE pu.purchase_invoice_no = ?
    ORDER BY pu.id ASC
");
$stmt->execute([$invoice_no]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($items) === 0) {
    die("Invoice not found");
}

$dealer_name = $items[0]['dealer_name'];
$dealer_address = $items[0]['dealer_address'] ?? '';
$dealer_email = $items[0]['dealer_email'] ?? '';
$invoice_date = date('d-M-Y', strtotime($items[0]['created_at']));

$grand_total = 0;
foreach($items as $item) {
    $grand_total += ($item['quantity'] * $item['purchase_rate']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice #<?php echo htmlspecialchars($invoice_no); ?> - <?php echo defined('APP_NAME') ? APP_NAME : 'Sanjeevni'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; } 
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            background-color: #fff;
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; }
            .invoice-box { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="py-10">
    <!-- Action Bar -->
    <div class="max-w-4xl mx-auto mb-6 no-print px-4 lg:px-0">
        <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200">
            <a href="purchases.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">&larr; Back to Ledger</a>
            <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-sm font-bold shadow-md transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Invoice
            </button>
        </div>
    </div>

    <!-- Invoice Document -->
    <div class="invoice-box text-gray-800 text-sm rounded-xl">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-center border-b-2 border-gray-800 pb-4 mb-4 gap-4 sm:gap-4">
            <div class="text-left sm:w-2/3 leading-tight">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 uppercase tracking-widest whitespace-nowrap mb-1">PURCHASE INVOICE</h2>
                <p class="text-gray-800 text-xs"><strong>Recorded At:</strong> <?php echo defined('APP_NAME') ? APP_NAME : 'Sanjeevni'; ?></p>
            </div>
            <div class="text-left sm:text-right sm:w-1/3 shrink-0">
                <div class="mt-1 leading-tight">
                    <p class="text-gray-800 text-xs whitespace-nowrap"><strong>Invoice No:</strong> <?php echo htmlspecialchars($invoice_no); ?></p>
                    <p class="text-gray-800 text-xs whitespace-nowrap"><strong>Date:</strong> <?php echo $invoice_date; ?></p>
                </div>
            </div>
        </div>

        <!-- Vendor Info -->
        <div class="mb-4 leading-tight">
            <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 border-b border-gray-200 pb-0.5">Purchased From (Vendor)</h3>
            <p class="font-bold text-base text-gray-900 mb-0.5"><?php echo htmlspecialchars($dealer_name); ?></p>
            <?php if (!empty($dealer_address)): ?>
                <p class="text-gray-600 text-xs"><?php echo nl2br(htmlspecialchars(ucwords($dealer_address))); ?></p>
            <?php endif; ?>
            <?php if (!empty($dealer_email)): ?>
                <p class="text-gray-600 text-xs mt-0.5">Email: <?php echo htmlspecialchars($dealer_email); ?></p>
            <?php endif; ?>
        </div>

        <!-- Items Table -->
        <table class="w-full mb-4 border-collapse border border-gray-300">
            <thead class="bg-gray-100 text-gray-900">
                <tr>
                    <th class="border border-gray-300 p-2 text-center w-10 font-bold uppercase text-[10px]">S.No</th>
                    <th class="border border-gray-300 p-2 text-left font-bold uppercase text-[10px]">Medicine Description</th>
                    <th class="border border-gray-300 p-2 text-right w-20 font-bold uppercase text-[10px]">Pur. Rate</th>
                    <th class="border border-gray-300 p-2 text-right w-16 font-bold uppercase text-[10px]">Qty</th>
                    <th class="border border-gray-300 p-2 text-right w-24 font-bold uppercase text-[10px]">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach($items as $item): 
                    $row_total = $item['quantity'] * $item['purchase_rate'];
                ?>
                <tr>
                    <td class="border border-gray-300 p-1.5 text-center text-gray-800 text-xs"><?php echo $sno++; ?></td>
                    <td class="border border-gray-300 p-1.5 font-semibold text-gray-900 text-xs"><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td class="border border-gray-300 p-1.5 text-right text-gray-800 text-xs">₹<?php echo number_format($item['purchase_rate'], 2); ?></td>
                    <td class="border border-gray-300 p-1.5 text-right font-bold text-gray-900 text-xs"><?php echo $item['quantity']; ?></td>
                    <td class="border border-gray-300 p-1.5 text-right font-black text-gray-900 text-xs">₹<?php echo number_format($row_total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-between items-end mb-6">
            <div class="w-1/2">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Amount in Words</p>
                <p class="text-xs font-semibold text-gray-800 italic">
                    <?php 
                        if (extension_loaded('intl')) {
                            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                            echo ucwords($f->format($grand_total)) . " Rupees Only"; 
                        } else {
                            echo "Rupees " . number_format($grand_total, 2) . " Only";
                        }
                    ?>
                </p>
            </div>
            <div class="w-1/2 sm:w-1/3">
                <table class="w-full text-xs border border-gray-300">
                    <tr class="bg-gray-50">
                        <td class="p-2 text-gray-800 font-black text-sm uppercase">Grand Total</td>
                        <td class="p-2 text-right text-sm font-black text-indigo-700">₹<?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between items-end mt-8 pt-4 border-t-2 border-gray-800">
            <div class="w-full text-center">
                <p class="font-bold text-gray-800 text-[10px] uppercase">Internal Purchase Record</p>
                <p class="text-[9px] text-gray-500 mt-0.5">Generated by <?php echo defined('APP_NAME') ? APP_NAME : 'Sanjeevni'; ?> System</p>
            </div>
        </div>
        
    </div>
</body>
</html>
