<?php
session_start();
include __DIR__ . '/../dbconnect.php';

if (!isset($_SESSION['patient_id'])) {
    header('location:../login.php');
    exit();
}

// Fetch appointments using the correct schema
$query = "SELECT a.*, d.full_name, d.department 
          FROM appointments a
          JOIN doctors d ON d.doctor_code = a.doctor_code
          WHERE a.patient_code = ? AND a.start_time >= NOW()
          ORDER BY a.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $_SESSION['patient_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Appointments - CareSync</title>
    <link href="../Bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/patient_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>

<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="text-white fw-bold mt-2" style="font-family: 'Custom';">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="./patient_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="./search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
            <a class="nav-link active" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="medical_records.php"><i data-lucide="pill"></i> <span>Prescriptions</span></a>
            <a class="nav-link" href="medical_records.php"><i data-lucide="file-text"></i> <span>Health Reports</span></a>
        </nav>

        <a href="../logout.php" class="nav-link logout-link">
            <i data-lucide="log-out"></i> <span>Logout</span>
        </a>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">My Appointments</h2>
                <p class="text-muted">Track and manage your upcoming visits securely.</p>
            </div>
            <a href="book_appointment.php" class="btn rounded-pill px-4 py-2 fw-bold shadow text-white" style="background: var(--blue-gradient);">
                <i data-lucide="calendar-plus" class="me-2" style="width: 18px; height: 18px; vertical-align: text-bottom;"></i> Book New Appointment
            </a>
        </div>

        <div class="container-fluid py-2">
            
            <div class="glass-card p-0 overflow-hidden border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="py-3 px-4 fw-semibold border-bottom-0">Date & Time</th>
                                <th class="py-3 px-4 fw-semibold border-bottom-0">Doctor</th>
                                <th class="py-3 px-4 fw-semibold border-bottom-0">Department</th>
                                <th class="py-3 px-4 fw-semibold border-bottom-0 text-center">Status</th>
                                <th class="py-3 px-4 fw-semibold border-bottom-0 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php if($result->num_rows > 0) { ?>
                            <?php while($row = $result->fetch_assoc()) { ?>

                            <tr>
                                <td class="py-3 px-4">
                                    <div class="fw-bold text-dark"><?= date("d M Y", strtotime($row['start_time'])) ?></div>
                                    <div class="text-muted small"><i data-lucide="clock" size="14"></i> <?= date("h:i A", strtotime($row['start_time'])) ?> - <?= date("h:i A", strtotime($row['end_time'])) ?></div>
                                </td>
                                <td class="py-3 px-4 fw-bold text-primary">Dr <?= htmlspecialchars($row['full_name']) ?></td>
                                <td class="py-3 px-4"><span class="badge bg-light text-secondary border"><?= htmlspecialchars($row['department']) ?></span></td>

                                <td class="py-3 px-4 text-center">
                                    <?php if(strtolower($row['status']) == 'completed') { ?>
                                        <span class="badge bg-success rounded-pill px-3 py-2">Completed</span>
                                    <?php } elseif(strtolower($row['status']) == 'cancelled') { ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-2">Cancelled</span>
                                    <?php } else { ?>
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-2">Upcoming</span>
                                    <?php } ?>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <?php if(strtolower($row['status']) == 'upcoming' || strtolower($row['status']) == 'confirmed') { ?>
                                        <a href="cancel_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                            Cancel
                                        </a>
                                    <?php } else { ?>
                                        <span class="text-muted opacity-50">-</span>
                                    <?php } ?>
                                </td>
                            </tr>

                            <?php } ?>
                        <?php } else { ?>

                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i data-lucide="calendar-x" size="40" class="mb-3 opacity-50"></i><br>
                                    <span class="fw-medium">No appointments found.</span>
                                </td>
                            </tr>

                        <?php } ?>

                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>