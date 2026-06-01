<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['patient_id'])) {
    header('location:../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('location:search_doctor.php');
    exit();
}

$doctor_id = $_GET['id'];
$qry = "SELECT * FROM doctors WHERE doctor_code = ?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<h3 class='text-center mt-5'>Invalid Doctor ID</h3>";
    exit();
}
$doctor = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Doctor Profile</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/patient_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/view_doctor.css?v=<?php echo time(); ?>">
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
            <a class="nav-link active" href="./search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
            <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
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
                <h2 class="fw-bold mb-0">Doctor Profile</h2>
                <p class="text-muted">Detailed background and expertise</p>
            </div>
            <a href="search_doctor.php" class="btn border border-primary text-primary rounded-pill px-4 fw-bold shadow-sm" style="background-color: white;">
                <i data-lucide="arrow-left" class="me-2" style="width: 18px; height: 18px; vertical-align: text-bottom;"></i> Back to Search
            </a>
        </div>

        <div class="container-fluid py-2">
            
            <div class="glass-card p-5 mx-auto" style="max-width: 900px; background: rgba(255, 255, 255, 0.9);">
                <div class="doctor-top border-bottom pb-4 mb-4 d-flex align-items-center gap-4">
                    <div class="avatar shadow d-flex align-items-center justify-content-center text-white rounded-circle" style="width: 100px; height: 100px; background: var(--blue-gradient);">
                        <i data-lucide="user" size="50"></i>
                    </div>

                    <div class="doctor-name">
                        <h2 class="fw-bold text-primary mb-1">
                            Dr. <?php echo htmlspecialchars($doctor['full_name']); ?>
                        </h2>
                        <span class="badge" style="background: var(--blue-gradient); border-radius: 20px; padding: 6px 12px; margin-bottom: 8px;">
                            <?php echo htmlspecialchars($doctor['department']); ?>
                        </span>
                        <p class="text-muted mb-0 fw-medium mt-2">
                            <?php echo htmlspecialchars($doctor['specialization']); ?> | <?php echo htmlspecialchars($doctor['experience']); ?> Years Experience
                        </p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: #64748b;">Contact Details</h5>
                        <div class="info">
                            <span><i data-lucide="mail" size="16"></i> Email Address</span>
                            <p><?php echo htmlspecialchars($doctor['email']); ?></p>
                        </div>
                        <div class="info mt-3">
                            <span><i data-lucide="phone" size="16"></i> Contact Number</span>
                            <p><?php echo htmlspecialchars($doctor['contact']); ?></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: #64748b;">Professional Info</h5>
                        <div class="info">
                            <span><i data-lucide="building" size="16"></i> Department</span>
                            <p><?php echo htmlspecialchars($doctor['department']); ?></p>
                        </div>
                        <div class="info mt-3">
                            <span><i data-lucide="star" size="16"></i> Specialization</span>
                            <p><?php echo htmlspecialchars($doctor['specialization'] ?: 'Not Specified'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <a href="./book_appointment.php?doctor_id=<?php echo urlencode($doctor['id']); ?>" class="btn rounded-pill px-5 py-3 fw-bold shadow border-0 text-white" style="background: var(--blue-gradient);">
                        <i data-lucide="calendar-check" class="me-2" style="vertical-align: text-bottom;"></i> Book Appointment
                    </a>
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
