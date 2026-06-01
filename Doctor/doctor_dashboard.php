<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['doctor_id'])) {
    header('location:../login.php');
    exit();
}

$doctor_code = $_SESSION['doctor_id'];

// Get user_id for the doctor from users table (since appointments table uses users.id)
$user_qry = "SELECT id FROM users WHERE doctor_code = ?";
$u_stmt = $conn->prepare($user_qry);
$u_stmt->bind_param("s", $doctor_code);
$u_stmt->execute();
$user_id = $u_stmt->get_result()->fetch_assoc()['id'] ?? die("User account not found");

// Fetch doctor details
$d_qry = "SELECT * FROM doctors WHERE doctor_code=?";
$stmt = $conn->prepare($d_qry);
$stmt->bind_param("s", $doctor_code);
$stmt->execute();
$doctor_row = $stmt->get_result()->fetch_assoc() ?: die("Doctor profile not found");

// Fetch today's consultations
$todayQry = "SELECT a.id, a.patient_code, a.doctor_code, a.start_time, a.reason, a.status, 
                    p.full_name as patient_name
             FROM appointments a
             JOIN patients p ON a.patient_code = p.patient_code
             WHERE a.doctor_code = ? AND DATE(a.start_time) = CURDATE() AND a.status != 'cancelled'
             ORDER BY a.start_time ASC";
$stmt = $conn->prepare($todayQry);
$stmt->bind_param("s", $doctor_code);
$stmt->execute();
$todayAppointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- DYNAMIC STATS ---
// 1. Today's Visits
$today_count = count($todayAppointments);

// 2. New Patients (registered in last 30 days)
$new_patients_qry = "SELECT COUNT(*) as count FROM patients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$new_patients_count = $conn->query($new_patients_qry)->fetch_assoc()['count'];

// 3. Pending Appointments (Total pending across all dates for this doctor)
$pending_qry = "SELECT COUNT(*) as count FROM appointments WHERE doctor_code = ? AND status = 'pending'";
$p_stmt = $conn->prepare($pending_qry);
$p_stmt->bind_param("s", $doctor_code);
$p_stmt->execute();
$pending_count = $p_stmt->get_result()->fetch_assoc()['count'];

