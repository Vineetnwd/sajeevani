<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$invoice_id = $_GET['id'] ?? 0;
if (!$invoice_id) {
    echo "Invalid Invoice ID.";
    exit;
}

// Fetch invoice details
$stmt = $pdo->prepare("
    SELECT i.*, u.name as stockist_name, u.phone as stockist_phone, u.address as stockist_address, u.email as stockist_email, u.gst_no as stockist_gst
    FROM stockist_invoices i
    JOIN users u ON i.stockist_id = u.id
    WHERE i.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    echo "Invoice not found.";
    exit;
}

// Fetch invoice items
$itemsStmt = $pdo->prepare("
    SELECT ii.*, p.name as product_name
    FROM stockist_invoice_items ii
    JOIN products p ON ii.product_id = p.id
    WHERE ii.invoice_id = ?
");
$itemsStmt->execute([$invoice_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Payment History
$paymentsStmt = $pdo->prepare("SELECT * FROM stockist_invoice_payments WHERE invoice_id = ? ORDER BY created_at ASC");
$paymentsStmt->execute([$invoice_id]);
$payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            <a href="sale_to_stockist.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">&larr; Back to Invoices</a>
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
            <div class="flex items-center gap-3 sm:w-2/3">
                <img src="logo.png" alt="Logo" class="h-10 sm:h-12 w-auto object-contain shrink-0" onerror="this.style.display='none'">
                <div class="leading-tight">
                    <h1 class="text-base sm:text-lg font-black text-gray-900 uppercase tracking-tight"><?php echo defined('APP_NAME') ? APP_NAME : 'Praanveda Ayurshakti'; ?></h1>
                    <p class="text-gray-600 text-xs">123 Business Road, Industrial Estate</p>
                    <p class="text-gray-600 text-xs">City, State - 123456</p>
                    <p class="text-gray-600 text-xs mt-0.5"><strong>Email:</strong> contact@example.com | <strong>Phone:</strong> +91-9876543210</p>
                </div>
            </div>
            <div class="text-left sm:text-right sm:w-1/3 shrink-0">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 uppercase tracking-widest whitespace-nowrap">TAX INVOICE</h2>
                <div class="mt-1 leading-tight">
                    <p class="text-gray-800 text-xs whitespace-nowrap"><strong>Invoice No:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    <p class="text-gray-800 text-xs whitespace-nowrap"><strong>Date:</strong> <?php echo date('d-M-Y', strtotime($invoice['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Billing Info -->
        <div class="flex justify-between mb-4 leading-tight">
            <div class="w-1/2 pr-4">
                <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 border-b border-gray-200 pb-0.5">Billed To</h3>
                <p class="font-bold text-base text-gray-900 mb-0.5"><?php echo htmlspecialchars($invoice['stockist_name']); ?></p>
                <p class="text-gray-600 text-xs">Role: Stockist</p>
                <?php if (!empty($invoice['stockist_address'])): ?>
                    <p class="text-gray-600 text-sm"><?php echo nl2br(htmlspecialchars(ucwords($invoice['stockist_address']))); ?></p>
                <?php endif; ?>
                <p class="text-gray-600 text-sm">Phone: <?php echo htmlspecialchars($invoice['stockist_phone']); ?></p>
                <?php if (!empty($invoice['stockist_email'])): ?>
                    <p class="text-gray-600 text-xs">Email: <?php echo htmlspecialchars($invoice['stockist_email']); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice['stockist_gst'])): ?>
                    <p class="text-gray-800 text-xs font-bold mt-1">GSTIN: <?php echo htmlspecialchars($invoice['stockist_gst']); ?></p>
                <?php endif; ?>
            </div>
            <div class="w-1/2 pl-4">
                <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 border-b border-gray-200 pb-0.5">Payment Details</h3>
                <p class="text-gray-600 text-xs"><strong>Method:</strong> <?php echo htmlspecialchars($invoice['payment_method'] ?? 'N/A'); ?></p>
                <p class="text-gray-600 text-xs"><strong>Status:</strong> <?php echo htmlspecialchars($invoice['payment_status']); ?></p>
                <p class="text-gray-600 text-xs"><strong>Ref/Txn:</strong> <?php echo htmlspecialchars($invoice['transaction_id'] ?? 'N/A'); ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full mb-4 border-collapse border border-gray-300">
            <thead class="bg-gray-100 text-gray-900">
                <tr>
                    <th class="border border-gray-300 p-2 text-center w-10 font-bold uppercase text-[10px]">S.No</th>
                    <th class="border border-gray-300 p-2 text-left font-bold uppercase text-[10px]">Item Description</th>
                    <th class="border border-gray-300 p-2 text-right w-20 font-bold uppercase text-[10px]">Unit Price</th>
                    <th class="border border-gray-300 p-2 text-right w-16 font-bold uppercase text-[10px]">Qty</th>
                    <th class="border border-gray-300 p-2 text-right w-24 font-bold uppercase text-[10px]">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach($items as $item): ?>
                <tr>
                    <td class="border border-gray-300 p-1.5 text-center text-gray-800 text-xs"><?php echo $sno++; ?></td>
                    <td class="border border-gray-300 p-1.5 font-semibold text-gray-900 text-xs"><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td class="border border-gray-300 p-1.5 text-right text-gray-800 text-xs">₹<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="border border-gray-300 p-1.5 text-right font-bold text-gray-900 text-xs"><?php echo $item['quantity']; ?></td>
                    <td class="border border-gray-300 p-1.5 text-right font-black text-gray-900 text-xs">₹<?php echo number_format($item['total_price'], 2); ?></td>
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
                            echo ucwords($f->format($invoice['total_amount'])) . " Rupees Only"; 
                        } else {
                            echo "Rupees " . number_format($invoice['total_amount'], 2) . " Only";
                        }
                    ?>
                </p>
            </div>
            <div class="w-1/2 sm:w-1/3">
                <table class="w-full text-xs">
                    <tr>
                        <td class="p-1.5 text-gray-600 font-bold uppercase text-[10px]">Subtotal</td>
                        <td class="p-1.5 text-right font-bold text-gray-900">₹<?php echo number_format($invoice['subtotal'], 2); ?></td>
                    </tr>
                    <tr class="border-t border-gray-300">
                        <td class="p-1.5 text-gray-800 font-black text-sm">Grand Total</td>
                        <td class="p-1.5 text-right text-sm font-black text-indigo-700">₹<?php echo number_format($invoice['total_amount'], 2); ?></td>
                    </tr>
                    <tr class="border-t border-gray-200">
                        <td class="p-1.5 text-gray-600 font-bold text-xs">Amount Paid</td>
                        <td class="p-1.5 text-right text-xs font-bold text-green-600">₹<?php echo number_format($invoice['amount_paid'], 2); ?></td>
                    </tr>
                    <tr class="border-t border-gray-300">
                        <td class="p-1.5 text-red-600 font-black text-xs uppercase">Balance Due</td>
                        <td class="p-1.5 text-right text-xs font-black text-red-600">₹<?php echo number_format($invoice['total_amount'] - $invoice['amount_paid'], 2); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment History -->
        <?php if (!empty($payments)): ?>
        <div class="mb-6">
            <h4 class="font-bold text-gray-800 text-[10px] uppercase tracking-wider mb-1.5 border-b border-gray-200 pb-0.5">Payment History</h4>
            <table class="w-full text-left text-[10px] border-collapse border border-gray-200">
                <thead class="bg-gray-50 text-gray-600 uppercase text-[9px]">
                    <tr>
                        <th class="p-1.5 border border-gray-200">Date</th>
                        <th class="p-1.5 border border-gray-200">Method</th>
                        <th class="p-1.5 border border-gray-200">Ref / Txn ID</th>
                        <th class="p-1.5 border border-gray-200">Remarks</th>
                        <th class="p-1.5 border border-gray-200 text-right">Amount Paid</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 text-[10px]">
                    <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td class="p-1.5 border border-gray-200"><?php echo date('d-M-Y h:i A', strtotime($pay['created_at'])); ?></td>
                        <td class="p-1.5 border border-gray-200 font-semibold"><?php echo htmlspecialchars($pay['payment_method'] ?? 'N/A'); ?></td>
                        <td class="p-1.5 border border-gray-200"><?php echo htmlspecialchars($pay['transaction_id'] ?? '-'); ?></td>
                        <td class="p-1.5 border border-gray-200 text-gray-600"><?php echo nl2br(htmlspecialchars($pay['remarks'] ?? '-')); ?></td>
                        <td class="p-1.5 border border-gray-200 text-right font-bold text-green-700">₹<?php echo number_format($pay['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Footer terms and Signature -->
        <div class="flex justify-between items-end mt-8 pt-4 border-t-2 border-gray-800">
            <div class="w-1/2">
                <h4 class="font-bold text-gray-800 text-[10px] uppercase mb-1">Terms & Conditions</h4>
                <ul class="text-[10px] text-gray-600 list-disc pl-4 space-y-0.5">
                    <li>Goods once sold will not be taken back.</li>
                    <li>Interest @ 18% p.a. will be charged if payment is delayed.</li>
                    <li>Subject to local jurisdiction.</li>
                </ul>
            </div>
            <div class="w-1/3 text-center">
                <div class="border-b border-gray-400 h-10 mb-1"></div>
                <p class="font-bold text-gray-800 text-[10px] uppercase">Authorized Signatory</p>
                <p class="text-[9px] text-gray-500 mt-0.5">For <?php echo defined('APP_NAME') ? APP_NAME : 'Praanveda Ayurshakti'; ?></p>
            </div>
        </div>
        
    </div>
</body>
</html>
