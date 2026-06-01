<?php
require_once '../config.php';

$action = $_GET['action'] ?? '';

// Handle CORS for Mobile App requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Respond to Preflights
}

if ($action === 'submit_lead') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid Request Method. Use POST.');
    }

    // Usually, you should authenticate via a passed JWT token or Session ID,
    // For now, we will expect the executive_id passed directly from the app for simplicity.
    $executive_id = $_POST['executive_id'] ?? 1; // Defaulting to 1 to prevent failure during initial tests
    
    // Patient Data
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $age = $_POST['age'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Vitals Data
    $bp = $_POST['bp'] ?? '';
    $sugar = $_POST['sugar'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $pulse = $_POST['pulse'] ?? '';
    $symptoms = $_POST['symptoms'] ?? '';

    // Basic Validation
    if (empty($name) || empty($phone) || empty($age)) {
        jsonResponse('error', 'Name, Phone, and Age are mandatory!');
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert Patient
        $stmt = $pdo->prepare("INSERT INTO patients (executive_id, name, phone, age, gender, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$executive_id, $name, $phone, $age, $gender, $address]);
        $patient_id = $pdo->lastInsertId();

        // 2. Insert Vitals
        $vitals_stmt = $pdo->prepare("INSERT INTO vitals (patient_id, bp, sugar, weight, pulse, symptoms_notes) VALUES (?, ?, ?, ?, ?, ?)");
        $vitals_stmt->execute([$patient_id, $bp, $sugar, $weight, $pulse, $symptoms]);

        // 3. Push to Consultations (Lead Queue for Doctor)
        $consultation_stmt = $pdo->prepare("INSERT INTO consultations (patient_id, status) VALUES (?, 'Pending')");
        $consultation_stmt->execute([$patient_id]);

        $pdo->commit();

        jsonResponse('success', 'Lead successfully submitted to the Doctor Queue!', ['patient_id' => $patient_id]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        // Handle Duplicate Phone if applicable in schema, etc
        if ($e->getCode() == 23000) {
            jsonResponse('error', 'A patient with this identifier or constraint already exists.', $is_local ? $e->getMessage() : null);
        }
        
        jsonResponse('error', 'Database execution failed.', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'list_pending') {
    // API for Doctor Web Panel to fetch pending leads
    try {
        $stmt = $pdo->query("
            SELECT c.id as consultation_id, c.status, c.created_at,
                   p.name, p.phone, p.age, p.gender, p.address,
                   v.bp, v.sugar, v.weight, v.pulse, v.symptoms_notes
            FROM consultations c
            JOIN patients p ON c.patient_id = p.id
            JOIN vitals v ON v.patient_id = p.id
            WHERE c.status = 'Pending'
            ORDER BY c.created_at ASC
        ");
        $leads = $stmt->fetchAll();
        
        jsonResponse('success', 'Fetched pending leads', $leads);
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch leads.', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'delete_lead') {
    $id = $_POST['id'] ?? 0;
    try {
        // Due to CASCADE foreign keys in schema.sql, deleting the patient deletes vitals & consultations
        $stmt = $pdo->prepare("DELETE FROM patients WHERE id = (SELECT patient_id FROM consultations WHERE id = ?)");
        $stmt->execute([$id]);
        jsonResponse('success', 'Lead successfully deleted');
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to delete lead', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'update_lead') {
    // Basic update logic for Lead Edit
    $id = $_POST['patient_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $bp = $_POST['bp'] ?? '';
    $sugar = $_POST['sugar'] ?? '';
    $symptoms = $_POST['symptoms_notes'] ?? '';
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE patients SET name=?, phone=?, address=? WHERE id=?");
        $stmt->execute([$name, $phone, $address, $id]);
        
        $vstmt = $pdo->prepare("UPDATE vitals SET bp=?, sugar=?, symptoms_notes=? WHERE patient_id=?");
        $vstmt->execute([$bp, $sugar, $symptoms, $id]);
        
        $pdo->commit();
        jsonResponse('success', 'Lead updated successfully');
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonResponse('error', 'Update Failed', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'list') {
    // API for Patient Master List to fetch all patients
    try {
        $stmt = $pdo->query("
            SELECT MAX(c.id) as consultation_id, p.id as patient_id, p.name, p.phone, p.age, p.gender, p.address, p.created_at
            FROM patients p
            LEFT JOIN consultations c ON c.patient_id = p.id
            GROUP BY p.id
            ORDER BY p.name ASC
        ");
        $patients = $stmt->fetchAll();
        jsonResponse('success', 'Fetched all patients', $patients);
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch patient master list.', $is_local ? $e->getMessage() : null);
    }
}

if ($action === 'my_leads') {
    // API for Mobile App to fetch executive's own leads
    $executive_id = $_GET['executive_id'] ?? 0;
    try {
        $stmt = $pdo->prepare("
            SELECT c.id as consultation_id, c.status, c.created_at,
                   p.id as patient_id, p.name, p.phone, p.age,
                   v.bp, v.sugar, v.symptoms_notes
            FROM patients p
            JOIN consultations c ON c.patient_id = p.id
            LEFT JOIN vitals v ON v.patient_id = p.id
            WHERE p.executive_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$executive_id]);
        $leads = $stmt->fetchAll();
        jsonResponse('success', 'Fetched my leads', $leads);
    } catch (PDOException $e) {
        jsonResponse('error', 'Failed to fetch my leads.', $is_local ? $e->getMessage() : null);
    }
}

jsonResponse('error', 'Invalid API Action');
?>
