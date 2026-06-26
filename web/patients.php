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
            <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Patient Master List</h1>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        <div class="text-sm text-gray-500">Total Registered: <span id="patientCount">0</span></div>
    </div>
</header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-4 flex justify-between items-center">
                <input type="text" id="searchInput" placeholder="Search Patient Name or Contact..." class="w-full sm:w-80 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
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
        </div>
    </main>

    <script>
        let allPatients = [];

        function renderPatients(patients) {
            const container = document.getElementById('patientTableBody');
            document.getElementById('patientCount').innerText = patients.length;
            
            if (patients.length === 0) {
                container.innerHTML = '<tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">No patients found.</td></tr>';
                return;
            }

            container.innerHTML = patients.map(p => `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900">${p.name}</div>
                        <div class="text-xs sm:text-[10px] text-gray-400 uppercase">Reg: ${new Date(p.created_at).toLocaleDateString()}</div>
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

        async function fetchPatients() {
            try {
                const response = await fetch('api/leads.php?action=list');
                const result = await response.json();
                
                if (result.status === 'success') {
                    allPatients = result.data;
                    renderPatients(allPatients);
                }
            } catch (error) {
                console.error('Fetch error:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchPatients();
            
            document.getElementById('searchInput').addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const filtered = allPatients.filter(p => 
                    (p.name && p.name.toLowerCase().includes(searchTerm)) || 
                    (p.phone && p.phone.toLowerCase().includes(searchTerm))
                );
                renderPatients(filtered);
            });
        });
    </script>
</body>
</html>
