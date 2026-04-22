<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function navClass($pageUrl, $currentPage) {
    if ($pageUrl === $currentPage) {
        return "flex items-center px-4 py-3 bg-green-50 text-green-700 rounded-lg font-medium transition-colors";
    }
    return "flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-lg font-medium transition-colors";
}
?>
<!-- Unified Sidebar -->
<aside class="w-64 bg-white shadow-xl flex flex-col h-full border-r border-gray-100 hidden lg:flex shrink-0 z-20">
    <div class="p-6 border-b border-gray-100">
        <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-green-600 to-teal-600 tracking-tight">Sanjeevani</h2>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-2 px-3">
            <li>
                <a href="dashboard.php" class="<?php echo navClass('dashboard.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="leads.php" class="<?php echo navClass('leads.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Leads Queue
                </a>
            </li>
            <li>
                <a href="patients.php" class="<?php echo navClass('patients.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Patient Master
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo navClass('products.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Medicines List
                </a>
            </li>
            <li>
                <a href="dealers.php" class="<?php echo navClass('dealers.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Dealers & Vendors
                </a>
            </li>
            <li>
                <a href="purchases.php" class="<?php echo navClass('purchases.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Purchase History
                </a>
            </li>
            <li>
                <a href="stocks.php" class="<?php echo navClass('stocks.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    Stock Management
                </a>
            </li>
            <li>
                <a href="orders.php" class="<?php echo navClass('orders.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Orders
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- User Profile & Logout -->
    <div class="p-4 border-t border-gray-100 mt-auto">
        <div class="flex items-center space-x-3 mb-4">
            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-green-400 to-teal-500 shadow-md"></div>
            <div>
                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                <p class="text-xs text-gray-500 font-medium"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Staff'); ?></p>
            </div>
        </div>
        <button onclick="logout()" class="w-full text-center py-2 px-4 shadow-sm text-sm font-medium rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
            Sign Out
        </button>
    </div>
</aside>

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
