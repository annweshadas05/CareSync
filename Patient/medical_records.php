<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['patient_id'])) {
    header('location:../login.php');
    exit();
}

$patient_code = $_SESSION['patient_id'];

$qry = "SELECT * FROM patients WHERE patient_code=?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("s", $patient_code);
$stmt->execute();
$result = $stmt->get_result();
$patient = ($result->num_rows > 0) ? $result->fetch_assoc() : die("Patient not found");

$presc_qry = "SELECT * FROM prescription_medicines WHERE patient_code=? ORDER BY created_at DESC, id ASC";
$presc_stmt = $conn->prepare($presc_qry);
$presc_stmt->bind_param("s", $patient_code);
$presc_stmt->execute();
$presc_result = $presc_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Medical Records</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/patient_dashboard.css">
    <script src="../js/lucide.js"></script>
    <style>
        .record-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .record-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .medicine-table {
            width: 100%;
            margin-top: 15px;
        }
        .medicine-table th {
            color: #000000;
            font-weight: 600;
            padding-bottom: 10px;
        }
        .medicine-table td {
            padding: 8px 0;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="text-white fw-bold mt-2" style="font-family: 'Custom';">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="patient_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
            <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link active" href="medical_records.php"><i data-lucide="pill"></i> <span>Medical Records</span></a>
            <a class="nav-link" href="my_reports.php"><i data-lucide="activity"></i> <span>My Reports</span></a>
            <a href="../logout.php" class="nav-link logout-link">
            <i data-lucide="log-out"></i> <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">My Medical Records</h2>
                <p class="text-dark">View your past prescriptions and health notes.</p>
            </div>
            <div class="glass-card p-2 px-3 d-flex align-items-center gap-3 shadow-sm">
                <i data-lucide="bell" size="20" class="text-primary"></i>
                <div class="vr"></div>
                <img src="../icons/crowd.png" width="35" class="rounded-circle shadow-sm">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php
                // Group medicine rows by created_at so each save session = one prescription card
                $prescriptions = [];
                while ($row = $presc_result->fetch_assoc()) {
                    $key = $row['created_at'];
                    if (!isset($prescriptions[$key])) {
                        $prescriptions[$key] = [
                            'created_at' => $row['created_at'],
                            'notes'      => $row['notes'],
                            'medicines'  => []
                        ];
                    }
                    $prescriptions[$key]['medicines'][] = $row;
                }
                ?>

                <?php if (empty($prescriptions)): ?>
                    <div class="alert alert-info text-center">No prescriptions found for your record.</div>
                <?php else: ?>
                    <?php foreach ($prescriptions as $presc): ?>
                        <div class="record-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                <div>
                                    <h5 class="fw-bold text-primary mb-1">Prescription</h5>
                                    <small class="text-dark">Date: <?php echo date('d M Y, h:i A', strtotime($presc['created_at'])); ?></small>
                                </div>
                                <i data-lucide="file-text" class="text-primary opacity-50" width="32" height="32"></i>
                            </div>

                            <?php if (!empty($presc['notes'])): ?>
                                <div class="mb-3 bg-light p-3 rounded text-dark">
                                    <strong>Doctor's Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($presc['notes'])); ?>
                                </div>
                            <?php endif; ?>

                            <h6 class="fw-bold mt-3">Prescribed Medicines</h6>
                            <table class="medicine-table">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Form</th>
                                        <th>Dosage</th>
                                        <th>Frequency</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($presc['medicines'] as $med): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($med['medicine']); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($med['form']); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($med['dosage']); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($med['frequency']); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($med['duration']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
