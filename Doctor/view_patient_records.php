<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['doctor_id'])) {
    header('location:../login.php');
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$qry = "SELECT * FROM doctors WHERE doctor_code=?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = ($result->num_rows > 0) ? $result->fetch_assoc() : die("Doctor not found");

$patient_code = isset($_GET['patient_code']) ? $_GET['patient_code'] : '';
$patient = null;
$vitals = [];
$prescriptions = [];
$appointments = [];
$error_message = '';

if ($patient_code) {
    // Fetch Patient Details
    $p_qry = "SELECT * FROM patients WHERE patient_code = ?";
    $p_stmt = $conn->prepare($p_qry);
    $p_stmt->bind_param("s", $patient_code);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result();
    
    if ($p_res->num_rows > 0) {
        $patient = $p_res->fetch_assoc();
        $p_internal_id = $patient['id'];

        // Fetch Vitals
        $v_qry = "SELECT * FROM vitals WHERE patient_code = ? ORDER BY recorded_at DESC";
        $v_stmt = $conn->prepare($v_qry);
        $v_stmt->bind_param("s", $patient_code);
        $v_stmt->execute();
        $vitals = $v_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch Prescriptions
        $pr_qry = "SELECT * FROM prescription_medicines WHERE patient_code = ? ORDER BY created_at DESC";
        $pr_stmt = $conn->prepare($pr_qry);
        $pr_stmt->bind_param("s", $patient_code);
        $pr_stmt->execute();
        $prescriptions = $pr_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch Appointments
        $a_qry = "SELECT a.*, d.full_name as doctor_name 
                  FROM appointments a 
                  JOIN doctors d ON a.doctor_code = d.doctor_code 
                  WHERE a.patient_code = ? 
                  ORDER BY a.start_time DESC";
        $a_stmt = $conn->prepare($a_qry);
        $a_stmt->bind_param("s", $patient_code);
        $a_stmt->execute();
        $appointments = $a_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $error_message = "No patient found with ID: " . htmlspecialchars($patient_code);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareSync | Patient Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/doctor_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
    <style>
        .record-section {
            margin-bottom: 2rem;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-container {
            max-width: 600px;
            margin-bottom: 3rem;
        }
        .patient-header {
            background: var(--blue-gradient);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
        }
        .data-label {
            font-size: 0.85rem;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }
        .data-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #000;
        }
        .table-glass {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 15px;
            overflow: hidden;
        }
        .btn-white {
            background: white;
            color: var(--primary-blue);
            border: none;
        }
        .btn-white:hover {
            background: #f8fafc;
            color: var(--primary-dark);
        }
        @media print {
            .sidebar, .search-container, .btn-white, .nav-link {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .glass-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-5">
        <img src="../Assets/CareSyncLogo.png" width="40" alt="Logo">
        <h4 class="text-white fw-bold mt-2">CareSync</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="doctor_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
        <a class="nav-link" href="manage_schedule.php"><i data-lucide="clock"></i> <span>My Schedule</span></a>
        <a class="nav-link active" href="view_patient_records.php"><i data-lucide="users"></i> <span>Patient Records</span></a>
        <a class="nav-link" href="#"><i data-lucide="clipboard-list"></i> <span>Medical Notes</span></a>
        <a href="../logout.php" class="nav-link text-danger fw-bold"><i data-lucide="log-out"></i> <span>Logout</span></a>
    
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0" style="color: var(--primary-dark);">Patient Medical Records</h2>
            <p class="fw-bold" style="color: var(--primary-blue);">Access and review patient history details</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="search-container">
        <form action="" method="GET" class="glass-card p-4">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-0"><i data-lucide="search" class="text-muted"></i></span>
                <input type="text" name="patient_code" class="form-control border-0 bg-transparent shadow-none" 
                       placeholder="Enter Patient Code (e.g., PAT-2026-002)" 
                       value="<?php echo htmlspecialchars($patient_code); ?>" required>
                <button class="btn btn-primary rounded-pill px-4" type="submit">Access Records</button>
            </div>
        </form>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger glass-card border-0 text-danger fw-bold">
            <i data-lucide="alert-circle" size="18" class="me-2"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($patient): ?>
        <!-- Patient Header -->
        <div class="patient-header shadow-lg">
            <div class="row align-items-center">
                <div class="col-md-auto">
                    <div class="avatar-lg bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i data-lucide="user" size="40"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($patient['full_name']); ?></h3>
                        <span class="badge bg-white text-primary rounded-pill"><?php echo htmlspecialchars($patient['patient_code']); ?></span>
                    </div>
                    <p class="mb-0 mt-1 opacity-75">
                        <i data-lucide="info" size="14" class="me-1"></i>
                        <?php echo htmlspecialchars($patient['gender']); ?> • <?php echo floor((time() - strtotime($patient['dob'])) / 31556926); ?> Years Old • Blood: <?php echo htmlspecialchars($patient['blood_group']); ?>
                    </p>
                </div>
                <div class="col-md-auto text-end">
                    <button class="btn btn-white btn-sm rounded-pill px-3 fw-bold" onclick="window.print()">
                        <i data-lucide="printer" size="16" class="me-1"></i> Print Report
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- Personal Info Card -->
                <div class="glass-card p-4 mb-4">
                    <h5 class="section-title"><i data-lucide="user"></i> Personal Information</h5>
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <p class="data-label mb-1">Email Address</p>
                            <p class="data-value"><?php echo htmlspecialchars($patient['email']); ?></p>
                        </div>
                        <div class="col-sm-6">
                            <p class="data-label mb-1">Mobile Number</p>
                            <p class="data-value"><?php echo htmlspecialchars($patient['mobile']); ?></p>
                        </div>
                        <div class="col-sm-6">
                            <p class="data-label mb-1">Date of Birth</p>
                            <p class="data-value"><?php echo date('d M, Y', strtotime($patient['dob'])); ?></p>
                        </div>
                        <div class="col-sm-6">
                            <p class="data-label mb-1">Aadhar Number</p>
                            <p class="data-value"><?php echo htmlspecialchars($patient['aadhar']); ?></p>
                        </div>
                        <div class="col-12">
                            <p class="data-label mb-1">Current Address</p>
                            <p class="data-value mb-0"><?php echo htmlspecialchars($patient['address']) . ', ' . htmlspecialchars($patient['city']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Vitals History -->
                <div class="glass-card p-4">
                    <h5 class="section-title"><i data-lucide="activity"></i> Vitals History</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead style="background: var(--primary-blue); color: white;">
                                <tr>
                                    <th>Date</th>
                                    <th>BP</th>
                                    <th>HR</th>
                                    <th>Temp</th>
                                    <th>SpO2</th>
                                    <th>Weight</th>
                                </tr>
                            </thead>
                            <tbody style="color: #000; font-weight: 500;">
                                <?php if ($vitals): ?>
                                    <?php foreach ($vitals as $v): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo date('d M, Y', strtotime($v['recorded_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($v['blood_pressure']); ?></td>
                                            <td><?php echo htmlspecialchars($v['heart_rate']); ?></td>
                                            <td><?php echo htmlspecialchars($v['temperature']); ?>°C</td>
                                            <td><?php echo htmlspecialchars($v['oxygen_saturation']); ?>%</td>
                                            <td><?php echo htmlspecialchars($v['weight_kg']); ?>kg</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4">No vitals recorded</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Prescriptions only -->
            <div class="col-lg-4">
                <!-- Prescription History -->
                <div class="glass-card p-4">
                    <h5 class="section-title"><i data-lucide="pill"></i> Prescriptions</h5>
                    <?php if ($prescriptions): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($prescriptions as $p): ?>
                                <div class="list-group-item bg-transparent border-bottom px-0 py-3">
                                    <h6 class="fw-bold mb-1" style="color: var(--primary-blue);"><?php echo htmlspecialchars($p['medicine']); ?></h6>
                                    <p class="mb-1" style="color: #000; font-weight: 500;"><?php echo htmlspecialchars($p['form']); ?> • <?php echo htmlspecialchars($p['dosage']); ?></p>
                                    <p class="mb-0 fw-bold small" style="color: var(--primary-dark);">Freq: <?php echo htmlspecialchars($p['frequency']); ?></p>
                                    <span class="badge bg-primary mt-2"><?php echo date('d M, Y', strtotime($p['created_at'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-3 fw-bold">No active prescriptions</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif ($patient_code && !$error_message): ?>
        <div class="text-center py-5">
            <img src="../Assets/search-empty.png" width="150" alt="Search" class="mb-3">
            <h4 class="fw-bold" style="color: var(--primary-dark);">Enter a valid Patient ID to view records</h4>
        </div>
    <?php endif; ?>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
