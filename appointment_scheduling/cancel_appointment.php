<?php
session_start();
require_once '../dbconnect.php';

if (!function_exists('connectDB')) {
    function connectDB() {
        global $conn;
        return $conn;
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success_message = "";
$error_message = "";

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid form submission.";
    } else {
        $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
        $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : '';

        if ($appointment_id <= 0) {
            $error_message = "Invalid appointment ID.";
        } else {
            try {
                $conn->begin_transaction();
                $stmt = $conn->prepare("
                    SELECT a.*, t.start_time, t.doctor_code, 
                           d.name as doctor_name, p.name as patient_name
                    FROM appointments a
                    JOIN time_slots t ON a.slot_id = t.id
                    JOIN users d ON t.doctor_code = d.id
                    JOIN users p ON a.patient_code = p.id
                    WHERE a.id = ? AND (a.patient_code = ? OR t.doctor_code = ?) AND a.status = 'confirmed'
                ");
                $stmt->bind_param("iii", $appointment_id, $user_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) throw new Exception("Appointment not found or already cancelled.");

                $appointment = $result->fetch_assoc();
                $canceller_type = ($appointment['patient_code'] == $user_id) ? 'patient' : 'doctor';
                $canceller_name = ($canceller_type == 'patient') ? $appointment['patient_name'] : $appointment['doctor_name'];

                $update = $conn->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                $update->bind_param("i", $appointment_id);
                $update->execute();

                $notification_recipient_id = ($canceller_type == 'patient') ? $appointment['doctor_code'] : $appointment['patient_code'];
                $appointment_date = date('l, F j, Y', strtotime($appointment['start_time']));
                $appointment_time = date('g:i A', strtotime($appointment['start_time']));
                
                $reason_text = !empty($cancellation_reason) ? " Reason: " . $cancellation_reason : "";
                $notification_message = "Your appointment on {$appointment_date} at {$appointment_time} has been cancelled by {$canceller_name}.{$reason_text}";

                $notify = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'appointment')");
                $notify->bind_param("is", $notification_recipient_id, $notification_message);
                $notify->execute();

                $free_slot = $conn->prepare("UPDATE time_slots SET status = 'available', booked_count = GREATEST(booked_count - 1, 0) WHERE id = ?");
                $free_slot->bind_param("i", $appointment['slot_id']);
                $free_slot->execute();

                $conn->commit();
                $success_message = "Appointment cancelled successfully. A notification has been sent.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        }
    }
}

$upcoming_appointments = [];
try {
    if ($role === 'patient') {
        $stmt = $conn->prepare("SELECT a.id, a.status, t.start_time, t.location, u.name as doctor_name FROM appointments a JOIN time_slots t ON a.slot_id = t.id JOIN users u ON t.doctor_code = u.id WHERE a.patient_code = ? AND a.status = 'confirmed' AND t.start_time > NOW() ORDER BY t.start_time ASC");
    } else {
        $stmt = $conn->prepare("SELECT a.id, a.status, t.start_time, t.location, u.name as patient_name FROM appointments a JOIN time_slots t ON a.slot_id = t.id JOIN users u ON a.patient_code = u.id WHERE t.doctor_code = ? AND a.status = 'confirmed' AND t.start_time > NOW() ORDER BY t.start_time ASC");
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $upcoming_appointments[] = $row;
} catch (Exception $e) {
    $error_message = "Error fetching appointments: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareSync | Cancel Appointment</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/<?php echo $role === 'doctor' ? 'doctor' : 'patient'; ?>_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="text-white fw-bold mt-2" style="font-family: 'Custom';">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
        <?php if ($role === 'doctor'): ?>
            <a class="nav-link" href="../Doctor/doctor_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="#"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link" href="#"><i data-lucide="clipboard-list"></i> <span>Notes</span></a>
        <?php else: ?>
            <a class="nav-link" href="../Patient/patient_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="../Patient/search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
            <a class="nav-link" href="appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="#"><i data-lucide="pill"></i> <span>Prescriptions</span></a>
            <a class="nav-link" href="#"><i data-lucide="file-text"></i> <span>Health Reports</span></a>
        <?php endif; ?>
        </nav>

        <a href="../logout.php" class="nav-link logout-link mt-auto">
            <i data-lucide="log-out"></i> <span>Logout</span>
        </a>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0 text-danger">Cancel Consultation</h2>
                <p class="text-muted">Select an upcoming appointment to release the slot</p>
            </div>
            <a href="appointments.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">Back to Log</a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success rounded-4"><i data-lucide="check-circle"></i> <?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger rounded-4"><i data-lucide="alert-triangle"></i> <?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="glass-card mb-5">
            <h5 class="fw-bold mb-4">Select Slot Options</h5>
            <?php if (empty($upcoming_appointments)): ?>
                <div class="text-center py-4 bg-light rounded-4">
                    <i data-lucide="calendar-check-2" class="text-muted mb-2" size="48"></i>
                    <p class="text-muted fw-bold">No upcoming appointments found to cancel.</p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="cancel_appointment" value="1">

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small">UPCOMING APPOINTMENT</label>
                            <select name="appointment_id" class="form-select rounded-pill px-4 py-2" required>
                                <option value="">-- Choose one --</option>
                                <?php foreach ($upcoming_appointments as $apt): ?>
                                    <?php 
                                    $with = isset($apt['doctor_name']) ? 'Dr. ' . htmlspecialchars($apt['doctor_name']) : htmlspecialchars($apt['patient_name']);
                                    $dateStr = date('M j, Y g:i A', strtotime($apt['start_time']));
                                    ?>
                                    <option value="<?= $apt['id'] ?>">
                                        <?= $dateStr ?> with <?= $with ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small">CANCELLATION REASON (OPT)</label>
                            <input type="text" name="cancellation_reason" class="form-control rounded-pill px-4 py-2" placeholder="Brief reason...">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger rounded-pill px-5 py-2 fw-bold" onclick="return confirm('Please confirm you want to release this slot')">
                        <i data-lucide="x-circle" size="18" class="me-2"></i> Cancel Appointment
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>