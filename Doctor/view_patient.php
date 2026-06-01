<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['doctor_id'])) {
    header('location:../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details | Doctor Dashboard</title>

    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/doctor_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/view_patient.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>

<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2 text-white" style="font-family: 'Custom';">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="doctor_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
            <a class="nav-link active" href="look_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a href="../logout.php" class="nav-link logout-link text-danger fw-bold"><i data-lucide="log-out"></i> <span>Logout</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container-fluid py-4">

            <div class="header-section align-items-center mb-4">
                <div class="icon-box text-info text-opacity-75 bg-opacity-10" style="width: 60px; height: 60px;">
                    <i data-lucide="user" size="32"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0 text-dark">Patient Profile</h2>
                    <p class="text-muted mb-0">CareSync Medical Record</p>
                </div>
            </div>

            <?php
            if (!isset($_GET['id'])) {
                header('location:manage_patient.php');
                exit();
            }

            $id = $_GET['id'];

            $qry = "SELECT * FROM patients WHERE patient_code=?";
            $stmt = $conn->prepare($qry);
            $stmt->bind_param("s", $id);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                ?>

                <div class="patient-card glass-card">

                    <div class="patient-top border-bottom pb-4 mb-4">

                        <div class="avatar shadow-sm d-flex align-items-center justify-content-center  bg-opacity-10 text-info rounded-circle" style="width: 80px; height: 80px;">
                            <i data-lucide="user" size="40"></i>
                        </div>

                        <div class="patient-name">
                            <h3 class="fw-bold text-white mb-1">
                                <?php echo htmlspecialchars($data['full_name']); ?>
                            </h3>
                            <p class="text-muted mb-0 fw-medium">Patient ID: 
                                <span class="text-info"><?php echo htmlspecialchars($id); ?></span>
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
                                <p><?php echo htmlspecialchars($data['mobile']); ?></p>
                            </div>
                            <div class="info">
                                <span><i data-lucide="calendar-heart" size="16"></i> Date of Birth</span>
                                <p><?php echo htmlspecialchars($data['dob']); ?></p>
                            </div>
                            <div class="info">
                                <span><i data-lucide="droplet" size="16"></i> Blood Group</span>
                                <p><?php echo htmlspecialchars($data['blood_group']); ?></p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info">
                                <span><i data-lucide="credit-card" size="16"></i> Aadhar Number</span>
                                <p><?php echo htmlspecialchars($data['aadhar']); ?></p>
                            </div>

                            <div class="info">
                                <span><i data-lucide="map-pin" size="16"></i> City</span>
                                <p><?php echo htmlspecialchars($data['city']); ?></p>
                            </div>

                            <div class="info">
                                <span><i data-lucide="home" size="16"></i> Address</span>
                                <p><?php echo htmlspecialchars($data['address']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
            } else {
                echo "<div class='glass-card text-center p-5'><h3 class='text-danger fw-bold'>Invalid Patient ID</h3><a href='manage_patient.php' class='btn btn-info mt-3 text-white'>Back to Manage Actions</a></div>";
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