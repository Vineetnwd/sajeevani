<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$stockists = $pdo->query("SELECT id, name FROM users WHERE role = 'Stockist' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, name, stock_quantity, price FROM products WHERE stock_quantity > 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$productsJson = json_encode($products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create POS Invoice - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
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
                    <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Point of Sale: Stockists</h1>
                    <a href="sale_to_stockist.php" class="text-sm text-indigo-600 hover:underline">&larr; Back to Invoices</a>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 flex flex-col lg:flex-row gap-6">
            <!-- Left Side: POS Form -->
            <div class="w-full lg:w-1/3 flex flex-col gap-6">
                <!-- Stockist Selection -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="font-bold text-gray-800 mb-4 border-b pb-2">1. Select Stockist</h2>
                    <select id="stockist_id" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring focus:ring-indigo-100 outline-none">
                        <option value="">-- Choose Stockist --</option>
                        <?php foreach($stockists as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Product Selection -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="font-bold text-gray-800 mb-4 border-b pb-2">2. Add Item</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                            <select id="product_id" onchange="updateProductInfo()" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring focus:ring-indigo-100 outline-none">
                                <option value="">-- Select Product --</option>
                                <?php foreach($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (Avail: <?php echo $p['stock_quantity']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <p id="product_info" class="text-xs text-indigo-600 mt-1 font-semibold"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number" id="qty" min="1" placeholder="Enter quantity" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring focus:ring-indigo-100 outline-none">
                        </div>
                        <button onclick="addToCart()" class="w-full bg-indigo-600 text-white font-bold py-2.5 rounded-lg shadow hover:bg-indigo-700 transition-colors">+ Add to Cart</button>
                    </div>
                </div>
            </div>

            <!-- Right Side: Current Invoice Cart -->
            <div class="w-full lg:w-2/3 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b bg-gray-50 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800 text-lg">Current Invoice</h2>
                </div>
                
                <div class="flex-1 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Unit Price</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cart_body" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">Cart is empty. Add products to begin.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary -->
                <div class="p-5 border-t bg-gray-50">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600 font-bold">Subtotal:</span>
                        <span id="subtotal" class="font-bold text-gray-800">₹0.00</span>
                    </div>
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-lg text-gray-800 font-bold uppercase">Grand Total:</span>
                        <span id="grand_total" class="text-2xl font-bold text-indigo-700">₹0.00</span>
                    </div>
                    <button onclick="processInvoice()" id="processBtn" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-green-700 transition-colors text-lg flex justify-center items-center">
                        Generate Invoice
                    </button>
                    <p id="error_msg" class="text-red-500 text-sm font-semibold mt-2 text-center hidden"></p>
                </div>
            </div>
        </div>
    </main>

    <script>
        const productsList = <?php echo $productsJson; ?>;
        let cart = [];

        function updateProductInfo() {
            const select = document.getElementById('product_id');
            const info = document.getElementById('product_info');
            if(select.value) {
                const prod = productsList.find(p => p.id == select.value);
                if(prod) {
                    info.innerText = `Price: ₹${prod.price} | Max Available: ${prod.stock_quantity}`;
                }
            } else {
                info.innerText = '';
            }
        }

        function addToCart() {
            const prodSelect = document.getElementById('product_id');
            const qtyInput = document.getElementById('qty');
            
            const prodId = parseInt(prodSelect.value);
            const qty = parseInt(qtyInput.value);

            if(!prodId || isNaN(qty) || qty <= 0) {
                alert("Please select a product and enter a valid quantity.");
                return;
            }

            const prod = productsList.find(p => p.id == prodId);
            if(!prod) return;

            if(qty > prod.stock_quantity) {
                alert(`Cannot add ${qty}. Only ${prod.stock_quantity} available in stock.`);
                return;
            }

            // Check if already in cart
            const existingItem = cart.find(i => i.product_id == prodId);
            if(existingItem) {
                if((existingItem.quantity + qty) > prod.stock_quantity) {
                    alert("Total quantity in cart exceeds available stock.");
                    return;
                }
                existingItem.quantity += qty;
                existingItem.total = existingItem.quantity * existingItem.unit_price;
            } else {
                cart.push({
                    product_id: prod.id,
                    name: prod.name,
                    unit_price: parseFloat(prod.price),
                    quantity: qty,
                    total: qty * parseFloat(prod.price)
                });
            }

            // Reset inputs
            prodSelect.value = '';
            qtyInput.value = '';
            updateProductInfo();
            renderCart();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        function renderCart() {
            const tbody = document.getElementById('cart_body');
            let subtotal = 0;

            if(cart.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">Cart is empty. Add products to begin.</td></tr>';
            } else {
                tbody.innerHTML = '';
                cart.forEach((item, index) => {
                    subtotal += item.total;
                    tbody.innerHTML += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-bold text-gray-900 text-sm">${item.name}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">₹${item.unit_price.toFixed(2)}</td>
                            <td class="px-6 py-4 font-bold text-indigo-700">${item.quantity}</td>
                            <td class="px-6 py-4 font-bold text-gray-900">₹${item.total.toFixed(2)}</td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700 font-bold text-sm">Remove</button>
                            </td>
                        </tr>
                    `;
                });
            }

            document.getElementById('subtotal').innerText = '₹' + subtotal.toFixed(2);
            document.getElementById('grand_total').innerText = '₹' + subtotal.toFixed(2);
        }

        async function processInvoice() {
            const stockistId = document.getElementById('stockist_id').value;
            const errorMsg = document.getElementById('error_msg');
            errorMsg.classList.add('hidden');

            if(!stockistId) {
                errorMsg.innerText = "Please select a Stockist.";
                errorMsg.classList.remove('hidden');
                return;
            }

            if(cart.length === 0) {
                errorMsg.innerText = "Cart is empty.";
                errorMsg.classList.remove('hidden');
                return;
            }

            const btn = document.getElementById('processBtn');
            btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...`;
            btn.disabled = true;

            const payload = {
                action: 'process_invoice',
                stockist_id: stockistId,
                items: cart
            };

            try {
                const response = await fetch('api/invoice_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if(result.status === 'success') {
                    window.location.href = `view_stockist_invoice.php?id=${result.invoice_id}`;
                } else {
                    errorMsg.innerText = result.message || "An error occurred.";
                    errorMsg.classList.remove('hidden');
                    btn.innerHTML = "Generate Invoice";
                    btn.disabled = false;
                }
            } catch(e) {
                errorMsg.innerText = "Connection error. Please try again.";
                errorMsg.classList.remove('hidden');
                btn.innerHTML = "Generate Invoice";
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
