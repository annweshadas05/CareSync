<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['attendee_id'])) {
    header('location:../login.php');
    exit();
}

$attendee_code = $_SESSION['attendee_id'];

$a_qry = "SELECT * FROM attendees WHERE attendee_code=?";
$stmt = $conn->prepare($a_qry);
$stmt->bind_param("s", $attendee_code);
$stmt->execute();
$attendee = $stmt->get_result()->fetch_assoc() ?: die("Attendee not found");


$today_pres_qry = "SELECT COUNT(*) as count FROM prescription_medicines WHERE DATE(created_at) = CURDATE()";
$today_pres_count = $conn->query($today_pres_qry)->fetch_assoc()['count'];

$today_vitals_qry = "SELECT COUNT(*) as count FROM vitals WHERE DATE(recorded_at) = CURDATE()";
$today_vitals_count = $conn->query($today_vitals_qry)->fetch_assoc()['count'];

$total_pres_qry = "SELECT COUNT(*) as count FROM prescription_medicines";
$total_pres_count = $conn->query($total_pres_qry)->fetch_assoc()['count'];

$activity_qry = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5";
$activities = $conn->query($activity_qry)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareSync | Attendee Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/attendee_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-5">
        <img src="../Assets/CareSyncLogo.png" width="40" alt="Logo">
        <h4 class="mt-2">CareSync</h4>
    </div>
    
    <nav class="nav flex-column">
        <a class="nav-link active" href="attendee_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="../prescription_reader/php-test-app/index.php"><i data-lucide="file-up"></i> <span>Upload Records</span></a>
        <a class="nav-link" href="enter_vitals.php"><i data-lucide="activity"></i> <span>Enter Vitals</span></a>
        <a href="../logout.php" class="nav-link logout-link"><i data-lucide="log-out"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Daily Overview</h2>
            <p class="text-muted">Welcome back, <?php echo explode(' ', $attendee['full_name'])[0]; ?></p>
        </div>
        <div class="glass-card py-2 px-3 d-flex align-items-center gap-3 shadow-sm">
            <i data-lucide="bell" size="20" class="text-primary"></i>
            <img src="../icons/medical-staff.png" width="35" height="35" class="rounded-circle border">
        </div>
    </div>

    <div class="glass-card mb-5 p-5 text-white position-relative overflow-hidden shadow-lg" style="background: var(--blue-gradient); border: none; border-radius: 30px;">
        <div class="row align-items-center position-relative" style="z-index: 2;">
            <div class="col-lg-8">
                <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill fw-bold">Digitization Hub</span>
                <h1 class="display-5 fw-bold mb-3">Record Management</h1>
                <p class="opacity-75 fs-5">You have processed <?php echo $today_pres_count; ?> prescriptions today. <?php echo $today_vitals_count; ?> vital records have been digitized.</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="../prescription_reader/php-test-app/index.php" class="btn btn-light rounded-pill px-5 py-2 fw-bold text-primary shadow-sm">Upload Prescriptions</a>
                </div>
            </div>
        </div>
        <i data-lucide="shield-check" size="180" style="position: absolute; right: -20px; bottom: -30px; opacity: 0.1; color: white;"></i>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card p-4 shadow-sm border-0">
                <div class="icon-box mb-3 text-primary"><i data-lucide="file-up"></i></div>
                <h6 class="text-muted fw-bold small">Prescriptions Today</h6>
                <h2 class="fw-800 mb-0"><?php echo sprintf("%02d", $today_pres_count); ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 shadow-sm border-0">
                <div class="icon-box mb-3 text-info"><i data-lucide="activity"></i></div>
                <h6 class="text-muted fw-bold small">Vitals Captured</h6>
                <h2 class="fw-800 mb-0"><?php echo sprintf("%02d", $today_vitals_count); ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 shadow-sm border-0">
                <div class="icon-box mb-3 text-success"><i data-lucide="database"></i></div>
                <h6 class="text-muted fw-bold small">Total Digital Records</h6>
                <h2 class="fw-800 mb-0"><?php echo sprintf("%02d", $total_pres_count); ?></h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="glass-card p-4 shadow-sm border-0">
                <h5 class="fw-bold mb-4">Recent System Activity</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>User Role</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($activities) > 0): ?>
                                <?php foreach ($activities as $act): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="icon-sm bg-light rounded-circle p-1"><i data-lucide="info" size="14"></i></div>
                                                <span class="fw-bold small"><?php echo htmlspecialchars($act['activity']); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark tiny"><?php echo htmlspecialchars($act['user']); ?></span></td>
                                        <td class="text-muted small"><?php echo date('d M, Y | H:i', strtotime($act['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-5 small text-muted">No recent logs</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>