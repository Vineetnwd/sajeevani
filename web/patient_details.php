<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$consultationId = $_GET['id'] ?? 0;

// If we came from leads, we have a consultation_id. 
// We need the patient_id to show full history.
try {
    $stmt = $pdo->prepare("SELECT patient_id FROM consultations WHERE id = ?");
    $stmt->execute([$consultationId]);
    $res = $stmt->fetch();
    $patientId = $res['patient_id'] ?? 0;

    if (!$patientId) {
        // Maybe ID passed is patient_id directly? (Compatibility)
        $patientId = $consultationId;
    }

    // Fetch Patient Info
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();

    if (!$patient) die("Patient not found.");

    // Fetch Last Vitals
    $stmt = $pdo->prepare("SELECT * FROM vitals WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$patientId]);
    $vitals = $stmt->fetch();

    // Fetch Consultation History
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as doctor_name 
        FROM consultations c 
        LEFT JOIN users u ON c.doctor_id = u.id 
        WHERE c.patient_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$patientId]);
    $consultations = $stmt->fetchAll();

    // Fetch Order History
    $stmt = $pdo->prepare("
        SELECT o.* 
        FROM orders o 
        WHERE o.patient_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$patientId]);
    $orders = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile - <?php echo htmlspecialchars($patient['name']); ?></title>
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
            <div class="min-w-0 flex items-center">
                <a href="leads.php" class="mr-4 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h1 class="text-lg sm:text-xl font-bold text-gray-800 truncate">Patient Profile: <?php echo htmlspecialchars($patient['name']); ?></h1>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        </div>
            <div class="flex space-x-2">
                <a href="lead_edit.php?id=<?php echo $consultationId; ?>" class="px-4 py-2 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-sm font-medium hover:bg-amber-100">Edit Info</a>
                <a href="lead_view.php?id=<?php echo $consultationId; ?>" target="_blank" class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Case File
                </a>
            </div>
    </div>
</header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:p-6">
                
                <!-- Left Column: Patient Info -->
                <div class="space-y-8">
                    <!-- Bio Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center space-x-4 mb-6">
                            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-2xl font-bold">
                                <?php echo substr($patient['name'], 0, 1); ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></h2>
                                <p class="text-sm text-gray-500">Reg. Date: <?php echo date('d M Y', strtotime($patient['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between border-b border-gray-50 pb-2">
                                <span class="text-sm text-gray-500">Gender / Age</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo $patient['gender']; ?> / <?php echo $patient['age']; ?> Yrs</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-50 pb-2">
                                <span class="text-sm text-gray-500">Phone</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($patient['phone']); ?></span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 block mb-1">Address</span>
                                <p class="text-sm text-gray-900 leading-relaxed"><?php echo nl2br(htmlspecialchars($patient['address'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Vitals Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Latest Vitals</h3>
                        <?php if ($vitals): ?>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <p class="text-xs sm:text-[10px] uppercase text-gray-400 font-bold">BP</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($vitals['bp']); ?></p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <p class="text-xs sm:text-[10px] uppercase text-gray-400 font-bold">Sugar</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($vitals['sugar']); ?></p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <p class="text-xs sm:text-[10px] uppercase text-gray-400 font-bold">Weight</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo $vitals['weight']; ?> <span class="text-xs font-normal">kg</span></p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <p class="text-xs sm:text-[10px] uppercase text-gray-400 font-bold">Pulse</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo $vitals['pulse']; ?> <span class="text-xs font-normal">bpm</span></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-400 italic">No vitals recorded.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: Histories -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Consultation History -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-extrabold text-gray-800 mb-6 flex items-center">
                            <span class="w-2 h-6 bg-green-500 rounded-full mr-3"></span>
                            Consultation History
                        </h3>
                        
                        <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-100">
                            <?php foreach($consultations as $c): ?>
                                <div class="relative pl-8">
                                    <div class="absolute left-0 top-1.5 w-6 h-6 rounded-full bg-white border-4 border-green-500"></div>
                                    <div class="flex justify-between items-start mb-1">
                                        <h4 class="font-bold text-gray-900">Visit #<?php echo $c['id']; ?></h4>
                                        <span class="text-xs font-medium text-gray-400"><?php echo date('d M Y, h:i A', strtotime($c['created_at'])); ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-3">Doctor: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($c['doctor_name'] ?? 'Not Assigned'); ?></span></p>
                                    
                                    <?php if(!empty($c['doctor_response'])): ?>
                                        <div class="bg-blue-50 p-4 rounded-xl text-sm text-blue-800 border-l-4 border-blue-400 mb-4 shadow-sm">
                                            <div class="font-bold text-xs sm:text-[10px] uppercase mb-1 tracking-wider opacity-60">Doctor's Advice / Response:</div>
                                            <div class="leading-relaxed"><?php echo nl2br(htmlspecialchars($c['doctor_response'])); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-3 mb-4 rounded-lg border border-dashed border-gray-200 text-xs text-gray-400 italic">
                                            No clinical advice or suggestions recorded for this visit.
                                        </div>
                                    <?php endif; ?>

                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                        <div class="font-bold text-xs sm:text-[10px] uppercase text-gray-400 mb-2 tracking-widest">Prescribed Medicines</div>
                                        <div class="space-y-2">
                                            <?php 
                                            $meds = json_decode($c['prescription_notes'], true);
                                            if (is_array($meds)):
                                                foreach($meds as $med): ?>
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($med['name']); ?></span>
                                                        <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-100"><?php echo htmlspecialchars($med['dosage']); ?> • Qty: <?php echo $med['qty']; ?></span>
                                                    </div>
                                                <?php endforeach;
                                            else: ?>
                                                <p class="text-sm text-gray-400 italic">No medicines prescribed.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <span class="px-2 py-0.5 rounded text-xs sm:text-[10px] font-bold uppercase <?php 
                                            echo $c['status'] === 'Prescribed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                            <?php echo $c['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Order History -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-extrabold text-gray-800 mb-6 flex items-center">
                            <span class="w-2 h-6 bg-blue-500 rounded-full mr-3"></span>
                            Order History
                        </h3>
                        
                        <div class="overflow-x-auto">
<table class="w-full text-left">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-4 text-xs sm:text-[10px] font-bold text-gray-400 uppercase">Order ID</th>
                                    <th class="py-2 px-4 text-xs sm:text-[10px] font-bold text-gray-400 uppercase">Date</th>
                                    <th class="py-2 px-4 text-xs sm:text-[10px] font-bold text-gray-400 uppercase">Status</th>
                                    <th class="py-2 px-4 text-xs sm:text-[10px] font-bold text-gray-400 uppercase text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach($orders as $o): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm font-bold text-gray-900">#ORD-<?php echo $o['id']; ?></td>
                                        <td class="py-3 px-4 text-xs text-gray-500"><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 rounded text-xs sm:text-[10px] font-bold uppercase bg-blue-50 text-blue-600">
                                                <?php echo $o['status']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-sm font-extrabold text-gray-900 text-right">₹<?php echo number_format($o['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="4" class="py-8 text-center text-gray-300 text-sm italic">No orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
