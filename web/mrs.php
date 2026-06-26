<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle AJAX for Locations
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get_districts' && isset($_GET['state_id'])) {
        $stmt = $pdo->prepare("SELECT id, name FROM districts WHERE state_id = ? ORDER BY name ASC");
        $stmt->execute([$_GET['state_id']]);
        jsonResponse('success', 'Districts fetched', $stmt->fetchAll());
    }
    if ($_GET['action'] === 'get_blocks' && isset($_GET['district_id'])) {
        $stmt = $pdo->prepare("SELECT id, name FROM blocks WHERE district_id = ? ORDER BY name ASC");
        $stmt->execute([$_GET['district_id']]);
        jsonResponse('success', 'Blocks fetched', $stmt->fetchAll());
    }
}

// Handle Add MR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_mr') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state_id = !empty($_POST['state_id']) ? $_POST['state_id'] : null;
    $district_id = !empty($_POST['district_id']) ? $_POST['district_id'] : null;
    $block_id = !empty($_POST['block_id']) ? $_POST['block_id'] : null;
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($phone)) {
        $error = "Name and Phone are required.";
    } else {
        $passHash = empty($password) ? md5('123456') : md5($password);

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                $error = "A user with this phone number already exists.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, address, state_id, district_id, block_id, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'MR', 'Active')");
                $stmt->execute([$name, $phone, $email, $address, $state_id, $district_id, $block_id, $passHash]);
                $success = "MR added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Edit MR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_mr') {
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state_id = !empty($_POST['state_id']) ? $_POST['state_id'] : null;
    $district_id = !empty($_POST['district_id']) ? $_POST['district_id'] : null;
    $block_id = !empty($_POST['block_id']) ? $_POST['block_id'] : null;
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($phone) || empty($id)) {
        $error = "Name and Phone are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $id]);
            if ($stmt->fetch()) {
                $error = "Another user with this phone number already exists.";
            } else {
                if (empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, address=?, state_id=?, district_id=?, block_id=? WHERE id=? AND role='MR'");
                    $stmt->execute([$name, $phone, $email, $address, $state_id, $district_id, $block_id, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, address=?, state_id=?, district_id=?, block_id=?, password=? WHERE id=? AND role='MR'");
                    $stmt->execute([$name, $phone, $email, $address, $state_id, $district_id, $block_id, md5($password), $id]);
                }
                $success = "MR updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $id = $_POST['id'] ?? 0;
    $current = $_POST['current_status'] ?? '';
    if ($id && $current) {
        $newStatus = ($current === 'Active') ? 'Inactive' : 'Active';
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'MR'");
            $stmt->execute([$newStatus, $id]);
            $success = "MR status updated to $newStatus.";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MRs - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Tom Select UI Improvements to match Tailwind */
        .ts-wrapper {
            padding: 0 !important;
            border: none !important;
        }
        .ts-control {
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.625rem 0.75rem !important;
            font-size: 0.875rem !important;
            box-shadow: none !important;
            background-color: white !important;
            min-height: 42px !important;
            display: flex;
            align-items: center;
        }
        .ts-control.focus {
            border-color: #14b8a6 !important;
            box-shadow: 0 0 0 1px #14b8a6 !important;
            outline: none !important;
        }
        .ts-dropdown {
            border-radius: 0.5rem !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            font-size: 0.875rem !important;
            overflow: hidden;
            margin-top: 4px;
        }
        .ts-dropdown .option {
            padding: 0.5rem 1rem !important;
            transition: background-color 0.1s ease;
        }
        .ts-dropdown .active {
            background-color: #f3f4f6 !important;
            color: #111827 !important;
        }
    </style>
    <link rel="stylesheet" href="admin-style.css">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
        <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <div class="min-w-0">
            <div>
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-800 truncate">Medical Representatives</h1>
                <p class="text-xs text-gray-400 mt-0.5">Manage MRs in the system</p>
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg shadow-sm whitespace-nowrap">
            + <span class="hidden sm:inline">Add MR</span>
        </button>
    </div>
</header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php $search = trim($_GET['search'] ?? ''); ?>

            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-4 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <form method="GET" action="" class="flex w-full sm:w-auto gap-3 items-center">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Name or Phone..." class="w-full sm:w-64 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm hover:bg-teal-700">Search</button>
                    <?php if($search): ?>
                        <a href="mrs.php" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                MR</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Address</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Joined</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $where_clause = "u.role = 'MR'";
                            $params = [];
                            
                            if ($search !== '') {
                                $where_clause .= " AND (u.name LIKE ? OR u.phone LIKE ?)";
                                $params[] = "%$search%";
                                $params[] = "%$search%";
                            }

                            $stmt = $pdo->prepare("SELECT u.id, u.name, u.phone, u.email, u.address, u.state_id, u.district_id, u.block_id, u.status, u.created_at, s.name as state_name, d.name as district_name, b.name as block_name FROM users u LEFT JOIN states s ON u.state_id = s.id LEFT JOIN districts d ON u.district_id = d.id LEFT JOIN blocks b ON u.block_id = b.id WHERE $where_clause ORDER BY u.created_at DESC");
                            $stmt->execute($params);
                            $mrs = $stmt->fetchAll();

                            if (count($mrs) === 0) {
                                echo '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No MRs found. Add one to get started!</td></tr>';
                            }

                            foreach ($mrs as $d) {
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900"><?php echo htmlspecialchars($d['name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($d['phone']); ?></div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo htmlspecialchars($d['email'] ?? 'No email'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs text-gray-500 truncate max-w-xs">
                                            <?php echo htmlspecialchars($d['address'] ?? ''); ?></div>
                                        <?php if ($d['state_name']): ?>
                                        <div class="text-xs sm:text-[10px] text-gray-400 mt-1">
                                            <?php 
                                            $loc = [];
                                            if ($d['block_name']) $loc[] = $d['block_name'];
                                            if ($d['district_name']) $loc[] = $d['district_name'];
                                            if ($d['state_name']) $loc[] = $d['state_name'];
                                            echo htmlspecialchars(implode(', ', $loc));
                                            ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 <?php echo $d['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> text-xs font-bold rounded-full">
                                            <?php echo htmlspecialchars($d['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-right whitespace-nowrap">
                                        <?php echo date('d M Y', strtotime($d['created_at'])); ?></td>
                                    <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                        <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode([
                                            "id" => $d["id"],
                                            "name" => $d["name"],
                                            "phone" => $d["phone"],
                                            "email" => $d["email"],
                                            "address" => $d["address"],
                                            "state_id" => $d["state_id"],
                                            "district_id" => $d["district_id"],
                                            "block_id" => $d["block_id"]
                                        ]), ENT_QUOTES, "UTF-8"); ?>)' class="text-teal-600 hover:text-teal-900 mr-3">Edit</button>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $d['status']; ?>">
                                            <button type="submit"
                                                class="<?php echo $d['status'] === 'Active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>">
                                                <?php echo $d['status'] === 'Active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="6" class="px-6 py-4 text-red-500">Error: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
</div>
            </div>
        </div>
    </main>

    <!-- Add MR Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-visible">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-xl">
                <h3 class="text-base font-bold text-gray-800">Add New MR</h3>
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="add_mr">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">MR Name *</label>
                            <input type="text" name="name" required
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all"
                                placeholder="e.g. Ramesh Kumar">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Phone * (Login ID)</label>
                            <input type="tel" name="phone" required
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all"
                                placeholder="e.g. 9876543210">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                            <input type="email" name="email"
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all"
                                placeholder="e.g. mr@example.com">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Password</label>
                            <input type="text" name="password"
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all"
                                placeholder="Leave blank for '123456'">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Full Address</label>
                        <textarea name="address" rows="2"
                            class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all"
                            placeholder="MR address..."></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">State</label>
                            <select name="state_id" id="add_state" onchange="fetchDistricts(this.value, 'add_district')" class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all bg-white">
                                <option value="">-- State --</option>
                                <?php
                                try {
                                    $states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll();
                                    foreach($states as $s) {
                                        echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['name']).'</option>';
                                    }
                                } catch(Exception $e) {}
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">District</label>
                            <select name="district_id" id="add_district" onchange="fetchBlocks(this.value, 'add_block')" class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all bg-white">
                                <option value="">-- District --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Block</label>
                            <select name="block_id" id="add_block" class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all bg-white">
                                <option value="">-- Block --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 bg-teal-600 text-white font-medium rounded-md text-sm shadow-sm hover:bg-teal-700 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">Add MR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit MR Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-visible">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-xl">
                <h3 class="text-base font-bold text-gray-800">Edit MR</h3>
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="edit_mr">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">MR Name *</label>
                            <input type="text" name="name" id="edit_name" required
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Phone *</label>
                            <input type="tel" name="phone" id="edit_phone" required
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                            <input type="email" name="email" id="edit_email"
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Password</label>
                            <input type="text" name="password"
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all"
                                placeholder="Leave blank to keep current">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Full Address</label>
                        <textarea name="address" id="edit_address" rows="2"
                            class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">State</label>
                            <select name="state_id" id="edit_state" onchange="fetchDistricts(this.value, 'edit_district')" class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all bg-white">
                                <option value="">-- State --</option>
                                <?php
                                try {
                                    $states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll();
                                    foreach($states as $s) {
                                        echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['name']).'</option>';
                                    }
                                } catch(Exception $e) {}
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">District</label>
                            <select name="district_id" id="edit_district" onchange="fetchBlocks(this.value, 'edit_block')" class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all bg-white">
                                <option value="">-- District --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Block</label>
                            <select name="block_id" id="edit_block" class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all bg-white">
                                <option value="">-- Block --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 bg-teal-600 text-white font-medium rounded-md text-sm shadow-sm hover:bg-teal-700 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(mr) {
            document.getElementById('edit_id').value = mr.id;
            document.getElementById('edit_name').value = mr.name;
            document.getElementById('edit_phone').value = mr.phone;
            document.getElementById('edit_email').value = mr.email || '';
            document.getElementById('edit_address').value = mr.address || '';
            
            document.getElementById('edit_state').value = mr.state_id || '';
            if (document.getElementById('edit_state').tomselect) {
                document.getElementById('edit_state').tomselect.setValue(mr.state_id || '');
            }
            
            // Fetch districts and then set selected district, then fetch blocks and set block
            if(mr.state_id) {
                fetchDistricts(mr.state_id, 'edit_district', mr.district_id, function() {
                    if(mr.district_id) {
                        fetchBlocks(mr.district_id, 'edit_block', mr.block_id);
                    } else {
                        updateSelectOptions('edit_block', [], '-- Block --');
                    }
                });
            } else {
                updateSelectOptions('edit_district', [], '-- District --');
                updateSelectOptions('edit_block', [], '-- Block --');
            }

            document.getElementById('editModal').classList.remove('hidden');
        }

        function updateSelectOptions(targetId, dataList, placeholder, selectedId = null) {
            const target = document.getElementById(targetId);
            const ts = target.tomselect;
            
            if (ts) {
                ts.clear(true);
                ts.clearOptions();
                ts.addOption({value: '', text: placeholder});
                dataList.forEach(item => {
                    ts.addOption({value: item.id, text: item.name});
                });
                ts.setValue(selectedId || '', true);
            } else {
                target.innerHTML = `<option value="">${placeholder}</option>`;
                dataList.forEach(item => {
                    const selected = item.id == selectedId ? 'selected' : '';
                    target.innerHTML += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                });
            }
        }

        function fetchDistricts(stateId, targetId, selectedId = null, callback = null) {
            const target = document.getElementById(targetId);
            
            // clear dependent block
            const blockTargetId = targetId === 'add_district' ? 'add_block' : 'edit_block';
            updateSelectOptions(blockTargetId, [], '-- Block --');

            if(!stateId) {
                updateSelectOptions(targetId, [], '-- District --');
                return;
            }

            // Optional: set loading state
            if (target.tomselect) {
                target.tomselect.clearOptions();
                target.tomselect.addOption({value: '', text: 'Loading...'});
                target.tomselect.setValue('');
            } else {
                target.innerHTML = '<option value="">Loading...</option>';
            }

            fetch(`mrs.php?action=get_districts&state_id=${stateId}`)
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        updateSelectOptions(targetId, data.data, '-- District --', selectedId);
                    } else {
                        updateSelectOptions(targetId, [], '-- District --');
                    }
                    if(callback) callback();
                });
        }

        function fetchBlocks(districtId, targetId, selectedId = null) {
            const target = document.getElementById(targetId);

            if(!districtId) {
                updateSelectOptions(targetId, [], '-- Block --');
                return;
            }

            if (target.tomselect) {
                target.tomselect.clearOptions();
                target.tomselect.addOption({value: '', text: 'Loading...'});
                target.tomselect.setValue('');
            } else {
                target.innerHTML = '<option value="">Loading...</option>';
            }

            fetch(`mrs.php?action=get_blocks&district_id=${districtId}`)
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        updateSelectOptions(targetId, data.data, '-- Block --', selectedId);
                    } else {
                        updateSelectOptions(targetId, [], '-- Block --');
                    }
                });
        }

        // Initialize TomSelect when DOM loads
        document.addEventListener('DOMContentLoaded', function() {
            const tsConfig = {
                create: false,
                sortField: { field: "text", direction: "asc" }
            };
            
            document.querySelectorAll('select').forEach((el) => {
                new TomSelect(el, tsConfig);
            });
        });
    </script>
</body>

</html>
