<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['Admin'])) {
    header("Location: index.php");
    exit();
}

// Handle Add Admin/Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = trim($_POST['role'] ?? 'Staff');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($phone)) {
        $error = "Name and Phone are required.";
    } elseif (!in_array($role, ['Admin', 'Staff'])) {
        $error = "Invalid role selected.";
    } else {
        // Password hashing
        $passHash = empty($password) ? password_hash('123456', PASSWORD_DEFAULT) : password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check if phone exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                $error = "A user with this phone number already exists.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'Active')");
                $stmt->execute([$name, $phone, $email, $passHash, $role]);
                $success = "User added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Edit Admin/Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = trim($_POST['role'] ?? 'Staff');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($phone) || empty($id)) {
        $error = "Name and Phone are required.";
    } elseif (!in_array($role, ['Admin', 'Staff'])) {
        $error = "Invalid role selected.";
    } else {
        try {
            // Check if phone exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $id]);
            if ($stmt->fetch()) {
                $error = "Another user with this phone number already exists.";
            } else {
                if (empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, role=? WHERE id=? AND role IN ('Admin', 'Staff')");
                    $stmt->execute([$name, $phone, $email, $role, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, role=?, password=? WHERE id=? AND role IN ('Admin', 'Staff')");
                    $stmt->execute([$name, $phone, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
                }
                $success = "User updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $id = (int)($_POST['id'] ?? 0);
    $current = $_POST['current_status'] ?? '';
    // Prevent self-deactivation
    if ($id === (int)$_SESSION['user_id']) {
        $error = "You cannot deactivate your own account.";
    } elseif ($id && $current) {
        $newStatus = ($current === 'Active') ? 'Inactive' : 'Active';
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role IN ('Admin', 'Staff')");
            $stmt->execute([$newStatus, $id]);
            $success = "User status updated to $newStatus.";
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
    <title>Admins & Staff - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
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
                        <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-800 truncate">Admins & Staff</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Manage administrative and staff users</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3 sm:space-x-4">
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-teal-600 text-white font-medium rounded-lg shadow hover:bg-teal-700 whitespace-nowrap">
                    + <span class="hidden sm:inline">Add User</span>
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
                        <a href="admins_staff.php" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">User</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Role</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Joined</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $where_clause = "role IN ('Admin', 'Staff')";
                                $params = [];
                                
                                if ($search !== '') {
                                    $where_clause .= " AND (name LIKE ? OR phone LIKE ?)";
                                    $params[] = "%$search%";
                                    $params[] = "%$search%";
                                }

                                $stmt = $pdo->prepare("SELECT id, name, phone, email, role, status, created_at FROM users WHERE $where_clause ORDER BY created_at DESC");
                                $stmt->execute($params);
                                $users = $stmt->fetchAll();

                                if (count($users) === 0) {
                                    echo '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">No users found. Add one to get started!</td></tr>';
                                }

                                foreach ($users as $u) {
                                    $isSelf = ((int)$u['id'] === (int)$_SESSION['user_id']);
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-gray-900"><?php echo htmlspecialchars($u['name']); ?> <?php if($isSelf) echo '<span class="text-xs text-teal-600 font-normal ml-1">(You)</span>'; ?></div>
                                            <div class="text-sm text-gray-600"><?php echo htmlspecialchars($u['phone']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($u['email'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 <?php echo $u['role'] === 'Admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?> text-xs font-bold rounded-full">
                                                <?php echo htmlspecialchars($u['role']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                                            <span class="px-2 py-1 <?php echo $u['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> text-xs font-bold rounded-full">
                                                <?php echo htmlspecialchars($u['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 text-right whitespace-nowrap">
                                            <?php echo date('d M Y', strtotime($u['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                            <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode([
                                                "id" => $u["id"],
                                                "name" => $u["name"],
                                                "phone" => $u["phone"],
                                                "email" => $u["email"],
                                                "role" => $u["role"]
                                            ]), ENT_QUOTES, "UTF-8"); ?>)' class="text-teal-600 hover:text-teal-900 mr-3">Edit</button>
                                            
                                            <?php if (!$isSelf): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $u['status']; ?>">
                                                <button type="submit"
                                                    class="<?php echo $u['status'] === 'Active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>">
                                                    <?php echo $u['status'] === 'Active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="5" class="px-6 py-4 text-red-500">Error: ' . $e->getMessage() . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add User Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-visible">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-xl">
                <h3 class="text-base font-bold text-gray-800">Add New Admin / Staff</h3>
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="add_user">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Name *</label>
                            <input type="text" name="name" required
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Phone * (Login ID)</label>
                            <input type="tel" name="phone" required
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                            <input type="email" name="email"
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Password</label>
                            <input type="text" name="password"
                                class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 transition-all"
                                placeholder="Leave blank for '123456'">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Role *</label>
                        <select name="role" required class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 bg-white">
                            <option value="Staff">Staff</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 bg-teal-600 text-white font-medium rounded-md text-sm shadow-sm hover:bg-teal-700 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-visible">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-xl">
                <h3 class="text-base font-bold text-gray-800">Edit Admin / Staff</h3>
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Name *</label>
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
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Role *</label>
                        <select name="role" id="edit_role" required class="w-full border border-gray-300 rounded-md p-2 text-sm outline-none focus:ring-1 focus:ring-teal-500 bg-white">
                            <option value="Staff">Staff</option>
                            <option value="Admin">Admin</option>
                        </select>
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
        function openEditModal(u) {
            document.getElementById('edit_id').value = u.id;
            document.getElementById('edit_name').value = u.name;
            document.getElementById('edit_phone').value = u.phone;
            document.getElementById('edit_email').value = u.email || '';
            document.getElementById('edit_role').value = u.role || 'Staff';
            document.getElementById('editModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
