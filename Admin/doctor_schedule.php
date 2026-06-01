<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['email'])) {
    header('location:../login.php');
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "WHERE a.status = 'confirmed'";
$params = [];
$types = "";

if ($search) {
    $whereClause .= " AND (p.name LIKE ? OR d.name LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $params = [$likeSearch, $likeSearch];
    $types = "ss";
}

$query = "
    SELECT a.id, a.status, t.start_time, d.name as doctor_name, p.name as patient_name
    FROM appointments a
    JOIN time_slots t ON a.slot_id = t.id
    JOIN users p ON a.patient_code = p.patient_code
    JOIN users d ON t.doctor_code = d.id
    $whereClause
    ORDER BY t.start_time DESC
";

$stmt = $conn->prepare($query);
if ($search) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Scheduled Appointments</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css"> 
    <link rel="stylesheet" href="../styles/admin_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2 text-white fw-bold">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
            <a class="nav-link" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link" href="manage_attendee.php"><i data-lucide="user-check"></i> <span>Attendees</span></a>
            <a class="nav-link active" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="reports.php"><i data-lucide="bar-chart-3"></i> <span>Reports</span></a>
        </nav>

        <a href="../logout.php" class="nav-link logout-link mt-auto">
            <i data-lucide="log-out"></i> <span>Logout</span>
        </a>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">Scheduled Appointments</h2>
                <p class="text-muted">Manage all confirmed appointments</p>
            </div>
            <div class="glass-card p-2 px-3 d-flex align-items-center gap-3">
                <span class="small fw-bold">Administrator</span>
                <div class="vr"></div>
                <img src="../icons/admin.png" width="35" class="rounded-circle shadow-sm" alt="Admin">
            </div>
        </div>

        <div class="glass-card mb-4 p-4 border-0 shadow-lg">
            <form method="GET" action="doctor_schedule.php" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control rounded-pill px-4 py-3 bg-light border-0 shadow-sm" placeholder="Search by doctor or patient name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold py-3 shadow-sm d-flex justify-content-center align-items-center gap-2">
                        <i data-lucide="search" size="18"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <div class="glass-card p-0 overflow-hidden shadow-lg border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 border-0">Date</th>
                            <th class="py-3 border-0">Time</th>
                            <th class="py-3 border-0">Doctor</th>
                            <th class="py-3 border-0">Patient Name</th>
                            <th class="py-3 border-0">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="py-3 text-muted"><?php echo date('d M Y', strtotime($row['start_time'])); ?></td>
                                    <td class="py-3 fw-bold text-dark"><?php echo date('h:i A', strtotime($row['start_time'])); ?></td>
                                    <td class="py-3 fw-bold text-primary">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                    <td class="py-3"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                    <td class="py-3">
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">
                                            Confirmed
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="opacity-50">
                                        <i data-lucide="calendar-x" size="48" class="mb-3 text-muted"></i>
                                        <h5>No confirmed appointments found.</h5>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>