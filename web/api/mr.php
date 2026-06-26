<?php
// web/api/mr.php — Plan 2: MR (Medical Representative) API
require_once '../config.php';

// CORS headers for mobile app
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

// ─────────────────────────────────────────────────
// GET DOCTORS — List all registered doctors
// ─────────────────────────────────────────────────
if ($action === 'get_doctors') {
    $mr_id = $_GET['mr_id'] ?? 0;
    try {
        if ($mr_id) {
            $mrStmt = $pdo->prepare("SELECT block_id FROM users WHERE id = ? AND role = 'MR'");
            $mrStmt->execute([$mr_id]);
            $mr = $mrStmt->fetch();
            $block_id = $mr ? $mr['block_id'] : -1;

            $stmt = $pdo->prepare("SELECT id, name, phone FROM users WHERE role = 'Doctor' AND status = 'Active' AND block_id = ? ORDER BY name ASC");
            $stmt->execute([$block_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id, name, phone FROM users WHERE role = 'Doctor' AND status = 'Active' ORDER BY name ASC");
            $stmt->execute();
        }
        jsonResponse('success', 'Fetched doctors', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch doctors', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// ADD DOCTOR — Register a new doctor
// ─────────────────────────────────────────────────
if ($action === 'add_doctor') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'POST method required');
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clinic = trim($_POST['clinic_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $mr_id = $_POST['mr_id'] ?? 0;
    $role = 'Doctor';

    // Default password if not provided
    $pwd = empty($password) ? md5('123456') : md5($password);

    if (empty($name) || empty($phone)) {
        jsonResponse('error', 'Name and Phone are required');
    }

    try {
        // Fetch MR's location
        $mrStmt = $pdo->prepare("SELECT state_id, district_id, block_id FROM users WHERE id = ?");
        $mrStmt->execute([$mr_id]);
        $mr = $mrStmt->fetch();
        
        $state_id = $mr ? $mr['state_id'] : null;
        $district_id = $mr ? $mr['district_id'] : null;
        $block_id = $mr ? $mr['block_id'] : null;

        // Check if phone exists
        $chk = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $chk->execute([$phone]);
        if ($chk->fetch()) {
            jsonResponse('error', 'Phone number already registered');
        }

        $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, clinic_name, address, state_id, district_id, block_id, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->execute([$name, $phone, $email, $clinic, $address, $state_id, $district_id, $block_id, $pwd, $role]);
        $newId = $pdo->lastInsertId();

        jsonResponse('success', 'Doctor added successfully', ['id' => $newId, 'name' => $name, 'phone' => $phone]);
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to add doctor', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// GET PRODUCTS — Medicines catalogue
// ─────────────────────────────────────────────────
if ($action === 'get_products') {
    try {
        $stmt = $pdo->query("SELECT id, name, description, price, stock_quantity FROM products ORDER BY name ASC");
        jsonResponse('success', 'Fetched products', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch products', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// PLACE ORDER — MR submits an order on behalf of a doctor
// ─────────────────────────────────────────────────
if ($action === 'place_order') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'POST method required');
    }

    $mr_id = $_POST['mr_id'] ?? 0;
    $doctor_id = $_POST['doctor_id'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    $items = $_POST['items'] ?? ''; // JSON string: [{product_id, quantity, unit_price}]

    if (!$mr_id || !$doctor_id || !$items) {
        jsonResponse('error', 'MR ID, Doctor ID, and items are required');
    }

    $itemsArray = json_decode($items, true);
    if (!is_array($itemsArray) || count($itemsArray) === 0) {
        jsonResponse('error', 'At least one product must be selected');
    }

    // Calculate total and validate stock
    $total = 0;
    $stockErrors = [];
    foreach ($itemsArray as $item) {
        $qty = max(1, (int) ($item['quantity'] ?? 0));
        $pid = $item['product_id'];

        $stmtStock = $pdo->prepare("SELECT name, stock_quantity FROM products WHERE id = ?");
        $stmtStock->execute([$pid]);
        $prod = $stmtStock->fetch();

        if (!$prod) {
            $stockErrors[] = "Product ID $pid not found.";
        }
        // Removed stock check: MRs can place order regardless of admin stock, 
        // because Admin will assign it to a Stockist later.

        $total += (float) ($item['unit_price'] ?? 0) * $qty;
    }

    if (count($stockErrors) > 0) {
        jsonResponse('error', implode(' ', $stockErrors));
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert doctor order
        $stmt = $pdo->prepare("INSERT INTO doctor_orders (mr_id, doctor_id, notes, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$mr_id, $doctor_id, $notes, $total]);
        $order_id = $pdo->lastInsertId();

        // 2. Insert each order item
        $itemStmt = $pdo->prepare("INSERT INTO doctor_order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        foreach ($itemsArray as $item) {
            $itemStmt->execute([
                $order_id,
                $item['product_id'],
                max(1, (int) $item['quantity']),
                (float) $item['unit_price']
            ]);
        }

        $pdo->commit();
        jsonResponse('success', 'Order placed successfully!', ['order_id' => $order_id, 'total_amount' => $total]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonResponse('error', 'Failed to place order', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// MY ORDERS — Orders placed by a specific MR
// ─────────────────────────────────────────────────
if ($action === 'my_orders') {
    $mr_id = $_GET['mr_id'] ?? 0;
    if (!$mr_id)
        jsonResponse('error', 'MR ID is required');

    try {
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.status, o.notes, o.total_amount, o.created_at,
                o.status_remarks, o.courier_company, o.awb_no,
                o.payment_status, o.payment_method, o.payment_date,
                d.name AS doctor_name, d.phone AS doctor_phone,
                (SELECT COUNT(*) FROM doctor_order_items WHERE order_id = o.id) AS item_count
            FROM doctor_orders o
            JOIN users d ON o.doctor_id = d.id
            WHERE o.mr_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$mr_id]);
        jsonResponse('success', 'Fetched orders', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch orders', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// GET DOCTOR ORDERS — Orders associated with a specific Doctor
// ─────────────────────────────────────────────────
if ($action === 'get_doctor_orders') {
    $doctor_id = $_GET['doctor_id'] ?? 0;
    if (!$doctor_id)
        jsonResponse('error', 'Doctor ID is required');

    try {
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.status, o.notes, o.total_amount, o.created_at,
                o.status_remarks, o.courier_company, o.awb_no,
                o.payment_status, o.payment_method, o.payment_date,
                mr.name AS mr_name, mr.phone AS mr_phone,
                (SELECT COUNT(*) FROM doctor_order_items WHERE order_id = o.id) AS item_count
            FROM doctor_orders o
            JOIN users mr ON o.mr_id = mr.id
            WHERE o.doctor_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$doctor_id]);
        jsonResponse('success', 'Fetched doctor orders', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch doctor orders', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// GET ORDER DETAIL — Full items breakdown for an order
// ─────────────────────────────────────────────────
if ($action === 'get_order_detail') {
    $order_id = $_GET['order_id'] ?? 0;
    if (!$order_id)
        jsonResponse('error', 'Order ID is required');

    try {
        $stmt = $pdo->prepare("
            SELECT 
                i.quantity, i.unit_price,
                p.name AS product_name,
                (i.quantity * i.unit_price) AS line_total
            FROM doctor_order_items i
            JOIN products p ON i.product_id = p.id
            WHERE i.order_id = ?
        ");
        $stmt->execute([$order_id]);
        jsonResponse('success', 'Fetched order items', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch order detail', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// GET ORDER RECEIPT — Metadata + Items for receipt view
// ─────────────────────────────────────────────────
if ($action === 'get_order_receipt') {
    $order_id = $_GET['order_id'] ?? 0;
    if (!$order_id)
        jsonResponse('error', 'Order ID is required');

    try {
        // Fetch order metadata
        $stmtOrder = $pdo->prepare("
            SELECT 
                o.id, o.status, o.total_amount, o.created_at,
                o.payment_status, o.payment_method, o.payment_date,
                d.name AS doctor_name, d.phone AS doctor_phone,
                d.clinic_name, d.address AS clinic_address,
                mr.name AS mr_name
            FROM doctor_orders o
            JOIN users d ON o.doctor_id = d.id
            JOIN users mr ON o.mr_id = mr.id
            WHERE o.id = ?
        ");
        $stmtOrder->execute([$order_id]);
        $orderInfo = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!$orderInfo) {
            jsonResponse('error', 'Order not found');
        }

        // Fetch items
        $stmtItems = $pdo->prepare("
            SELECT 
                i.quantity, i.unit_price,
                p.name AS product_name,
                (i.quantity * i.unit_price) AS line_total
            FROM doctor_order_items i
            JOIN products p ON i.product_id = p.id
            WHERE i.order_id = ?
        ");
        $stmtItems->execute([$order_id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse('success', 'Fetched order receipt', [
            'order' => $orderInfo,
            'items' => $items
        ]);
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch order receipt', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// UPDATE ORDER STATUS — Admin/Distributor updates status
// ─────────────────────────────────────────────────
if ($action === 'update_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'POST method required');
    }
    $order_id = $_POST['order_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $courier = $_POST['courier_company'] ?? '';
    $awb = $_POST['awb_no'] ?? '';

    $payment_status = $_POST['payment_status'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    $allowed = ['Pending', 'Confirmed', 'Dispatched', 'Delivered', 'Cancelled'];
    if (!$order_id || !in_array($status, $allowed)) {
        jsonResponse('error', 'Valid Order ID and status are required');
    }

    if ($status !== 'Dispatched') {
        $courier = null;
        $awb = null;
    }

    try {
        $pdo->beginTransaction();

        // Check if status is transitioning to Dispatched
        $stmtCheck = $pdo->prepare("SELECT status, stockist_id FROM doctor_orders WHERE id = ?");
        $stmtCheck->execute([$order_id]);
        $currentOrder = $stmtCheck->fetch();

        if ($currentOrder) {
            $currentStatus = $currentOrder['status'];
            $stId = $currentOrder['stockist_id'] ?? null;
            // Deduct stock if changing to Dispatched
            if ($status === 'Dispatched' && !in_array($currentStatus, ['Dispatched', 'Delivered'])) {
                $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM doctor_order_items WHERE order_id = ?");
                $itemsStmt->execute([$order_id]);
                $items = $itemsStmt->fetchAll();

                if ($stId) {
                    $deductStmt = $pdo->prepare("UPDATE stockist_inventory SET quantity = GREATEST(0, quantity - ?) WHERE stockist_id = ? AND product_id = ?");
                    foreach ($items as $item) {
                        $deductStmt->execute([$item['quantity'], $stId, $item['product_id']]);
                    }
                } else {
                    $deductStmt = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");
                    foreach ($items as $item) {
                        $deductStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                }
            }
        }

        $updateSql = "UPDATE doctor_orders SET status = ?, status_remarks = ?, courier_company = ?, awb_no = ?, updated_at = CURRENT_TIMESTAMP";
        $updateParams = [$status, $remarks, $courier, $awb];

        if ($payment_status === 'Paid') {
            $updateSql .= ", payment_status = 'Paid', payment_method = ?, payment_date = IFNULL(payment_date, CURRENT_TIMESTAMP)";
            $updateParams[] = $payment_method;
        } else if ($payment_status === 'Pending') {
            $updateSql .= ", payment_status = 'Pending', payment_method = NULL, payment_date = NULL";
        }

        $updateSql .= " WHERE id = ?";
        $updateParams[] = $order_id;

        $stmt = $pdo->prepare($updateSql);
        $stmt->execute($updateParams);

        $pdo->commit();
        jsonResponse('success', 'Order status updated');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse('error', 'Failed to update status', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// DOCTOR MARK DELIVERED — Doctor marks a dispatched order as delivered
// ─────────────────────────────────────────────────
if ($action === 'doctor_mark_delivered') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'POST method required');
    }
    $order_id = $_POST['order_id'] ?? 0;
    $remark = trim($_POST['remark'] ?? '');

    if (!$order_id) {
        jsonResponse('error', 'Order ID is required');
    }

    try {
        if ($remark) {
            $stmt = $pdo->prepare("UPDATE doctor_orders SET status = 'Delivered', status_remarks = CONCAT(IFNULL(status_remarks, ''), '\n[Doctor]: ', ?), updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'Dispatched'");
            $stmt->execute([$remark, $order_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE doctor_orders SET status = 'Delivered', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'Dispatched'");
            $stmt->execute([$order_id]);
        }

        if ($stmt->rowCount() > 0) {
            jsonResponse('success', 'Item marked as delivered');
        } else {
            jsonResponse('error', 'Could not update status. Ensure the item is currently Dispatched.');
        }
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to update order', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// GET ELIGIBLE STOCKISTS — For assigning order
// ─────────────────────────────────────────────────
if ($action === 'get_eligible_stockists') {
    $order_id = $_GET['order_id'] ?? 0;
    if (!$order_id)
        jsonResponse('error', 'Order ID required');

    try {
        // Fetch order items
        $stmtItems = $pdo->prepare("SELECT product_id, quantity FROM doctor_order_items WHERE order_id = ?");
        $stmtItems->execute([$order_id]);
        $items = $stmtItems->fetchAll();

        // Fetch all stockists
        $stmtStockists = $pdo->prepare("SELECT id, name FROM users WHERE role = 'Stockist' AND status = 'Active'");
        $stmtStockists->execute();
        $stockists = $stmtStockists->fetchAll();

        $eligible = [];
        foreach ($stockists as $st) {
            $hasStock = true;
            foreach ($items as $item) {
                $chk = $pdo->prepare("SELECT quantity FROM stockist_inventory WHERE stockist_id = ? AND product_id = ?");
                $chk->execute([$st['id'], $item['product_id']]);
                $inv = $chk->fetch();
                if (!$inv || $inv['quantity'] < $item['quantity']) {
                    $hasStock = false;
                    break;
                }
            }
            if ($hasStock) {
                $eligible[] = $st;
            }
        }
        jsonResponse('success', 'Fetched eligible stockists', $eligible);
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch stockists', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// ASSIGN STOCKIST
// ─────────────────────────────────────────────────
if ($action === 'assign_stockist') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        jsonResponse('error', 'POST required');
    $order_id = $_POST['order_id'] ?? 0;
    $stockist_id = $_POST['stockist_id'] ?? 0;
    if (!$order_id || !$stockist_id)
        jsonResponse('error', 'Order ID and Stockist ID required');

    try {
        $stmt = $pdo->prepare("UPDATE doctor_orders SET stockist_id = ? WHERE id = ?");
        $stmt->execute([$stockist_id, $order_id]);
        jsonResponse('success', 'Order assigned to stockist');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to assign stockist', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// STOCKIST ORDERS
// ─────────────────────────────────────────────────
if ($action === 'stockist_orders') {
    $stockist_id = $_GET['stockist_id'] ?? 0;
    if (!$stockist_id)
        jsonResponse('error', 'Stockist ID required');

    try {
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.status, o.notes, o.total_amount, o.created_at,
                o.status_remarks, o.courier_company, o.awb_no,
                d.name AS doctor_name, d.phone AS doctor_phone,
                (SELECT COUNT(*) FROM doctor_order_items WHERE order_id = o.id) AS item_count
            FROM doctor_orders o
            JOIN users d ON o.doctor_id = d.id
            WHERE o.stockist_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$stockist_id]);
        jsonResponse('success', 'Fetched orders', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch orders', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// STOCKIST INVENTORY
// ─────────────────────────────────────────────────
if ($action === 'stockist_inventory') {
    $stockist_id = $_GET['stockist_id'] ?? 0;
    if (!$stockist_id) jsonResponse('error', 'Stockist ID required');

    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price, COALESCE(si.quantity, 0) as quantity
            FROM products p
            LEFT JOIN stockist_inventory si ON p.id = si.product_id AND si.stockist_id = ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$stockist_id]);
        jsonResponse('success', 'Fetched inventory', $stmt->fetchAll());
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch inventory', $is_local ? $e->getMessage() : null);
    }
}

// ─────────────────────────────────────────────────
// STOCKIST UPDATE INVENTORY
// ─────────────────────────────────────────────────
if ($action === 'stockist_update_inventory') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse('error', 'POST required');
    
    $stockist_id = $_POST['stockist_id'] ?? 0;
    $product_id = $_POST['product_id'] ?? 0;
    $adjustment = (int)($_POST['adjustment'] ?? 0);

    if (!$stockist_id || !$product_id || $adjustment === 0) {
        jsonResponse('error', 'Invalid input');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO stockist_inventory (stockist_id, product_id, quantity)
            VALUES (?, ?, GREATEST(0, ?))
            ON DUPLICATE KEY UPDATE quantity = GREATEST(0, quantity + ?)
        ");
        $stmt->execute([$stockist_id, $product_id, $adjustment, $adjustment]);
        
        $fetchStmt = $pdo->prepare("SELECT quantity FROM stockist_inventory WHERE stockist_id = ? AND product_id = ?");
        $fetchStmt->execute([$stockist_id, $product_id]);
        $newQty = $fetchStmt->fetchColumn() ?: 0;
        
        jsonResponse('success', 'Inventory updated', ['new_quantity' => $newQty]);
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to update inventory', $is_local ? $e->getMessage() : null);
    }
}

jsonResponse('error', 'Invalid API Action');
?>