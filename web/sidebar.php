<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function navClass($pageUrl, $currentPage)
{
    if ($pageUrl === $currentPage) {
        return "flex items-center px-4 py-2.5 bg-teal-50 text-teal-700 rounded-lg font-semibold transition-colors text-sm shadow-sm";
    }
    return "flex items-center px-4 py-2.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-lg font-medium transition-colors text-sm";
}

// Helper to determine if a group should be open
function isGroupActive($pages, $currentPage)
{
    return in_array($currentPage, $pages) ? '' : 'hidden';
}
function isGroupIconActive($pages, $currentPage)
{
    return in_array($currentPage, $pages) ? 'rotate-180' : '';
}
function groupIconColor($pages, $currentPage)
{
    return in_array($currentPage, $pages) ? 'text-teal-600' : 'text-orange-500';
}
?>
<!-- Mobile Overlay -->
<div id="sidebar-overlay" onclick="toggleMobileSidebar()"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden lg:hidden"></div>

<!-- Unified Sidebar -->
<aside id="main-sidebar"
    class="fixed lg:static inset-y-0 left-0 w-64 bg-white shadow-xl flex flex-col h-full border-r border-gray-100 shrink-0 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="p-6 border-b border-gray-100 flex items-center justify-center shrink-0">
        <img src="logo.png?v=<?php echo time(); ?>"
            alt="<?php echo defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Praanveda'; ?> Logo"
            class="h-10 w-auto object-contain"
            onerror="this.onerror=null; this.src='https://via.placeholder.com/150x50?text=Logo';">
    </div>

    <nav class="flex-1 overflow-y-auto py-4 min-h-0">
        <ul class="space-y-1 px-3">
            <?php $role = $_SESSION['user_role'] ?? 'Admin'; ?>

            <?php if ($role !== 'Stockist'): ?>
                <!-- Dashboard -->
                <li>
                    <a href="dashboard.php" class="<?php echo navClass('dashboard.php', $currentPage); ?>">
                        <svg class="w-5 h-5 mr-3 <?php echo $currentPage === 'dashboard.php' ? 'text-teal-600' : 'text-orange-500'; ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <!-- Enquiries -->
                <li>
                    <a href="manage_enquiries.php" class="<?php echo navClass('manage_enquiries.php', $currentPage); ?>">
                        <svg class="w-5 h-5 mr-3 <?php echo $currentPage === 'manage_enquiries.php' ? 'text-teal-600' : 'text-orange-500'; ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        Enquiries
                    </a>
                </li>

                <!-- Patients & Consultations -->
                <li class="pt-2">
                    <button onclick="toggleDropdown('menu-patients', 'icon-patients')"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                        <div class="flex items-center min-w-0">
                            <svg class="w-5 h-5 mr-3 shrink-0 <?php echo groupIconColor(['patients.php', 'leads.php', 'doctor_orders.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                            <span class="truncate">Patients & Leads</span>
                        </div>
                        <svg id="icon-patients"
                            class="w-4 h-4 text-orange-500 transition-transform duration-200 <?php echo isGroupIconActive(['patients.php', 'leads.php', 'doctor_orders.php'], $currentPage); ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <ul id="menu-patients"
                        class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['patients.php', 'leads.php', 'doctor_orders.php'], $currentPage); ?>">
                        <li><a href="patients.php" class="<?php echo navClass('patients.php', $currentPage); ?>">Patient
                                Master</a></li>
                        <li><a href="leads.php" class="<?php echo navClass('leads.php', $currentPage); ?>">Leads Queue</a>
                        </li>
                        <li><a href="doctor_orders.php"
                                class="<?php echo navClass('doctor_orders.php', $currentPage); ?> flex justify-between w-full">Doctor
                                Orders <span
                                    class="ml-auto text-[10px] font-bold bg-teal-100 text-teal-600 px-1.5 py-0.5 rounded-full">Plan
                                    2</span></a></li>
                    </ul>
                </li>

                <!-- Inventory & Purchases -->
                <li class="pt-2">
                    <button onclick="toggleDropdown('menu-inventory', 'icon-inventory')"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                        <div class="flex items-center min-w-0">
                            <svg class="w-5 h-5 mr-3 shrink-0 <?php echo groupIconColor(['manage_products.php', 'stocks.php', 'purchases.php', 'purchase_returns.php', 'dealers.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span class="truncate">Inventory & Purchases</span>
                        </div>
                        <svg id="icon-inventory"
                            class="w-4 h-4 text-orange-500 transition-transform duration-200 <?php echo isGroupIconActive(['manage_products.php', 'stocks.php', 'purchases.php', 'purchase_returns.php', 'dealers.php'], $currentPage); ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <ul id="menu-inventory"
                        class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['manage_products.php', 'stocks.php', 'purchases.php', 'purchase_returns.php', 'dealers.php'], $currentPage); ?>">
                        <li><a href="manage_products.php"
                                class="<?php echo navClass('manage_products.php', $currentPage); ?>">Product Catalog</a>
                        </li>
                        <li><a href="stocks.php" class="<?php echo navClass('stocks.php', $currentPage); ?>">Stock Management</a></li>
                        <li><a href="purchases.php" class="<?php echo navClass('purchases.php', $currentPage); ?>">Purchase History</a></li>
                        <li><a href="purchase_returns.php" class="<?php echo navClass('purchase_returns.php', $currentPage); ?>">Purchase Returns</a></li>
                        <li><a href="dealers.php" class="<?php echo navClass('dealers.php', $currentPage); ?>">Manufactures</a></li>
                    </ul>
                </li>

                <!-- Sales & Orders -->
                <li class="pt-2">
                    <button onclick="toggleDropdown('menu-sales', 'icon-sales')"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                        <div class="flex items-center min-w-0">
                            <svg class="w-5 h-5 mr-3 shrink-0 <?php echo groupIconColor(['sale_to_stockist.php', 'stockist_ledgers.php', 'orders.php', 'sale_returns.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="truncate">Sales & Orders</span>
                        </div>
                        <svg id="icon-sales"
                            class="w-4 h-4 text-orange-500 transition-transform duration-200 <?php echo isGroupIconActive(['sale_to_stockist.php', 'stockist_ledgers.php', 'orders.php', 'sale_returns.php'], $currentPage); ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <ul id="menu-sales"
                        class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['sale_to_stockist.php', 'stockist_ledgers.php', 'orders.php', 'sale_returns.php'], $currentPage); ?>">
                        <li><a href="sale_to_stockist.php" class="<?php echo navClass('sale_to_stockist.php', $currentPage); ?>">Sale to Stockists</a></li>
                        <li><a href="stockist_ledgers.php" class="<?php echo navClass('stockist_ledgers.php', $currentPage); ?>">Stockist Ledgers</a></li>
                        <li><a href="orders.php" class="<?php echo navClass('orders.php', $currentPage); ?>">All Orders</a></li>
                        <li><a href="sale_returns.php" class="<?php echo navClass('sale_returns.php', $currentPage); ?>">Sale Returns</a></li>
                    </ul>
                </li>

                <!-- User Management -->
                <li class="pt-2">
                    <button onclick="toggleDropdown('menu-users', 'icon-users')"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                        <div class="flex items-center min-w-0">
                            <svg class="w-5 h-5 mr-3 shrink-0 <?php echo groupIconColor(['doctors.php', 'mrs.php', 'stockists.php', 'stockist_stocks.php', 'admins_staff.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            <span class="truncate">User Management</span>
                        </div>
                        <svg id="icon-users"
                            class="w-4 h-4 text-orange-500 transition-transform duration-200 <?php echo isGroupIconActive(['doctors.php', 'mrs.php', 'stockists.php', 'stockist_stocks.php', 'admins_staff.php'], $currentPage); ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <ul id="menu-users"
                        class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['doctors.php', 'mrs.php', 'stockists.php', 'stockist_stocks.php', 'admins_staff.php'], $currentPage); ?>">
                        <li><a href="doctors.php" class="<?php echo navClass('doctors.php', $currentPage); ?>">Doctors</a>
                        </li>
                        <li><a href="mrs.php" class="<?php echo navClass('mrs.php', $currentPage); ?>">MRs</a></li>
                        <li><a href="stockists.php"
                                class="<?php echo navClass('stockists.php', $currentPage); ?>">Stockists</a></li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
                            <li><a href="admins_staff.php"
                                    class="<?php echo navClass('admins_staff.php', $currentPage); ?>">Admins & Staff</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Locations Management -->
                <li class="pt-2">
                    <button onclick="toggleDropdown('menu-locations', 'icon-locations')"
                        class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                        <div class="flex items-center min-w-0">
                            <svg class="w-5 h-5 mr-3 shrink-0 <?php echo groupIconColor(['states.php', 'districts.php', 'blocks.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="truncate">Locations</span>
                        </div>
                        <svg id="icon-locations"
                            class="w-4 h-4 text-orange-500 transition-transform duration-200 <?php echo isGroupIconActive(['states.php', 'districts.php', 'blocks.php'], $currentPage); ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <ul id="menu-locations"
                        class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['states.php', 'districts.php', 'blocks.php'], $currentPage); ?>">
                        <li><a href="states.php" class="<?php echo navClass('states.php', $currentPage); ?>">States</a></li>
                        <li><a href="districts.php"
                                class="<?php echo navClass('districts.php', $currentPage); ?>">Districts</a></li>
                        <li><a href="blocks.php" class="<?php echo navClass('blocks.php', $currentPage); ?>">Blocks</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($role === 'Admin'): ?>
                <!-- Website Settings -->
                <li class="pt-2">
                    <a href="website_settings.php" class="<?php echo navClass('website_settings.php', $currentPage); ?>">
                        <svg class="w-5 h-5 mr-3 <?php echo $currentPage === 'website_settings.php' ? 'text-teal-600' : 'text-orange-500'; ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9">
                            </path>
                        </svg>
                        Website Settings
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($role === 'Stockist'): ?>
                <li>
                    <a href="stockist_stocks.php?id=<?php echo $_SESSION['user_id']; ?>"
                        class="<?php echo navClass('stockist_stocks.php', $currentPage); ?>">
                        <svg class="w-5 h-5 mr-3 <?php echo $currentPage === 'stockist_stocks.php' ? 'text-teal-600' : 'text-orange-500'; ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        My Inventory
                    </a>
                </li>
                <li>
                    <a href="stockist_orders.php" class="<?php echo navClass('stockist_orders.php', $currentPage); ?>">
                        <svg class="w-5 h-5 mr-3 <?php echo $currentPage === 'stockist_orders.php' ? 'text-teal-600' : 'text-orange-500'; ?>"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        My Assigned Orders
                    </a>
                </li>
            <?php endif; ?>

            <!-- Reports & Analytics -->
            <li class="pt-2 pb-2">
                <button onclick="toggleDropdown('menu-reports', 'icon-reports')"
                    class="w-full flex items-center justify-between px-4 py-2.5 text-gray-700 hover:bg-gray-50 rounded-lg font-bold transition-colors text-sm">
                    <div class="flex items-center min-w-0">
                        <svg class="w-5 h-5 mr-3 shrink-0 <?php echo groupIconColor(['report_purchases.php', 'report_sales.php', 'report_pnl.php'], $currentPage); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m4 2v-4m4 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span class="truncate">Reports & Analytics</span>
                    </div>
                    <svg id="icon-reports"
                        class="w-4 h-4 text-orange-500 transition-transform duration-200 <?php echo isGroupIconActive(['report_purchases.php', 'report_sales.php', 'report_pnl.php'], $currentPage); ?>"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <ul id="menu-reports"
                    class="mt-1 space-y-1 pl-11 pr-2 <?php echo isGroupActive(['report_purchases.php', 'report_sales.php', 'report_pnl.php'], $currentPage); ?>">
                    <li><a href="report_purchases.php" class="<?php echo navClass('report_purchases.php', $currentPage); ?>">Purchase Report</a></li>
                    <li><a href="report_sales.php" class="<?php echo navClass('report_sales.php', $currentPage); ?>">Sale Report</a></li>
                    <li><a href="report_pnl.php" class="<?php echo navClass('report_pnl.php', $currentPage); ?>">Profit & Loss Report</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <!-- User Profile & Logout -->
    <div class="p-4 border-t border-gray-100 mt-auto bg-gray-50 relative shrink-0">
        <!-- Dropdown Menu -->
        <div id="profile-menu" class="hidden absolute bottom-full left-4 right-4 mb-2 bg-white rounded-xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1),0_4px_6px_-1px_rgba(0,0,0,0.1)] border border-gray-100 overflow-hidden z-50">
            <div class="p-2 space-y-1">
                <a href="profile.php" class="w-full flex items-center py-2 px-3 text-sm font-semibold rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    My Profile
                </a>
                <button onclick="logout()" class="w-full flex items-center py-2 px-3 text-sm font-semibold rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Sign Out
                </button>
            </div>
        </div>

        <button onclick="toggleDropdown('profile-menu', 'profile-chevron')" class="w-full flex items-center justify-between space-x-3 p-2 -m-2 hover:bg-gray-100 rounded-xl transition-colors focus:outline-none text-left">
            <div class="flex items-center space-x-3 overflow-hidden">
                <div class="h-10 w-10 shrink-0 flex items-center justify-center rounded-full bg-gradient-to-r from-teal-500 to-teal-500 shadow-md text-white font-bold text-lg">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-bold text-gray-900 truncate">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                    </p>
                    <p class="text-xs text-teal-600 font-semibold truncate">
                        <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Staff'); ?>
                    </p>
                </div>
            </div>
            <svg id="profile-chevron" class="w-4 h-4 text-orange-500 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
        </button>
    </div>
</aside>

<script>
    // Dynamically inject favicon if it doesn't exist
    if (!document.querySelector("link[rel*='icon']")) {
        const link = document.createElement('link');
        link.type = 'image/x-icon';
        link.rel = 'shortcut icon';
        link.href = 'favicon.png?v=' + new Date().getTime();
        document.head.appendChild(link);
    }
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

    function toggleMobileSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    async function logout() {
        try {
            await fetch('api/auth.php?action=logout');
            window.location.href = 'index.php';
        } catch (e) {
            console.error('Logout failed');
        }
    }
</script>