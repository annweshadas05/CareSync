<?php
session_start();
require_once '../dbconnect.php';

// Polyfill for connectDB() used by these files
if (!function_exists('connectDB')) {
    function connectDB() {
        global $conn;
        return $conn;
    }
}

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = connectDB();
$user_id = $_SESSION['user_id'];

$doctor_code = isset($_GET['doctor_code']) ? (int)$_GET['doctor_code'] : null;
$today = date('Y-m-d');
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : $today;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d', strtotime('+7 days'));

if ($date_from < $today) $date_from = $today;

$doctors_query = "SELECT u.id, d.full_name as username, d.specialization as specialty 
                  FROM users u 
                  JOIN doctors d ON u.doctor_code = d.doctor_code 
                  WHERE u.role = 'doctor'";
$doctors_result = $conn->query($doctors_query);
$doctors = [];
if ($doctors_result) {
    while ($doctor = $doctors_result->fetch_assoc()) $doctors[] = $doctor;
}

$query = "SELECT ts.*, d.full_name as doctor_name, d.specialization as specialty, 
          (SELECT COUNT(*) FROM appointments WHERE slot_id = ts.id) as booked_count
          FROM time_slots ts
          JOIN users u ON ts.doctor_code = u.id 
          JOIN doctors d ON u.doctor_code = d.doctor_code
          WHERE ts.status = 'available'
          AND ts.start_time BETWEEN ? AND ?
          AND ts.start_time > NOW() ";

$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
$types = "ss";

if ($doctor_code) {
    $query .= "AND ts.doctor_code = ? ";
    $params[] = $doctor_code;
    $types .= "i";
}
$query .= "ORDER BY ts.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$slots = [];

while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['start_time']));
    $slots[$date][] = $row;
}

