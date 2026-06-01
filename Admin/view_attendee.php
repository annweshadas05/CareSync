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
    <title>CareSync | View Attendee</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/admin_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/view_patient.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>

<body class="admin-bg">

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2 text-white">CareSync</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
            <a class="nav-link" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link active" href="manage_attendee.php"><i data-lucide="user-check"></i> <span>Attendees</span></a>
            <a class="nav-link" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="reports.php"><i data-lucide="bar-chart-3"></i> <span>Reports</span></a>
            <a href="../logout.php" class="nav-link logout-link"><i data-lucide="log-out"></i> <span>Logout</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="header-section align-items-center mb-4">
                <div class="icon-box text-info text-opacity-75 bg-opacity-10" style="width: 60px; height: 60px;">
                    <i data-lucide="user" size="32"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">Attendee Profile</h2>
                    <p class="text-muted mb-0">Detailed view of attendee information</p>
                </div>
            </div>

            <?php
            if (!isset($_GET['id'])) {
                header('location:manage_attendee.php');
                exit();
            }

            $id = $_GET['id'];

            $qry = "SELECT * FROM attendees WHERE attendee_code = ?";
            $stmt = $conn->prepare($qry);
            $stmt->bind_param("s", $id);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                ?>

                <div class="patient-card glass-card">

                    <div class="patient-top border-bottom pb-4 mb-4">

                        <div class="avatar shadow-sm d-flex align-items-center justify-content-center bg-opacity-10 text-info rounded-circle" style="width: 80px; height: 80px;">
                            <i data-lucide="user" size="40"></i>
                        </div>

                        <div class="patient-name">
                            <h3 class="fw-bold text-white mb-1">
                                <?php echo htmlspecialchars($data['full_name']); ?>
                            </h3>
                            <p class="text-muted mb-0 fw-medium">Attendee ID: 
                                <span class="text-info"><?php echo htmlspecialchars($id); ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6 col-lg-4">
                            <div class="info">
                                <span><i data-lucide="mail"></i> Email</span>
                                <p><?php echo htmlspecialchars($data['email']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="info">
                                <span><i data-lucide="phone"></i> Mobile Number</span>
                                <p><?php echo htmlspecialchars($data['mobile']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="info">
                                <span><i data-lucide="building"></i> Hospital Branch</span>
                                <p><?php echo htmlspecialchars($data['hospital_branch']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="info">
                                <span><i data-lucide="calendar"></i> Registered On</span>
                                <p><?php echo date("d M Y", strtotime($data['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            } else {
                echo "
                <div class='glass-card text-center py-5'>
                    <i data-lucide='user-x' size='50' class='text-muted mb-3'></i>
                    <h3 class='fw-bold text-muted'>Attendee Not Found</h3>
                    <p class='text-muted'>The requested attendee record does not exist or has been removed.</p>
                    <a href='manage_attendee.php' class='btn btn-primary mt-3 px-4'>Go Back</a>
                </div>";
            }
            ?>

        </div>
    </div>
    
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>
