<?php
session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token validation failed");
}
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slot_id = (int)$_POST['slot_id'];
    $patient_code = $_SESSION['patient_id']; // This is the actual patient code like PAT-...
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    try {
        $conn->begin_transaction();

        $existingBookingStmt = $conn->prepare("
            SELECT id FROM appointments 
            WHERE patient_code = ? AND slot_id = ?
        ");
        $existingBookingStmt->bind_param("si", $patient_code, $slot_id);
        $existingBookingStmt->execute();
        $existingBookingResult = $existingBookingStmt->get_result();

        if ($existingBookingResult->num_rows > 0) {
            $conn->rollback();
            $_SESSION['error'] = "You have already booked this appointment slot";
            header("Location: calendar_booking.php");
            exit;
        }

        $slotStmt = $conn->prepare("
            SELECT ts.*, 
                   (SELECT COUNT(*) FROM appointments WHERE slot_id = ts.id AND status != 'cancelled') as current_bookings 
            FROM time_slots ts
            WHERE ts.id = ? AND ts.status = 'available' 
            FOR UPDATE
        ");
        $slotStmt->bind_param("i", $slot_id);
        $slotStmt->execute();
        $slot = $slotStmt->get_result()->fetch_assoc();

        if (!$slot) {
            $conn->rollback();
            $_SESSION['error'] = "Time slot no longer available";
            header("Location: calendar_booking.php");
            exit;
        }

        $current_time = time();
        $appointment_time = strtotime($slot['start_time']);

        if ($appointment_time < $current_time) {
            $conn->rollback();
            $_SESSION['error'] = "Cannot book appointments for past dates and times";
            header("Location: calendar_booking.php");
            exit;
        }

        if (isset($slot['capacity']) && $slot['current_bookings'] >= $slot['capacity']) {
            $conn->rollback();
            $_SESSION['error'] = "This appointment slot is full";
            header("Location: calendar_booking.php");
            exit;
        }

        $stmt_doc = $conn->prepare("SELECT u.name, d.doctor_code as actual_doc_code FROM users u JOIN doctors d ON u.doctor_code = d.doctor_code WHERE u.id = ?");
        $stmt_doc->bind_param("i", $slot['doctor_code']);
        $stmt_doc->execute();
        $docData = $stmt_doc->get_result()->fetch_assoc();
        $actual_doctor_code = $docData['actual_doc_code'] ?? null;

        $apptStmt = $conn->prepare("INSERT INTO appointments 
            (patient_code, slot_id, reason, doctor_code, start_time, end_time, location) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $apptStmt->bind_param("sississ", $patient_code, $slot_id, $reason, $actual_doctor_code, $slot['start_time'], $slot['end_time'], $slot['location']);

        if (!$apptStmt->execute()) {
            $conn->rollback();
            $_SESSION['error'] = "Failed to create appointment: " . $conn->error;
            header("Location: calendar_booking.php");
            exit;
        }

        $updateSlotStmt = $conn->prepare("UPDATE time_slots SET booked_count = booked_count + 1 WHERE id = ?");
        $updateSlotStmt->bind_param("i", $slot_id);

        if (!$updateSlotStmt->execute()) {
            $conn->rollback();
            $_SESSION['error'] = "Failed to update slot booking count: " . $conn->error;
            header("Location: calendar_booking.php");
            exit;
        }

        if ($slot['current_bookings'] + 1 >= $slot['capacity']) {
            $updateStatusStmt = $conn->prepare("UPDATE time_slots SET status = 'booked' WHERE id = ?");
            $updateStatusStmt->bind_param("i", $slot_id);
            $updateStatusStmt->execute();
        }

        $conn->commit();

        $doctorId = $slot['doctor_code'];
        $notificationMessage = "New appointment scheduled with patient #$patient_code for " . date('M j, Y g:i A', strtotime($slot['start_time']));
        $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'appointment')");
        $notifStmt->bind_param("is", $doctorId, $notificationMessage);
        $notifStmt->execute();

        $stmt_user = $conn->prepare("SELECT full_name as name, email FROM patients WHERE patient_code = ?");
        $stmt_user->bind_param("s", $patient_code);
        $stmt_user->execute();
        $patData = $stmt_user->get_result()->fetch_assoc();

        $doctor_name = $docData['name'] ?? 'Doctor';

        if ($patData && !empty($patData['email'])) {
            require_once '../PHPMailer/src/Exception.php';
            require_once '../PHPMailer/src/PHPMailer.php';
            require_once '../PHPMailer/src/SMTP.php';
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = 'caresyncbbsr@gmail.com'; 
                $mail->Password   = 'eptjochajicisedu'; 
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('caresyncbbsr@gmail.com', 'CareSync Hospitals');
                $mail->addAddress($patData['email'], $patData['name']);

                // Formatting
                $date_formatted = date('l, d M Y', strtotime($slot['start_time']));
                $time_formatted = date('h:i A', strtotime($slot['start_time'])) . ' - ' . date('h:i A', strtotime($slot['end_time']));

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Appointment Confirmed - CareSync';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                        <h2 style='color: #0061ff;'>CareSync Hospitals</h2>
                        <h3>Dear {$patData['name']},</h3>
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
            } catch (\Exception $e) {
                error_log("Mail Error: " . $mail->ErrorInfo);
            }
        }

        $_SESSION['success'] = "Appointment booked successfully & confirmation email dispatched.";

        header("Location: appointments.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        header("Location: calendar_booking.php");
        exit;
    }
}