$patient_code_session = $_SESSION['patient_id'];
$booked_query = "SELECT slot_id FROM appointments WHERE patient_code = ?";
$booked_stmt = $conn->prepare($booked_query);
$booked_stmt->bind_param("s", $patient_code_session);
$booked_stmt->execute();
$booked_result = $booked_stmt->get_result();
$booked_slots = [];
while ($booked = $booked_result->fetch_assoc()) {
    $booked_slots[] = $booked['slot_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Book Appointment</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/patient_dashboard.css?v=<?php echo time(); ?>">
     <script src="../js/lucide.js"></script>
    <style>
        .slot-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            transition: 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
        }
        .slot-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 97, 255, 0.1);
            border-left: 4px solid #0061ff;
        }
        .slot-card.booked { opacity: 0.6; border-left: 4px solid #dc3545; }
        .slot-card.past { opacity: 0.5; background: #f8fafc; }
        .slot-time { font-size: 1.25rem; font-weight: bold; color: #061727; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;}
        .slot-doctor, .slot-specialty, .slot-location { color: #64748b; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;}
        
        .slot-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(6, 23, 39, 0.6); z-index: 1050; justify-content: center; align-items: center; backdrop-filter: blur(5px);
        }
        .modal-content-custom {
            background-color: white; padding: 2rem; border-radius: 24px; width: 100%; max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease forwards;
        }
        @keyframes slideUp { from{transform: translateY(20px); opacity: 0;} to{transform: translateY(0); opacity: 1;} }
    </style>
</head>
<body>
    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="text-white fw-bold mt-2" style="font-family: 'Custom';">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="../Patient/patient_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="../Patient/search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
            <a class="nav-link active" href="appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="#"><i data-lucide="pill"></i> <span>Prescriptions</span></a>
            <a class="nav-link" href="#"><i data-lucide="file-text"></i> <span>Health Reports</span></a>
            <a href="../logout.php" class="nav-link logout-link mt-auto">
            <i data-lucide="log-out"></i> <span>Logout</span>
            </a>
        </nav>

    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Book Appointment</h2>
                <p class="text-muted">Find and reserve a slot with your preferred doctor</p>
            </div>
            <div class="d-flex gap-2">
                 <a href="appointments.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2"><i data-lucide="eye" size="18"></i> View My Appointments</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <i data-lucide="check-circle" class="me-2" size="20"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                <i data-lucide="alert-circle" class="me-2" size="20"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="glass-card mb-5">
            <form method="GET" class="row border-0">
                <div class="col-md-4 mb-3">
                    <label for="doctor_code" class="form-label fw-bold text-muted small">SELECT DOCTOR</label>
                    <select name="doctor_code" id="doctor_code" class="form-select rounded-pill px-4 py-2">
                        <option value="">All Doctors</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo $doctor_code == $doctor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doctor['username']) . ' (' . htmlspecialchars($doctor['specialty']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date_from" class="form-label fw-bold text-muted small">FROM DATE</label>
                    <input type="date" name="date_from" id="date_from" class="form-control rounded-pill px-4 py-2" value="<?php echo $date_from; ?>" min="<?php echo $today; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date_to" class="form-label fw-bold text-muted small">TO DATE</label>
                    <input type="date" name="date_to" id="date_to" class="form-control rounded-pill px-4 py-2" value="<?php echo $date_to; ?>" min="<?php echo $today; ?>">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold"><i data-lucide="filter" size="18" class="me-2"></i> Filter</button>
                </div>
            </form>
        </div>

        <div>
            <?php if (empty($slots)): ?>
                <div class="text-center py-5 opacity-50">
                    <i data-lucide="calendar-x" size="64" class="mb-3 text-muted"></i>
                    <h4>No available slots found</h4>
                    <p>Try adjusting your search filters or check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($slots as $date => $day_slots): ?>
                    <div class="mb-5">
                        <h4 class="fw-bold mb-3 d-flex align-items-center gap-2"><i data-lucide="calendar-days" class="text-primary"></i> <?php echo date('l, F j, Y', strtotime($date)); ?></h4>
                        <div class="row g-4">
                            <?php foreach ($day_slots as $slot): ?>
                                <?php
                                $is_booked = in_array($slot['id'], $booked_slots);
                                $is_full = isset($slot['capacity']) && $slot['booked_count'] >= $slot['capacity'];
                                $slot_time = strtotime($slot['start_time']);
                                $is_past = $slot_time < time();
                                ?>
                                <div class="col-md-4">
                                    <div class="slot-card <?php echo $is_booked ? 'booked' : ($is_past ? 'past' : ''); ?>">
                                        <div class="slot-time">
                                            <i data-lucide="clock" size="20"></i>
                                            <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                        </div>
                                        <div class="slot-doctor fw-bold h6 text-dark mt-3">
                                            <i data-lucide="user-round" size="18" class="text-primary"></i> Dr. <?php echo htmlspecialchars($slot['doctor_name']); ?>
                                        </div>
                                        <div class="slot-specialty text-primary small fw-bold">
                                            <i data-lucide="stethoscope" size="16"></i> <?php echo htmlspecialchars($slot['specialty']); ?>
                                        </div>
                                        <div class="slot-location small text-muted">
                                            <i data-lucide="map-pin" size="16"></i> <?php echo htmlspecialchars($slot['location']); ?>
                                        </div>

                                        <div class="mt-4">
                                        <?php if ($is_booked): ?>
                                            <button class="btn btn-outline-danger w-100 rounded-pill fw-bold" disabled>Already Booked</button>
                                        <?php elseif ($is_full): ?>
                                            <button class="btn btn-secondary w-100 rounded-pill fw-bold" disabled>Fully Booked</button>
                                        <?php elseif ($is_past): ?>
                                            <button class="btn btn-light w-100 rounded-pill fw-bold text-muted" disabled>Past Date</button>
                                        <?php else: ?>
                                            <button class="btn btn-primary w-100 rounded-pill fw-bold" onclick="openBookingModal(<?php echo $slot['id']; ?>, '<?php echo htmlspecialchars($slot['doctor_name']); ?>', '<?php echo date('l, F j, Y g:i A', strtotime($slot['start_time'])); ?>')">Book Appointment</button>
                                        <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="slot-modal" id="bookingModal">
        <div class="modal-content-custom">
            <h4 class="fw-bold mb-4 d-flex align-items-center gap-2"><i data-lucide="calendar-check" class="text-primary"></i> Confirm Appointment</h4>
            <div id="modalDetails" class="p-3 bg-light rounded-4 mb-4 border"></div>
            
            <form id="bookingForm" method="POST" action="book_appointment.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="slot_id" id="slotId">
                <div class="mb-4">
                    <label for="reason" class="form-label fw-bold text-muted small">REASON FOR VISIT (OPTIONAL)</label>
                    <textarea name="reason" id="reason" class="form-control rounded-4" rows="3" placeholder="Describe symptoms or reason..."></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="closeBookingModal()">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="submitBooking()">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        function openBookingModal(slotId, doctorName, slotTime) {
            document.getElementById('slotId').value = slotId;
            document.getElementById('modalDetails').innerHTML = `
                <div class='mb-2'><strong class='text-muted small'>DOCTOR:</strong><br><span class='h6 fw-bold'>Dr. ${doctorName}</span></div>
                <div><strong class='text-muted small'>DATE & TIME:</strong><br><span class='text-primary fw-bold'>${slotTime}</span></div>
            `;
            document.getElementById('bookingModal').style.display = 'flex';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }

        function submitBooking() {
            document.getElementById('bookingForm').submit();
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('bookingModal')) closeBookingModal();
        }
    </script>
</body>
</html>