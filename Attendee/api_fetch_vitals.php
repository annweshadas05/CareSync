<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['attendee_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$patient_code = trim($_GET['patient_code'] ?? '');
if (!$patient_code) {
    echo json_encode(['success' => false, 'patient_found' => false, 'vitals_found' => false]);
    exit();
}

$patientStmt = $conn->prepare("SELECT id FROM patients WHERE patient_code = ? LIMIT 1");
$patientStmt->bind_param("s", $patient_code);
$patientStmt->execute();
$patientStmt->store_result();
$patient_found = $patientStmt->num_rows > 0;
$patientStmt->close();

if (!$patient_found) {
    echo json_encode(['success' => false, 'patient_found' => false, 'vitals_found' => false]);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM vitals WHERE patient_code = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $patient_code);
$stmt->execute();
$result = $stmt->get_result();

if ($vital = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'patient_found' => true, 'vitals_found' => true, 'data' => $vital]);
} else {
    echo json_encode(['success' => false, 'patient_found' => true, 'vitals_found' => false]);
}
$stmt->close();
