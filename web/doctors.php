<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle Add Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_doctor') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clinic = trim($_POST['clinic_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($phone)) {
        $error = "Name and Phone are required.";
    } else {
        // Password hashing
        $passHash = empty($password) ? md5('123456') : md5($password);

        try {
            // Check if phone exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                $error = "A user with this phone number already exists.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, clinic_name, address, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'Doctor', 'Active')");
                $stmt->execute([$name, $phone, $email, $clinic, $address, $passHash]);
                $success = "Doctor added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Edit Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_doctor') {
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clinic = trim($_POST['clinic_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($phone) || empty($id)) {
        $error = "Name and Phone are required.";
    } else {
        try {
            // Check if phone exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $id]);
            if ($stmt->fetch()) {
                $error = "Another user with this phone number already exists.";
            } else {
                if (empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, clinic_name=?, address=? WHERE id=? AND role='Doctor'");
                    $stmt->execute([$name, $phone, $email, $clinic, $address, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, clinic_name=?, address=?, password=? WHERE id=? AND role='Doctor'");
                    $stmt->execute([$name, $phone, $email, $clinic, $address, md5($password), $id]);
                }
                $success = "Doctor updated successfully!";
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
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'Doctor'");
            $stmt->execute([$newStatus, $id]);
            $success = "Doctor status updated to $newStatus.";
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
    <title>Doctors - <?php echo APP_NAME; ?></title>
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
                <h1 class="text-xl font-bold text-gray-800">Doctors</h1>
                <p class="text-xs text-gray-400 mt-0.5">Manage doctors in the system</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow hover:bg-indigo-700">
                + Add Doctor
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
                                Doctor</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Clinic & Address</th>
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
                            $doctors = $pdo->query("SELECT id, name, phone, email, clinic_name, address, status, created_at FROM users WHERE role = 'Doctor' ORDER BY created_at DESC")->fetchAll();

                            if (count($doctors) === 0) {
                                echo '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">No doctors found. Add one to get started!</td></tr>';
                            }

                            foreach ($doctors as $d) {
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
                                        <div class="text-sm text-gray-900 font-medium">
                                            <?php echo htmlspecialchars($d['clinic_name'] ?? 'N/A'); ?></div>
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
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode([
                                            'id' => $d['id'],
                                            'name' => $d['name'],
                                            'phone' => $d['phone'],
                                            'email' => $d['email'],
                                            'clinic_name' => $d['clinic_name'],
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
                            echo '<tr><td colspan="5" class="px-6 py-4 text-red-500">Error: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Doctor Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-5 text-gray-800">Add New Doctor</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_doctor">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Doctor Name *</label>
                        <input type="text" name="name" required
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="e.g. Dr. Rajesh Kumar">
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
                            placeholder="e.g. doctor@clinic.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Clinic Name</label>
                        <input type="text" name="clinic_name"
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="e.g. Sanjeevni Clinic">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Address</label>
                        <textarea name="address" rows="2"
                            class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-100"
                            placeholder="Clinic address..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl text-sm shadow hover:bg-indigo-700">Add
                        Doctor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-5 text-gray-800">Edit Doctor</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_doctor">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Doctor Name *</label>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Clinic Name</label>
                        <input type="text" name="clinic_name" id="edit_clinic_name"
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
        function openEditModal(doctor) {
            document.getElementById('edit_id').value = doctor.id;
            document.getElementById('edit_name').value = doctor.name;
            document.getElementById('edit_phone').value = doctor.phone;
            document.getElementById('edit_email').value = doctor.email || '';
            document.getElementById('edit_clinic_name').value = doctor.clinic_name || '';
            document.getElementById('edit_address').value = doctor.address || '';
            document.getElementById('editModal').classList.remove('hidden');
        }
    </script>
</body>

</html>