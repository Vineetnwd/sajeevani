<?php
require_once '../config.php';

$action = $_GET['action'] ?? '';

if ($action === 'create_order') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method');
    }

    $consultation_id = $_POST['consultation_id'] ?? 0;
    $patient_id = $_POST['patient_id'] ?? 0;
    $total_amount = $_POST['total_amount'] ?? 0;
    $doctor_response = $_POST['doctor_response'] ?? '';
    $rxData = json_decode($_POST['rx'] ?? '[]', true);

    if (empty($consultation_id) || empty($patient_id) || empty($rxData)) {
        jsonResponse('error', 'Missing critical prescription identifiers or empty medicine list.');
    }

    try {
        $pdo->beginTransaction();
        
        // Auto-migrate: Add doctor_response if missing
        $check = $pdo->query("SHOW COLUMNS FROM consultations LIKE 'doctor_response'");
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE consultations ADD COLUMN doctor_response TEXT DEFAULT NULL AFTER status");
        }

        $rxNotes = json_encode($rxData);
        $stmt = $pdo->prepare("UPDATE consultations SET status = 'Prescribed', prescription_notes = ?, doctor_id = ?, doctor_response = ?, medicine_required = 1 WHERE id = ?");
        $stmt->execute([$rxNotes, $_SESSION['user_id'], $doctor_response, $consultation_id]);

        $orderStmt = $pdo->prepare("INSERT INTO orders (consultation_id, patient_id, status, total_amount) VALUES (?, ?, 'Created', ?)");
        $orderStmt->execute([$consultation_id, $patient_id, $total_amount]);
        $orderId = $pdo->lastInsertId();

        $updateStockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        foreach($rxData as $med) {
            $updateStockStmt->execute([$med['qty'], $med['id']]);
        }

        $pdo->commit();
        jsonResponse('success', 'Prescription saved and Order generated', ['order_id' => $orderId]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonResponse('error', 'Failed to generate order', $is_local ? $e->getMessage() : null);
    }
}

jsonResponse('error', 'Invalid API Action');
?>
