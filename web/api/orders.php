<?php
require_once '../config.php';

$action = $_GET['action'] ?? '';

if ($action === 'update_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $orderId = $_POST['order_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    if ($orderId == 0 || empty($status)) {
        jsonResponse('error', 'Order ID and Status are required');
    }

    try {
        // Auto-migrate: Add status_remarks if missing
        $check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status_remarks'");
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN status_remarks TEXT DEFAULT NULL AFTER status");
        }

        $stmt = $pdo->prepare("UPDATE orders SET status = ?, status_remarks = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt->execute([$status, $remarks, $orderId])) {
            // Record History
            $historyStmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, remarks, updated_by) VALUES (?, ?, ?, ?)");
            $historyStmt->execute([$orderId, $status, $remarks, $_SESSION['user_id'] ?? null]);
            
            jsonResponse('success', 'Order status updated successfully');
        } else {
            jsonResponse('error', 'Failed to update order status');
        }
    } catch (PDOException $e) {
        jsonResponse('error', 'Database Error', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'get_history') {
    $orderId = $_GET['order_id'] ?? 0;
    try {
        $stmt = $pdo->prepare("
            SELECT h.*, u.name as updater_name 
            FROM order_status_history h 
            LEFT JOIN users u ON h.updated_by = u.id 
            WHERE h.order_id = ? 
            ORDER BY h.created_at DESC
        ");
        $stmt->execute([$orderId]);
        jsonResponse('success', 'Fetched history', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch history', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'delete_order') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $orderId = $_POST['order_id'] ?? 0;

    if ($orderId == 0) {
        jsonResponse('error', 'Order ID is required');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        if ($stmt->execute([$orderId])) {
            jsonResponse('success', 'Order removed successfully');
        } else {
            jsonResponse('error', 'Failed to remove order');
        }
    } catch (PDOException $e) {
        jsonResponse('error', 'Database Error', $is_local ? $e->getMessage() : null);
    }
}

jsonResponse('error', 'Invalid API Action');
?>
