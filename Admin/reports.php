<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['email'])) {
    header('location:../login.php');
    exit();
}

$weekLabels = [];
$reportCounts = [];
$prescriptionCounts = [];
$patientVisitCounts = [];

for ($i = 3; $i >= 0; $i--) {
    $weekStart = date('Y-m-d', strtotime("monday this week -{$i} weeks"));
    $weekEnd = date('Y-m-d', strtotime("sunday this week -{$i} weeks"));

    $weekLabels[] = date('M j', strtotime($weekStart)) . ' - ' . date('M j', strtotime($weekEnd));

    $reportStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed' AND DATE(start_time) BETWEEN ? AND ?");
    $reportStmt->bind_param('ss', $weekStart, $weekEnd);
    $reportStmt->execute();
    $reportRes = $reportStmt->get_result();
    $reportCounts[] = (int) ($reportRes->fetch_assoc()['count'] ?? 0);
    $reportStmt->close();

    $prescStmt = $conn->prepare("SELECT COUNT(*) as count FROM prescription_medicines WHERE DATE(created_at) BETWEEN ? AND ?");
    $prescStmt->bind_param('ss', $weekStart, $weekEnd);
    $prescStmt->execute();
    $prescRes = $prescStmt->get_result();
    $prescriptionCounts[] = (int) ($prescRes->fetch_assoc()['count'] ?? 0);
    $prescStmt->close();

    $patientStmt = $conn->prepare("SELECT COUNT(DISTINCT patient_code) as count FROM appointments WHERE status = 'confirmed' AND DATE(start_time) BETWEEN ? AND ?");
    $patientStmt->bind_param('ss', $weekStart, $weekEnd);
    $patientStmt->execute();
    $patientRes = $patientStmt->get_result();
    $patientVisitCounts[] = (int) ($patientRes->fetch_assoc()['count'] ?? 0);
    $patientStmt->close();
}

$weekLabelsJson = json_encode($weekLabels);
$reportCountsJson = json_encode($reportCounts);
$prescriptionCountsJson = json_encode($prescriptionCounts);
$patientVisitCountsJson = json_encode($patientVisitCounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Admin Reports</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/admin_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2">CareSync</h4>
        </div>

        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
            <a class="nav-link" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link" href="manage_attendee.php"><i data-lucide="user-check"></i> <span>Attendees</span></a>
            <a class="nav-link" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link active" href="reports.php"><i data-lucide="bar-chart-3"></i> <span>Reports</span></a>
              <a href="../logout.php" class="nav-link logout-link">
            <i data-lucide="log-out"></i> <span>Logout</span>
        </a>
        </nav>

      
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0 fs-1">Reports & Analytics</h2>
                <p class="text-muted fw-bold">Weekly insights for reports, prescriptions and patient visits</p>
            </div>
            <div class="glass-card p-2 px-3 d-flex align-items-center gap-3">
                <span class="small fw-bold">Administrator</span>
                <div class="vr"></div>
                <img src="../icons/admin.png" width="35" class="rounded-circle shadow-sm">
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <h6 class="text-muted small fw-bold mb-3 fs-5">Health Reports Generated</h6>
                    <div class="h1 fw-bold text-primary mb-0"><?php echo array_sum($reportCounts); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <h6 class="text-muted small fw-bold mb-3 fs-5">Prescriptions Created</h6>
                    <div class="h1 fw-bold text-info mb-0"><?php echo array_sum($prescriptionCounts); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center h-100">
                    <h6 class="text-muted small fw-bold mb-3 fs-5">Patients Visited</h6>
                    <div class="h1 fw-bold text-success mb-0"><?php echo array_sum($patientVisitCounts); ?></div>
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0 fs-4">Weekly Trend</h5>
                <span class="text-muted small fw-bold">Last 4 weeks</span>
            </div>
            <div class="chart-container" style="min-height: 360px;">
                <canvas id="adminReportChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const adminCtx = document.getElementById('adminReportChart').getContext('2d');
        const gradientBlue = adminCtx.createLinearGradient(0, 0, 0, 300);
        gradientBlue.addColorStop(0, 'rgba(13, 110, 253, 0.28)');
        gradientBlue.addColorStop(1, 'rgba(13, 110, 253, 0)');

        new Chart(adminCtx, {
            type: 'line',
            data: {
                labels: <?php echo $weekLabelsJson; ?>,
                datasets: [
                    {
                        label: 'Health Reports',
                        data: <?php echo $reportCountsJson; ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: gradientBlue,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#0d6efd'
                    },
                    {
                        label: 'Prescriptions',
                        data: <?php echo $prescriptionCountsJson; ?>,
                        borderColor: '#20c997',
                        backgroundColor: 'rgba(32, 201, 151, 0.18)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#20c997'
                    },
                    {
                        label: 'Patients Visited',
                        data: <?php echo $patientVisitCountsJson; ?>,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.18)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#ffc107'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 14,
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(222, 226, 230, 0.6)'
                        },
                        ticks: {
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
    </script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>