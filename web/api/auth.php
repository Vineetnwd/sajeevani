<?php
// web/api/auth.php
require_once '../config.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($phone) || empty($password)) {
        jsonResponse('error', 'Please provide phone and password');
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, phone, `role`, password, status FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] !== 'Active') {
                jsonResponse('error', 'Account is suspended or inactive');
            }

            // Using md5 for simplicity based on schema setup, but password_verify() is standard for prod.
            if (md5($password) === $user['password'] || password_verify($password, $user['password'])) {
                // Success!
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                jsonResponse('success', 'Login successful!', [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    // Can generate JWT token here later for mobile App
                ]);
            } else {
                jsonResponse('error', 'Invalid password');
            }
        } else {
            jsonResponse('error', 'User not found');
        }
    } catch (PDOException $e) {
        jsonResponse('error', 'Database error', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'logout') {
    session_destroy();
    jsonResponse('success', 'Logged out successfully');
}

jsonResponse('error', 'Invalid API Action');
?>
