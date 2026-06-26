<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$currentPage = 'sale_returns.php'; // Keep sidebar active on Sale Returns

// Fetch Stockists
$stmtStockists = $pdo->query("SELECT id, name FROM users WHERE role = 'Stockist' ORDER BY name ASC");
$stockists = $stmtStockists->fetchAll(PDO::FETCH_ASSOC);

// Fetch Products
$stmtProducts = $pdo->query("SELECT id, name FROM products ORDER BY name ASC");
$products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Sale Return - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        /* Tom Select UI Improvements */
        .ts-wrapper { padding: 0 !important; border: none !important; }
        .ts-control {
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important; /* rounded-lg */
            padding: 0.625rem !important; /* p-2.5 */
            font-size: 0.875rem !important; /* text-sm */
            box-shadow: none !important;
            background-color: white !important;
            min-height: 42px !important;
            display: flex;
            align-items: center;
        }
        .ts-control.focus {
            border-color: #f97316 !important; /* Orange to indicate return */
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2) !important;
            outline: none !important;
        }
        .ts-dropdown {
            border-radius: 0.5rem !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            font-size: 0.875rem !important;
            overflow: hidden;
            margin-top: 4px;
        }
        .ts-dropdown .option { padding: 0.5rem 1rem !important; transition: background-color 0.1s ease; }
        .ts-dropdown .active { background-color: #f3f4f6 !important; color: #111827 !important; }
        .ts-dropdown .option:hover { background-color: #e5e7eb !important; }
    </style>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <a href="sale_returns.php" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Process Sale Return</h1>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('returnForm').dispatchEvent(new Event('submit'))" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold shadow-md transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Save Return
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 pb-32">
            <form id="returnForm" class="max-w-5xl mx-auto space-y-6">
                <!-- Header Info -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider border-b border-gray-100 pb-2 mb-4">Return Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Returned By (Stockist)</label>
                            <select name="stockist_id" id="stockistSelect" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                                <option value="">Select a Stockist...</option>
                                <?php foreach($stockists as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s['id']); ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Reason for Return</label>
                            <input type="text" name="reason" placeholder="e.g. Near expiry, Damage, Unsold..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2 mb-4">
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Returned Items</h2>
                        <button type="button" onclick="addRow()" class="text-orange-500 hover:text-orange-600 font-bold text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Add Item
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left" id="itemsTable">
                            <thead>
                                <tr class="text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    <th class="pb-3 font-semibold">Product</th>
                                    <th class="pb-3 font-semibold w-32">Return Qty</th>
                                    <th class="pb-3 font-semibold w-40">Credit Rate (₹)</th>
                                    <th class="pb-3 font-semibold w-32 text-right">Total (₹)</th>
                                    <th class="pb-3 font-semibold w-12 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="tableBody">
                                <!-- First Row -->
                                <tr class="item-row">
                                    <td class="py-3 pr-4">
                                        <select name="products[]" required class="product-select w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                                            <option value="">Select Product...</option>
                                            <?php foreach($products as $p): ?>
                                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <input type="number" name="quantities[]" min="1" required class="qty-input w-full border border-gray-300 rounded-lg p-2 text-sm text-right focus:ring-2 focus:ring-orange-500 outline-none" oninput="calculateRow(this)">
                                    </td>
                                    <td class="py-3 pr-4">
                                        <input type="number" name="rates[]" min="0.00" step="0.01" value="0.00" class="rate-input w-full border border-gray-300 rounded-lg p-2 text-sm text-right focus:ring-2 focus:ring-orange-500 outline-none" oninput="calculateRow(this)">
                                    </td>
                                    <td class="py-3 pr-4 text-right">
                                        <span class="row-total font-bold text-gray-900">0.00</span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-red-500 transition-colors" onclick="removeRow(this)">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Grand Total -->
                <div class="flex justify-end">
                    <div class="bg-orange-50 border border-orange-100 p-6 rounded-2xl w-full sm:w-80 text-right shadow-sm">
                        <p class="text-orange-600 font-bold uppercase text-xs tracking-wider mb-1">Credit Value</p>
                        <p class="text-4xl font-black text-orange-900">₹<span id="grandTotal">0.00</span></p>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl transform transition-all">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-2xl font-black text-gray-900 mb-2">Saved!</h3>
            <p class="text-gray-600 mb-6 font-medium">Sale Return recorded successfully. Stock has been credited back to inventory.</p>
            <a href="sale_returns.php" class="block w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-xl transition-colors">View Returns</a>
        </div>
    </div>

    <!-- Product Options Template -->
    <template id="productOptions">
        <option value="">Select Product...</option>
        <?php foreach($products as $p): ?>
            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
        <?php endforeach; ?>
    </template>

    <script>
        const tsConfig = {
            create: false,
            sortField: { field: "text", direction: "asc" }
        };

        document.addEventListener('DOMContentLoaded', function() {
            new TomSelect('#stockistSelect', tsConfig);
            document.querySelectorAll('.product-select').forEach((el) => {
                new TomSelect(el, tsConfig);
            });
        });

        function addRow() {
            const tbody = document.getElementById('tableBody');
            const template = document.getElementById('productOptions').innerHTML;
            
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td class="py-3 pr-4">
                    <select name="products[]" required class="product-select w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                        ${template}
                    </select>
                </td>
                <td class="py-3 pr-4">
                    <input type="number" name="quantities[]" min="1" required class="qty-input w-full border border-gray-300 rounded-lg p-2 text-sm text-right focus:ring-2 focus:ring-orange-500 outline-none" oninput="calculateRow(this)">
                </td>
                <td class="py-3 pr-4">
                    <input type="number" name="rates[]" min="0.00" step="0.01" value="0.00" class="rate-input w-full border border-gray-300 rounded-lg p-2 text-sm text-right focus:ring-2 focus:ring-orange-500 outline-none" oninput="calculateRow(this)">
                </td>
                <td class="py-3 pr-4 text-right">
                    <span class="row-total font-bold text-gray-900">0.00</span>
                </td>
                <td class="py-3 text-center">
                    <button type="button" class="text-gray-400 hover:text-red-500 transition-colors" onclick="removeRow(this)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            
            const newSelect = tr.querySelector('.product-select');
            new TomSelect(newSelect, tsConfig);
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length > 1) {
                btn.closest('tr').remove();
                calculateGrandTotal();
            } else {
                alert("You must have at least one item.");
            }
        }

        function calculateRow(input) {
            const tr = input.closest('tr');
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            const rate = parseFloat(tr.querySelector('.rate-input').value) || 0;
            const total = qty * rate;
            tr.querySelector('.row-total').innerText = total.toFixed(2);
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let total = 0;
            document.querySelectorAll('.row-total').forEach(span => {
                total += parseFloat(span.innerText) || 0;
            });
            document.getElementById('grandTotal').innerText = total.toFixed(2);
        }

        document.getElementById('returnForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.querySelector('button[onclick*="dispatchEvent"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Saving...';
            btn.disabled = true;

            try {
                const formData = new FormData(this);
                const response = await fetch('api/sale_return_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    document.getElementById('successModal').classList.remove('hidden');
                } else {
                    alert(data.message || 'Error saving return');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Network error occurred');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
