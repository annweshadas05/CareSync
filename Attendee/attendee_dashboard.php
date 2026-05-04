<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['attendee_id'])) {
    header('location:../login.php');
    exit();
}


$id = $_SESSION['attendee_id'];
$qry = "SELECT * FROM attendees WHERE attendee_code=?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = ($result->num_rows > 0) ? $result->fetch_assoc() : die("Attendee not found");
// 1. Total Doctors
// $docQuery = "SELECT COUNT(*) as total FROM doctors";
// $docResult = $conn->query($docQuery);
// $docCount = $docResult->fetch_assoc()['total'];

// // 2. Total Patients
// $patQuery = "SELECT COUNT(*) as total FROM patients";
// $patResult = $conn->query($patQuery);
// $patCount = $patResult->fetch_assoc()['total'];

// // 3. Activity Logs
// $activityQuery = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5";
// $activityResult = $conn->query($activityQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareSync | Attendee Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/attendee_dashboard.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-5">
        <img src="../Assets/CareSyncLogo.png" width="40" alt="Logo">
        <h4 class="mt-2">CareSync</h4>
    </div>
    
    <nav class="nav flex-column">
        <a class="nav-link active" href="#"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="new_registration.php"><i data-lucide="user-plus"></i> <span>New Patient</span></a>
        <a class="nav-link" href="../prescription_reader/php-test-app/index.php"><i data-lucide="file-up"></i> <span>Upload Records</span></a>
        <a class="nav-link" href="queue.php"><i data-lucide="list-ordered"></i> <span>Today's Queue</span></a>
        <a href="../logout.php" class="nav-link logout-link"><i data-lucide="log-out"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Daily Overview</h2>
            <p class="text-muted">Welcome back, <?php echo explode(' ', $row['full_name'] ?? 'Attendee')[0]; ?></p>
        </div>
        <div class="glass-card py-2 px-3 d-flex align-items-center gap-3">
            <i data-lucide="bell" size="20" class="text-muted"></i>
            <img src="../icons/medical-staff.png" width="35" height="35" class="rounded-circle border">
        </div>
    </div>

    <div class="glass-card mb-5 p-5 text-white position-relative overflow-hidden" style="background: var(--blue-gradient); border: none;">
        <div class="row align-items-center position-relative" style="z-index: 2;">
            <div class="col-lg-8">
                <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill fw-bold">System Live</span>
                <h1 class="display-5 fw-bold mb-3">Pulse Analytics</h1>
                <p class="opacity-75 fs-5">There are 14 patients currently in the waiting lounge. Record digitization is 82% complete for today.</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="queue.php" class="btn btn-light rounded-pill px-5 py-2 fw-bold text-primary">View Queue</a>
                </div>
            </div>
        </div>
        <i data-lucide="activity" size="180" style="position: absolute; right: -20px; bottom: -30px; opacity: 0.1; color: white;"></i>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div class="icon-box"><i data-lucide="users" class="text-primary"></i></div>
                    <span class="badge bg-primary-soft text-primary rounded-pill">+12%</span>
                </div>
                <h6 class="text-muted fw-bold small">Checked In</h6>
                <h2 class="fw-800 mb-0">42</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card">
                <div class="icon-box mb-4"><i data-lucide="file-text" class="text-primary"></i></div>
                <h6 class="text-muted fw-bold small">Pending Scans</h6>
                <h2 class="fw-800 mb-0">12</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div class="icon-box"><i data-lucide="clock" class="text-primary"></i></div>
                    <span class="text-danger small fw-bold mt-1">High Load</span>
                </div>
                <h6 class="text-muted fw-bold small">Avg Wait Time</h6>
                <h2 class="fw-800 mb-0">12m</h2>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="glass-card h-100">
                <h5 class="fw-bold mb-4">Patient Traffic</h5>
                <canvas id="trafficChart" height="280"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card h-100">
                <h5 class="fw-bold mb-4">Task Status</h5>
                <canvas id="taskChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script>
    lucide.createIcons();

    const trafficCtx = document.getElementById('trafficChart').getContext('2d');
    new Chart(trafficCtx, {
        type: 'line',
        data: {
            labels: ['9am', '11am', '1pm', '3pm', '5pm'],
            datasets: [{
                data: [15, 35, 20, 45, 30],
                borderColor: '#0061ff',
                backgroundColor: 'rgba(0, 97, 255, 0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { display: false }, x: { grid: { display: false } } }
        }
    });

    const taskCtx = document.getElementById('taskChart').getContext('2d');
    new Chart(taskCtx, {
        type: 'doughnut',
        data: {
            labels: ['Done', 'Pending'],
            datasets: [{
                data: [82, 18],
                backgroundColor: ['#0061ff', '#e2e8f0'],
                borderWidth: 0
            }]
        },
        options: { cutout: '80%', plugins: { legend: { position: 'bottom' } } }
    });
</script>
</body>
</html>