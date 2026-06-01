<?php
session_start();
require_once '../dbconnect.php';
if (!isset($_SESSION['patient_id'])) {
    header('location:../login.php');
    exit();
}

$id = $_SESSION['patient_id'];
$qry = "SELECT * FROM patients WHERE patient_code=?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = ($result->num_rows > 0) ? $result->fetch_assoc() : die("Patient not found");

$upcoming_date = "None";
$banner_text = "No upcoming appointments scheduled. Check your vitals below.";
if (!empty($id)) {
    $apt_query = "SELECT start_time FROM appointments WHERE patient_code = ? AND start_time >= NOW() ORDER BY start_time ASC LIMIT 1";
    $apt_stmt = $conn->prepare($apt_query);
    $apt_stmt->bind_param("s", $id);
    $apt_stmt->execute();
    $apt_res = $apt_stmt->get_result();
    if ($apt_res->num_rows > 0) {
        $apt_row = $apt_res->fetch_assoc();
        $start_time = strtotime($apt_row['start_time']);
        $upcoming_date = date("d M Y", $start_time);
        
        $diff = $start_time - time();
        $days_until = ceil($diff / (60 * 60 * 24));
        if ($days_until == 0) {
            $banner_text = "You have a follow-up appointment today. Check your vitals below.";
        } else if ($days_until == 1) {
            $banner_text = "You have a follow-up appointment in 1 day. Check your vitals below.";
        } else {
            $banner_text = "You have a follow-up appointment in " . $days_until . " days. Check your vitals below.";
        }
    }
}

// Fetch prescription count and medicine list
$presc_count_qry = "SELECT COUNT(DISTINCT created_at) AS prescription_count FROM prescription_medicines WHERE patient_code = ?";
$presc_count_stmt = $conn->prepare($presc_count_qry);
$presc_count_stmt->bind_param("s", $id);
$presc_count_stmt->execute();
$presc_count_result = $presc_count_stmt->get_result();
$prescription_count = ($presc_count_result->num_rows > 0) ? $presc_count_result->fetch_assoc()['prescription_count'] : 0;

$latest_med_qry = "SELECT medicine, form, dosage, frequency, duration FROM prescription_medicines WHERE patient_code = ? ORDER BY created_at DESC, id ASC LIMIT 1";
$latest_med_stmt = $conn->prepare($latest_med_qry);
$latest_med_stmt->bind_param("s", $id);
$latest_med_stmt->execute();
$latest_med_result = $latest_med_stmt->get_result();
$latest_med = ($latest_med_result->num_rows > 0) ? $latest_med_result->fetch_assoc() : null;

$all_meds_qry = "SELECT medicine, form, dosage, frequency, duration, created_at FROM prescription_medicines WHERE patient_code = ? ORDER BY created_at DESC, id ASC";
$all_meds_stmt = $conn->prepare($all_meds_qry);
$all_meds_stmt->bind_param("s", $id);
$all_meds_stmt->execute();
$all_meds_result = $all_meds_stmt->get_result();
$medicines = [];
while ($med = $all_meds_result->fetch_assoc()) {
    $medicines[] = $med;
}

$health_metrics = [];
for ($i = 3; $i >= 0; $i--) {
    $week_start = date('Y-m-d', strtotime("-" . $i . " weeks"));
    $week_end = date('Y-m-d', strtotime("+1 day -" . ($i - 1) . " weeks"));
    
    $apt_count_qry = "SELECT COUNT(*) as apt_count FROM appointments WHERE patient_code = ? AND DATE(start_time) >= ? AND DATE(start_time) <= ?";
    $apt_count_stmt = $conn->prepare($apt_count_qry);
    $apt_count_stmt->bind_param("sss", $id, $week_start, $week_end);
    $apt_count_stmt->execute();
    $apt_count_result = $apt_count_stmt->get_result();
    $apt_count = $apt_count_result->fetch_assoc()['apt_count'];
    
    $med_count_qry = "SELECT COUNT(*) as med_count FROM prescription_medicines WHERE patient_code = ? AND DATE(created_at) >= ? AND DATE(created_at) <= ?";
    $med_count_stmt = $conn->prepare($med_count_qry);
    $med_count_stmt->bind_param("sss", $id, $week_start, $week_end);
    $med_count_stmt->execute();
    $med_count_result = $med_count_stmt->get_result();
    $med_count = $med_count_result->fetch_assoc()['med_count'];
    
    // Combine metrics (weighted: appointments * 3000 + medicines * 2000 = health score)
    $health_score = ($apt_count * 3000) + ($med_count * 2000);
    $health_metrics[] = $health_score;
}

