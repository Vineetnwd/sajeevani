<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$consultationId = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.id as consultation_id, 
               p.id as patient_id, p.name, p.age, p.gender, p.phone, p.address,
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
    <title>Edit Lead #<?php echo $consultationId; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 p-8 flex justify-center h-screen overflow-y-auto">

    <div class="w-full max-w-3xl">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Patient Lead</h1>
                <p class="text-sm text-gray-500">Updating information for Consultation #<?php echo $lead['consultation_id']; ?></p>
            </div>
            <a href="leads.php" class="text-sm font-medium text-gray-600 hover:text-gray-900 border border-gray-300 px-4 py-2 rounded-lg bg-white shadow-sm">
                Cancel & Back
            </a>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
            <form id="editForm" class="space-y-6">
                <!-- Hidden inputs to link correct db row -->
                <input type="hidden" name="patient_id" value="<?php echo $lead['patient_id']; ?>">
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Patient Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($lead['name']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2.5 shadow-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($lead['phone']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2.5 shadow-sm focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Complete Address</label>
                    <textarea name="address" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md p-2.5 shadow-sm focus:ring-amber-500 focus:border-amber-500"><?php echo htmlspecialchars($lead['address']); ?></textarea>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Vitals Details</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Blood Pressure</label>
                            <input type="text" name="bp" value="<?php echo htmlspecialchars($lead['bp']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md p-2.5 shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sugar Level</label>
                            <input type="text" name="sugar" value="<?php echo htmlspecialchars($lead['sugar']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md p-2.5 shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700">Reported Symptoms</label>
                        <textarea name="symptoms_notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md p-2.5 shadow-sm focus:ring-amber-500 focus:border-amber-500"><?php echo htmlspecialchars($lead['symptoms_notes']); ?></textarea>
                    </div>
                </div>

                <div class="pt-6 flex justify-end">
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-xl shadow-md transition-colors flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = "Saving...";
            
            try {
                const formData = new FormData(this);
                const response = await fetch('api/leads.php?action=update_lead', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if(result.status === 'success') {
                    alert('Lead updated successfully!');
                    window.location.href = 'leads.php';
                } else {
                    alert('Error: ' + result.message);
                    btn.innerHTML = "Save Changes";
                }
            } catch(error) {
                alert('API request failed');
                btn.innerHTML = "Save Changes";
            }
        });
    </script>
</body>
</html>
