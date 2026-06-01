<?php
header('Content-Type: text/plain');

// Database connection
require_once '../../dbconnect.php'; // Include CareSync db connection

// Get the raw POST data
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if ($data && isset($data['patient_code'])) {
    $patientCode = $data['patient_code'];
    
    // SERVER-SIDE CHECK: Verify patient exists in the database
    $checkStmt = $conn->prepare("SELECT id FROM patients WHERE patient_code = ?");
    $checkStmt->bind_param("s", $patientCode);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    
    if ($checkRes->num_rows === 0) {
        echo "Error: Patient Code '$patientCode' does not exist in our records. Saving cancelled.";
        mysqli_close($conn);
        exit;
    }
    
    $patientCode = mysqli_real_escape_string($conn, $patientCode);
    $notes = isset($data['notes']) ? mysqli_real_escape_string($conn, $data['notes']) : '';
    $medicines = isset($data['medicines']) ? $data['medicines'] : [];

    if (!empty($medicines)) {
        // Use a single shared timestamp so all medicines from this save are grouped as one prescription
        $sharedTimestamp = date('Y-m-d H:i:s');

        $medSql = "INSERT INTO prescription_medicines (patient_code, notes, medicine, form, dosage, frequency, duration, created_at) VALUES ";
        $medValues = [];
        
        foreach ($medicines as $med) {
            $medicine = mysqli_real_escape_string($conn, $med['medicine'] ?? '');
            $form = mysqli_real_escape_string($conn, $med['form'] ?? '');
            $dosage = mysqli_real_escape_string($conn, $med['dosage'] ?? '');
            $frequency = mysqli_real_escape_string($conn, $med['frequency'] ?? '');
            $duration = mysqli_real_escape_string($conn, $med['duration'] ?? '');
            
            $medValues[] = "('$patientCode', '$notes', '$medicine', '$form', '$dosage', '$frequency', '$duration', '$sharedTimestamp')";
        }
        
        if (!empty($medValues)) {
            $medSql .= implode(", ", $medValues);
            if (mysqli_query($conn, $medSql)) {
                echo "Data saved successfully to patient record ($patientCode).";
            } else {
                echo "Error saving medicines: " . mysqli_error($conn);
            }
        } else {
            echo "No medicines to save for patient ($patientCode).";
        }
    } else {
        echo "No medicines provided. Nothing was saved.";
    }
} else {
    echo "Invalid data received. Patient code is required.";
}

mysqli_close($conn);
?>
