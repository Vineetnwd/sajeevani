<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle Add State
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_state') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = "State name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO states (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "State added successfully!";
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
    <title>States - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <link rel="stylesheet" href="admin-style.css">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header
            class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <button onclick="toggleMobileSidebar()"
                    class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="min-w-0">
                    <div>
                        <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-800 truncate">States</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Manage states</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3 sm:space-x-4">
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg shadow-sm whitespace-nowrap">
                    + <span class="hidden sm:inline">Add State</span>
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
            <div
                class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-4 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <form method="GET" action="" class="flex w-full sm:w-auto gap-3 items-center">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search State Name..."
                        class="w-full sm:w-64 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <button type="submit"
                        class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm hover:bg-teal-700">Search</button>
                    <?php if ($search): ?>
                        <a href="states.php" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                    ID</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                    State Name</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                    Created At</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $where_clause = "1=1";
                                $params = [];

                                if ($search !== '') {
                                    $where_clause .= " AND name LIKE ?";
                                    $params[] = "%$search%";
                                }

                                $stmt = $pdo->prepare("SELECT * FROM states WHERE $where_clause ORDER BY name ASC");
                                $stmt->execute($params);
                                $states = $stmt->fetchAll();

                                if (count($states) === 0) {
                                    echo '<tr><td colspan="3" class="px-6 py-12 text-center text-gray-400">No states found. Add one to get started!</td></tr>';
                                }

                                foreach ($states as $s) {
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $s['id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                            <?php echo htmlspecialchars($s['name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            <?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
                                    </tr>
                                <?php
                                }
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="3" class="px-6 py-4 text-red-500">Error: ' . $e->getMessage() . ' (Make sure the table is created)</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-xl w-96">
            <h3 class="text-lg font-bold mb-5 text-gray-800">Add New State</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_state">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State Name *</label>
                    <input type="text" name="name" required
                        class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-teal-100">
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-teal-600 text-white font-bold rounded-xl text-sm shadow hover:bg-teal-700">Add
                        State</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>