$health_labels = ["Week 1", "Week 2", "Week 3", "Week 4"];
$health_data_json = json_encode($health_metrics);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Patient Portal</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/patient_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="text-white fw-bold mt-2" style="font-family: 'Custom';">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link active" href="#"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="./search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
            <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="medical_records.php"><i data-lucide="file-text"></i> <span>Health Reports</span></a>
            <a href="../logout.php" class="nav-link text-danger fw-bold"><i data-lucide="log-out"></i> <span>Logout</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">Patient Overview</h2>
                <p class="text-muted">Welcome back, <?php echo explode(' ', $row['full_name'])[0]; ?></p>
            </div>
            <div class="glass-card p-2 px-3 d-flex align-items-center gap-3 shadow-sm">
                <i data-lucide="bell" size="20" class="text-primary"></i>
                <div class="vr"></div>
                <img src="../icons/crowd.png" width="35" class="rounded-circle shadow-sm">
            </div>
        </div>

        <div class="welcome-banner mb-5 shadow-lg">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold">Health Status: Great</span>
                    <h1 class="fw-bold display-5">Stay Healthy, Stay Fit!</h1>
                    <p class="opacity-75 fs-5"><?php echo $banner_text; ?></p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="../appointment_scheduling/appointments.php" class="btn btn-light rounded-pill px-5 py-2 fw-bold text-primary">My Appointments</a>
                        <a href="./profile.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold text-white border-white">View Profile</a>
                    </div>
                </div>
            </div>
            <i data-lucide="heart-pulse" size="180" class="banner-icon"></i>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="glass-card p-4 h-100">
                    <div class="icon-box bg-light text-danger mb-3"><i data-lucide="droplet"></i></div>
                    <h6 class="text-muted fw-bold small">BLOOD GROUP</h6>
                    <div class="h3 fw-bold text-primary"><?php echo $row['blood_group']; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card p-4 h-100">
                    <div class="icon-box bg-light text-primary mb-3"><i data-lucide="calendar"></i></div>
                    <h6 class="text-muted fw-bold small">UPCOMING VISIT</h6>
                    <div class="h4 fw-bold text-primary"><?php echo $upcoming_date; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card p-4 h-100">
                    <div class="icon-box bg-light text-warning mb-3"><i data-lucide="pill"></i></div>
                    <h6 class="text-muted fw-bold small">PRESCRIPTIONS</h6>
                    <div class="h3 fw-bold text-primary"><?php echo number_format($prescription_count); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card p-4 h-100">
                    <div class="icon-box bg-light text-success mb-3"><i data-lucide="file-check"></i></div>
                    <h6 class="text-muted fw-bold small">READY REPORTS</h6>
                    <div class="h3 fw-bold text-primary">01</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="glass-card p-4">
                    <h5 class="fw-bold mb-4">Activity & Wellness</h5>
                    <div class="chart-container">
                        <canvas id="healthChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="glass-card p-4 h-100 d-flex flex-column">
                    <h5 class="fw-bold mb-4">Medication</h5>
                    <?php if ($latest_med): ?>
                        <div class="p-3 bg-white rounded-4 border mb-3">
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($latest_med['medicine']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($latest_med['frequency'] . ' - ' . $latest_med['dosage']); ?></small>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-white rounded-4 border mb-3">
                            <h6 class="mb-1 fw-bold">No current medication</h6>
                            <small class="text-muted">No prescription history found.</small>
                        </div>
                    <?php endif; ?>
                    <button class="btn btn-primary w-100 rounded-pill py-3 fw-bold mt-auto" data-bs-toggle="modal" data-bs-target="#medicineListModal">View Full List</button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="medicineListModal" tabindex="-1" aria-labelledby="medicineListModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center gap-3 w-100">
                            <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #0061ff 0%, #0081ff 100%);">
                                <i data-lucide="pill" size="24" style="color: white;"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold mb-1" id="medicineListModalLabel">Your Medications</h5>
                                <small class="text-muted">Complete prescription history</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-4 px-4">
                        <?php if (!empty($medicines)): ?>
                            <div class="row g-3">
                                <?php $med_count = 0; foreach ($medicines as $med): $med_count++; ?>
                                    <div class="col-12">
                                        <div class="p-4 rounded-3 border-0 transition-all" style="background: linear-gradient(135deg, rgba(0, 97, 255, 0.05) 0%, rgba(0, 129, 255, 0.05) 100%); border-left: 4px solid #0061ff;">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <div class="p-2 rounded-circle" style="background: rgba(0, 97, 255, 0.1);">
                                                        <i data-lucide="box" size="20" style="color: #0061ff;"></i>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <h6 class="fw-bold mb-2" style="color: #1a1a1a;"><?php echo htmlspecialchars($med['medicine']); ?></h6>
                                                    <div class="d-flex flex-wrap gap-3">
                                                        <div class="d-flex align-items-center gap-2 small">
                                                            <i data-lucide="type" size="14" style="color: #010508f6;"></i>
                                                            <span class="text-muted text-bold"><?php echo htmlspecialchars($med['form']); ?></span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 small">
                                                            <i data-lucide="droplet" size="14" style="color: #010508f6;;"></i>
                                                            <span class="text-muted text-bold"><?php echo htmlspecialchars($med['dosage']); ?></span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 small">
                                                            <i data-lucide="clock" size="14" style="color: #010508f6;"></i>
                                                            <span class="text-muted text-bold"><?php echo htmlspecialchars($med['frequency']); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <span class="badge bg-primary rounded-pill"><?php echo $med_count; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4 p-3 rounded-2 text-center" style="background: rgba(0, 97, 255, 0.05); border: 1px dashed rgba(0, 97, 255, 0.2);">
                                <small class="text-muted"><i data-lucide="info" size="14" class="me-2" style="vertical-align: middle;"></i>Total <?php echo count($medicines); ?> prescription(s) in your history</small>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="p-4 mx-auto mb-3 rounded-circle" style="background: rgba(255, 193, 7, 0.1); width: fit-content;">
                                    <i data-lucide="pill" size="48" style="color: #ffc107;"></i>
                                </div>
                                <p class="fw-bold mb-2">No Medications Found</p>
                                <p class="text-muted small">Your prescription history is empty. Once you receive a prescription, it will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        const ctx = document.getElementById('healthChart').getContext('2d');
        const chartGradient = ctx.createLinearGradient(0, 0, 0, 300);
        chartGradient.addColorStop(0, 'rgba(0, 97, 255, 0.2)');
        chartGradient.addColorStop(1, 'rgba(0, 97, 255, 0)');

        const healthData = <?php echo $health_data_json; ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    data: healthData,
                    borderColor: '#0061ff',
                    borderWidth: 4,
                    backgroundColor: chartGradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { display: false },
                    x: { 
                        grid: { display: false },
                        ticks: {
                            color: 'black',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>