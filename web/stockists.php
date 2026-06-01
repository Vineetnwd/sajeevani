<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle Add Stockist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_stockist') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
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
                $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, address, password, role, status) VALUES (?, ?, ?, ?, ?, 'Stockist', 'Active')");
                $stmt->execute([$name, $phone, $email, $address, $passHash]);
                $success = "Stockist added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Edit Stockist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_stockist') {
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
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
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, address=? WHERE id=? AND role='Stockist'");
                    $stmt->execute([$name, $phone, $email, $address, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, address=?, password=? WHERE id=? AND role='Stockist'");
                    $stmt->execute([$name, $phone, $email, $address, md5($password), $id]);
                }
                $success = "Stockist updated successfully!";
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
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'Stockist'");
            $stmt->execute([$newStatus, $id]);
            $success = "Stockist status updated to $newStatus.";
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
    <title>Stockists - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Stockists</h1>
                <p class="text-xs text-gray-400 mt-0.5">Manage Stockists in the system</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow hover:bg-indigo-700">
                + Add Stockist
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
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

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Stockist</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Address</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Joined</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stockists = $pdo->query("SELECT id, name, phone, email, address, status, created_at FROM users WHERE role = 'Stockist' ORDER BY created_at DESC")->fetchAll();

                            if (count($stockists) === 0) {
                                echo '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No Stockists found. Add one to get started!</td></tr>';
                            }

                            foreach ($stockists as $d) {
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
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-gray-500 truncate max-w-xs">
                                            <?php echo htmlspecialchars($d['address'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span
                                            class="px-2 py-1 <?php echo $d['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> text-xs font-bold rounded-full">
                                            <?php echo htmlspecialchars($d['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-right whitespace-nowrap">
                                        <?php echo date('d M Y', strtotime($d['created_at'])); ?></td>
                                    <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                        <a href="stockist_stocks.php?id=<?php echo $d['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Manage Stock</a>
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode([
                                            'id' => $d['id'],
                                            'name' => $d['name'],
                                            'phone' => $d['phone'],
                                            'email' => $d['email'],
                                            'address' => $d['address']
                                        ])); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
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
    </main>

    <!-- Add Stockist Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-5 text-gray-800">Add New Stockist</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_stockist">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stockist Name *</label>
                        <input type="text" name="name" required
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="e.g. Ramesh Kumar">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number * (Login ID)</label>
                        <input type="tel" name="phone" required
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="e.g. 9876543210">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="text" name="password"
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="Leave blank for '123456'">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email"
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="e.g. stockist@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Address</label>
                        <textarea name="address" rows="2"
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="Stockist address..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl text-sm shadow hover:bg-indigo-700">Add
                        Stockist</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Stockist Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-5 text-gray-800">Edit Stockist</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_stockist">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stockist Name *</label>
                        <input type="text" name="name" id="edit_name" required
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="tel" name="phone" id="edit_phone" required
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="text" name="password"
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="Leave blank to keep current password">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="edit_email"
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Address</label>
                        <textarea name="address" id="edit_address" rows="2"
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl text-sm shadow hover:bg-indigo-700">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(stockist) {
            document.getElementById('edit_id').value = stockist.id;
            document.getElementById('edit_name').value = stockist.name;
            document.getElementById('edit_phone').value = stockist.phone;
            document.getElementById('edit_email').value = stockist.email || '';
            document.getElementById('edit_address').value = stockist.address || '';
            document.getElementById('editModal').classList.remove('hidden');
        }
    </script>
</body>

</html>
