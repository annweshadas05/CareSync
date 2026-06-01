<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

session_start();
include '../dbconnect.php';

// Get patient_code from session
if (!isset($_SESSION['patient_id'])) {
    die("Patient session not found! Please login again.");
}
$patient_code_session = $_SESSION['patient_id'];

// Get patient users.id
$stmt = $conn->prepare("SELECT id FROM users WHERE patient_code = ?");
$stmt->bind_param("s", $patient_code_session);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows == 0) die("Invalid patient profile attached to session!");
$patient_user_id = $res->fetch_assoc()['id'];

// Get POST data
if (!isset($_POST['doctor_id']) || !isset($_POST['slot_id'])) {
    die("Incomplete form data.");
}

$doctor_id = (int)$_POST['doctor_id'];
$slot_id = (int)$_POST['slot_id'];
$reason = $_POST['reason'] ?? '';

// Get doctor users.id and code
$stmt = $conn->prepare("SELECT users.id, doctors.doctor_code as actual_doc_code, doctors.full_name as doctor_name FROM users INNER JOIN doctors ON users.doctor_code = doctors.doctor_code WHERE doctors.id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$docRes = $stmt->get_result();
if($docRes->num_rows == 0) {
    die("Invalid doctor account!");
}
$docData = $docRes->fetch_assoc();
$doctor_user_id = $docData['id'];
$actual_doctor_code = $docData['actual_doc_code'];
$doctor_name = $docData['doctor_name'];

// Validate Slot
$stmt = $conn->prepare("SELECT start_time, end_time, location, capacity, booked_count FROM time_slots WHERE id = ? AND doctor_code = ? AND status='available'");
$stmt->bind_param("ii", $slot_id, $doctor_user_id);
$stmt->execute();
$slotInfo = $stmt->get_result();

if($slotInfo->num_rows == 0) {
    echo "<script>alert('Slot is no longer available or invalid! Please try another slot.'); window.location='book_appointment.php';</script>";
    exit();
}

$slot = $slotInfo->fetch_assoc();

// Check if patient already has a booking for the same slot
$stmt = $conn->prepare("SELECT id FROM appointments WHERE patient_code = ? AND slot_id = ? AND status != 'cancelled'");
$stmt->bind_param("si", $patient_code_session, $slot_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo "<script>alert('You have already booked this slot!'); window.location='book_appointment.php';</script>";
    exit();
}

// Insert Appointment
$query = "INSERT INTO appointments (patient_code, doctor_code, slot_id, reason, start_time, end_time, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssissss", $patient_code_session, $actual_doctor_code, $slot_id, $reason, $slot['start_time'], $slot['end_time'], $slot['location']);

if($stmt->execute()){
    // Update slot booking count
    $new_booked = (int)$slot['booked_count'] + 1;
    $new_status = ($new_booked >= (int)$slot['capacity']) ? 'booked' : 'available';
    $updateSlot = $conn->prepare("UPDATE time_slots SET booked_count = ?, status = ? WHERE id = ?");
    $updateSlot->bind_param("isi", $new_booked, $new_status, $slot_id);
    $updateSlot->execute();

    // =============== SEND EMAIL VIA PHPMAILER ===============
    
    // Fetch Patient Info for Email
    $stmt_pat = $conn->prepare("SELECT email, full_name FROM patients WHERE patient_code = ?");
    $stmt_pat->bind_param("s", $patient_code_session);
    $stmt_pat->execute();
    $patData = $stmt_pat->get_result()->fetch_assoc();
    
    if ($patData && !empty($patData['email'])) {
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            // !!! UPDATE THESE WITH REAL CREDENTIALS !!!
            $mail->Username   = 'caresyncbbsr@gmail.com'; 
            $mail->Password   = 'eptjochajicisedu'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('caresyncbbsr@gmail.com', 'CareSync Hospitals');
            $mail->addAddress($patData['email'], $patData['full_name']);

            // Formatting
            $date_formatted = date('l, d M Y', strtotime($slot['start_time']));
            $time_formatted = date('h:i A', strtotime($slot['start_time'])) . ' - ' . date('h:i A', strtotime($slot['end_time']));

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment Confirmed - CareSync';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2 style='color: #0061ff;'>CareSync Hospitals</h2>
                    <h3>Dear {$patData['full_name']},</h3>
                    <p>Your appointment has been successfully confirmed!</p>
                    <div style='background: #f8f9fa; border-left: 4px solid #0061ff; padding: 15px; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><b>Doctor:</b> Dr. {$doctor_name}</p>
                        <p style='margin: 5px 0;'><b>Date:</b> {$date_formatted}</p>
                        <p style='margin: 5px 0;'><b>Time:</b> {$time_formatted}</p>
                        <p style='margin: 5px 0;'><b>Location:</b> {$slot['location']}</p>
                    </div>
                    <p>Please arrive at least 15 minutes early and bring your relevant medical records.</p>
                    <p>Thank you,<br><strong>CareSync Team</strong></p>
                </div>
            ";

            $mail->send();
        } catch (Exception $e) {
            // Mail silently failed, but booking was successful.
            error_log("Mail Error: " . $mail->ErrorInfo);
        }
    }

    echo "<script>alert('Appointment securely booked successfully! A confirmation email has been dispatched.'); window.location='my_appointments.php';</script>";
} else {
    echo "Error processing appointment: " . $conn->error;
}
?>