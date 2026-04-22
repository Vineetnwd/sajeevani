<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$consultationId = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.age, p.gender, p.phone, p.id as patient_id, v.bp, v.sugar, v.weight, v.symptoms_notes 
        FROM consultations c 
        JOIN patients p ON c.patient_id = p.id 
        LEFT JOIN vitals v ON v.patient_id = p.id
        WHERE c.id = ? AND c.status = 'Pending'
    ");
    $stmt->execute([$consultationId]);
    $lead = $stmt->fetch();
    
    if(!$lead) {
        die("Invalid Consultation ID or Lead is no longer Pending.");
    }
} catch (PDOException $e) {
    die("Database Error.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Builder - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <div class="flex-1 flex flex-col h-full items-center p-6">
        
        <div class="w-full max-w-5xl flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Rx Prescription Builder</h1>
                <p class="text-sm text-gray-500">Consultation ID: #<?php echo $lead['id']; ?></p>
            </div>
            <a href="leads.php" class="text-sm font-medium text-gray-500 hover:text-gray-900 border border-gray-300 px-4 py-2 rounded-lg bg-white shadow-sm">
                &larr; Back to Queue
            </a>
        </div>

        <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="col-span-1 space-y-6">
                <!-- Profile -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="h-16 w-16 bg-gradient-to-r from-green-400 to-teal-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mb-4 shadow-md">
                        <?php echo substr($lead['name'], 0, 1); ?>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($lead['name']); ?></h2>
                    <p class="text-sm text-gray-500 mb-4"><?php echo $lead['gender'] . ', ' . $lead['age']; ?> yrs • <?php echo htmlspecialchars($lead['phone']); ?></p>
                    
                    <div class="pt-4 border-t border-gray-100 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-medium">Blood Pressure</span>
                            <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($lead['bp']); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-medium">Sugar Level</span>
                            <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($lead['sugar']); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-medium">Weight</span>
                            <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($lead['weight']); ?> kg</span>
                        </div>
                    </div>
                </div>

                <!-- Symptoms -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2">Reported Symptoms</h3>
                    <p class="text-sm text-gray-700 bg-orange-50 p-3 rounded-lg border border-orange-100 italic">
                        "<?php echo htmlspecialchars($lead['symptoms_notes']); ?>"
                    </p>
                </div>
            </div>

            <div class="col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col h-[75vh]">
                    
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            Add Medicines
                        </h2>
                        
                        <div class="flex space-x-3">
                            <select id="medicineSelect" class="flex-1 border border-gray-300 rounded-lg p-3 text-sm focus:ring-green-500 focus:border-green-500">
                                <option value="">Loading Medicines...</option>
                            </select>
                            <input type="text" id="dosageInput" placeholder="Dosage (e.g. 1-0-1)" class="w-32 border border-gray-300 rounded-lg p-3 text-sm focus:ring-green-500 focus:border-green-500">
                            <input type="number" id="qtyInput" placeholder="Qty" value="1" class="w-20 border border-gray-300 rounded-lg p-3 text-sm focus:ring-green-500 focus:border-green-500">
                            <button onclick="addMedicineRow()" class="bg-gray-900 text-white px-5 py-3 rounded-lg font-medium hover:bg-gray-800 transition">Add</button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Medicine</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Dosage</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody id="rxTableBody" class="divide-y divide-gray-100">
                                <tr id="emptyRxRow"><td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm">No medicines added to Rx.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-6 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Doctor's Clinical Notes / Response</label>
                            <textarea id="doctorResponse" rows="3" placeholder="Enter findings, diagnosis or advice..." class="w-full border border-gray-300 rounded-xl p-3 text-sm focus:ring-green-500 focus:border-green-500 outline-none transition-all resize-none"></textarea>
                        </div>
                        <div class="flex justify-between items-center mb-6">
                            <span class="text-gray-600 font-medium">Estimated Total Bill:</span>
                            <span class="text-2xl font-bold text-green-600" id="totalBill">₹0.00</span>
                        </div>
                        <button onclick="submitPrescription()" class="w-full bg-gradient-to-r from-green-600 to-teal-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transition-shadow flex justify-center items-center">
                            Save Prescription & Generate Order →
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        let availableMedicines = [];
        let rxList = [];

        async function loadMedicines() {
            try {
                const response = await fetch('api/inventory.php?action=get_products');
                const result = await response.json();
                if(result.status === 'success') {
                    availableMedicines = result.data;
                    const select = document.getElementById('medicineSelect');
                    select.innerHTML = '<option value="">Select a Medicine...</option>';
                    availableMedicines.forEach(m => {
                        select.innerHTML += `<option value="${m.id}" data-price="${m.price}">${m.name} (₹${m.price})</option>`;
                    });
                }
            } catch(e) { console.error('Failed to load medicines', e); }
        }

        function addMedicineRow() {
            const select = document.getElementById('medicineSelect');
            const medId = select.value;
            const dosage = document.getElementById('dosageInput').value;
            const qty = document.getElementById('qtyInput').value;

            if(!medId || !dosage || qty < 1) {
                alert('Please select medicine, enter dosage, and valid quantity.');
                return;
            }

            const medOption = select.options[select.selectedIndex];
            const medName = medOption.text.split('(')[0].trim();
            const price = parseFloat(medOption.getAttribute('data-price'));
            const totalRowPrice = price * parseInt(qty);

            rxList.push({ id: medId, name: medName, dosage: dosage, qty: qty, price: totalRowPrice });
            
            document.getElementById('medicineSelect').value = '';
            document.getElementById('dosageInput').value = '';
            document.getElementById('qtyInput').value = '1';
            
            renderRxTable();
        }

        function removeMedicine(index) {
            rxList.splice(index, 1);
            renderRxTable();
        }

        function renderRxTable() {
            const tbody = document.getElementById('rxTableBody');
            if(rxList.length === 0) {
                tbody.innerHTML = '<tr id="emptyRxRow"><td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm">No medicines added to Rx.</td></tr>';
                document.getElementById('totalBill').innerText = '₹0.00';
                return;
            }
            let html = '';
            let total = 0;
            rxList.forEach((item, index) => {
                total += item.price;
                html += `
                <tr class="bg-white">
                    <td class="px-6 py-3 text-sm font-bold text-gray-900">${item.name}</td>
                    <td class="px-6 py-3 text-sm text-gray-600">${item.dosage}</td>
                    <td class="px-6 py-3 text-sm font-medium text-gray-800">${item.qty}</td>
                    <td class="px-6 py-3 text-sm text-gray-600">₹${item.price.toFixed(2)}</td>
                    <td class="px-6 py-3 text-right">
                        <button onclick="removeMedicine(${index})" class="text-red-500 hover:text-red-700 p-1 bg-red-50 rounded">Remove</button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
            document.getElementById('totalBill').innerText = '₹' + total.toFixed(2);
        }

        async function submitPrescription() {
            if(rxList.length === 0) {
                alert('Add at least one medicine to prescribe!');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('consultation_id', '<?php echo $consultationId; ?>');
                formData.append('patient_id', '<?php echo $lead['patient_id']; ?>');
                formData.append('total_amount', document.getElementById('totalBill').innerText.replace('₹',''));
                formData.append('doctor_response', document.getElementById('doctorResponse').value);
                formData.append('rx', JSON.stringify(rxList));

                const response = await fetch('api/prescribe.php?action=create_order', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if(result.status === 'success') {
                    alert('Prescription Saved! Order generated successfully.');
                    window.location.href = 'leads.php'; // Return to queue
                } else {
                    alert('API Error: ' + result.message);
                }
            } catch(e) {
                alert('Network Error connecting to prescribe API.');
            }
        }

        document.addEventListener('DOMContentLoaded', loadMedicines);
    </script>
</body>
</html>
