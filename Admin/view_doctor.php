<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['email'])) {
    header('location:../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Details | Admin Dashboard</title>

    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/admin_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/view_doctor.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2 text-white" style="font-family: 'Custom';">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
            <a class="nav-link active" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
            <a class="nav-link" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link" href="manage_attendee.php"><i data-lucide="user-check"></i> <span>Attendees</span></a>
            <a class="nav-link" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="records.php"><i data-lucide="file-text"></i> <span>Records</span></a>
            <a class="nav-link" href="reports.php"><i data-lucide="bar-chart-3"></i> <span>Reports</span></a>
        </nav>

        <a href="../logout.php" class="nav-link logout-link">
            <i data-lucide="log-out"></i> <span>Logout</span>
        </a>
    </div>

    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="header-section align-items-center mb-4">
                <div class="icon-box text-primary bg-primary-soft" style="width: 60px; height: 60px;">
                    <i data-lucide="stethoscope" size="32"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0 text-dark">Doctor Profile</h2>
                    <p class="text-muted mb-0">CareSync Medical Record</p>
                </div>
            </div>

            <?php
            if (!isset($_GET['id'])) {
                header('location:manage_doctor.php');
                exit();
            }

            $id = $_GET['id'];

            $qry = "SELECT * FROM doctors WHERE doctor_code = ?";
            $stmt = $conn->prepare($qry);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                ?>

                <div class="doctor-card glass-card">
                    <div class="doctor-top border-bottom pb-4 mb-4">
                        <div class="avatar shadow-sm d-flex align-items-center justify-content-center bg-primary-soft text-primary rounded-circle" style="width: 80px; height: 80px;">
                            <i data-lucide="user" size="40"></i>
                        </div>

                        <div class="doctor-name">
                            <h3 class="fw-bold text-white mb-1">
                                <?php echo htmlspecialchars($data['full_name']); ?>
                            </h3>
                            <p class="text-muted mb-0 fw-medium">Doctor ID: 
                                <span class="text-white"><?php echo htmlspecialchars($id); ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info">
                                <span><i data-lucide="mail" size="16"></i> Email</span>
                                <p><?php echo htmlspecialchars($data['email']); ?></p>
                            </div>
                            <div class="info">
                                <span><i data-lucide="phone" size="16"></i> Mobile</span>
                                <p><?php echo htmlspecialchars($data['contact']); ?></p>
                            </div>
                            <div class="info">
                                <span><i data-lucide="building" size="16"></i> Department</span>
                                <p><?php echo htmlspecialchars($data['department']); ?></p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info">
                                <span><i data-lucide="award" size="16"></i> Specialization</span>
                                <p><?php echo htmlspecialchars($data['specialization']); ?></p>
                            </div>
                            <div class="info">
                                <span><i data-lucide="star" size="16"></i> Experience</span>
                                <p><?php echo htmlspecialchars($data['experience']); ?> Years</p>
                            </div>
                        </div>
                    </div>
                <?php
            } else {
                echo "<div class='glass-card text-center p-5'><h3 class='text-danger fw-bold'>Invalid Doctor ID</h3><a href='manage_doctor.php' class='btn btn-primary mt-3'>Back to Manage Actions</a></div>";
            }
            ?>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>