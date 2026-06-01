<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function navClass($pageUrl, $currentPage) {
    if ($pageUrl === $currentPage) {
        return "flex items-center px-4 py-2.5 bg-green-50 text-green-700 rounded-lg font-semibold transition-colors text-sm shadow-sm";
    }
    return "flex items-center px-4 py-2.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors text-sm";
}

// Helper to determine if a group should be open
function isGroupActive($pages, $currentPage) {
    return in_array($currentPage, $pages) ? '' : 'hidden';
}
function isGroupIconActive($pages, $currentPage) {
    return in_array($currentPage, $pages) ? 'rotate-180' : '';
}
?>
<!-- Unified Sidebar -->
<aside class="w-64 bg-white shadow-xl flex flex-col h-full border-r border-gray-100 hidden lg:flex shrink-0 z-20">
    <div class="p-6 border-b border-gray-100 flex items-center justify-center">
        <h2 class="text-2xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-green-600 to-teal-600 tracking-tight">Praanveda</h2>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-3">
            <?php $role = $_SESSION['user_role'] ?? 'Admin'; ?>
            
            <?php if ($role !== 'Stockist'): ?>
            <!-- Dashboard -->
            <li>
                <a href="dashboard.php" class="<?php echo navClass('dashboard.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3 <?php echo $currentPage === 'dashboard.php' ? 'text-green-600' : 'text-gray-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
            </li>

            <!-- Patients & Consultations -->
            <li class="pt-2">
                <button onclick="toggleDropdown('menu-patients', 'icon-patients')" class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Patients & Leads
                    </div>
                    <svg id="icon-patients" class="w-4 h-4 text-gray-400 transition-transform duration-200 <?php echo isGroupIconActive(['patients.php', 'leads.php', 'doctor_orders.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <ul id="menu-patients" class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['patients.php', 'leads.php', 'doctor_orders.php'], $currentPage); ?>">
                    <li><a href="patients.php" class="<?php echo navClass('patients.php', $currentPage); ?>">Patient Master</a></li>
                    <li><a href="leads.php" class="<?php echo navClass('leads.php', $currentPage); ?>">Leads Queue</a></li>
                    <li><a href="doctor_orders.php" class="<?php echo navClass('doctor_orders.php', $currentPage); ?> flex justify-between w-full">Doctor Orders <span class="ml-auto text-[10px] font-bold bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full">Plan 2</span></a></li>
                </ul>
            </li>

            <!-- Inventory & Sales -->
            <li class="pt-2">
                <button onclick="toggleDropdown('menu-inventory', 'icon-inventory')" class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        Inventory & Sales
                    </div>
                    <svg id="icon-inventory" class="w-4 h-4 text-gray-400 transition-transform duration-200 <?php echo isGroupIconActive(['products.php', 'stocks.php', 'purchases.php', 'orders.php', 'dealers.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <ul id="menu-inventory" class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['products.php', 'stocks.php', 'purchases.php', 'orders.php', 'dealers.php'], $currentPage); ?>">
                    <li><a href="products.php" class="<?php echo navClass('products.php', $currentPage); ?>">Medicines List</a></li>
                    <li><a href="stocks.php" class="<?php echo navClass('stocks.php', $currentPage); ?>">Stock Management</a></li>
                    <li><a href="purchases.php" class="<?php echo navClass('purchases.php', $currentPage); ?>">Purchase History</a></li>
                    <li><a href="orders.php" class="<?php echo navClass('orders.php', $currentPage); ?>">All Orders</a></li>
                    <li><a href="dealers.php" class="<?php echo navClass('dealers.php', $currentPage); ?>">Dealers & Vendors</a></li>
                </ul>
            </li>

            <!-- User Management -->
            <li class="pt-2">
                <button onclick="toggleDropdown('menu-users', 'icon-users')" class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        User Management
                    </div>
                    <svg id="icon-users" class="w-4 h-4 text-gray-400 transition-transform duration-200 <?php echo isGroupIconActive(['doctors.php', 'mrs.php', 'stockists.php', 'stockist_stocks.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <ul id="menu-users" class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['doctors.php', 'mrs.php', 'stockists.php', 'stockist_stocks.php'], $currentPage); ?>">
                    <li><a href="doctors.php" class="<?php echo navClass('doctors.php', $currentPage); ?>">Doctors</a></li>
                    <li><a href="mrs.php" class="<?php echo navClass('mrs.php', $currentPage); ?>">MRs</a></li>
                    <li><a href="stockists.php" class="<?php echo navClass('stockists.php', $currentPage); ?>">Stockists</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($role === 'Stockist'): ?>
            <li>
                <a href="stockist_stocks.php?id=<?php echo $_SESSION['user_id']; ?>" class="<?php echo navClass('stockist_stocks.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3 <?php echo $currentPage === 'stockist_stocks.php' ? 'text-green-600' : 'text-gray-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    My Inventory
                </a>
            </li>
            <li>
                <a href="stockist_orders.php" class="<?php echo navClass('stockist_orders.php', $currentPage); ?>">
                    <svg class="w-5 h-5 mr-3 <?php echo $currentPage === 'stockist_orders.php' ? 'text-green-600' : 'text-gray-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    My Assigned Orders
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <!-- User Profile & Logout -->
    <div class="p-4 border-t border-gray-100 mt-auto bg-gray-50">
        <div class="flex items-center space-x-3 mb-4 px-2">
            <div class="h-10 w-10 flex items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-teal-500 shadow-md text-white font-bold text-lg">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                <p class="text-xs text-green-600 font-semibold truncate"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Staff'); ?></p>
            </div>
        </div>
        <button onclick="logout()" class="w-full flex items-center justify-center py-2 px-4 shadow-sm text-sm font-semibold rounded-lg text-red-600 bg-white border border-red-200 hover:bg-red-50 hover:border-red-300 transition-all">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Sign Out
        </button>
    </div>
</aside>

<script>
    function toggleDropdown(menuId, iconId) {
        const menu = document.getElementById(menuId);
        const icon = document.getElementById(iconId);
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            menu.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }

    async function logout() {
        try {
            await fetch('api/auth.php?action=logout');
            window.location.href = 'index.php';
        } catch(e) {
            console.error('Logout failed');
        }
    }
</script>
