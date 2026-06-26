<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Location - Praanveda Ayurshakti</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAF9; }
    </style>
</head>
<body class="text-gray-900 antialiased h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="w-full bg-white border-b border-gray-200 py-3 px-6 lg:px-12 flex justify-between items-center shadow-sm z-50">
        <div class="flex items-center gap-2">
            <a href="inde.php" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-teal-800 rounded-sm flex items-center justify-center">
                    <span class="text-white font-bold text-sm tracking-tighter">PA</span>
                </div>
                <span class="text-lg font-bold text-gray-900 tracking-tight">Praanveda Ayurshakti</span>
            </a>
        </div>
        <div class="flex items-center gap-3">
            <a href="inde.php" class="text-sm font-semibold text-gray-700 hover:text-teal-800 transition-colors px-3">Back to Home</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col items-center justify-center p-6">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 p-8">
            
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Add New Location</h2>
            
            <?php
            $type = isset($_GET['type']) ? $_GET['type'] : 'state';
            ?>

            <div class="flex justify-center gap-2 mb-8">
                <a href="?type=state" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?= $type == 'state' ? 'bg-teal-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">State</a>
                <a href="?type=district" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?= $type == 'district' ? 'bg-teal-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">District</a>
                <a href="?type=block" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?= $type == 'block' ? 'bg-teal-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">Block</a>
            </div>

            <form action="" method="POST" class="space-y-4">
                
                <?php if ($type == 'district' || $type == 'block'): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Select <?= $type == 'district' ? 'State' : 'District' ?>
                    </label>
                    <select name="parent_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500 bg-white">
                        <option value="">-- Select --</option>
                        <!-- PHP code to fetch from DB would go here -->
                        <option value="1">Sample Option 1</option>
                        <option value="2">Sample Option 2</option>
                    </select>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <?= ucfirst($type) ?> Name
                    </label>
                    <input type="text" name="name" required placeholder="Enter <?= $type ?> name" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
                </div>

                <button type="submit" name="submit" value="1" 
                    class="w-full mt-6 px-6 py-3 bg-teal-800 text-white rounded-lg font-semibold text-sm text-center hover:bg-teal-900 transition-colors shadow-sm">
                    Save <?= ucfirst($type) ?>
                </button>

            </form>

            <?php
            if(isset($_POST['submit'])){
                // Here you would add your database connection and INSERT logic based on $type
                // e.g. INSERT INTO states (name) VALUES ('...')
                echo '<div class="mt-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm text-center font-medium">';
                echo 'Successfully added ' . htmlspecialchars($_POST['name']) . '!';
                echo '</div>';
            }
            ?>
        </div>
    </main>

</body>
</html>
