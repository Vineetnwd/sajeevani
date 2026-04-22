<?php
require_once 'config.php';

// Check if logged in
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
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm border-b border-gray-200 z-10 px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <div class="flex items-center space-x-2 text-sm text-gray-500">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <span>System Online</span>
            </div>
        </header>

        <!-- Dynamic Content Area -->
        <div class="flex-1 overflow-y-auto p-8 relative">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                
                <!-- Stat Cards -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full transition-transform group-hover:scale-150 duration-500"></div>
                    <div class="relative z-10">
                        <div class="text-sm font-medium text-gray-500 mb-1">Total Leads</div>
                        <div class="text-3xl font-bold text-gray-900">0</div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full transition-transform group-hover:scale-150 duration-500"></div>
                    <div class="relative z-10">
                        <div class="text-sm font-medium text-gray-500 mb-1">Pending Consultations</div>
                        <div class="text-3xl font-bold text-gray-900">0</div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-50 rounded-full transition-transform group-hover:scale-150 duration-500"></div>
                    <div class="relative z-10">
                        <div class="text-sm font-medium text-gray-500 mb-1">Orders Displayed</div>
                        <div class="text-3xl font-bold text-gray-900">0</div>
                    </div>
                </div>

            </div>

            <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Activity</h3>
                <div class="text-center text-gray-500 py-10">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-2 text-sm">No activity recorded yet.</p>
                </div>
            </div>

        </div>
    </main>

    <script>
        async function logout() {
            try {
                await fetch('api/auth.php?action=logout');
                window.location.href = 'index.php';
            } catch(e) {
                console.error('Logout failed');
            }
        }
    </script>
</body>
</html>
