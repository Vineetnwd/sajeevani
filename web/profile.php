<?php
// web/profile.php — Allow any logged-in user to update their profile and password
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$successMsg = '';
$errorMsg   = '';
$userId     = $_SESSION['user_id'];

// Fetch current user details
$stmt = $pdo->prepare("SELECT name, phone, email, password FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($phone)) {
            $errorMsg = "Name and Phone are required.";
        } else {
            // Check if phone already exists for another user
            $check = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $check->execute([$phone, $userId]);
            if ($check->fetch()) {
                $errorMsg = "This phone number is already registered to another account.";
            } else {
                try {
                    $update = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $update->execute([$name, $email, $phone, $userId]);
                    
                    // Update session
                    $_SESSION['user_name'] = $name;
                    $successMsg = "Profile updated successfully.";
                    
                    // Refresh user data
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                } catch (PDOException $e) {
                    $errorMsg = "Error updating profile: " . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMsg = "All password fields are required.";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = "New passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            if (password_verify($currentPassword, $user['password'])) {
                try {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->execute([$hash, $userId]);
                    $successMsg = "Password changed successfully.";
                    $user['password'] = $hash;
                } catch (PDOException $e) {
                    $errorMsg = "Error updating password: " . $e->getMessage();
                }
            } else {
                $errorMsg = "Incorrect current password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — <?php echo APP_NAME; ?></title>
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
  <!-- Header -->
  <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
    <div class="flex items-center gap-3">
      <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 mr-2">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div>
        <h1 class="text-xl font-bold text-gray-800">My Profile</h1>
        <p class="text-xs text-gray-500 mt-0.5">Manage your personal information and security</p>
      </div>
    </div>
  </header>

  <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6 relative">
    
    <?php if ($successMsg): ?>
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded-lg flex items-center gap-2 mb-4">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <?php echo htmlspecialchars($successMsg); ?>
    </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm font-medium px-4 py-3 rounded-lg flex items-center gap-2 mb-4">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      <?php echo htmlspecialchars($errorMsg); ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Profile Information -->
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Personal Information</h2>
        </div>
        <div class="p-6">
          <form method="POST" class="space-y-4">
            <input type="hidden" name="update_profile" value="1">
            
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Full Name <span class="text-red-500">*</span></label>
              <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Phone Number <span class="text-red-500">*</span></label>
              <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Email Address</label>
              <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
            </div>

            <div class="pt-2">
              <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors text-sm">
                Save Profile Changes
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden h-max">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Change Password</h2>
        </div>
        <div class="p-6">
          <form method="POST" class="space-y-4">
            <input type="hidden" name="update_password" value="1">
            
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Current Password <span class="text-red-500">*</span></label>
              <input type="password" name="current_password" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">New Password <span class="text-red-500">*</span></label>
                <input type="password" name="new_password" required minlength="6" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
              </div>
              
              <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Confirm New <span class="text-red-500">*</span></label>
                <input type="password" name="confirm_password" required minlength="6" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
              </div>
            </div>

            <div class="pt-2">
              <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors text-sm">
                Update Password
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

  </div>
</main>
</body>
</html>
