<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$consultationId = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.id as consultation_id, c.status, c.created_at as visit_date, c.doctor_response, c.prescription_notes,
               p.name, p.age, p.gender, p.phone, p.address,
               v.bp, v.sugar, v.weight, v.pulse, v.symptoms_notes 
        FROM consultations c 
        JOIN patients p ON c.patient_id = p.id 
        LEFT JOIN vitals v ON v.patient_id = p.id
        WHERE c.id = ?
    ");
    $stmt->execute([$consultationId]);
    $lead = $stmt->fetch();
    
    if(!$lead) die("Lead not found.");
} catch (PDOException $e) {
    die("Database Error.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Details #<?php echo $consultationId; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; }
        @media print {
            body { background: #ffffff; }
            .no-print { display: none !important; }
            .print-border { border: 1px solid #e5e7eb; box-shadow: none !important; }
        }
    </style>
</head>
<body class="p-8 flex justify-center">

    <div class="w-full max-w-3xl">
        <!-- Actions (Hidden on Print) -->
        <div class="no-print flex justify-between items-center mb-6">
            <a href="leads.php" class="text-sm font-medium text-gray-600 hover:text-gray-900 flex items-center bg-white px-4 py-2 rounded-lg border border-gray-300 shadow-sm">
                &larr; Back to Queue
            </a>
            <button onclick="window.print()" class="text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 px-5 py-2 rounded-lg shadow-md flex items-center transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Download PDF / Print
            </button>
        </div>

        <!-- Printable Document -->
        <div class="bg-white p-10 rounded-2xl shadow-xl print-border">
            <!-- Clinic Header -->
            <div class="flex justify-between items-start border-b border-gray-200 pb-6 mb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-green-700 tracking-tight">PRAANVEDA AYURSHAKTI</h1>
                    <p class="text-gray-500 text-sm mt-1">Ayurvedic Life Platform</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-900">Patient Case File</p>
                    <p class="text-sm text-gray-500">ID: #<?php echo str_pad($lead['consultation_id'], 5, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-sm text-gray-500">Date: <?php echo date('d M Y', strtotime($lead['visit_date'])); ?></p>
                </div>
            </div>

            <!-- Patient Bio -->
            <div class="grid grid-cols-2 gap-6 mb-8">
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Patient Details</h3>
                    <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($lead['name']); ?></p>
                    <p class="text-sm text-gray-600"><?php echo $lead['gender'] . ', ' . $lead['age'] . ' yrs'; ?></p>
                    <p class="text-sm text-gray-600 mt-1">📞 <?php echo htmlspecialchars($lead['phone']); ?></p>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Address</h3>
                    <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($lead['address'])); ?></p>
                </div>
            </div>

            <!-- Vitals Table -->
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Field Vitals Recorded</h3>
            <table class="w-full text-left border-collapse mb-8">
                <thead>
                    <tr class="bg-gray-50 border-y border-gray-200">
                        <th class="py-3 px-4 text-sm font-semibold text-gray-700">Blood Pressure</th>
                        <th class="py-3 px-4 text-sm font-semibold text-gray-700">Sugar Level</th>
                        <th class="py-3 px-4 text-sm font-semibold text-gray-700">Weight</th>
                        <th class="py-3 px-4 text-sm font-semibold text-gray-700">Pulse</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-100">
                        <td class="py-4 px-4 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($lead['bp']) ?: 'N/A'; ?></td>
                        <td class="py-4 px-4 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($lead['sugar']) ?: 'N/A'; ?></td>
                        <td class="py-4 px-4 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($lead['weight']) ?: '--'; ?> kg</td>
                        <td class="py-4 px-4 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($lead['pulse']) ?: '--'; ?> bpm</td>
                    </tr>
                </tbody>
            </table>

            <!-- Symptoms & Advice -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Reported Symptoms</h3>
                    <div class="p-4 bg-orange-50 border border-orange-100 rounded-xl">
                        <p class="text-sm text-gray-800 italic font-serif">"<?php echo nl2br(htmlspecialchars($lead['symptoms_notes'])); ?>"</p>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Doctor's Advice</h3>
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl min-h-[60px]">
                        <p class="text-sm text-blue-900 font-medium">
                            <?php echo $lead['doctor_response'] ? nl2br(htmlspecialchars($lead['doctor_response'])) : '<span class="text-gray-300 italic">No advice recorded.</span>'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Prescribed Medicines -->
            <div class="mb-8">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Rx - Prescribed Medicines</h3>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="py-2 px-4 text-[10px] font-bold text-gray-400 uppercase">Medicine Name</th>
                                <th class="py-2 px-4 text-[10px] font-bold text-gray-400 uppercase">Dosage Schedule</th>
                                <th class="py-2 px-4 text-[10px] font-bold text-gray-400 uppercase text-right">Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            $meds = json_decode($lead['prescription_notes'] ?? '[]', true);
                            if (is_array($meds) && count($meds) > 0):
                                foreach($meds as $med): ?>
                                <tr>
                                    <td class="py-3 px-4 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($med['name']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-600 font-medium"><?php echo htmlspecialchars($med['dosage']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-900 font-bold text-right"><?php echo $med['qty']; ?></td>
                                </tr>
                                <?php endforeach;
                            else: ?>
                                <tr><td colspan="3" class="py-6 text-center text-gray-300 text-sm italic">Pending prescription from doctor.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Notes -->
            <div class="mt-16 pt-6 border-t border-gray-200 flex justify-between items-end">
                <div>
                    <p class="text-xs text-gray-400">Generated by Praanveda Ayurshakti Platform</p>
                </div>
                <div class="text-center w-48">
                    <div class="border-b border-gray-300 pb-8 mb-2"></div>
                    <p class="text-sm font-medium text-gray-600">Doctor Signature</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
