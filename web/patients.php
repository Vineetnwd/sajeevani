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
    <title>Patient Master List - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Patient Master List</h1>
            <div class="text-sm text-gray-500">Total Registered: <span id="patientCount">0</span></div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Patient Name</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Age/Gender</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="patientTableBody" class="divide-y divide-gray-100">
                        <!-- Loaded via JS for performance -->
                        <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">Loading patients...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        async function fetchPatients() {
            try {
                // We'll reuse the leads API but with a different action if needed, 
                // or just fetch all patients.
                const response = await fetch('api/leads.php?action=list');
                const result = await response.json();
                
                if (result.status === 'success') {
                    const patients = result.data;
                    document.getElementById('patientCount').innerText = patients.length;
                    
                    const container = document.getElementById('patientTableBody');
                    if (patients.length === 0) {
                        container.innerHTML = '<tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">No patients registered yet.</td></tr>';
                        return;
                    }

                    container.innerHTML = patients.map(p => `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">${p.name}</div>
                                <div class="text-[10px] text-gray-400 uppercase">Reg: ${new Date(p.created_at).toLocaleDateString()}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">${p.phone}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">${p.age} Yrs / ${p.gender}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="patient_details.php?id=${p.consultation_id}" class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-700 rounded-lg text-xs font-bold hover:bg-green-100 transition-colors">
                                    View History
                                </a>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Fetch error:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', fetchPatients);
    </script>
</body>
</html>
