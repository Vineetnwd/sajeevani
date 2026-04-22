<?php
require_once 'config.php';

// Check if logged in (Admin or Doctor)
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
    <title>Lead Queue - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 z-10 px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Pending Patient Leads</h1>
            <div class="flex items-center space-x-4">
                <button onclick="generateDummyLead()" class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Add Dummy Lead
                </button>
                <button onclick="fetchLeads()" class="text-sm font-medium text-green-600 hover:text-green-700 flex items-center px-3 py-1.5">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh Queue
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Patient Details</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contact</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Vitals</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leadsContainer" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">Loading pending leads...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Prescription Modal Modal structure for later -->

    <script>
        async function fetchLeads() {
            const container = document.getElementById('leadsContainer');
            try {
                const response = await fetch('api/leads.php?action=list_pending');
                const result = await response.json();

                if (result.status === 'success') {
                    if(result.data.length === 0) {
                        container.innerHTML = `
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.514"></path></svg>
                                        <p class="text-gray-500 font-medium">No pending leads right now.</p>
                                    </div>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    container.innerHTML = result.data.map(lead => `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">${lead.name}</div>
                                <div class="text-xs text-gray-500 mt-0.5">${lead.gender}, ${lead.age} yrs</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-medium">${lead.phone}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-xs text-gray-900"><span class="text-gray-500">BP:</span> ${lead.bp || 'N/A'}</div>
                                <div class="text-xs text-gray-900"><span class="text-gray-500">Sugar:</span> ${lead.sugar || 'N/A'}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-[11px] leading-4 font-bold rounded-full bg-yellow-100 text-yellow-800">
                                    PENDING
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <button onclick="window.location.href='patient_details.php?id=${lead.consultation_id}'" class="p-2 text-gray-400 hover:text-blue-600 bg-gray-50 hover:bg-blue-50 rounded-lg transition-colors" title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    <button onclick="window.location.href='lead_edit.php?id=${lead.consultation_id}'" class="p-2 text-gray-400 hover:text-amber-600 bg-gray-50 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button onclick="openPrescription(${lead.consultation_id})" class="p-2 text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-sm" title="Prescribe Action">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </button>
                                    <button onclick="deleteLead(${lead.consultation_id})" class="p-2 text-gray-400 hover:text-red-600 bg-gray-50 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    container.innerHTML = `<div class="col-span-full text-red-500">${result.message}</div>`;
                }
            } catch (error) {
                container.innerHTML = `<div class="col-span-full text-red-500">Failed to connect to API</div>`;
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', fetchLeads);

        function openPrescription(consultationId) {
            window.location.href = 'prescription.php?id=' + consultationId;
        }

        async function deleteLead(consultationId) {
            if(!confirm("Are you sure you want to delete this lead? This action cannot be reversed.")) return;
            try {
                const formData = new FormData();
                formData.append('id', consultationId);
                const response = await fetch('api/leads.php?action=delete_lead', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if(result.status === 'success') {
                    fetchLeads();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(e) {
                alert('Connection to API failed.');
            }
        }

        async function generateDummyLead() {
            const formData = new FormData();
            formData.append('executive_id', '1');
            formData.append('name', 'Priya Sharma');
            formData.append('phone', '9876543210');
            formData.append('age', '34');
            formData.append('gender', 'Female');
            formData.append('address', '45 Green Park, New Delhi');
            formData.append('bp', '125/80');
            formData.append('sugar', '115 Random');
            formData.append('weight', '62');
            formData.append('pulse', '78');
            formData.append('symptoms', 'Patient is complaining of frequent headaches, mild nausea, and fatigue over the last 5 days. Needs Ayurvedic consultation.');

            try {
                const response = await fetch('api/leads.php?action=submit_lead', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if(result.status === 'success') {
                    fetchLeads(); // Refresh UI instantly
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(e) {
                alert('Connection to API failed.');
            }
        }
    </script>
</body>
</html>
