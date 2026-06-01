<?php
session_start();
require_once '../dbconnect.php';

// Polyfill for connectDB
if (!function_exists('connectDB')) {
    function connectDB() {
        global $conn;
        return $conn;
    }
}

// Security: Check if user is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['doctor_id']) || $_SESSION['role'] !== 'doctor') {
    header('location:../login.php');
    exit();
}

$conn = connectDB();
$doctor_user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Form Submission for Creating a New Slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_slot') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed.";
    } else {
        $slot_date = $_POST['slot_date'];
        $start_time_input = $_POST['start_time'];
        $end_time_input = $_POST['end_time'];
        $capacity = (int)$_POST['capacity'];
        $location = trim($_POST['location']);

        // Validate time
        if (strtotime($end_time_input) <= strtotime($start_time_input)) {
            $error_msg = "End time must be after start time.";
        } else {
            // Combine date and time
            $start_datetime = date('Y-m-d H:i:s', strtotime("$slot_date $start_time_input"));
            $end_datetime = date('Y-m-d H:i:s', strtotime("$slot_date $end_time_input"));

            if (strtotime($start_datetime) < time()) {
                $error_msg = "Cannot create slots in the past.";
            } else {
                try {
                    $insertStmt = $conn->prepare("INSERT INTO time_slots (doctor_code, start_time, end_time, status, location, capacity, booked_count) VALUES (?, ?, ?, 'available', ?, ?, 0)");
                    $insertStmt->bind_param("isssi", $doctor_user_id, $start_datetime, $end_datetime, $location, $capacity);
                    
                    if ($insertStmt->execute()) {
                        $success_msg = "New time slot successfully created!";
                    } else {
                        throw new Exception("Error saving to database.");
                    }
                } catch(Exception $e) {
                    $error_msg = "Failed to create slot: " . $e->getMessage();
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_slot') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed.";
    } else {
        $del_id = (int)$_POST['slot_id'];
        
        $checkStmt = $conn->prepare("SELECT booked_count FROM time_slots WHERE id = ? AND doctor_code = ?");
        $checkStmt->bind_param("ii", $del_id, $doctor_user_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['booked_count'] > 0) {
                $error_msg = "Cannot delete this slot! It already has active reservations.";
            } else {
                $delStmt = $conn->prepare("DELETE FROM time_slots WHERE id = ?");
                $delStmt->bind_param("i", $del_id);
                if ($delStmt->execute()) {
                    $success_msg = "Slot deleted successfully.";
                }
            }
        }
    }
}

// Fetch Doctor's Current & Future Slots
$slots = [];
try {
    $slotQuery = "SELECT * FROM time_slots WHERE doctor_code = ? AND start_time >= CURRENT_DATE() ORDER BY start_time ASC";
    $stmt = $conn->prepare($slotQuery);
    $stmt->bind_param("i", $doctor_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $date_key = date('Y-m-d', strtotime($row['start_time']));
        $slots[$date_key][] = $row;
    }
} catch(Exception $e) {
    $error_msg = "Failed to load active schedule.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareSync | Manage Schedule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/doctor_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
    <style>
        .schedule-card {
            background: white; border-radius: 16px; padding: 1.5rem; transition: 0.3s;
            border: 1px solid rgba(0,0,0,0.05); height: 100%;
        }
        .schedule-card:hover {  box-shadow: 0 10px 20px rgba(0, 97, 255, 0.1); border-left: 4px solid var(--primary-blue, #0061ff); }
        .schedule-card.status-booked { border-left: 4px solid #dc3545; }
        .schedule-card.status-available { border-left: 4px solid #198754; }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-5">
        <img src="../Assets/CareSyncLogo.png" width="40" alt="Logo">
        <h4 class="text-white fw-bold mt-2" style="font-family: 'Custom';">CareSync</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="doctor_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
        <a class="nav-link active" href="manage_schedule.php"><i data-lucide="clock"></i> <span>My Schedule</span></a> 
        <a class="nav-link" href="#"><i data-lucide="clipboard-list"></i> <span>Notes</span></a>
        <a href="../logout.php" class="nav-link logout-link" style="color: #ff4d4d !important;">
            <i data-lucide="log-out"></i> <span>Logout</span>
        </a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Manage My Schedule</h2>
            <p class="text-muted">Control your active timeslots to allow patient bookings</p>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center"><i data-lucide="check-circle" class="me-2"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center"><i data-lucide="alert-circle" class="me-2"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="glass-card mb-5 p-4">
        <h5 class="fw-bold mb-4 d-flex align-items-center gap-2"><i data-lucide="calendar-plus" class="text-primary"></i> Create Available Slot</h5>
        <form method="POST">
            <input type="hidden" name="action" value="create_slot">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold">DATE</label>
                    <input type="date" name="slot_date" class="form-control rounded-pill px-3" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold">START TIME</label>
                    <input type="time" name="start_time" class="form-control rounded-pill px-3" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold">END TIME</label>
                    <input type="time" name="end_time" class="form-control rounded-pill px-3" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold">CAPACITY (Pats)</label>
                    <input type="number" name="capacity" class="form-control rounded-pill px-3" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold">VENUE / LOCATION</label>
                    <input type="text" name="location" class="form-control rounded-pill px-3" value="CareSync Clinic Room A" required>
                </div>
            </div>
            
            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                    Publish Availability To Portal
                </button>
            </div>
        </form>
    </div>

    <!-- SCHEDULE TIMELINE -->
    <div>
        <h4 class="fw-bold mb-4 border-bottom pb-3">My Upcoming Slots</h4>
        
        <?php if (empty($slots)): ?>
            <div class="text-center py-5 opacity-50">
                <i data-lucide="inbox" size="64" class="mb-3 text-muted"></i>
                <h5>No slots generated</h5>
                <p>Use the form above to add blocks of availability into your calendar.</p>
            </div>
        <?php else: ?>
            <?php foreach ($slots as $date => $day_slots): ?>
                <div class="mb-5">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2"><i data-lucide="calendar-days" class="text-primary"></i> <?php echo date('l, d M Y', strtotime($date)); ?></h5>
                    <div class="row g-3">
                        <?php foreach ($day_slots as $slot): ?>
                            <div class="col-md-4">
                                <div class="schedule-card status-<?php echo strtolower($slot['status']); ?>">
                                    <div class="d-flex justify-content-between">
                                        <div class="fw-bold h5 mb-1"><i data-lucide="clock" size="18" class="text-muted"></i> <?php echo date('g:i A', strtotime($slot['start_time'])); ?></div>
                                        <span class="badge bg-<?php echo $slot['status'] == 'available' ? 'success' : 'danger'; ?> rounded-pill mb-auto">
                                            <?php echo ucfirst($slot['status']); ?>
                                        </span>
                                    </div>
                                    <div class="text-muted small mb-2"><i data-lucide="map-pin" size="14"></i> <?php echo htmlspecialchars($slot['location']); ?></div>
                                    <div class="text-muted small fw-bold mb-3"><i data-lucide="users" size="14"></i> Booked: <?php echo $slot['booked_count']; ?> / <?php echo $slot['capacity']; ?></div>
                                    
                                    <?php if ($slot['booked_count'] == 0 && $slot['status'] == 'available'): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this available slot?');">
                                        <input type="hidden" name="action" value="delete_slot">
                                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill w-100 fw-bold">
                                            <i data-lucide="trash-2" size="16" class="me-1"></i> Delete Slot
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-light btn-sm rounded-pill w-100 text-muted fw-bold" disabled>Cannot Outline Actively Booked Slot</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script src="../Bootstrap/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();
    setTimeout(() => {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => alert.remove());
    }, 4500);
</script>
</body>
</html>