// 4. Notifications (latest 5)
$notif_qry = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$n_stmt = $conn->prepare($notif_qry);
$n_stmt->bind_param("i", $user_id);
$n_stmt->execute();
$notifications = $n_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareSync | Premium Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/doctor_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-5">
        <img src="../Assets/CareSyncLogo.png" width="40" alt="Logo">
        <h4 class="text-white fw-bold mt-2">CareSync</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link active" href="#"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
        <a class="nav-link" href="manage_schedule.php"><i data-lucide="clock"></i> <span>My Schedule</span></a>
        <a class="nav-link" href="view_patient_records.php"><i data-lucide="users"></i> <span>Patient Records</span></a>
        <a class="nav-link" href="#"><i data-lucide="clipboard-list"></i> <span>Medical Notes</span></a>
        <a href="../logout.php" class="nav-link logout-link" style="color: #ff4d4d !important;">
            <i data-lucide="log-out"></i> <span>Logout</span>
        </a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Daily Overview</h2>
            <p class="text-muted">Welcome back, Dr. <?php echo explode(' ', $doctor_row['full_name'])[0]; ?></p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="glass-card p-2 px-3 d-flex align-items-center gap-2">
                <i data-lucide="bell" size="18"></i>
                <div class="profile-pic">
                    <img src="../icons/doctor.png" width="35" class="rounded-circle shadow-sm">
                </div>
            </div>
        </div>
    </div>

    <div class="welcome-banner mb-5 shadow-lg">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-glass mb-3 px-3 py-2">System Live</span>
                <h1 class="fw-800 display-5">Pulse Analytics</h1>
                <p class="opacity-75 fs-5">You have <?php echo count($todayAppointments); ?> consultations today. Your efficiency rating is up by 14% compared to last week.</p>
                <button class="btn btn-glass rounded-pill px-5 py-2 fw-bold mt-3" data-bs-toggle="modal" data-bs-target="#scheduleModal">View Full Schedule</button>
            </div>
        </div>
        <i data-lucide="activity" size="200"></i>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card p-4 border-0">
                <div class="d-flex justify-content-between mb-3">
                    <div class="icon-box text-primary"><i data-lucide="calendar-check"></i></div>
                </div>
                <h6 class="text-muted fw-bold">Today's Visits</h6>
                <div class="stat-value"><?php echo sprintf("%02d", $today_count); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 border-0">
                <div class="d-flex justify-content-between mb-3">
                    <div class="icon-box text-info"><i data-lucide="user-plus"></i></div>
                </div>
                <h6 class="text-muted fw-bold">New Patients (30d)</h6>
                <div class="stat-value"><?php echo sprintf("%02d", $new_patients_count); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 border-0">
                <div class="d-flex justify-content-between mb-3">
                    <div class="icon-box text-danger"><i data-lucide="clock"></i></div>
                </div>
                <h6 class="text-muted fw-bold">Pending Appointments</h6>
                <div class="stat-value"><?php echo sprintf("%02d", $pending_count); ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="glass-card p-4 border-0 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Upcoming Consultations</h5>
                    <button class="btn btn-link text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#scheduleModal">View All</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($todayAppointments) > 0): ?>
                                <?php foreach (array_slice($todayAppointments, 0, 4) as $appt): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs" style="background: var(--primary-blue); width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; color: white; border-radius: 50%; font-size: 10px; font-weight: bold;">
                                                    <?php echo strtoupper(substr($appt['patient_name'], 0, 2)); ?>
                                                </div>
                                                <span class="fw-bold small"><?php echo htmlspecialchars($appt['patient_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-muted small"><?php echo date('H:i A', strtotime($appt['start_time'])); ?></td>
                                        <td>
                                            <?php if ($appt['status'] === 'confirmed'): ?>
                                                <span class="badge bg-soft-success tiny">Confirmed</span>
                                            <?php else: ?>
                                                <span class="badge bg-light tiny"><?php echo ucfirst($appt['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><button class="btn btn-primary btn-sm rounded-pill px-2 py-0 small" style="font-size: 11px;">View</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4 small">No sessions today</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card p-4 border-0 h-100">
                <h5 class="fw-bold mb-4">Notifications</h5>
                <div class="notification-list">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="d-flex gap-3 mb-3 pb-3 border-bottom last-child-border-0">
                                <div class="icon-sm text-primary"><i data-lucide="bell" size="16"></i></div>
                                <div>
                                    <p class="mb-0 small fw-bold"><?php echo htmlspecialchars($n['message']); ?></p>
                                    <span class="text-muted tiny"><?php echo date('d M | H:i', strtotime($n['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-5 small">No new alerts</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0" style="background: var(--glass-bg); backdrop-filter: blur(20px);">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Today's Schedule</h5>
                    <p class="text-muted small mb-0"><?php echo date('d M, Y'); ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Buttons -->
                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-sm rounded-pill px-4 filter-btn active" data-filter="all" style="background: var(--primary-blue); color: white;">
                        All
                    </button>
                    <button class="btn btn-sm rounded-pill px-4 filter-btn" data-filter="diagnosed" style="background: #dcfce7; color: #15803d;">
                        Diagnosed
                    </button>
                    <button class="btn btn-sm rounded-pill px-4 filter-btn" data-filter="undiagnosed" style="background: #fee2e2; color: #991b1b;">
                        Undiagnosed
                    </button>
                </div>

                <!-- Consultations List -->
                <div id="consultationsList" style="max-height: 400px; overflow-y: auto;">
                    <?php if (count($todayAppointments) > 0): ?>
                        <?php foreach ($todayAppointments as $appt): ?>
                            <div class="consultation-item mb-3 p-3 rounded-3 border" 
                                 data-status="<?php echo ($appt['status'] === 'completed') ? 'diagnosed' : 'undiagnosed'; ?>"
                                 style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                
                                <!-- Undiagnosed Blur Layer -->
                                <div class="undiagnosed-blur" 
                                     style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); backdrop-filter: blur(5px); border-radius: 0.75rem; display: none; z-index: 10;"></div>
                                
                                <div class="row align-items-center" style="position: relative; z-index: 1;">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-sm" style="background: var(--primary-blue); width: 45px; height: 45px;">
                                                <?php echo strtoupper(substr($appt['patient_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($appt['patient_name']); ?></h6>
                                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($appt['reason'] ?: 'General Checkup'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-0 text-muted">
                                            <i data-lucide="clock" size="14" style="display: inline;"></i>
                                            <?php echo date('H:i A', strtotime($appt['start_time'])); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <?php if ($appt['status'] === 'completed'): ?>
                                            <span class="badge bg-light text-success">Completed</span>
                                        <?php elseif ($appt['status'] === 'confirmed'): ?>
                                            <span class="badge bg-light text-info">Confirmed</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-danger">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i data-lucide="inbox" size="48" class="text-muted mb-3"></i>
                            <p class="text-muted">No consultations scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    // Filter Buttons Functionality
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            const items = document.querySelectorAll('.consultation-item');
            
            items.forEach(item => {
                const status = item.dataset.status;
                const blurLayer = item.querySelector('.undiagnosed-blur');
                
                if (filter === 'all') {
                    item.style.display = 'block';
                    if (status === 'undiagnosed') {
                        blurLayer.style.display = 'block';
                    } else {
                        blurLayer.style.display = 'none';
                    }
                } else if (filter === 'diagnosed') {
                    item.style.display = status === 'diagnosed' ? 'block' : 'none';
                    blurLayer.style.display = 'none';
                } else if (filter === 'undiagnosed') {
                    item.style.display = status === 'undiagnosed' ? 'block' : 'none';
                    blurLayer.style.display = 'block';
                }
            });
        });
    });

    // Initial blur on undiagnosed items in "All" view
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.consultation-item').forEach(item => {
            const status = item.dataset.status;
            if (status === 'undiagnosed') {
                item.querySelector('.undiagnosed-blur').style.display = 'block';
            }
        });
    });
</script>
</body>
</html>