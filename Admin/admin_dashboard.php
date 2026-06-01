<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['email'])) {
    header('location:../login.php');
    exit();
}

$docQuery = "SELECT COUNT(*) as total FROM doctors";
$docResult = $conn->query($docQuery);
$docCount = $docResult->fetch_assoc()['total'];

$patQuery = "SELECT COUNT(*) as total FROM patients";
$patResult = $conn->query($patQuery);
$patCount = $patResult->fetch_assoc()['total'];

$appQuery = "SELECT COUNT(*) as total 
             FROM appointments 
             WHERE DATE(start_time) = CURDATE()
             AND status = 'confirmed'";
$appResult = $conn->query($appQuery);
$appCount = $appResult->fetch_assoc()['total'];

$activityQuery = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5";
$activityResult = $conn->query($activityQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Admin Dashboard</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/admin_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link active" href="#"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
            <a class="nav-link" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link" href="manage_attendee.php"><i data-lucide="user-check"></i> <span>Attendees</span></a>
            <a class="nav-link" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="reports.php"><i data-lucide="bar-chart-3"></i> <span>Reports</span></a>
            <a href="../logout.php" class="nav-link logout-link">
            <i data-lucide="log-out"></i> <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">Admin Overview</h2>
                <p class="text-muted">System Management & Statistics</p>
            </div>
            <div class="glass-card p-2 px-3 d-flex align-items-center gap-3">
                <span class="small fw-bold">Administrator</span>
                <div class="vr"></div>
                <img src="../icons/admin.png" width="35" class="rounded-circle shadow-sm">
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <div class="icon-box text-primary mx-auto mb-3"><i data-lucide="stethoscope"></i></div>
                    <h3 class="fw-bold"><?php echo $docCount; ?></h3>
                    <p class="text-muted small fw-bold mb-0">TOTAL DOCTORS</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <div class="icon-box text-info mx-auto mb-3"><i data-lucide="users"></i></div>
                    <h3 class="fw-bold"><?php echo $patCount; ?></h3>
                    <p class="text-muted small fw-bold mb-0">TOTAL PATIENTS</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <div class="icon-box text-warning mx-auto mb-3"><i data-lucide="calendar-days"></i></div>
                    <h3 class="fw-bold"><?php echo $appCount; ?></h3>
                    <p class="text-muted small fw-bold mb-0">APPOINTMENTS TODAY</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">

        <div class="row g-4 mb-5">
            <div class="col-lg-4">
                <div class="glass-card h-100">
                    <h5 class="fw-bold mb-4">Quick Actions</h5>
                    <div class="d-grid gap-3">
                        <a href="./add_doctor.php" class="action-btn text-center">Add New Doctor</a>
                        <a href="./add_patient.php" class="btn btn-outline-primary rounded-pill py-2 fw-bold">Add New Patient</a>
                        <a href="./add_attendee.php" class="action-btn text-center">Add New Attendee</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="glass-card h-100">
                    <h5 class="fw-bold mb-4">Recent System Activity</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">Activity</th>
                                    <th class="border-0">User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($activityResult->num_rows > 0) {
                                    while ($row = $activityResult->fetch_assoc()) {
                                        echo "<tr>
                                                <td class='small'>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                                                <td><span class='badge bg-primary-soft text-primary'>" . $row['activity'] . "</span></td>
                                                <td class='fw-bold small'>" . $row['user'] . "</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-4'>No Activity Found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Icons
        lucide.createIcons();
    </script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>